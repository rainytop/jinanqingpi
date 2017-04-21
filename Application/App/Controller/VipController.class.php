<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class VipController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    public function index()
    {
        $backurl = base64_encode(U('App/Vip/index'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $data = self::$WAP['vip'];
        //判断签到状态
        $d1 = date('Y-m-d', time());
        $d2 = date('Y-m-d', $data['signtime']);
        $data['issign'] = ($d1 == $d2) ? 1 : 0;
        //计算未读消息
        $msglist = M('vip_message')->select();
        $msg_pids = '';
        foreach ($msglist as $k => $v) {
            if ($v['pids'] == '') {
                $msg_pids = $msg_pids . ',' . $v['id'];
            } else {
                if (in_array($vipid, explode(',', $v['pids']))) {
                    $msg_pids = $msg_pids . ',' . $v['id'];
                }
            }
        }
        if ($msg_pids) {
            $map['id'] = array('in', in_parse_str($msg_pids));
            $msg = M('vip_message')->where($map)->select();
            $msgread = M('vip_log')->where('vipid=' . $vipid . ' and type=5')->select();
            $data['unread'] = count($msg) - count($msgread);
        } else {
            $data['unread'] = 0;
        }
        //计算未使用卡券
        $today = strtotime(date('Y-m-d'));
        $map_card['etime'] = array('EGT', $today);
        $map_card['vipid'] = $vipid;
        $map_card['status'] = 1;
        $data['cardnum'] = M('vip_card')->where($map_card)->count();
        if ($data['isfxgd']) {
            $data['fxname'] = '花股';
        } else {
            if ($data['isfx']) {
                $data['fxname'] = $_SESSION['SHOP']['set']['fxname'];
            } else {
                $data['fxname'] = '非' . $_SESSION['SHOP']['set']['fxname'];
            }
        }

        $father = M('Vip')->where('id=' . self::$WAP['vip']['pid'])->find();
        if ($father) {
            $this->assign('showfather', 1);
            $this->assign('father', $father);
        }
        $this->assign('data', $data);
        $this->assign('actname', 'ftvip');
        $this->assign('isqiandao', $_SESSION['WAP']['vipset']['isqiandao']);
        $this->assign('ispaihang', $_SESSION['WAP']['vipset']['ispaihang']);
        $this->display();
    }

    public function sign()
    {
        $backurl = base64_encode(U('App/Vip/index'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        $sign_score = explode(',', self::$WAP['vipset']['sign_score']);
        $sign_exp = explode(',', self::$WAP['vipset']['sign_exp']);
        $vip = self::$WAP['vip'];
        $d1 = date_create(date('Y-m-d', $vip['signtime']));
        $d2 = date_create(date('Y-m-d', time()));
        $diff = date_diff($d1, $d2);
        $late = $diff->format("%a");
        //判断是否签到过
        if ($late < 1) {
            $info['status'] = 0;
            $info['msg'] = "您今日已经签过到了！";
            $this->ajaxReturn($info);
        }
        //正常签到累计流程
        if ($late >= 1 && $late < 2) {
            $vip['sign'] = $vip['sign'] ? $vip['sign'] : 0; //防止空值

            $data_vip['sign'] = $vip['sign'] + 1; //签到次数+1
            //积分
            if ($data_vip['sign'] >= count($sign_score)) {
                $score = $sign_score[count($sign_score) - 1];
            } else {
                $score = $sign_score[$data_vip['sign']];
            }
            //经验
            if ($data_vip['sign'] >= count($sign_exp)) {
                $exp = $sign_exp[count($sign_exp) - 1];
            } else {
                $exp = $sign_exp[$data_vip['sign']];
            }
        } else {
            $data_vip['sign'] = 0; //签到次数置零
            $score = $sign_score[0];
            $exp = $sign_exp[0];
        }
        $data_vip['score'] = array('exp', 'score+' . $score);
        $data_vip['exp'] = array('exp', 'exp+' . $exp);
        $data_vip['signtime'] = time();
        $data_vip['cur_exp'] = array('exp', 'cur_exp+' . $exp);
        $level = $this->getlevel(self::$WAP['vip']['cur_exp'] + $exp);
        $data_vip['levelid'] = $level['levelid'];
        $m = M('Vip');
        $r = $m->where(array('id' => $vipid))->save($data_vip);

        if ($r) {
            //增加签到日志
            $data_log['ip'] = get_client_ip();
            $data_log['vipid'] = $vipid;
            $data_log['event'] = '会员签到-连续' . $data_vip['sign'] . '天';
            $data_log['score'] = $score;
            $data_log['exp'] = $exp;
            $data_log['type'] = 2;
            $data_log['ctime'] = time();
            M('vip_log')->add($data_log);
            $info['status'] = 1;
            $info['msg'] = "签到成功！";
            $data_log['levelname'] = $level['levelname'];
            $info['data'] = $data_log;
        } else {
            $info['status'] = 0;
            $info['msg'] = "签到失败！" . $r;
        }
        $this->ajaxReturn($info);
    }

    public function reg()
    {
        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            //判断重复注册
            if ($m->where('mobile=' . $post['mobile'])->find()) {
                $info['status'] = 0;
                $info['msg'] = '此手机号已注册过！';
                $this->ajaxReturn($info, "json");
            }
            //判断验证码
            if (self::$WAP['vipset']['isverify'] == 1) {
                $last_ver = M('vip_log')->where('mobile=' . $post['mobile'] . ' and type=1')->order('ctime desc')->find();
                if ($last_ver['code'] != $post['code']) {
                    $info['status'] = 0;
                    $info['msg'] = '验证码错误！';
                    $this->ajaxReturn($info, "json");
                }
            }
            $post['password'] = md5($post['password']);
            $post['score'] = self::$WAP['vipset']['reg_score'];
            $post['exp'] = self::$WAP['vipset']['reg_exp'];
            $post['cur_exp'] = self::$WAP['vipset']['reg_exp'];
            $level = $this->getLevel($post['exp']);
            $post['levelid'] = $level['levelid'];
            $post['ctime'] = time();
            unset($post['code']);
            $r = $m->add($post);
            if ($r) {
                //赠送操作
                if (self::$WAP['vipset']['isgift']) {
                    $gift = explode(",", self::$WAP['vipset']['gift_detail']);
                    $cardnopwd = $this->getCardNoPwd();
                    $data_card['type'] = $gift[0];
                    $data_card['vipid'] = $r;
                    $data_card['money'] = $gift[1];
                    $data_card['usemoney'] = $gift[3];
                    $data_card['cardno'] = $cardnopwd['no'];
                    $data_card['cardpwd'] = $cardnopwd['pwd'];
                    $data_card['status'] = 1;
                    $data_card['stime'] = $data_card['ctime'] = time();
                    $data_card['etime'] = time() + $gift[2] * 24 * 60 * 60;
                    M('vip_card')->add($data_card);

                    //发送赠送通知消息
                    //					$data_msg['pids']=$r;
                    //					$data_msg['title']="新人礼包";
                    //					$data_msg['content']="新用户注册赠送新人礼包，内含代金券，请至个人中心查收！";
                    //					$data_msg['ctime']=time();
                    //					M('vip_message')->add($data_msg);
                }
                //记录日志
                $data_log['ip'] = get_client_ip();
                $data_log['vipid'] = $r['id'];
                $data_log['ctime'] = time();
                $data_log['event'] = "会员注册";
                $data_log['score'] = $post['score'];
                $data_log['exp'] = $post['exp'];
                $data_log['type'] = 4;
                M('vip_log')->add($data_log);

                $info['status'] = 1;
                $info['msg'] = '注册成功！马上去登陆';
                $info['mobile'] = $post['mobile'];
            } else {
                $info['status'] = 0;
                $info['msg'] = '注册失败！';
            }
            $this->ajaxReturn($info, "json");
        } else {
            if (self::$WAP['vipset']['isverify'] == 1) {
                if ($_SESSION['mobile_tmp']) {
                    $mobile = $_SESSION['mobile_tmp'];
                    $last_ver = M('vip_log')->where('mobile=' . $mobile)->order('ctime desc')->find();
                    $times = $last_ver['ctime'] + self::$WAP['vipset']['ver_interval'] * 60 - time();
                }
            }
            $status = $times > 0 ? 0 : 1;
            $times = $times > 0 ? $times : 0;
            $this->assign('status', $status);
            $this->assign('times', $times);
            $this->assign('isverify', self::$WAP['vipset']['isverify']);
            $this->display();
        }
    }

    private function getCardNoPwd()
    {
        $dict_no = "0123456789";
        $length_no = 10;
        $dict_pwd = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length_pwd = 10;
        $card['no'] = "";
        $card['pwd'] = "";
        for ($i = 0; $i < $length_no; $i++) {
            $card['no'] .= $dict_no[rand(0, (strlen($dict_no) - 1))];
        }
        for ($i = 0; $i < $length_pwd; $i++) {
            $card['pwd'] .= $dict_pwd[rand(0, (strlen($dict_pwd) - 1))];
        }
        return $card;
    }

    public function sendCode()
    {
        $m = M('vip_log');
        $post = I('get.');

        //已验证次数
        $counts = $m->where('mobile=' . $post['mobile'])->count();
        if ($counts >= self::$WAP['vipset']['ver_times']) {
            $info['status'] = 0;
            $info['msg'] = "超出验证次数！";
            $this->ajaxReturn($info);
        }
        $data_log['ip'] = get_client_ip();
        $post['code'] = rand(1000, 9999);
        $post['ctime'] = time();
        $post['event'] = "注册获取验证码";
        $post['type'] = 1;
        $r = $m->add($post);

        if ($r) {
            $info['status'] = 1;
            $info['msg'] = "验证码发送成功！";
            $info['times'] = self::$WAP['vipset']['ver_interval'] * 60;
            $_SESSION['mobile_tmp'] = $post['mobile'];
        } else {
            $info['status'] = 0;
            $info['msg'] = "发送失败！";
        }
        $this->ajaxReturn($info);
    }

    public function login()
    {
        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $r = $m->where("mobile='" . $post['mobile'] . "' and password='" . md5($post['password']) . "'")->find();
            if ($r) {
                //记录日志
                $data_log['ip'] = get_client_ip();
                $data_log['vipid'] = $r['id'];
                $data_log['ctime'] = time();
                $data_log['event'] = "会员登陆";
                $data_log['type'] = 3;
                M('vip_log')->add($data_log);
                //记录最后登陆
                $data_vip['cctime'] = time();
                $m->where('id=' . $r['id'])->save($data_vip);

                $info['status'] = 1;
                $info['msg'] = "登陆成功！";

                $_SESSION['WAP']['vipid'] = $r['id'];
                $_SESSION['WAP']['vip'] = $r;
            } else {
                $info['status'] = 0;
                $info['msg'] = "账号密码错误！";
            }
            $this->ajaxReturn($info);
        } else {
            $this->assign('mobile', I('mobile'));
            $this->assign('backurl', base64_decode(I('backurl')));
            $this->display();
        }
    }

    public function logout()
    {
        session(null);
        $this->redirect('App/Vip/login');
    }

    public function message()
    {
        $backurl = base64_encode(U('App/Vip/message'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip_message');

        $msglist = $m->select();
        $msg_pids = '';
        foreach ($msglist as $k => $v) {
            if ($v['pids'] == '') {
                $msg_pids = $msg_pids . ',' . $v['id'];
            } else {
                if (in_array($vipid, explode(',', $v['pids']))) {
                    $msg_pids = $msg_pids . ',' . $v['id'];
                }
            }
        }
        $map['id'] = array('in', in_parse_str($msg_pids));
        $data = $m->where($map)->order('ctime desc')->select();
        foreach ($data as $k => $val) {
            $read = M('vip_log')->where('vipid=' . $vipid . ' and opid=' . $val['id'] . ' and type=5')->find();
            $data[$k]['read'] = $read ? 1 : 0;
        }
        $this->assign('data', $data);
        $this->assign('actname', 'ftvip');
        $this->display();
    }

    public function msgRead()
    {
        $backurl = base64_encode(U('App/Vip/message'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        $m = M('vip_message');
        $id = I('id');

        $msgread = M('vip_log')->where('opid=' . $id . ' and vipid=' . $vipid)->find();

        if ($msgread) {
            $info['status'] = 0;
        } else {
            $data_log['ip'] = get_client_ip();
            $data_log['event'] = "会员浏览消息";
            $data_log['type'] = 5;
            $data_log['vipid'] = $vipid;
            $data_log['opid'] = $id;
            $data_log['ctime'] = time();
            M('vip_log')->add($data_log);
            $info['status'] = 1;
        }
        $data = $m->where('id=' . $id)->find();
        $info['data'] = $data;
        $this->ajaxReturn($info);

    }

    public function info()
    {
        $backurl = base64_encode(U('App/Vip/info'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $r = $m->where("id=" . $vipid)->save($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "资料修改成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "资料修改失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data = self::$WAP['vip'];
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function tx()
    {
        $backurl = base64_encode(U('App/Vip/tx'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $r = $m->where("id=" . $vipid)->save($post);
            //dump($m->getLastSql());
            //die('ok');
            if ($r !== FALSE) {

                $this->success('提现资料修改成功！');
            } else {
                $this->error('提现资料修改失败！');
            }
        } else {
            $data = self::$WAP['vip'];
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function txOrder()
    {
        $backurl = base64_encode(U('App/Vip/txOrder'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip');
        $vip = $m->where('id=' . $vipid)->find();
        $this->assign('vip', $vip);
        if (IS_POST) {

            $mtx = M('vip_tx');
            $post = I('post.');
            if (!$post['txprice']) {
                $this->error('提现' . $_SESSION['SHOP']['set']['yjname'] . '不能为空！');
            }
            if ($post['txprice'] < self::$WAP['vipset']['tx_money']) {
                $this->error('提现' . $_SESSION['SHOP']['set']['yjname'] . '不得少于' . self::$WAP['vipset']['tx_money'] . '个！');
            }

            if ($post['txprice'] > $vip['money']) {
                $this->error('您的' . $_SESSION['SHOP']['set']['yjname'] . '不足！');
            }
            $vip['money'] = $vip['money'] - $post['txprice'];
            $rvip = $m->save($vip);

            if (FALSE !== $rvip) {
                $post['vipid'] = $vipid;
                $post['txsqtime'] = time();
                $post['status'] = 1;
                $r = $mtx->add($post);
                if ($r) {
                    $data_msg['pids'] = $vipid;
                    $data_msg['title'] = "您的" . $post['txprice'] . $_SESSION['SHOP']['set']['yjname'] . "提现申请已成功提交！会在三个工作日内审核完毕并发放！";
                    $data_msg['content'] = "提现订单编号：" . $r . "<br><br>提现申请数量：" . $post['txprice'] . "<br><br>提现申请时间：" . date('Y-m-d H:i', time()) . "<br><br>提现申请将在三个工作日内审核完成，如有问题，请联系客服！";
                    $data_msg['ctime'] = time();
                    $rmsg = M('vip_message')->add($data_msg);

                    // 发送信息===============
                    $customer = M('Wx_customer')->where(array('type' => 'tx1'))->find();
                    $options['appid'] = self::$_wxappid;
                    $options['appsecret'] = self::$_wxappsecret;
                    $wx = new \Util\Wx\Wechat($options);
                    $msg = array();
                    $msg['touser'] = $vip['openid'];
                    $msg['msgtype'] = 'text';
                    $str = $customer['value'];
                    $msg['text'] = array('content' => $str);
                    $ree = $wx->sendCustomMessage($msg);
                    // 发送消息完成============

                    $this->success('提现申请成功！');
                } else {
                    $data_msg['pids'] = $vipid;
                    $data_msg['title'] = "您的" . $post['txprice'] . $_SESSION['SHOP']['set']['yjname'] . "提现申请已成功提交！会在三个工作日内审核完毕并发放！";
                    $data_msg['content'] = "提现订单编号：" . $r . "<br><br>提现申请数量：" . $post['txprice'] . "<br><br>提现申请时间：" . date('Y-m-d H:i', time()) . "<br><br>" . $_SESSION['SHOP']['set']['yjname'] . "余额已扣除，但未成功生成提现订单，凭此信息联系客服补偿损失！";
                    $data_msg['ctime'] = time();
                    $rmsg = M('vip_message')->add($data_msg);
                    $this->error($_SESSION['SHOP']['set']['yjname'] . '余额扣除成功，但未成功生成提现申请，请联系客服！');
                }
            } else {
                $this->error('提现申请失败！请重新尝试！');
            }

        } else {
            $data = self::$WAP['vip'];
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function address()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        $data = $m->where('vipid=' . $vipid)->select();
        $this->assign('data', $data);
        $this->display();
    }

    public function addressSet()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        if (IS_POST) {
            $post = I('post.');
            $post['vipid'] = $vipid;
            $r = $post['id'] ? $m->save($post) : $m->add($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址保存成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址保存失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data['mobile'] = self::$WAP['vip']['mobile'];
            $data['name'] = self::$WAP['vip']['name'];
            if (I('id')) {
                $data = $m->where('id=' . I('id'))->find();
            }
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function addressDel()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        if (IS_POST) {
            $r = $m->where('id=' . I('id') . ' and vipid=' . $vipid)->delete();
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址删除成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址删除失败！";
            }
            $this->ajaxReturn($info);
        }
    }

    public function xqChoose()
    {
        $m = M('xq');
        if (IS_POST) {
            $post = I('post.');
            $post['vipid'] = $vipid;
            $post['xqgroupid'] = M('xq')->where('id=' . $post['xqid'])->getField('groupid');
            $r = $post['id'] ? $m->save($post) : $m->add($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址保存成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址保存失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data = $m->ORDER("convert(name USING gbk)")->select();
            foreach ($data as $k => $v) {
                $data[$k]['char'] = $this->getfirstchar($v['name']);
                if ($data[$k]['char'] == $data[$k - 1]['char']) {
                    $data[$k]['charshow'] = 0;
                } else {
                    $data[$k]['charshow'] = 1;
                }
            }
            if (I('addressid')) {
                $this->assign('addressid', I('addressid'));
            }
            $this->assign('data', $data);
            $this->display();
        }
    }

    //获取中文首字拼音字母
    public function getfirstchar($s0)
    {
        //手动添加未识别记录
        if (mb_substr($s0, 0, 1, 'utf-8') == "怡") {
            return "Y";
        }

        if (mb_substr($s0, 0, 1, 'utf-8') == "泗") {
            return "S";
        }

        $fchar = ord(substr($s0, 0, 1));
        if (($fchar >= ord("a") and $fchar <= ord("z")) or ($fchar >= ord("A") and $fchar <= ord("Z"))) {
            return strtoupper(chr($fchar));
        }

        $s = iconv("UTF-8", "GBK", $s0);
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        //dump($s0.':'.$asc);
        if ($asc >= -20319 and $asc <= -20284) {
            return "A";
        }

        if ($asc >= -20283 and $asc <= -19776) {
            return "B";
        }

        if ($asc >= -19775 and $asc <= -19219) {
            return "C";
        }

        if ($asc >= -19218 and $asc <= -18711) {
            return "D";
        }

        if ($asc >= -18710 and $asc <= -18527) {
            return "E";
        }

        if ($asc >= -18526 and $asc <= -18240) {
            return "F";
        }

        if ($asc >= -18239 and $asc <= -17923) {
            return "G";
        }

        if ($asc >= -17922 and $asc <= -17418) {
            return "H";
        }

        if ($asc >= -17417 and $asc <= -16475) {
            return "J";
        }

        if ($asc >= -16474 and $asc <= -16213) {
            return "K";
        }

        if ($asc >= -16212 and $asc <= -15641) {
            return "L";
        }

        if ($asc >= -15640 and $asc <= -15166) {
            return "M";
        }

        if ($asc >= -15165 and $asc <= -14923) {
            return "N";
        }

        if ($asc >= -14922 and $asc <= -14915) {
            return "O";
        }

        if ($asc >= -14914 and $asc <= -14631) {
            return "P";
        }

        if ($asc >= -14630 and $asc <= -14150) {
            return "Q";
        }

        if ($asc >= -14149 and $asc <= -14091) {
            return "R";
        }

        if ($asc >= -14090 and $asc <= -13319) {
            return "S";
        }

        if ($asc >= -13318 and $asc <= -12839) {
            return "T";
        }

        if ($asc >= -12838 and $asc <= -12557) {
            return "W";
        }

        if ($asc >= -12556 and $asc <= -11848) {
            return "X";
        }

        if ($asc >= -11847 and $asc <= -11056) {
            return "Y";
        }

        if ($asc >= -11055 and $asc <= -10247) {
            return "Z";
        }

        return "?";
    }

    public function about()
    {
        $temp = M('Shop_set')->find();
        $this->assign('shop', $temp);
        $this->display();
    }

    public function intro()
    {
        $this->display();
    }

    public function cz()
    {
        $this->display();
    }

    public function zxczSet()
    {
        $backurl = base64_encode(U('App/Vip/cz'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $money = I('money');
        $type = I('type');
        //记录充值log，同时作为充值返回数据调用
        $data_log['ip'] = get_client_ip();
        $data_log['vipid'] = $vipid;
        $data_log['ctime'] = time();
        $data_log['event'] = "会员在线充值";
        $data_log['money'] = $money;
        $data_log['score'] = round($money * self::$WAP['vipset']['cz_score'] / 100);
        $data_log['exp'] = round($money * self::$WAP['vipset']['cz_exp'] / 100);
        $data_log['opid'] = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $data_log['status'] = 1;
        $data_log['type'] = 7;
        $re = M('vip_log')->add($data_log);
        //跳转充值页面
        if ($re) {
            switch ($type) {
                case '1':
                    $this->redirect('App/Alipay/alipay', array('price' => $money, 'oid' => $data_log['opid']));
                    break;
                case '2':
                    $this->redirect('Home/Wxpay/pay', array('price' => $money, 'oid' => $data_log['opid']));
                    break;
                default:
                    $this->error('支付方式未知！');
                    break;
            }
        } else {
            $this->error('出错啦！');
        }

    }

    public function card()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip_card');
        $status = I('status') ? intval(I('status')) : 1;
        $map['status'] = $status;
        $today = strtotime(date('Y-m-d'));
        if ($status == 3) {
            $map['etime'] = array('LT', $today);
            $map['status'] = 1;
        } else if ($status == 1) {
            $map['etime'] = array('EGT', $today);
        }
        $map['vipid'] = $vipid;
        $map['type'] = 2; //代金券

        $data = $m->where($map)->select();

        $this->assign('data', $data);
        $this->assign('status', $status);
        $this->display();
    }

    public function addCard()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipCard');
        $map = I('post.');
//        $map['type'] = 2; //充值卡充值
        $card = $m->where($map)->find();
        if ($card) {
            if ($card['status'] == 0) {
                //未发卡
                $info['status'] = 0;
                $info['msg'] = '此卡尚未激活，请重试或联系管理员！';
            } else if ($card['status'] == 2) {
                //已使用
                $info['status'] = 0;
                $info['msg'] = '此卡已使用过了哦！';
            } else if ($card['status'] == 1) {
                //修改会员信息：账户金额、积分、经验、等级
                $data_vip['money'] = array('exp', 'money+' . $card['money']);
                $data_vip['score'] = array('exp', 'score+' . round($card['money'] * self::$WAP['vipset']['cz_score'] / 100));
                if (round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100) > 0) {
                    $data_vip['exp'] = array('exp', 'exp+' . round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $level = $this->getLevel(self::$WAP['vip']['cur_exp'] + round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $data_vip['levelid'] = $level['levelid'];
                }
                $re = M('vip')->where('id=' . $vipid)->save($data_vip);
                if ($re) {
                    //修改卡状态
                    $card['status'] = 2;
                    $card['vipid'] = $vipid;
                    $card['usetime'] = time();
                    $m->save($card);
                    //记录日志
                    $data_log['ip'] = get_client_ip();
                    $data_log['vipid'] = $vipid;
                    $data_log['ctime'] = time();
                    $data_log['event'] = "会员充值卡充值";
                    $data_log['money'] = $card['money'];
                    $data_log['score'] = round($card['money'] * self::$WAP['vipset']['cz_score'] / 100);
                    $data_log['exp'] = round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100);
                    $data_log['opid'] = $card['id'];
                    $data_log['type'] = 6;
                    M('vip_log')->add($data_log);

                    $info['status'] = 1;
                    $info['msg'] = '充值成功！前往会员中心查看？';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '充值失败，请重试或联系管理员！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '此卡状态异常，请重试或联系管理员！';
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '卡号密码有误，请核对后重试！';
        }
        $this->ajaxReturn($info);
    }

    public function addVipCard()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipCard');
        $map = I('post.');
        $map['type'] = 2; //代金券
        $card = $m->where($map)->find();
        if ($card) {
            if ($card['status'] == 0) {
                $m->where(array("id"=>$card["id"]))->save(array("vipid"=>$vipid ,"status"=>1));
                $this->ajaxReturn(array("info"=>"充值成功"));
            }else{
                $this->ajaxReturn(array("info"=>"充值失败"));
            }
        }
        $this->ajaxReturn(array("info"=>"充值失败"));

    }

}
