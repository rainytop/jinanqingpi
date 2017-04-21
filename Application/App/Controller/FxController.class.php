<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class FxController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();

    }

    public function index()
    {
        $data = self::$WAP['vip'];
        if ($data['isfxgd']) {
            $data['fxname'] = '超级VIP';
        } else {
            if ($data['isfx']) {
                $data['fxname'] = self::$SHOP['set']['fxname'];
            } else {
                $data['fxname'] = '非' . self::$SHOP['set']['fxname'];
            }
        }
        $mvip = M('Vip');
        //超级VIP取20层，普通取3层
        //dump($data['plv']);
        $maxlv = $data['plv'] + 3;
        $likepath = $data['path'] . '-' . $data['id'];
        //取出超级VIP团队总人数
        //		if($data['isfxgd']){
        //			$maphg['plv']=array('elt',$data['plv']+20);
        //			$maphg['path']=array('like',$likepath.'%');
        //			$data['total_hglink']=$mvip->field('id')->where($maphg)->count();
        //		}
        //取出符合的所有会员ID;
        //两次模糊查询
        //1:取出第一层，2:取出其他层
        $firstlv = $data['plv'] + 1;
        $firstpath = $likepath;
        $mapfirst['plv'] = $firstlv;
        $mapfirst['path'] = $firstpath;
        $firstsub = $mvip->field('id,plv,path,nickname')->where($mapfirst)->select();
        //dump($sub);
        if ($firstsub) {
            //模糊查询第二层和第三层
            $maplike['plv'] = array('gt', $firstlv);
            $maplike['plv'] = array('elt', $maxlv);
            $maplike['path'] = array('like', $likepath . '-%');
            $sesendsub = $mvip->field('id,plv,path,nickname')->where($maplike)->select();
            //dump($firstsub);
            //dump($sesendsub);
            //合并两个数组
            if ($sesendsub) {
                $sub = array_merge($firstsub, $sesendsub);
            } else {
                $sub = $firstsub;
            }

            //分组
            $subarr = array();
            foreach ($sub as $v) {
                //按层级分组
                $subarr[$v['plv']] = $subarr[$v['plv']] . $v['id'] . ',';
                //array_push($subarr[$v['plv']],$v['id']);
            }

            $subarr = array_values($subarr);
            //dump($subarr);
            //找出关联订单
            $shopset = M('ShopSet')->find();
            $morder = M('ShopOrder');
            $fx1rate = $shopset['fx1rate'];
            $fx2rate = $shopset['fx2rate'];
            $fx3rate = $shopset['fx3rate'];
            $commission = D('Commission');
            if ($fx1rate && $subarr[0]) {
                $tmprate = $fx1rate;
                $tmplv = $data['plv'] + 1;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[0]));
                $tmptotal = $morder->where($maporder)->sum('payprice');
                $fx1total = $tmptotal * ($tmprate / 100);
                // 添加修改
                $tempids = array();
                $temp = $morder->field('id')->where($maporder)->select();
                foreach ($temp as $v) {
                    array_push($tempids, $v['id']);
                }
                $fx1total = $commission->ordersCommission('fx1rate', $tempids);
            } else {
                $fx1total = 0;
            }
            if ($fx2rate) {
                $tmprate = $fx2rate;
                $tmplv = $data['plv'] + 2;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[1]));

                $tmptotal = $morder->where($maporder)->sum('payprice');
                $fx2total = $tmptotal * ($tmprate / 100);
                // 添加修改
                $tempids = array();
                $temp = $morder->field('id')->where($maporder)->select();
                foreach ($temp as $v) {
                    array_push($tempids, $v['id']);
                }
                $fx2total = $commission->ordersCommission('fx2rate', $tempids);
            } else {
                $fx2total = 0;
            }
            if ($fx3rate) {
                $tmprate = $fx3rate;
                $tmplv = $data['plv'] + 3;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[2]));

                $tmptotal = $morder->where($maporder)->sum('payprice');
                $fx3total = $tmptotal * ($tmprate / 100);
                // 添加修改
                $tempids = array();
                $temp = $morder->field('id')->where($maporder)->select();
                foreach ($temp as $v) {
                    array_push($tempids, $v['id']);
                }
                $fx3total = $commission->ordersCommission('fx3rate', $tempids);
            } else {
                $fx3total = 0;
            }
            $data['fxmoney'] = number_format(($fx1total + $fx2total + $fx3total), 2);

        } else {
            $data['fxmoney'] = 0.00;
        }
        $maptx['vipid'] = $data['id'];
        $maptx['status'] = 1;
        $txtotal = M('VipTx')->where($maptx)->sum('txprice');
        if ($txtotal > 0) {
            $data['txmoney'] = number_format($txtotal, 2);
        } else {
            $data['txmoney'] = number_format(0, 2);
        }
        //dump($txtotal);
        $this->assign('data', $data);
        $this->display();
    }

    public function paihang()
    {
        $m = M('Vip');
        $map['isfx'] = 1;
        $map['total_xxyj'] = array('gt', 0);
        $cache = $m->where($map)->limit(50)->order('total_xxyj desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    public function myqrcode()
    {
        //追入分享特效
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $wx = new \Util\Wx\Wechat($options);
        //生成JSSDK实例
        $opt['appid'] = self::$_wxappid;
        $opt['token'] = $wx->checkAuth();
        $opt['url'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $jssdk = new \Util\Wx\Jssdk($opt);
        $jsapi = $jssdk->getSignPackage();
        if (!$jsapi) {
            die('未正常获取数据！');
        }
        $this->assign('jsapi', $jsapi);

        $vip = self::$WAP['vip'];
        $img = __ROOT__."/QRcode/promotion/" . "employee" . $vip['openid'] . '.jpg';

        $this->assign('img', $img);
        $this->assign('vip', $vip);
        $this->display();
    }

    public function getqrcode()
    {
        $set = M('Set')->find();
        $url = $set['wxurl'] . '/App/Shop/index/ppid/' . self::$WAP['vipid'];
        $QR = new \Util\QRcode();
        $QR::png($url);
    }

    public function myuser()
    {
        $m = M('vip');
        $type = intval(I('type')) ? intval(I('type')) : 1;
        $vipid = $_SESSION['WAP']['vipid'];
        if ($type == 1) {
            $this->assign('type', self::$SHOP['set']['fx1name']);
            $cache = $m->where(array('pid' => $vipid))->order('ctime desc')->limit(50)->select();
            $total = $m->where(array('pid' => $vipid))->count();
        }
        if ($type == 2) {
            $this->assign('type', self::$SHOP['set']['fx2name']);
            $arr = array();
            $tmp = $m->field('id')->where(array('pid' => $vipid))->order('ctime desc')->limit(50)->select();
            foreach ($tmp as $v) {
                array_push($arr, $v['id']);
            }
            $cache = $m->where(array('pid' => array('in', in_parse_str($arr))))->select();
            $total = $m->where(array('pid' => array('in', in_parse_str($arr))))->count();
        }
        if ($type == 3) {
            $this->assign('type', self::$SHOP['set']['fx3name']);
            $arr = array();
            $tmp = $m->field('id')->where(array('pid' => $vipid))->select();
            foreach ($tmp as $v) {
                array_push($arr, $v['id']);
            }
            $tmp2 = $m->field('id')->where(array('pid' => array('in', in_parse_str($arr))))->select();
            $arr2 = array();
            foreach ($tmp2 as $v) {
                array_push($arr2, $v['id']);
            }

            if (!$arr2) {
                $arr2 = '';
            }
            $cache = $m->where(array('pid' => array('in', in_parse_str($arr2))))->order('ctime desc')->limit(50)->select();
            $total = $m->where(array('pid' => array('in', in_parse_str($arr2))))->count();
        }
        $this->assign('total', $total);
        $this->assign('cache', $cache);
        $this->display();
    }

    public function dslog()
    {
        $m = M('fx_dslog');
        $map['to'] = $_SESSION['WAP']['vipid'];
        $map['status'] = 1;
        $cache = $m->where($map)->limit(50)->order('ctime desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    public function fxlog()
    {
        $m = M('fx_syslog');
        $map['to'] = $_SESSION['WAP']['vipid'];
        $map['status'] = 1;
        $cache = $m->where($map)->limit(50)->order('ctime desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    public function tjlog()
    {
        $m = M('fx_log_tj');
        $map['vipid'] = $_SESSION['WAP']['vipid'];
        $cache = $m->where($map)->limit(50)->order('ctime desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    public function about()
    {
        $this->display();
    }

}
