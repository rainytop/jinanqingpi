<?php
// +----------------------------------------------------------------------
// | 单用户微信基础类
// +----------------------------------------------------------------------
namespace Home\Controller;

use App\QRcode;
use Home\Model\WxBiz;
use Think\Controller;
use Vendor\Hiland\Biz\Loger\CommonLoger;
use Vendor\Hiland\Utils\Data\BoolHelper;
use Vendor\Hiland\Utils\DataModel\ModelMate;
use Vendor\Hiland\Utils\IO\Thread;
use Vendor\Hiland\Utils\Web\WebHelper;

class WxController extends Controller
{
    //全局相关
    public static $_set; //缓存全局配置
    public static $_shop; //缓存全局配置

    public static $_wx; //缓存微信对象
    public static $_ppvip; //缓存会员通信证模型
    public static $_ppvipmessage; //缓存会员消息模型
    public static $_fx; //缓存分销模型
    public static $_fxlog; //缓存分销新用户推广模型	qd(渠道)=1为朋友圈，2为渠道场景二微码
    public static $_token;
    public static $_location; //用户地理信息
    //信息接收相关
    public static $_revtype; //微信发来的信息类型
    public static $_revdata; //微信发来的信息内容
    //信息推送相关
    //public static $_url='http://shop.hylanca.com/';//推送地址前缀
    public static $_url;
    public static $_openid;
    public static $_actopen;

    public static $WAP;//CMS全局静态变量

    // 自动计算模型
    public static $_demployee;

