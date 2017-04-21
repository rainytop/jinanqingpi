<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class ShopController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法	
        parent::_initialize();
        $shopset = M('Shop_set')->where('id=1')->find();
        if ($shopset['pic']) {
            $listpic = $this->getPic($shopset['pic']);
            $shopset['sharepic'] = $listpic['imgurl'];
        }
        if ($shopset) {
            self::$WAP['shopset'] = $_SESSION['WAP']['shopset'] = $shopset;
            $this->assign('shopset', $shopset);
        } else {
            $this->diemsg(0, '您还没有进行商城配置！');
        }
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function index()
    {
        //追入分享特效
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $wx = new \Util\Wx\Wechat($options);

        //生成JSSDK实例
        $opt['appid'] = self::$_wxappid;
        $opt['token'] = $wx->checkAuth();
        $opt['url'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $jssdk = new \Util\Wx\Jssdk($opt);
        $jsapi = $jssdk->getSignPackage();
        if (!$jsapi) {
            die('未正常获取数据！');
        }
        $this->assign('jsapi', $jsapi);
        //正常逻辑
        $m = M('Shop_goods');
        //$tmpgroup=M('Shop_group')->select();
        $tmpgroup = M('Shop_group')->where(array('status' => 1))->find();
        $this->assign('group', $tmpgroup);
        // $group=array();
        // foreach($tmpgroup as $k=>$v){
        // 	$group[$v['id']]=$v['goods'];
        // }
        //重磅推荐
        //$mrtj=$m->where(array('id'=>array('in',$group[1])))->select();
        $mrtj = $m->where(array('id' => array('in', in_parse_str($tmpgroup['goods']))))->select();
        foreach ($mrtj as $k => $v) {
            $listpic = $this->getPic($v['indexpic']);
            $mrtj[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('mrtj', $mrtj);
        $type = intval(I('type')) ? intval(I('type')) : 0;
        $this->assign('type', $type);
        if ($type) {
            $map['cid'] = $type;
        }
        $map['status'] = 1;
        $cache = $m->where($map)->select();
        foreach ($cache as $k => $v) {
            $listpic = $this->getPic($v['listpic']);
            $cache[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('type', $type);
        $this->assign('cache', $cache);
        //分组调用
        $mapx['id'] = array('in', in_parse_str(self::$WAP['shopset']['indexgroup']));
        $indexicons = M('Shop_cate')->where($mapx)->select();
        foreach ($indexicons as $k => $v) {
            $listpic = $this->getPic($v['icon']);
            $indexicons[$k]['iconurl'] = $listpic['imgurl'];
            $indexicons[$k]['ison'] = $type == $v['id'] ? '1' : '0';
            // 获取下级
            if ($indexicons[$k]['soncate']) {
                $son = M('Shop_cate')->where(array('id' => array('in', in_parse_str($indexicons[$k]['soncate']))))->select();
                foreach ($son as $kk => $vv) {
                    $temp = $this->getPic($vv['icon']);
                    $son[$kk]['iconurl'] = $temp['imgurl'];
                    $son[$kk]['ison'] = $type == $vv['id'] ? '1' : '0';
                    $son[$kk]['url'] = U('App/Shop/index#nav', array('type' => $v['id']));
                }
                $indexicons[$k]['son'] = 1;
                $indexicons[$k]['sonlist'] = $son;
                $indexicons[$k]['url'] = "javascript:;";
            } else {
                $indexicons[$k]['son'] = 0;
                $indexicons[$k]['url'] = U('App/Shop/index#nav', array('type' => $v['id']));
            }

        }
        //首页轮播图集
        $indexalbum = M('Shop_ads')->where('id', array('in', in_parse_str(self::$WAP['shopset']['indexalbum'])))->select();
        foreach ($indexalbum as $k => $v) {
            $listpic = $this->getPic($v['pic']);
            $indexalbum[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('indexalbum', $indexalbum);
//        dump($indexicons);
        $this->assign('indexicons', $indexicons);
        //首页分享特效
        //dump(self::$WAP['vip']['ppid']);
        if (!self::$WAP['vip']['subscribe']) {
//            if (self::$WAP['vip']['pid']) {
//                $father = M('Vip')->where('id=' . self::$WAP['vip']['pid'])->find();
//                $this->assign('showsub', 1);
//                if ($father) {
//                    $this->assign('showfather', 1);
//                    $this->assign('father', $father);
//                } else {
//                    $this->assign('showfather', 0);
//                }
//
//            } else {
                $this->assign('showsub', 1);
//                $this->assign('showfather', 0);
//            }
        } else {
            $this->assign('showsub', 0);
        }

        $this->display();
    }

    public function goods()
    {
        $id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
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
        $m = M('Shop_goods');
        $cache = $m->where('id=' . $id)->find();
        if (!$cache) {
            $this->error('此商品已下架！', U('App/Shop/index'));
        }
        if (!$cache['status']) {
            $this->error('此商品已下架！', U('App/Shop/index'));
        }
        //自动计数
        $rclick = $m->where('id=' . $id)->setInc('clicks', 1);
        //读取标签
        foreach (explode(',', $cache['lid']) as $k => $v) {
            $label[$k] = M('ShopLabel')->where(array('id' => $v))->getField('name');
        }
        $cache['label'] = $label;
        $this->assign('cache', $cache);
        if ($cache['issku']) {
            if ($cache['skuinfo']) {
                $skuinfo = unserialize($cache['skuinfo']);
                $skm = M('Shop_skuattr_item');
                foreach ($skuinfo as $k => $v) {
                    $checked = explode(',', $v['checked']);
                    $attr = $skm->field('path,name')->where('pid=' . $v['attrid'])->select();
                    foreach ($attr as $kk => $vv) {
                        $attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
                    }
                    $skuinfo[$k]['allitems'] = $attr;
                }
                $this->assign('skuinfo', $skuinfo);
            } else {
                $this->diemsg(0, '此商品还没有设置SKU属性！');
            }
            $skuitems = M('Shop_goods_sku')->field('sku,skuattr,price,num,hdprice,hdnum')->where(array('goodsid' => $id, 'status' => 1))->select();
            if (!$skuitems) {
                $this->diemsg(0, '此商品还未生成SKU!');
            }
            $skujson = array();
            foreach ($skuitems as $k => $v) {
                $skujson[$v['sku']]['sku'] = $v['sku'];
                $skujson[$v['sku']]['skuattr'] = $v['skuattr'];
                $skujson[$v['sku']]['price'] = $v['price'];
                $skujson[$v['sku']]['num'] = $v['num'];
                $skujson[$v['sku']]['hdprice'] = $v['hdprice'];
                $skujson[$v['sku']]['hdnum'] = $v['hdnum'];
            }
            $this->assign('skujson', json_encode($skujson));
        }

        //绑定图集
        if ($cache['album']) {
            $appalbum = $this->getAlbum($cache['album']);
            if ($appalbum) {
                $this->assign('appalbum', $appalbum);
            }
        }
        //绑定图片
        if ($cache['pic']) {
            $apppic = $this->getPic($cache['pic']);
            if ($apppic) {
                $this->assign('apppic', $apppic);
            }
        }
        //绑定购物车数量
        $basketnum = M('Shop_basket')->where(array('sid' => 0, 'vipid' => self::$WAP['vipid']))->sum('num');
        $this->assign('basketnum', $basketnum);
        //绑定登陆跳转地址
        $backurl = base64_encode(U('App/Shop/goods', array('id' => $id)));
        $loginback = U('App/Vip/login', array('backurl' => $backurl));
        $this->assign('loginback', $loginback);
        $this->assign('lasturl', $backurl);
        $this->display();
    }

    public function basket()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $lasturl = I('lasturl') ? I('lasturl') : $this->diemsg(0, '缺少LastURL参数');
        $basketlasturl = base64_decode($lasturl);
        $basketurl = U('App/Shop/basket', array('sid' => $sid, 'lasturl' => $lasturl));
        $backurl = base64_encode($basketurl);
        $basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //保存当前购物车地址
        $this->assign('basketurl', $basketurl);
        //保存登陆购物车地址
        $this->assign('basketloginurl', $basketloginurl);
        //保存购物车前地址
        $this->assign('basketlasturl', $basketlasturl);
        //保存购物车加密地址，用于OrderMaker正常返回
        $this->assign('lasturlencode', $lasturl);
        //已登陆
        $m = M('Shop_basket');
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $returnurl = base64_decode($lasturl);
        $this->assign('returnurl', $returnurl);
        $cache = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
        //错误标记
        $errflag = 0;
        //等待删除ID
        $todelids = '';
        //totalprice
        $totalprice = 0;
        //totalnum
        $totalnum = 0;
        foreach ($cache as $k => $v) {
            //sku模型
            $goods = $mgoods->where('id=' . $v['goodsid'])->find();
            $pic = $this->getPic($goods['pic']);
            if ($v['sku']) {
                //取商品数据				
                if ($goods['issku'] && $goods['status']) {
                    $map['sku'] = $v['sku'];
                    $sku = $msku->where($map)->find();
                    if ($sku['status']) {
                        if ($sku['num']) {
                            //调整购买量
                            $cache[$k]['name'] = $goods['name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                            $cache[$k]['price'] = $sku['price'];
                            $cache[$k]['total'] = $sku['num'];
                            $cache[$k]['pic'] = $pic['imgurl'];
                            $totalnum = $totalnum + $cache[$k]['num'];
                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);

                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    $todelids = $todelids . $v['id'] . ',';
                    unset($cache[$k]);
                }

            } else {
                if ($goods['status']) {
                    if ($goods['num']) {
                        //调整购买量
                        $cache[$k]['name'] = $goods['name'];
                        $cache[$k]['skuattr'] = $sku['skuattr'];
                        $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                        $cache[$k]['price'] = $goods['price'];
                        $cache[$k]['total'] = $goods['num'];
                        $cache[$k]['pic'] = $pic['imgurl'];
                        $totalnum = $totalnum + $cache[$k]['num'];
                        $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                    } else {
                        //无库存删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    $todelids = $todelids . $v['id'] . ',';
                    unset($cache[$k]);
                }
            }
        }
        if ($todelids) {
            $rdel = $m->delete($todelids);
            if (!$rdel) {
                $this->error('购物车获取失败，请重新尝试！');
            }
        }


        $this->assign('cache', $cache);
        $this->assign('totalprice', $totalprice);
        $this->assign('totalnum', $totalnum);
        $this->display();
    }

    //添加购物车
    public function addtobasket()
    {
        if (IS_AJAX) {
            $m = M('Shop_basket');
            $data = I('post.');
            if (!$data) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }
            //区分SKU模式
            if ($data['sku']) {
                $old = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid'], 'sku' => $data['sku']))->find();
                if ($old) {
                    $old['num'] = $old['num'] + $data['num'];
                    $rold = $m->save($old);
                    if ($rold === FALSE) {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    } else {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    }
                } else {
                    $rold = $m->add($data);
                    if ($rold) {
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    } else {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    }
                }
            } else {
                $old = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid'], 'goodsid' => $data['goodsid']))->find();
                if ($old) {
                    $old['num'] = $old['num'] + $data['num'];
                    $rold = $m->save($old);
                    if ($rold === FALSE) {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    } else {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    }
                } else {
                    $rold = $m->add($data);
                    if ($rold) {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    } else {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    }
                }
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //删除购物车
    public function delbasket()
    {
        if (IS_AJAX) {
            $id = I('id');
            if (!$id) {
                $info['status'] = 0;
                $info['msg'] = '未获取ID参数,请重新尝试！';
                $this->ajaxReturn($info);
            }
            $m = M('Shop_basket');
            $re = $m->where('id=' . $id)->delete();
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = '删除成功，更新购物车状态...';

            } else {
                $info['status'] = 0;
                $info['msg'] = '删除失败，自动重新加载购物车...';
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //清空购物车
    public function clearbasket()
    {
        if (IS_AJAX) {
            $sid = $_GET['sid'];
            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            if (!$vipid) {
                $info['status'] = 3;
                $info['msg'] = '登陆已超时，2秒后自动跳转登陆页面！';
                $this->ajaxReturn($info);
            }
            if ($sid == '') {
                $info['status'] = 0;
                $info['msg'] = '未获取SID参数,请重新尝试！';
                $this->ajaxReturn($info);
            }
            $m = M('Shop_basket');
            $re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();
            if ($re) {
                $info['status'] = 2;
                $info['msg'] = '购物车已清空';
                $this->ajaxReturn($info);
            } else {
                $info['status'] = 0;
                $info['msg'] = '购物车清空失败，请重新尝试！';
                $this->ajaxReturn($info);
            }
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //购物车库存检测
    public function checkbasket()
    {
        if (IS_AJAX) {
            $sid = $_GET['sid'];
            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            if (!$vipid) {
                $info['status'] = 3;
                $info['msg'] = '登陆已超时，2秒后自动跳转登陆页面！';
                $this->ajaxReturn($info);
            }
            $arr = $_POST;
            if ($sid == '') {
                $info['status'] = 0;
                $info['msg'] = '未获取SID参数';
                $this->ajaxReturn($info);
            }
            if (!$arr) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }
            $m = M('Shop_basket');
            $mgoods = M('Shop_goods');
            $msku = M('Shop_goods_sku');
            $data = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
            foreach ($data as $k => $v) {
                $goods = $mgoods->where('id=' . $v['goodsid'])->find();
                if ($v['sku']) {
                    $sku = $msku->where(array('sku' => $v['sku']))->find();
                    if ($sku && $sku['status'] && $goods && $goods['issku'] && $goods['status']) {
                        $nownum = $arr[$v['id']];
                        if ($sku['num'] - $nownum >= 0) {
                            //保存购物车新库存
                            if ($nownum <> $v['num']) {
                                $v['num'] = $nownum;
                                $rda = $m->save($v);
                            }
                        } else {
                            $info['status'] = 2;
                            $info['msg'] = '存在已下架或库存不足商品！';
                            $this->ajaxReturn($info);
                        }

                    } else {
                        $info['status'] = 2;
                        $info['msg'] = '存在已下架或库存不足商品！';
                        $this->ajaxReturn($info);
                    }
                } else {
                    if ($goods && $goods['status']) {
                        $nownum = $arr[$v['id']];
                        if ($goods['num'] - $nownum >= 0) {
                            //保存购物车新库存
                            if ($nownum <> $v['num']) {
                                $v['num'] = $nownum;
                                $rda = $m->save($v);
                            }
                        } else {
                            $info['status'] = 2;
                            $info['msg'] = '存在已下架或库存不足商品！';
                            $this->ajaxReturn($info);
                        }

                    } else {
                        $info['status'] = 2;
                        $info['msg'] = '存在已下架或库存不足商品！';
                        $this->ajaxReturn($info);
                    }

                }
            }
            $info['status'] = 1;
            $info['msg'] = '商品库存检测通过，进入结算页面！';
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //立刻购买逻辑
    public function fastbuy()
    {
        if (IS_AJAX) {
            $m = M('Shop_basket');
            $data = I('post.');
            if (!$data) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }

            //	$this->ajaxReturn($info);
            //判定是否有库存
//			if($data['sku']){
//				$gd=M('Shop_goods_sku')->where('id='.$data['sku'])->find();
//				if(!$gd['status']){
//					$info['status']=0;
//					$info['msg']='此产品已下架，请挑选其他产品！';
//					$this->ajaxReturn($info);
//				}
//				if($gd['num']-$data['num']<0){
//					$info['status']=0;
//					$info['msg']='该属性产品缺货或库存不足，请调整购买量！';
//					$this->ajaxReturn($info);
//				}
//			}else{
//				$info['status']=0;
//				$info['msg']='此产品已下架，请挑选其他产品！';
//				$this->ajaxReturn($info);
//				$gd=M('Shop_goods')->where('id='.$data['goodsid'])->find();
//				if(!$gd['status']){
//					$info['status']=0;
//					$info['msg']='此产品已下架，请挑选其他产品！';
//					$this->ajaxReturn($info);
//				}
//				if($gd['num']-$data['num']<0){
//					$info['status']=0;
//					$info['msg']='该产品缺货或库存不足，请调整购买量！';
//					$this->ajaxReturn($info);
//				}
//			}
            //清除购物车
            $sid = 0;
            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            $re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();
            //区分SKU模式
            if ($data['sku']) {
                $rold = $m->add($data);
                if ($rold) {
                    $info['status'] = 1;
                    $info['msg'] = '库存检测通过！2秒后自动生成订单！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '通讯失败，请重新尝试！';
                }
            } else {
                $rold = $m->add($data);
                if ($rold) {
                    $info['status'] = 1;
                    $info['msg'] = '库存检测通过！2秒后自动生成订单！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '通讯失败，请重新尝试！';
                }
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //Order逻辑
    public function orderMake()
    {
        if (IS_POST) {
            $morder = M('Shop_order');
            $data = I('post.');
            $data['items'] = stripslashes(htmlspecialchars_decode($data['items']));
            $data['ispay'] = 0;
            $data['status'] = 1;//订单成功，未付款
            $data['ctime'] = time();
            $data['payprice'] = $data['totalprice'];
            //代金卷流程
            if ($data['djqid']) {
                $mcard = M('Vip_card');
                $djq = $mcard->where('id=' . $data['djqid'])->find();
                if (!$djq) {
                    $this->error('通讯失败！请重新尝试支付！');
                }
                if ($djq['usetime']) {
                    $this->error('此代金卷已使用！');
                }
                $djq['status'] = 2;
                $djq['usetime'] = time();
                $rdjq = $mcard->save($djq);
                if (FALSE === $rdjq) {
                    $this->error('通讯失败！请重新尝试支付！');
                }
                //修改支付价格
                $data['payprice'] = $data['totalprice'] - $djq['money'];
            }
            //邮费逻辑
            if (self::$WAP['shopset']['isyf']) {
                if ($data['totalprice'] >= self::$WAP['shopset']['yftop']) {
                    $data['yf'] = 0;
                } else {
                    $data['yf'] = self::$WAP['shopset']['yf'];
                    $data['payprice'] = $data['payprice'] + $data['yf'];
                }

            } else {
                $data['yf'] = 0;
            }
            $re = $morder->add($data);
            if ($re) {
                $old = $morder->where('id=' . $re)->setField('oid', date('YmdHis') . '-' . $re);
                if (FALSE !== $old) {
                    //后端日志
                    $mlog = M('Shop_order_syslog');
                    $dlog['oid'] = $re;
                    $dlog['msg'] = '订单创建成功';
                    $dlog['type'] = 1;
                    $dlog['ctime'] = time();
                    $rlog = $mlog->add($dlog);
                    //清空购物车
                    $rbask = M('Shop_basket')->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->delete();
//					$this->success('订单创建成功，转向支付界面!',U('App/Shop/pay/',array('sid'=>$data['sid'],'orderid'=>$re)));
                    $this->redirect('App/Shop/pay/', array('sid' => $data['sid'], 'orderid' => $re));
                } else {
                    $old = $morder->delete($re);
                    $this->error('订单生成失败！请重新尝试！');
                }
            } else {
                //可能存在代金卷问题
                $this->error('订单生成失败！请重新尝试！');
            }

        } else {
            //非提交状态
            $sid = $_GET['sid'] <> '' ? $_GET['sid'] : $this->diemsg(0, '缺少SID参数');//sid可以为0
            $lasturl = $_GET['lasturl'] ? $_GET['lasturl'] : $this->diemsg(0, '缺少LastURL参数');
            $basketlasturl = base64_decode($lasturl);
            $basketurl = U('App/Shop/basket', array('sid' => $sid, 'lasturl' => $lasturl));
            $backurl = base64_encode($basketurl);
            $basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
            $re = $this->checkLogin($backurl);
            //保存当前购物车地址
            $this->assign('basketurl', $basketurl);
            //保存登陆购物车地址
            $this->assign('basketloginurl', $basketloginurl);
            //保存购物车前地址
            $this->assign('basketlasturl', $basketlasturl);
            //保存lasturlencode
            //保存购物车加密地址，用于OrderMaker正常返回
            $this->assign('lasturlencode', $lasturl);
            $this->assign('sid', $sid);
            //清空临时地址
            unset($_SESSION['WAP']['orderURL']);
            //已登陆
            $m = M('Shop_basket');
            $mgoods = M('Shop_goods');
            $msku = M('Shop_goods_sku');
            $cache = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
            //错误标记
            $errflag = 0;
            //等待删除ID
            $todelids = '';
            //totalprice
            $totalprice = 0;
            //totalnum
            $totalnum = 0;
            foreach ($cache as $k => $v) {
                //sku模型
                $goods = $mgoods->where('id=' . $v['goodsid'])->find();
                $pic = $this->getPic($goods['pic']);
                if ($v['sku']) {
                    //取商品数据				
                    if ($goods['issku'] && $goods['status']) {
                        $map['sku'] = $v['sku'];
                        $sku = $msku->where($map)->find();
                        if ($sku['status']) {
                            if ($sku['num']) {
                                //调整购买量
                                $cache[$k]['goodsid'] = $goods['id'];
                                $cache[$k]['skuid'] = $sku['id'];
                                $cache[$k]['name'] = $goods['name'];
                                $cache[$k]['skuattr'] = $sku['skuattr'];
                                $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                                $cache[$k]['price'] = $sku['price'];
                                $cache[$k]['total'] = $v['num'] * $sku['price'];
                                $cache[$k]['pic'] = $pic['imgurl'];
                                $totalnum = $totalnum + $cache[$k]['num'];
                                $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                            } else {
                                //无库存删除
                                $todelids = $todelids . $v['id'] . ',';
                                unset($cache[$k]);

                            }
                        } else {
                            //下架删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }

                } else {
                    if ($goods['status']) {
                        if ($goods['num']) {
                            //调整购买量
                            $cache[$k]['goodsid'] = $goods['id'];
                            $cache[$k]['skuid'] = 0;
                            $cache[$k]['name'] = $goods['name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                            $cache[$k]['price'] = $goods['price'];
                            $cache[$k]['total'] = $v['num'] * $goods['price'];
                            $cache[$k]['pic'] = $pic['imgurl'];
                            $totalnum = $totalnum + $cache[$k]['num'];
                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                }
            }

            if ($todelids) {
                $rdel = $m->delete($todelids);
                if (!$rdel) {
                    $this->error('购物车获取失败，请重新尝试！');
                }
            }
            //将商品列表
            sort($cache);
            $allitems = serialize($cache);
            $this->assign('allitems', $allitems);
            //VIP信息
            $vipadd = I('vipadd');
            if ($vipadd) {
                $vip = M('Vip_address')->where('id=' . $vipadd)->find();
            } else {
                $vip = M('Vip_address')->where('vipid=' . $_SESSION['WAP']['vipid'])->find();
            }
            $this->assign('vip', $vip);
            //可用代金卷
            $mdjq = M('Vip_card');
            $mapdjq['type'] = 2;
            $mapdjq['vipid'] = $_SESSION['WAP']['vipid'];
            $mapdjq['status'] = 1;//1为可以使用
            $mapdjq['usetime'] = 0;
            $mapdjq['etime'] = array('gt', time());
            $mapdjq['usemoney'] = array('lt', $totalprice);
            $djq = $mdjq->field('id,money')->where($mapdjq)->select();
            $this->assign('djq', $djq);
            //邮费逻辑
            if (self::$WAP['shopset']['isyf']) {
                $this->assign('isyf', 1);
                $yf = $totalprice >= self::$WAP['shopset']['yftop'] ? 0 : self::$WAP['shopset']['yf'];
                $this->assign('yf', $yf);
                $this->assign('yftop', self::$WAP['shopset']['yftop']);
            } else {
                $this->assign('isyf', 0);
                $this->assign('yf', 0);
            }
            //是否可以用余额支付
            $useryue = $_SESSION['WAP']['vip']['money'];
            $isyue = $_SESSION['WAP']['vip']['money'] - $totalprice >= 0 ? 0 : 1;
            $this->assign('isyue', $isyue);
            //
            $this->assign('cache', $cache);
            $this->assign('totalprice', $totalprice);
            $this->assign('totalnum', $totalnum);
            $this->display();
        }

    }

    //订单地址跳转
    public function orderAddress()
    {
        $sid = I('sid');
        $lasturlencode = I('lasturl');
        $backurl = U('App/Shop/orderMake', array('sid' => $sid, 'lasturl' => $lasturlencode));
        $_SESSION['WAP']['orderURL'] = $backurl;
        $this->redirect('App/Vip/address');
    }

    //订单列表
    public function orderList()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $type = I('type') ? I('type') : 4;
        $this->assign('type', $type);
        $bkurl = U('App/Shop/orderList', array('sid' => $sid, 'type' => $type));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['vipid'] = $vipid;
        switch ($type) {
            case '1':
                $map['status'] = 1;
                break;
            case '2':
                $map['status'] = array('in', array('2', '3'));
                break;
            case '3':
                $map['status'] = array('in', array('5', '6'));
                break;
            case '4':
                //全部
                $map['status'] = array('neq', '0');
                break;
            default:
                $map['status'] = 1;
                break;
        }
        $cache = $m->where($map)->order('ctime desc')->select();
        if ($cache) {
            foreach ($cache as $k => $v) {
                if ($v['items']) {
                    $cache[$k]['items'] = unserialize($v['items']);
                }
            }
        }
        $this->assign('cache', $cache);

        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //订单详情
    //订单列表
    public function orderDetail()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg('此订单不存在!');
        }
        $cache['items'] = unserialize($cache['items']);
        //order日志
        $mlog = M('Shop_order_log');
        $log = $mlog->where('oid=' . $cache['id'])->select();
        $this->assign('log', $log);
        if (!$cache['status'] == 1) {
            //是否可以用余额支付
            $useryue = $_SESSION['WAP']['vip']['money'];
            $isyue = $_SESSION['WAP']['vip']['money'] - $cache['payprice'] >= 0 ? 0 : 1;
            $this->assign('isyue', $isyue);
        }
        $this->assign('cache', $cache);
        //代金卷调用
        if ($cache['djqid']) {
            $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
            $this->assign('djq', $djq);
        }
        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //订单取消
    public function orderCancel()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 1) {
            $this->error('只有未付款订单可以取消！');
        }
        $re = $m->where($map)->setField('status', 0);
        if ($re) {
            //订单取消只有后端日志
            $mslog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '订单取消';
            $dlog['type'] = 0;
            $dlog['ctime'] = time();
            $rlog = $mslog->add($dlog);
            $this->success('订单取消成功！');
        } else {
            $this->error('订单取消失败,请重新尝试！');
        }
    }

    //确认收货
    public function orderOK()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 3) {
            $this->error('只有待收货订单可以确认收货！');
        }
        $cache['etime'] = time();//交易完成时间
        $cache['status'] = 5;
        $rod = $m->save($cache);
        if (FALSE !== $rod) {
            //修改会员账户金额、经验、积分、等级
            $data_vip['id'] = $cache['vipid'];
            $data_vip['score'] = array('exp', 'score+' . round($cache['payprice'] * self::$WAP['vipset']['cz_score'] / 100));
            if (self::$WAP['vipset']['cz_exp'] > 0) {
                $data_vip['exp'] = array('exp', 'exp+' . round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
                $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
                $level = $this->getLevel(self::$WAP['vip']['cur_exp'] + round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
                $data_vip['levelid'] = $level['levelid'];
                //会员分销统计字段
                //会员购买一次变成分销商
                $data_vip['isfx'] = 1;
                //会员合计支付
                $data_vip['total_buy'] = $data_vip['total_buy'] + $cache['payprice'];
            }
            $re = M('vip')->save($data_vip);
            if (FALSE === $re) {
                $this->error('更新会员信息失败！');
            }

            //分销佣金计算
            $commission = D('Commission');
            $orderids = array();
            $orderids[] = $cache['id'];

            $pid = $_SESSION['WAP']['vip']['pid'];
            $mvip = M('vip');
            $mfxlog = M('fx_syslog');
            $fxlog['oid'] = $cache['id'];
            $fxlog['fxprice'] = $fxprice = $cache['payprice'] - $cache['yf'];
            $fxlog['ctime'] = time();
            // $fx1rate=self::$WAP['shopset']['fx1rate']/100;
            // $fx2rate=self::$WAP['shopset']['fx2rate']/100;
            // $fx3rate=self::$WAP['shopset']['fx3rate']/100;
            $fxtmp = array();//缓存3级数组
            if ($pid) {
                //第一层分销
                $fx1 = $mvip->where('id=' . $pid)->find();
                if ($fx1['isfx']) {
                    $fxlog['fxyj'] = $commission->ordersCommission('fx1rate', $orderids);
                    $fx1['money'] = $fx1['money'] + $fxlog['fxyj'];
                    $fx1['total_xxbuy'] = $fx1['total_xxbuy'] + 1;//下线中购买产品总次数
                    $fx1['total_xxyj'] = $fx1['total_xxyj'] + $fxlog['fxyj'];//下线贡献佣金
                    $rfx = $mvip->save($fx1);
                    $fxlog['from'] = $_SESSION['WAP']['vipid'];
                    $fxlog['fromname'] = $_SESSION['WAP']['vip']['nickname'];
                    $fxlog['to'] = $fx1['id'];
                    $fxlog['toname'] = $fx1['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $fxlog['status'] = 1;
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }
                    //单层逻辑					
                    //$rfxlog=$mfxlog->add($fxlog);
                    //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                    array_push($fxtmp, $fxlog);
                }
                //第二层分销
                if ($fx1['pid']) {
                    $fx2 = $mvip->where('id=' . $fx1['pid'])->find();
                    if ($fx2['isfx']) {
                        $fxlog['fxyj'] = $commission->ordersCommission('fx2rate', $orderids);
                        $fx2['money'] = $fx2['money'] + $fxlog['fxyj'];
                        $fx2['total_xxbuy'] = $fx2['total_xxbuy'] + 1;//下线中购买产品人数计数
                        $fx2['total_xxyj'] = $fx2['total_xxyj'] + $fxlog['fxyj'];//下线贡献佣金
                        $rfx = $mvip->save($fx2);
                        $fxlog['from'] = $_SESSION['WAP']['vipid'];
                        $fxlog['fromname'] = $_SESSION['WAP']['vip']['nickname'];
                        $fxlog['to'] = $fx2['id'];
                        $fxlog['toname'] = $fx2['nickname'];
                        if (FALSE !== $rfx) {
                            //佣金发放成功
                            $fxlog['status'] = 1;
                        } else {
                            //佣金发放失败
                            $fxlog['status'] = 0;
                        }
                        //单层逻辑
                        //$rfxlog=$mfxlog->add($fxlog);
                        //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                        array_push($fxtmp, $fxlog);
                    }
                }
                //第三层分销
                if ($fx2['pid']) {
                    $fx3 = $mvip->where('id=' . $fx2['pid'])->find();
                    if ($fx3['isfx']) {
                        $fxlog['fxyj'] = $commission->ordersCommission('fx3rate', $orderids);
                        $fx3['money'] = $fx3['money'] + $fxlog['fxyj'];
                        $fx3['total_xxbuy'] = $fx3['total_xxbuy'] + 1;//下线中购买产品人数计数
                        $fx3['total_xxyj'] = $fx3['total_xxyj'] + $fxlog['fxyj'];//下线贡献佣金
                        $rfx = $mvip->save($fx3);
                        $fxlog['from'] = $_SESSION['WAP']['vipid'];
                        $fxlog['fromname'] = $_SESSION['WAP']['vip']['nickname'];
                        $fxlog['to'] = $fx3['id'];
                        $fxlog['toname'] = $fx3['nickname'];
                        if (FALSE !== $rfx) {
                            //佣金发放成功
                            $fxlog['status'] = 1;
                        } else {
                            //佣金发放失败
                            $fxlog['status'] = 0;
                        }
                        //单层逻辑
                        //$rfxlog=$mfxlog->add($fxlog);
                        //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                        array_push($fxtmp, $fxlog);
                    }
                }
                //多层分销
                if (count($fxtmp) >= 1) {
                    $refxlog = $mfxlog->addAll($fxtmp);
                    if (!$refxlog) {
                        file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $rfxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                    }
                }
                //花鼓分销方案
                $allhg = $mvip->field('id')->where('isfxgd=1')->select();
                if ($allhg) {
                    $tmppath = array_slice(explode('-', $_SESSION['WAP']['vip']['path']), -20);
                    $tmphg = array();
                    foreach ($allhg as $v) {
                        array_push($tmphg, $v['id']);
                    }
                    //需要计算的花鼓
                    $needhg = array_intersect($tmphg, $tmppath);
                    if (count($needhg)) {
                        $fxlog['oid'] = $cache['id'];
                        $fxlog['fxprice'] = $fxprice;
                        $fxlog['ctime'] = time();
                        $fxlog['fxyj'] = $fxprice * 0.05;
                        $fxlog['from'] = $_SESSION['WAP']['vipid'];
                        $fxlog['fromname'] = $_SESSION['WAP']['vip']['nickname'];
                        foreach ($needhg as $k => $v) {
                            $hg = $mvip->where('id=' . $v)->find();
                            if ($hg) {
                                $rhg = $mvip->where('id=' . $v)->setInc('money', $fxlog['fxyj']);
                                if ($rhg) {
                                    $fxlog['to'] = $hg['id'];
                                    $fxlog['toname'] = $hg['nickname'] . '[花股收益]';
                                    $rehgfxlog = $mfxlog->add($fxlog);
                                }
                            }
                        }
                    }
                }

            }

            $mlog = M('Shop_order_log');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货,交易完成。';
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);

            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '交易完成-会员点击';
            $dlog['type'] = 5;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            $this->success('交易已完成，感谢您的支持！');
        } else {
            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            $this->error('确认收货失败，请重新尝试！');
        }
    }

    //订单退货
    public function orderTuihuo()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderTuihuo', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg('此订单不存在!');
        }
        $cache['items'] = unserialize($cache['items']);

        $this->assign('cache', $cache);
        //代金卷调用
        if ($cache['djqid']) {
            $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
            $this->assign('djq', $djq);
        }
        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //订单取消
    public function orderTuihuoSave()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderTuihuo', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 3) {
            $this->error('只有待收货订单可以办理退货！');
        }
        $data = I('post.');
        $cache['status'] = 4;
        $cache['tuihuoprice'] = $data['tuihuoprice'];
        $cache['tuihuokd'] = $data['tuihuokd'];
        $cache['tuihuokdnum'] = $data['tuihuokdnum'];
        $cache['tuihuomsg'] = $data['tuihuomsg'];
        //退货申请时间
        $cache['tuihuosqtime'] = time();
        $re = $m->where($map)->save($cache);
        if ($re) {
            //后端日志
            $mlog = M('Shop_order_log');
            $mslog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '申请退货';
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            $dlog['type'] = 4;
            $rslog = $mslog->add($dlog);
            $this->success('申请退货成功！请等待工作人员审核！');
        } else {
            $this->error('申请退货失败,请重新尝试！');
        }
    }

    //订单支付
    public function pay()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $type = I('type');
        $bkurl = U('App/Shop/pay', array('sid' => $sid, 'orderid' => $orderid, 'type' => $type));
//		$backurl=base64_encode($orderdetail);
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $order = $m->where('id=' . $orderid)->find();
        if (!$order) {
            $this->error('此订单不存在！');
        }
        if ($order['status'] <> 1) {
            $this->error('此订单不可以支付！');
        }
        $paytype = I('type') ? I('type') : $order['paytype'];
        switch ($paytype) {
            case 'money':
                $mvip = M('Vip');
                $vip = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->find();
                $pp = $vip['money'] - $order['payprice'];
                if ($pp >= 0) {
                    $re = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->setField('money', $pp);
                    if ($re) {
                        $order['paytype'] = 'money';
                        $order['ispay'] = 1;
                        $order['paytime'] = time();
                        $order['status'] = 2;
                        $rod = $m->save($order);
                        if (FALSE !== $rod) {
                            //销量计算-只减不增
                            $rsell = $this->doSells($order);
                            //前端日志
                            $mlog = M('Shop_order_log');
                            $dlog['oid'] = $order['id'];
                            $dlog['msg'] = '余额-付款成功';
                            $dlog['ctime'] = time();
                            $rlog = $mlog->add($dlog);
                            //后端日志
                            $mlog = M('Shop_order_syslog');
                            $dlog['type'] = 2;
                            $rlog = $mlog->add($dlog);
                            $this->success('余额付款成功！', U('App/Shop/orderList', array('sid' => $sid, 'type' => '2')));

                            //首次支付成功自动变为花蜜
                            if ($vip && !$vip['isfx']) {
                                $rvip = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->setField('isfx', 1);
                                $data_msg['pids'] = $_SESSION['WAP']['vipid'];

                                $shopset = self::$WAP['shopset'] = $_SESSION['WAP']['shopset'];
                                $data_msg['title'] = "您成功升级为" . $shopset['name'] . "的" . $shopset['fxname'] . "！";
                                $data_msg['content'] = "欢迎成为" . $shopset['name'] . "的" . $shopset['fxname'] . "，开启一个新的旅程！";
                                $data_msg['ctime'] = time();
                                $rmsg = M('vip_message')->add($data_msg);
                            }

                            //代收花生米计算-只减不增
                            $rds = $this->doDs($order);

                        } else {
                            //后端日志
                            $mlog = M('Shop_order_syslog');
                            $dlog['oid'] = $order['id'];
                            $dlog['msg'] = '余额付款失败';
                            $dlog['type'] = -1;
                            $dlog['ctime'] = time();
                            $rlog = $mlog->add($dlog);
                            $this->error('余额付款失败！请联系客服！');
                        }

                    } else {
                        //后端日志
                        $mlog = M('Shop_order_syslog');
                        $dlog['oid'] = $order['id'];
                        $dlog['msg'] = '余额付款失败';
                        $dlog['type'] = -1;
                        $dlog['ctime'] = time();
                        $this->error('余额支付失败，请重新尝试！');
                    }
                } else {
                    $this->error('余额不足，请使用其它方式付款！');
                }
                break;
            case 'alipayApp':
                $this->redirect("App/Alipay/alipay", array('sid' => $sid, 'price' => $order['payprice'], 'oid' => $order['oid']));
                break;
            case 'wxpay':
                $_SESSION['wxpaysid'] = 0;
                $_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
                $this->redirect('Home/Wxpay/pay', array('oid' => $order['oid']));
                break;
            default:
                $this->error('支付方式未知！');
                break;
        }

    }

    //销量计算
    private function doSells($order)
    {
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $mlogsell = M('Shop_syslog_sells');
        //封装dlog
        $dlog['oid'] = $order['id'];
        $dlog['vipid'] = $order['vipid'];
        $dlog['vipopenid'] = $order['vipopenid'];
        $dlog['vipname'] = $order['vipname'];
        $dlog['ctime'] = time();
        $items = unserialize($order['items']);
        $tmplog = array();
        foreach ($items as $k => $v) {
            //销售总量
            $dnum = $dlog['num'] = $v['num'];
            if ($v['skuid']) {
                $rg = $mgoods->where('id=' . $v['goodsid'])->setDec('num', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('sells', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('dissells', $dnum);
                $rs = $msku->where('id=' . $v['skuid'])->setDec('num', $dnum);
                $rs = $msku->where('id=' . $v['skuid'])->setInc('sells', $dnum);
                //sku模式
                $dlog['goodsid'] = $v['goodsid'];
                $dlog['goodsname'] = $v['name'];
                $dlog['skuid'] = $v['skuid'];
                $dlog['skuattr'] = $v['skuattr'];
                $dlog['price'] = $v['price'];
                $dlog['num'] = $v['num'];
                $dlog['total'] = $v['total'];
            } else {
                $rg = $mgoods->where('id=' . $v['goodsid'])->setDec('num', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('sells', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('dissells', $dnum);
                //纯goods模式
                $dlog['goodsid'] = $v['goodsid'];
                $dlog['goodsname'] = $v['name'];
                $dlog['skuid'] = 0;
                $dlog['skuattr'] = 0;
                $dlog['price'] = $v['price'];
                $dlog['num'] = $v['num'];
                $dlog['total'] = $v['total'];
            }
            array_push($tmplog, $dlog);
        }
        if (count($tmplog)) {
            $rlog = $mlogsell->addAll($tmplog);
        }
        return true;
    }

    //代收花生米计算
    public function doDs($order)
    {
        //分销佣金计算
        $commission = D('Commission');
        $orderids = array();
        $orderids[] = $order['id'];

        $vipid = $order['vipid'];
        $mvip = M('vip');
        $vip = $mvip->where('id=' . $vipid)->find();
        if (!$vip && !$vip['pid']) {
            return FALSE;
        }
        //初始化 
        $pid = $vip['pid'];
        $mfxlog = M('fx_dslog');
        $shopset = M('Shop_set')->find();//追入商城设置
        $fxlog['oid'] = $order['id'];
        $fxlog['fxprice'] = $fxprice = $order['payprice'] - $order['yf'];
        $fxlog['ctime'] = time();
        // $fx1rate=$shopset['fx1rate']/100;
        // $fx2rate=$shopset['fx2rate']/100;
        // $fx3rate=$shopset['fx3rate']/100;
        $fxtmp = array();//缓存3级数组
        if ($pid) {
            //第一层分销
            $fx1 = $mvip->where('id=' . $pid)->find();
            if ($fx1['isfx']) {
                $fxlog['fxyj'] = $commission->ordersCommission('fx1rate', $orderids);
                $fxlog['from'] = $vip['id'];
                $fxlog['fromname'] = $vip['nickname'];
                $fxlog['to'] = $fx1['id'];
                $fxlog['toname'] = $fx1['nickname'];
                $fxlog['status'] = 1;
                //单层逻辑					
                //$rfxlog=$mfxlog->add($fxlog);
                //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                array_push($fxtmp, $fxlog);
            }
            //第二层分销
            if ($fx1['pid']) {
                $fx2 = $mvip->where('id=' . $fx1['pid'])->find();
                if ($fx2['isfx']) {
                    $fxlog['fxyj'] = $commission->ordersCommission('fx2rate', $orderids);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $fx2['id'];
                    $fxlog['toname'] = $fx2['nickname'];
                    $fxlog['status'] = 1;
                    //单层逻辑
                    //$rfxlog=$mfxlog->add($fxlog);
                    //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                    array_push($fxtmp, $fxlog);
                }
            }
            //第三层分销
            if ($fx2['pid']) {
                $fx3 = $mvip->where('id=' . $fx2['pid'])->find();
                if ($fx3['isfx']) {
                    $fxlog['fxyj'] = $commission->ordersCommission('fx3rate', $orderids);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $fx3['id'];
                    $fxlog['toname'] = $fx3['nickname'];
                    $fxlog['status'] = 1;
                    //单层逻辑
                    //$rfxlog=$mfxlog->add($fxlog);
                    //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                    array_push($fxtmp, $fxlog);
                }
            }
            //多层分销
            if (count($fxtmp) >= 1) {
                $refxlog = $mfxlog->addAll($fxtmp);
                if (!$refxlog) {
                    file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $rfxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
            //花鼓分销方案
            $allhg = $mvip->field('id')->where('isfxgd=1')->select();
            if ($allhg) {
                $tmppath = array_slice(explode('-', $vip['path']), -20);
                $tmphg = array();
                foreach ($allhg as $v) {
                    array_push($tmphg, $v['id']);
                }
                //需要计算的花鼓
                $needhg = array_intersect($tmphg, $tmppath);
                if (count($needhg)) {
                    $fxlog['oid'] = $order['id'];
                    $fxlog['fxprice'] = $fxprice;
                    $fxlog['ctime'] = time();
                    $fxlog['fxyj'] = $fxprice * 0.05;
                    $fxlog['from'] = $vip['vipid'];
                    $fxlog['fromname'] = $vip['nickname'];
                    foreach ($needhg as $k => $v) {
                        $hg = $mvip->where('id=' . $v)->find();
                        if ($hg) {
                            $fxlog['to'] = $hg['id'];
                            $fxlog['toname'] = $hg['nickname'] . '[花股收益]';
                            $fxlog['ishg'] = 1;
                            $rehgfxlog = $mfxlog->add($fxlog);
                        }
                    }
                }
            }

        }
        return true;
        //逻辑完成
    }

}
