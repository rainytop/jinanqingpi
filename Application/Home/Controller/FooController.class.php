<?php
/**
 * Created by PhpStorm.
 * User: xiedalie
 * Date: 2016/11/25
 * Time: 15:58
 */

namespace Home\Controller;


use Home\Model\VipFriendsBiz;
use Home\Model\VipSignonBiz;
use Home\Model\WxBiz;
use Think\Controller;
use Vendor\Hiland\Biz\Tencent\WechatHelper;
use Vendor\Hiland\Utils\Data\Calendar;
use Vendor\Hiland\Utils\Data\CalendarHelper;
use Vendor\Hiland\Utils\Data\ChineseHelper;
use Vendor\Hiland\Utils\Data\DateHelper;
use Vendor\Hiland\Utils\Data\StringHelper;
use Vendor\Hiland\Utils\DataModel\ModelMate;
use Vendor\Hiland\Utils\IO\DirHelper;
use Vendor\Hiland\Utils\IO\ImageHelper;
use Vendor\Hiland\Utils\Web\NetHelper;

class FooController extends Controller
{
    public function index()
    {
        dump(1111111111);
    }

    public function wxbiz()
    {
        WxBiz::createQrcode(3, "oinMwxGi-Ok20PEf5lUn6TtPaQXg");
    }

    public function wximg()
    {
        $headimgurl = "http://wx.qlogo.cn/mmopen/Ib5852jAybibhPd6DV1FzXCgLicqMreYh8LTWtFje4ePscFDPl8KMc2jAo65z5IjNluaQBBwkIVS2oxX67eqFBaoRnjoesVAWL/0";
//        $data = NetHelper::get($headimgurl);
//        dump($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $headimgurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $headimg = curl_exec($ch);
        curl_close($ch);
        dump($headimg);
    }

    public function calendarop($y = 2017, $m = 3, $d = 15)
    {
        $cal = new Calendar();
        $data = $cal->Calc($y, $m, $d);
        dump($data);

        $bb = CalendarHelper::convertSolarToLunar(2017, 3, 15);
        dump($bb);

        $lunar = CalendarHelper::convertSolarToLunar(date('Y'), date('m'), date('d'));
        dump($lunar);
        dump($lunar[1]);
        //dump(substr($lunar[1],0,1)) ;
        dump(StringHelper::subString($lunar[1], 0, 1));
        dump(StringHelper::subString($lunar[1], 1, 1));
    }

    public function vipsignop($vipid = 0)
    {
        VipSignonBiz::signOn($vipid);
    }

    public function stampop()
    {
        $str_5 = date('Y-m-d') . ' 5:0:0';
        $timestamp_5 = DateHelper::getTimestamp($str_5);

        $str_8 = date('Y-m-d') . ' 8:0:0';
        $timestamp_8 = DateHelper::getTimestamp($str_8);
        $timestamp_10 = DateHelper::getTimestamp(date('Y-m-d') . ' 10:0:0');

        $currentTime = time();

        dump($str_5);
        dump($timestamp_5);
        dump($currentTime);
        dump($str_8);
        dump($timestamp_8);
    }

    public function continuousdaycountop($vipid = 1)
    {
        $days = VipSignonBiz::getContinuousDayCount($vipid);
        dump($days);
    }

    public function getMyFriendsOp($vipid = 1, $includeSelf = 0, $makeFriedsType = 0, $friendStatus = 10)
    {
        $result = VipFriendsBiz::getMyFriends($vipid, $includeSelf, $makeFriedsType, $friendStatus);
        dump($result);
    }

    public function getFriendsSignonCountOp($vipid = 1){
        $result= VipSignonBiz::getFriendsSignonCount($vipid);
        dump($result);
    }

    public function vipfixedop($openid = 'oZeE8w2pliOkFLhoeVYzMu3PP09A')
    {
        $vipFixed = new ModelMate("vip_fixed");
        $condition = array(
            "openid" => $openid,
        );
        $entity = $vipFixed->find($condition);
        dump($entity);
    }

    public function filecountop()
    {
        $count = DirHelper::getFileCount('./Upload/shenqi/qiandao/');
        dump($count);
    }

    public function uploadimg()
    {
        $wechat = WxBiz::getWechat();

        $file = PHYSICAL_ROOT_PATH . "\\QRcode\\promotion\\oinMwxGi-Ok20PEf5lUn6TtPaQXg.jpg";
        dump($file);
        $data = array('media' => '@' . $file);
        $result = $wechat->uploadMedia($data, 'image');
        dump($result);

        $rt = WechatHelper::uploadMedia($file);
        dump($rt);
    }

    public function wxav()
    {
        $hostName = "http://wx.qlogo.cn";

        $ip = C('WX_AVATARSERVER_IP');
        $hostName = "http://$ip";
        $recommenduseravatar = "$hostName/mmopen/Ib5852jAybibhPd6DV1FzXCgLicqMreYh8LTWtFje4ePscFDPl8KMc2jAo65z5IjNluaQBBwkIVS2oxX67eqFBaoRnjoesVAWL/0";

        //$headimg = ImageHelper::loadImage($recommenduseravatar, 'non');

        $headimg = NetHelper::request($recommenduseravatar, null, 30);
        //$headimg= NetHelper::get($recommenduseravatar,true);
        //$headimg= $this-> ss($recommenduseravatar);

        $headimg = imagecreatefromstring($headimg);
        ImageHelper::display($headimg);
        //dump($headimg);
    }

    public function eggOp($max = 5000)
    {
//        for($i=0;$i<$max;$i++){
//           if( $i%2==1 &&
//               $i%3==0 &&
//               $i%4==1 &&
//               $i%5==4 &&
//               $i%6==3 &&
//               $i%7==0 &&
//               $i%8==1 &&
//               $i%9==0 )
//
//               dump($i);
//        }

        dump(441 % 1);
        dump(441 % 2);
        dump(441 % 3);
        dump(441 % 4);
        dump(441 % 5);
        dump(441 % 6);
        dump(441 % 7);
        dump(441 % 8);
        dump(441 % 9);
    }

    public function jsop()
    {
        $this->display();
    }

    public function dirop()
    {
        $path = "E:\\aa\\bb\\cc\\dd";
//        if(is_dir($path)==false){
//            mkdir($path);
//        }

        DirHelper::surePathExist($path);
    }

    public function aa()
    {
        dump('http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/index.php/Home/Wxpay/nd/');
    }

    public function weeknameop()
    {
        $time = mktime(9, 1, 1, 3, 15, 2017);

        dump(DateHelper::getWeekName('c', $time));
    }

    private function ss($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function chineseOp(){
        $input= "山东省枣庄市高新区";

        $result= ChineseHelper::getFirstChar($input);
        dump($result);

        $result= ChineseHelper::getPinyin($input);
        dump($result);
    }

    public function getnoticeopenids(){
        $result= C('NEWUSER_REGISTER_NOTICE2OPENIDS');
        dump($result);
        foreach ($result as $item){
            dump($item);
        }
    }

    public function gettime($time=1494297461){

    }
}