    public function __construct($options)
    {
        // 读取商城全局配置
        self::$_shop = M('Shop_set')->find();
        //读取用户配置存全局
        self::$_set = M('Set')->find();
        self::$_url = self::$_set['wxurl'];
        self::$_token = self::$_set['wxtoken'];

        //检测token是否合法
        $tk = $_GET['token'];
        if ($tk != self::$_token) {
            die('token error');
        }

        self::$_wx = WxBiz::getWechat();

        //缓存通行证数据模型
        self::$_ppvip = M('Vip');
        self::$_ppvipmessage = M('Vip_message');
        self::$_fx = M('Vip');
        self::$_fxlog = M('Vip_log_sub');
        self::$_demployee = D('Employee');

        self::$WAP['vipset'] = $this->checkVipSet();

        //判断验证模式
        if (IS_GET) {
            self::$_wx->valid();
        } else {
            if (!self::$_wx->valid(true)) {
                die('no access!!!');
            }
            //读取微信平台推送来的信息类型存全局
            self::$_revtype = self::$_wx->getRev()->getRevType();
            //读取微型平台推送来的信息存全局
            self::$_revdata = self::$_wx->getRevData();
            self::$_openid = self::$_wx->getRevFrom();
            //读取用户地理信息
            //self::$_location=self::$_wx->getRevData();
            $str = "";
            foreach (self::$_revdata as $k => $v) {
                $str = $str . $k . "=>" . $v . '  ';
            }
            file_put_contents('./Data/app_rev.txt', '收到请求:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
        }
    }

    //返回VIP配置
    public function checkVipSet()
    {
        $set = M('Vip_set')->find();
        return $set ? $set : utf8error('会员设置未定义！');
    }

    public function index()
    {
        $this->go();
    }

    /*微信访问判断主路由控制器by App
    return
     */
    public function go()
    {
        switch (self::$_revtype) {
            case \Util\Wx\Wechat::MSGTYPE_TEXT:
                $this->checkKeyWord(self::$_revdata['Content']);
                //self::$_wx->text(self::$_revdata['Content'])->reply();
                break;
            case \Util\Wx\Wechat::MSGTYPE_EVENT:
                $this->checkEvent(self::$_revdata['Event']);
                break;
            case \Util\Wx\Wechat::MSGTYPE_IMAGE:
                //$this -> checkImg();
                self::$_wx->text('本系统暂不支持图片信息！')->reply();
                break;
            default:
                self::$_wx->text("本系统暂时无法识别您的指令！")->reply();
        }
    } //end go

    /**
     * 关键词指引
     * @param $key
     */
    public function checkKeyWord($key)
    {
        //更新认证服务号的微信用户表信息（24小时内）
        $reUP = $this->updateUser(self::$_openid);

        //App调试模式
        if (substr($key, 0, 5) == 'App-') {
            $this->toAppDebug(substr($key, 5));
        }

        //强制关键词匹配
        //*********************************************************************
        switch ($key) {
            case 'test': {
                $qrUrl = U("WxNonValid/reply4Test", array("openid" => self::$_revdata['FromUserName']));
                Thread::asynExec($qrUrl);
                self::$_wx->text("测试信息生成中。$qrUrl")->reply();
                break;
            }
            case '操作指导': {
                $msg = '未配置';
                self::$_wx->text($msg)->reply();
                break;
            }
            case '员工二维码': {
                //$this->reply4YuanGongErWeiMa();
                $qrUrl = U("WxNonValid/reply4YuanGongErWeiMa", array("openid" => self::$_revdata['FromUserName']));
                Thread::asynExec($qrUrl);
                self::$_wx->text("您的推广二维码生成之中，请稍等片刻。")->reply();
                break;
            }
            case '推广二维码': {
                $qrUrl = U("WxNonValid/reply4TuiGuangErWeiMa", array("openid" => self::$_revdata['FromUserName']));
                Thread::asynExec($qrUrl);
                self::$_wx->text("您的推广二维码生成之中，请稍等片刻。")->reply();
                break;
            }
            case '签到':
            case 'qd':
            {
                $qrUrl = U("WxNonValid/reply4signon", array("openid" => self::$_revdata['FromUserName']));
                Thread::asynExec($qrUrl);
                self::$_wx->text("您的签到图片生成之中，请稍等片刻。")->reply();
                break;
            }
            default: {
                //用户自定义关键词匹配
                //*********************************************************************
                $mapkey['keyword'] = $key;
                //用户自定义关键词
                $keyword = M('Wx_keyword');
                $record = $keyword->where($mapkey)->find();
                if ($record) {
                    //进入用户自定义关键词回复
                    $this->toUsersKeyWord($record);
                }
                //*********************************************************************

                //系统自定义关键词数组
                //$osWgw=array('官网','首页','微官网','Home','home','Index','index');
                //if(in_array($key,$osWgw)){$this->toWgw('index',false);}

                //未知关键词匹配
                //*********************************************************************
                $this->toKeyUnknow();
                break;
            }
        }
    }

    public function updateUser($openid)
    {
        $old = self::$_ppvip->where(array('openid' => $openid))->find();
        if ($old) {
            if ((time() - $old['cctime']) > 86400) {
                $user = self::$_wx->getUserInfo($openid);
                //当成功拉去数据后
                if ($user) {
                    $user['cctime'] = time();
                    unset($user['groupid']);
                    $re = self::$_ppvip->where(array('id' => $old['id']))->save($user);
                } else {
                    $str = '更新用户资料失败，用户为：' . $openid;
                    file_put_contents('./Data/app_fail.txt', '微信接口失败:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            } else {
                //1天内，直接保存最后的交互时间
                $old['cctime'] = time();
                $re = self::$_ppvip->save($old);
            }
        }
        return ture;
    }

    public function toAppDebug($type)
    {
        $title = "App管理员模式：\n命令：" . $type . "\n结果：\n";

        switch ($type) {
            case 'dkf':
                $str = "人工客服接入！";
                self::$_wx->dkf($str)->reply();
                break;
            case 'openid':
                self::$_wx->text($title . self::$_revdata['FromUserName'])->reply();
                break;
            default:
                self::$_wx->text("App:未知命令")->reply();
        }
    }

    /** 根据微信接口获取用户信息
     * @param $openid
     * @return array|bool 用户信息/未获取
     */
    public function apiClient($openid)
    {
        $user = self::$_wx->getUserInfo($openid);
        return $user ? $user : FALSE;
    }

    /*具体微管网推送方式 by App
    $type=对应应用的类型
    $imglist=true/false 是否以多条返回/最多10条
     */




    public function toUsersKeyWord($record)
    {
        $type = $record['type'];
        switch ($type) {
            //文本
            case "1":
                self::$_wx->text($record['summary'])->reply();
                break;
            //单图文
            case "2":
                $news[0]['Title'] = $record['name'];
                $news[0]['Description'] = $record['summary'];
                $img = $this->getPic($record['pic']);
                $news[0]['PicUrl'] = $img['imgurl'];
                $news[0]['Url'] = $record['url'];
                self::$_wx->news($news)->reply();
                break;
            //多图文
            case "3":
                $pagelist = M('Wx_keyword_img')->where(array('kid' => $record['id']))->order('sorts desc')->select();
                $news = array();
                foreach ($pagelist as $k => $v) {
                    $news[$k]['Title'] = $v['name'];
                    $news[$k]['Description'] = $v['summary'];
                    $img = $this->getPic($v['pic']);
                    $news[$k]['PicUrl'] = $img['imgurl'];
                    $news[$k]['Url'] = $v['url'];
                }
                self::$_wx->news($news)->reply();
                break;
            default:
                self::$_wx->text("未知类型的关键词，请联系客服！")->reply();
                break;
        }
    }

    /**
     * 获取单张图片
     * @param $id
     * @return bool|mixed
     */
    public function getPic($id)
    {
        $m = M('Upload_img');
        $map['id'] = $id;
        $list = $m->where($map)->find();
        $list['imgurl'] = WebHelper::getHostNameFull().__ROOT__ . "/Upload/" . $list['savepath'] . $list['savename'];
        return $list ? $list : false;
    }


    public function toKeyUnknow()
    {
        self::$_wx->text("未找到此关键词匹配！")->reply();
    }

    /*认证服务号微信用户资料更新 by App
    return
     */

    public function checkEvent($event)
    {
        switch ($event) {
            case 'subscribe': {//首次关注事件
                $this->subscribe();
                break;
            }
            case 'unsubscribe': {//取消关注事件
                $this->unSubscribe();
                break;
            }
            case 'CLICK': {//自定义菜单点击事件
                $key = self::$_revdata['EventKey'];
                //self::$_wx->text('菜单点击拦截'.self::$_revdata['EventKey'].'!')->reply();
                switch ($key) {
                    case '#sy':
                        break;
                }

                //不存在拦截命令,走关键词流程
                $this->checkKeyWord($key);
                break;
            }
        }
    }

    ///////////////////增值方法//////////////////////////

    private function subscribe()
    {
        //用户关注：判断是否已存在
        //检查用户是否已存在
        $old['openid'] = self::$_revdata['FromUserName'];
        $isold = self::$_ppvip->where($old)->find();

        if ($isold) {
            $data['subscribe'] = 1;
            $re = self::$_ppvip->where($old)->setField('subscribe', 1);
            //增加上线关注人数
            if ($isold['pid']) {
                $fxs = self::$_fx->where('id=' . $isold['pid'])->find();
                if ($fxs) {
                    $dlog['ppid'] = $isold['pid'];
                    $dlog['from'] = $isold['id'];
                    $dlog['fromname'] = $isold['nickname'];
                    $dlog['to'] = $fxs['id'];
                    $dlog['toname'] = $fxs['nickname'];
                    $dlog['issub'] = 1;
                    $dlog['ctime'] = time();
                    $rdlog = self::$_fxlog->add($dlog);
                    $rfxs = self::$_fx->where('id=' . $isold['pid'])->setInc('total_xxsub', 1);    //下线累计关注
                } else {
                    $dlog['ppid'] = 0;
                    $dlog['from'] = $isold['id'];
                    $dlog['fromname'] = $isold['nickname'];
                    $dlog['to'] = 0;
                    $dlog['toname'] = self::$_shop['name'];
                    $dlog['issub'] = 1;
                    $dlog['ctime'] = time();
                    $rdlog = self::$_fxlog->add($dlog);
                }
            }

            $tourl = self::$_url . '/App/Shop/index/ppid/' . $isold['id'] . '/';
            //$str = "<a href='" . $tourl . "'>" . htmlspecialchars_decode(self::$_set['wxsummary']) . "</a>";
            $str = htmlspecialchars_decode(self::$_set['wxsummary']);
            self::$_wx->text($str)->reply();
        } else {
            $pid = 0;
            $old = array();
            if (!empty(self::$_revdata['Ticket'])) {
                $ticket = self::$_revdata['Ticket'];
                $old = self::$_ppvip->where(array("ticket" => $ticket))->find();
                $pid = $old["id"];
            }

            $user = $this->apiClient(self::$_revdata['FromUserName']);
            unset($user['groupid']);
            if ($user) {
                //新用户注册政策
                $vipset = M('Vip_set')->find();
                $user['score'] = $vipset['reg_score'];
                $user['exp'] = $vipset['reg_exp'];
                $user['cur_exp'] = $vipset['reg_exp'];
                //$level=$this->getLevel($user['exp']);报错
                $user['levelid'] = 1;
                //追入首次时间和更新时间
                $user['ctime'] = $user['cctime'] = time();

                //系统追入path 追入员工
                if ($old['id']) {
                    $user['pid'] = $old['id'];
                    $user['path'] = $old['path'] . '-' . $old['id'];
                    $user['plv'] = $old['plv'] + 1;
                    $user['employee'] = $old['employee'];
                } else {
                    $user['pid'] = 0;
                    $user['path'] = 0;
                    $user['plv'] = 1;
                    $user['employee'] = D('Employee')->randomEmployee();
                }
                $revip = self::$_ppvip->add($user);

                if ($revip) {
                    if ($old['id']) {
                        //----------------------------------------------------
                        //添加朋友关系（将主邀请人自动设置为被邀请人的朋友）
                        $viprelation = array();
                        $viprelation['a_vipid'] =$old['id'];
                        $viprelation['b_vipid'] =$revip;

                        $relationtime= date('Y-m-d H:i:s',time());
                        $viprelation['invitetime'] = $relationtime;
                        $viprelation['accepttime'] =$relationtime;
                        $viprelation['friendstatus'] =10;
                        $modal= new ModelMate("vip_friends");
                        $modal->interact($viprelation);
                        //----------------------------------------------------
                    }

                    //赠送操作
                    if ($vipset['isgift']) {
                        $gift = explode(",", $vipset['gift_detail']);
                        $cardnopwd = $this->getCardNoPwd();
                        $data_card['type'] = $gift[0];
                        $data_card['vipid'] = $revip;
                        $data_card['money'] = $gift[1];
                        $data_card['usemoney'] = $gift[3];
                        $data_card['cardno'] = $cardnopwd['no'];
                        $data_card['cardpwd'] = $cardnopwd['pwd'];
                        $data_card['status'] = 1;
                        $data_card['stime'] = $data_card['ctime'] = time();
                        $data_card['etime'] = time() + $gift[2] * 24 * 60 * 60;
                        $rcaSrd = M('Vip_card')->add($data_card);
                    }
                    //发送注册通知消息
                    //记录日志
                    $data_log['ip'] = 'wechat';    //源自微信注册
                    $data_log['vipid'] = $revip;
                    $data_log['ctime'] = time();
                    $data_log['openid'] = $user['openid'];
                    $data_log['nickname'] = $user['nickname'];
                    $data_log['event'] = "会员注册";
                    $data_log['score'] = $user['score'];
                    $data_log['exp'] = $user['exp'];
                    $data_log['type'] = 4;
                    $rlog = M('Vip_log')->add($data_log);
                }
                //追入新用户关注日志
                $dlog['ppid'] = 0;
                $dlog['from'] = $revip;
                $dlog['fromname'] = $user['nickname'];
                $dlog['to'] = 0;
                $dlog['toname'] = self::$_shop['name'];
                $dlog['issub'] = 1;
                $dlog['ctime'] = time();
                $rdlog = self::$_fxlog->add($dlog);

                //处理父亲
                $mvip = self::$_ppvip;
                $old = $mvip->where(array('id' => $pid))->find();
                if ($old) {
                    $tj_score = self::$WAP['vipset']['tj_score'];
                    $tj_exp = self::$WAP['vipset']['tj_exp'];
                    $tj_money = self::$WAP['vipset']['tj_money'];
                    if ($tj_score || $tj_exp || $tj_money) {
                        $msg = "推荐新用户奖励：<br>新用户：" . $user['nickname'] . "<br>奖励内容：<br>";
                        $mglog = "获得新用户注册奖励:";
                        if ($tj_score) {
                            $old['score'] = $old['score'] + $tj_score;
                            $msg = $msg . $tj_score . "个积分<br>";
                            $mglog = $mglog . $tj_score . "个积分；";
                        }
                        if ($tj_exp) {
                            $old['exp'] = $old['exp'] + $tj_exp;
                            $msg = $msg . $tj_exp . "点经验<br>";
                            $mglog = $mglog . $tj_exp . "点经验；";
                        }
                        if ($tj_money) {
                            $old['money'] = $old['money'] + $tj_money;
                            $msg = $msg . $tj_money . "元余额<br>";
                            $mglog = $mglog . $tj_money . "元余额；";
                        }
                        $msg = $msg . "此奖励已自动打入您的帐户！感谢您的支持！";
                        $rold = $mvip->save($old);
                        if (FALSE !== $rold) {
                            $data_msg['pids'] = $old['id'];
                            $data_msg['title'] = "你获得一份推荐奖励！";
                            $data_msg['content'] = $msg;
                            $data_msg['ctime'] = time();
                            $rmsg = M('Vip_message')->add($data_msg);
                            $data_mglog['vipid'] = $old['id'];
                            $data_mglog['nickname'] = $old['nickname'];
                            $data_mglog['xxnickname'] = $user['nickname'];
                            $data_mglog['msg'] = $mglog;
                            $data_mglog['ctime'] = time();
                            $rmglog = M('Vx_log_tj')->add($data_mglog);
                        }
                    }

                    //三层上线追溯统计
                    // 三层上线追溯客服接口
                    $old['total_xxlink'] = $old['total_xxlink'] + 1;
                    $r1 = $mvip->save($old);
                    // 上下级自定义及Wechat配置
                    // $customerdown = M('Wx_customer')->where(array('type'=>'down'))->find();
                    $customerup = M('Wx_customer')->where(array('type' => 'up'))->find();
                    $shopset = M('Shop_set')->find();

                    // 发送信息给自己===============
                    //$msg = array();
                    //$msg['touser'] = $vip['openid'];
                    //$msg['msgtype'] = 'text';
                    //$str = $customerdown['value'];
                    //$msg['text'] = array('content'=>$str);
                    //$ree = $wx->sendCustomMessage($msg);
                    // 发送消息完成============
                    // 发送信息给父级===============
                    $msg = array();
                    $msg['touser'] = $old['openid'];
                    $msg['msgtype'] = 'text';
                    $newUserName= $user['nickname'];
                    $str = "通过您的推广，用户[$newUserName]成为了您的[" . $shopset['fx1name'] . "](" . $customerup['value'].")";
                    $msg['text'] = array('content' => $str);
                    $ree = self::$_wx->sendCustomMessage($msg);
                    // 发送消息完成============
                    if ($old['pid']) {
                        $oldold = $mvip->where('id=' . $old['pid'])->find();
                        $oldold['total_xxlink'] = $oldold['total_xxlink'] + 1;
                        $r2 = $mvip->save($oldold);
                        // 发送信息给父级的父级===============
                        $msg = array();
                        $msg['touser'] = $oldold['openid'];
                        $msg['msgtype'] = 'text';
                        $str = "通过您的推广，用户[$newUserName]成为了您的[" . $shopset['fx2name'] . "](" . $customerup['value'].")";
                        $msg['text'] = array('content' => $str);
                        $ree = self::$_wx->sendCustomMessage($msg);
                        // 发送消息完成============
                        if ($oldold['pid']) {
                            $oldoldold = $mvip->where('id=' . $oldold['pid'])->find();
                            $oldoldold['total_xxlink'] = $oldoldold['total_xxlink'] + 1;
                            $r3 = $mvip->save($oldoldold);
                            // 发送信息给父级的父级的父级===============
                            $msg = array();
                            $msg['touser'] = $oldoldold['openid'];
                            $msg['msgtype'] = 'text';
                            $str = "通过您的推广，用户[$newUserName]成为了您的[" . $shopset['fx3name'] . "](" . $customerup['value'].")";
                            $msg['text'] = array('content' => $str);
                            $ree = self::$_wx->sendCustomMessage($msg);
                            // 发送消息完成============
                        }
                    }
                    // 上报员工	向员工发送信息
                    $employee = M('Employee')->where(array('id' => $old['employee']))->find();
                    if ($old['employee'] && $employee && $employee['vipid']) {
                        $customeremp = M('Wx_customer')->where(array('type' => 'emp'))->find();
                        $empvip = $mvip->where(array('id' => $employee['vipid']))->find();
                        if ($empvip) {
                            $msg = array();
                            $msg['touser'] = $empvip['openid'];
                            $msg['msgtype'] = 'text';
                            $str = "通过您的推广，用户[$newUserName]成为了您的[" . $shopset['fxname'] . "](" . $customeremp['value'].")";
                            $msg['text'] = array('content' => $str);
                            $ree = self::$_wx->sendCustomMessage($msg);
                        }
                    }
                }

                $tourl = self::$_url . '/index.php/App/Shop/index/ppid/' . $revip . '/';
                $str = "<a href='" . $tourl . "'>" . htmlspecialchars_decode(self::$_set['wxsummary']) . "</a>";
            } else {
                $tourl = self::$_url . '/index.php/App/Shop/index/';
                $str = "<a href='" . $tourl . "'>" . htmlspecialchars_decode(self::$_set['wxsummary']) . "</a>";
            }
        }
        $this->subscribeReturn($str);
    }

    public function getCardNoPwd()
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

    /**关注时返回信息
     * @param $msg
     */
    function subscribeReturn($msg)
    {
        $temp = getcwd() . $this->getSubscribePic(self::$_set['wxpicture']);
        $switchs = file_exists($temp);
        if (self::$_set['wxswitch'] == '0' || !$switchs) {
            self::$_wx->text($msg)->reply();
        } else {
            $data = array('media' => '@' . $temp);
            $uploadresult = self::$_wx->uploadMedia($data, 'image');
            self::$_wx->image($uploadresult['media_id'])->reply();
        }
    }

    function getSubscribePic($id)
    {
        $m = M('UploadImg');
        $temparr = split(',', $id);
        foreach ($temparr as $v) {
            if ($v != '') {
                $map['id'] = $v;
                break;
            }
        }
        if ($map) {
            $list = $m->where($map)->find();
            if ($list) {
                $list['imgurl'] = "/Upload/" . $list['savepath'] . $list['savename'];
                $temp = str_replace('/', '/', $list['imgurl']);
            }
        }
        return $temp ? $temp : '';
    }

    private function unSubscribe()
    {
        //更新库内的用户关注状态字段
        $map['openid'] = self::$_revdata['FromUserName'];
        $old = self::$_ppvip->where($map)->find();
        if ($old) {
            $rold = self::$_ppvip->where($map)->setField('subscribe', 0);
            if ($old['ppid']) {
                $fxs = self::$_fx->where('id=' . $old['ppid'])->find();
                if ($fxs) {
                    $dlog['ppid'] = $old['ppid'];
                    $dlog['from'] = $old['id'];
                    $dlog['fromname'] = $old['nickname'];
                    $dlog['to'] = $fxs['id'];
                    $dlog['toname'] = $fxs['nickname'];
                    $dlog['issub'] = 0;
                    $dlog['ctime'] = time();
                    $rdlog = self::$_fxlog->add($dlog);
                    $rfxs = self::$_fx->where('id=' . $old['ppid'])->setInc('total_xxunsub', 1);    //下线累计取消关注
                }
            } else {
                $dlog['ppid'] = 0;
                $dlog['from'] = $old['id'];
                $dlog['fromname'] = $old['nickname'];
                $dlog['to'] = 0;
                $dlog['toname'] = self::$_shop['name'];
                $dlog['issub'] = 0;
                $dlog['ctime'] = time();
                $rdlog = self::$_fxlog->add($dlog);
            }
        }
    }

    public function toWgw($type, $imglist)
    {
        $wgw = F(self::$_uid . "/config/wgw_set"); //微官网设置缓存
        switch ($type) {
            case 'index':
                //准备各项参数
                $title = $wgw['title'] ? $wgw['title'] : '欢迎访问' . self::$_userinfo['wxname'];
                $summary = $wgw['summary'];
                $picid = $wgw['pic'];
                $picurl = $picid ? $this->getPic($picid) : false;
                //封装图文信息
                $news[0]['Title'] = $title;
                $news[0]['Description'] = $summary;
                $news[0]['PicUrl'] = $picurl['imgurl'] ? $picurl['imgurl'] : '#';
                $news[0]['Url'] = self::$_url . '/App/Wgw/Index/uid/' . self::$_uid;
                //推送图文信息
                self::$_wx->news($news)->reply();
                break;
        }
    }

    // 获取头像函数


    /**
     * 将图文信息封装为二维数组
     * @param $array
     * @param bool $return 是新闻数组还是直接直接推送
     * @return mixed
     */
    public function makeNews($array, $return = false)
    {
        if (!$array) {
            die('no items!');
        }
        $news[0]['Title'] = $array[0];
        $news[0]['Description'] = $array[1];
        $news[0]['PicUrl'] = $array[2];
        $news[0]['Url'] = $array[3];
        if ($return) {
            return $news;
        } else {
            self::$_wx->news($news)->reply();
        }
    }

    // 获取单张图片

    public function getlevel($exp)
    {
        $data = M('Vip_level')->order('exp')->select();
        if ($data) {
            $level = array();
            foreach ($data as $k => $v) {
                if ($k + 1 == count($data)) {
                    if ($exp >= $data[$k]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                } else {
                    if ($exp >= $data[$k]['exp'] && $exp < $data[$k + 1]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                }
            }
        } else {
            return false;
        }
        return $level;
    }

} //API类结束