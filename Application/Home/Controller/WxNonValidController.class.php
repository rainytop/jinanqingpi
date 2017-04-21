<?php
/**
 * Created by PhpStorm.
 * User: xiedalie
 * Date: 2016/11/25
 * Time: 9:37
 */

namespace Home\Controller;


use App\QRcode;
use Home\Model\VipSignonBiz;
use Home\Model\WxBiz;
use Think\Controller;
use Vendor\Hiland\Biz\Loger\CommonLoger;
use Vendor\Hiland\Biz\Tencent\WechatHelper;
use Vendor\Hiland\Utils\Data\CalendarHelper;
use Vendor\Hiland\Utils\Data\DateHelper;
use Vendor\Hiland\Utils\Data\StringHelper;
use Vendor\Hiland\Utils\DataModel\ModelMate;
use Vendor\Hiland\Utils\Web\NetHelper;

class WxNonValidController extends Controller
{
    public static $_wx; //缓存微信对象

    public function __construct($options)
    {
        self::$_wx = WxBiz::getWechat();
    }

    public function reply4Test($openid)
    {
        $accessToken = WechatHelper::getAccessToken();
        CommonLoger::log($openid, $accessToken);
        WechatHelper::responseCustomerServiceText($openid, "nihao");
    }

    /**
     * 对关键词“员工二维码”进行响应
     */
    public function reply4YuanGongErWeiMa($openid)
    {
        // 获取用户信息
        $map['openid'] = $openid;//self::$_revdata['FromUserName'];
        $vipModel = M('Vip');
        $vip = $vipModel->where($map)->find();

        // 用户校正
        if (!$vip) {
            $msg = "用户信息缺失，请重新关注公众号";
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        }

        $cacheKey = 'employee-' . $vip['openid'];

        // 获取员工信息
        $employee = M('Employee')->where(array('vipid' => $vip['id']))->find();

        // 员工校正
        if (!$employee) {
            $msg = "抱歉，您不是员工，请先联系系统管理员！";
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        }

        // 过滤连续请求-打开
        if (F($cacheKey) != null) {
            $msg = "员工二维码正在生成，请稍等！";
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        } else {
            F($cacheKey, $vip['openid']);
        }

        // 生产二维码基本信息，存入本地文档，获取背景
        $background = WxBiz::createQrcodeBg4Employee();
        //$qrcode = $this->createQrcode($vip['id'],$vip['openid']);
        $qrcode = WxBiz::createQrcode4Employee($employee['id'], $vip['openid']);
        if (!$qrcode) {
            $msg = "员工二维码 生成失败";
            WechatHelper::responseCustomerServiceText($openid, $msg);
            F($cacheKey, null);
            exit();
        }
        // 生产二维码基本信息，存入本地文档，获取背景 结束

        // 获取头像信息
        $mark = false; // 是否需要写入将图片写入文件
        $imageUrl = $vip['headimgurl'];
        $wxAvatarIp = C('WX_AVATARSERVER_IP');
        if ($wxAvatarIp) {
            $imageUrl = str_replace('wx.qlogo.cn', $wxAvatarIp, $imageUrl);
        }
        $headimg = NetHelper::request($imageUrl);
        if (!$headimg) {// 没有头像先从头像库查找，再没有就选择默认头像
            if (file_exists('./QRcode/headimg/' . $vip['openid'] . '.jpg')) { // 获取不到远程头像，但存在本地头像，需要更新
                $headimg = file_get_contents('./QRcode/headimg/' . $vip['openid'] . '.jpg');
            } else {
                $headimg = file_get_contents('./QRcode/headimg/' . 'default' . '.jpg');
            }
            $mark = true;
        }
        $headimg = imagecreatefromstring($headimg);
        // 获取头像信息 结束

        // 生成二维码推广图片=======================

        // Combine QRcode and background and HeadImg
        $b_width = imagesx($background);
        $b_height = imagesy($background);
        $q_width = imagesx($qrcode);
        $q_height = imagesy($qrcode);
        $h_width = imagesx($headimg);
        $h_height = imagesy($headimg);
        imagecopyresampled($background, $qrcode, $b_width * 0.24, $b_height * 0.5, 0, 0, $q_width * 1.5, $q_height * 1.5, $q_width, $q_height);
        imagecopyresampled($background, $headimg, $b_width * 0.10, 12, 0, 0, 120, 120, $h_width, $h_height);

        // Set Font Type And Color
        $fonttype = './Public/Common/fonts/wqy-microhei.ttc';
        $fontcolor = imagecolorallocate($background, 0x00, 0x00, 0x00);

        // Combine All And Text, Then store in local
        imagettftext($background, 18, 0, 280, 100, $fontcolor, $fonttype, $vip['nickname']);
        imagejpeg($background, './QRcode/promotion/' . "employee" . $vip['openid'] . '.jpg');

        // 生成二维码推广图片 结束==================

        // 上传下载相应
        $file = getcwd() . "/QRcode/promotion/" . "employee" . $vip['openid'] . '.jpg';
        if (file_exists($file)) {
            $mediaId = WechatHelper::uploadMedia($file);
            WechatHelper::responseCustomerServiceImage($openid, $mediaId);
        } else {
            $msg = "员工二维码生成失败";
            WechatHelper::responseCustomerServiceText($openid, $msg);
        }
        // 上传下载相应 结束

        // 过滤连续请求-关闭
        F($cacheKey, null);

        // 后续数据操作（写入头像到本地，更新个人信息）
        if ($mark) {
            $tempvip = self::$_wx->getUserInfo($openid);
            $vip['nickname'] = $tempvip['nickname'];
            $vip['headimgurl'] = $tempvip['headimgurl'];
            $vipModel->save($vip);
        } else {
            // 将头像文件写入
            imagejpeg($headimg, './QRcode/headimg/' . $vip['openid'] . '.jpg');
        }
    }

    public function reply4TuiGuangErWeiMa($openid)
    {
        // 获取用户信息
        $map['openid'] = $openid;

        $vipModel = M('Vip');
        $vip = $vipModel->where($map)->find();

        // 用户校正
        if (!$vip) {
            $msg = "用户信息缺失，请重新关注公众号";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        } else if ($vip['isfx'] == 0) {
            $shopSet = M('Shop_set')->find();
            $msg = "您还未成为" . $shopSet['fxname'] . "，请先购买成为" . $shopSet['fxname'] . "！";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        }

        $cacheKey = 'reply4TuiGuangErWeiMa-' . $vip['openid'];

        // 过滤连续请求-打开
        if (F($cacheKey) != null) {
            $msg = "推广二维码正在生成，请稍等！";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        } else {
            F($cacheKey, $vip['openid']);
        }

        // 生产二维码基本信息，存入本地文档，获取背景
        $background = WxBiz::createQrcodeBg4Common(); //$this->createQrcodeBg();
        //WechatHelper::responseCustomerServiceText($openid,$background);
        $qrcode = WxBiz::createQrcode4Common($vip['id'], $vip['openid']);
        if (!$qrcode) {
            $msg = "专属二维码 生成失败";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
            F($cacheKey, null);
            exit();
        }
        // 生产二维码基本信息，存入本地文档，获取背景 结束

        // 获取头像信息
        $mark = false; // 是否需要写入将图片写入文件

        //WechatHelper::responseCustomerServiceText($openid,$vip['headimgurl']);
        $imageUrl = $vip['headimgurl'];
        $wxAvatarIp = C('WX_AVATARSERVER_IP');
        if ($wxAvatarIp) {
            $imageUrl = str_replace('wx.qlogo.cn', $wxAvatarIp, $imageUrl);
        }
        $headimg = NetHelper::request($imageUrl);
        //WechatHelper::responseCustomerServiceText($openid,$headimg);
        if (!$headimg) {// 没有头像先从头像库查找，再没有就选择默认头像
            if (file_exists('./QRcode/headimg/' . $vip['openid'] . '.jpg')) { // 获取不到远程头像，但存在本地头像，需要更新
                $headimg = file_get_contents('./QRcode/headimg/' . $vip['openid'] . '.jpg');
            } else {
                $headimg = file_get_contents('./QRcode/headimg/' . 'default' . '.jpg');
            }
            $mark = true;
        }

        $headimg = imagecreatefromstring($headimg);
        // 获取头像信息 结束

        // 生成二维码推广图片=======================

        // Combine QRcode and background and HeadImg
        $b_width = imagesx($background);
        $b_height = imagesy($background);
        $q_width = imagesx($qrcode);
        $q_height = imagesy($qrcode);
        $h_width = imagesx($headimg);
        $h_height = imagesy($headimg);
        imagecopyresampled($background, $qrcode, $b_width * 0.24, $b_height * 0.5, 0, 0, 297, 297, $q_width, $q_height);
        imagecopyresampled($background, $headimg, $b_width * 0.10, 12, 0, 0, 120, 120, $h_width, $h_height);

        // Set Font Type And Color
        $fonttype = './Public/Common/fonts/wqy-microhei.ttc';
        $fontcolor = imagecolorallocate($background, 0x00, 0x00, 0x00);

        // Combine All And Text, Then store in local
        imagettftext($background, 18, 0, 280, 100, $fontcolor, $fonttype, $vip['nickname']);
        imagejpeg($background, './QRcode/promotion/' . $vip['openid'] . '.jpg');

        // 生成二维码推广图片 结束==================

        //WechatHelper::responseCustomerServiceText($openid,'dddddddddddddddd');
        // 上传下载相应
        $file = getcwd() . "/QRcode/promotion/" . $vip['openid'] . '.jpg';
        if (file_exists($file)) {
            $mediaId = WechatHelper::uploadMedia($file);
            WechatHelper::responseCustomerServiceImage($openid, $mediaId);
        } else {
            $msg = "专属二维码生成失败";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
        }
        // 上传下载相应 结束

        // 过滤连续请求-关闭
        F($cacheKey, null);

        // 后续数据操作（写入头像到本地，更新个人信息）
        if ($mark) {
            $tempvip = self::$_wx->getUserInfo($openid); //$this->apiClient(self::$_revdata['FromUserName']);
            $vip['nickname'] = $tempvip['nickname'];
            $vip['headimgurl'] = $tempvip['headimgurl'];
            $vipModel->save($vip);
        } else {
            // 将头像文件写入
            imagejpeg($headimg, './QRcode/headimg/' . $vip['openid'] . '.jpg');
        }
    }

    public function reply4signon($openid)
    {
        // 获取用户信息
        $map['openid'] = $openid;

        $vipModel = M('Vip');
        $vip = $vipModel->where($map)->find();

        // 用户校正
        if (!$vip) {
            $msg = "用户信息缺失，请重新关注公众号";
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        }

//        $timestamp_5 = DateHelper::getTimestamp(date('Y-m-d') . ' 5:0:0');
//        $currentTime = time();
//
//        if ($currentTime < $timestamp_5) {
//            $msg = "目前签到时间尚未开始,请每天早上5点后前来签到.";
//            WechatHelper::responseCustomerServiceText($openid, $msg);
//            exit();
//        }
        $vipid = $vip['id'];
        $returnMessage = '';
        $signOrder = 0;
        $score = 0;
        $signResult = VipSignonBiz::signOn($vipid, $signOrder, $score, $returnMessage);

        WechatHelper::responseCustomerServiceText($openid, $returnMessage);
        if (!$signResult) {
            exit();
        }


        $cacheKey = 'reply4signon--' . $vip['openid'];

        // 过滤连续请求-打开
        if (F($cacheKey) != null) {
            $msg = "签到图片正在生成，请稍等！";
            WechatHelper::responseCustomerServiceText($openid, $msg);
            exit();
        } else {
            F($cacheKey, $vip['openid']);
        }


        // 生产二维码基本信息，存入本地文档，获取背景
        $background = WxBiz::createSignOnBg();

        //WechatHelper::responseCustomerServiceText($openid,$background);
        $qrcode = WxBiz::createQrcode4Common($vip['id'], $vip['openid']);
        if (!$qrcode) {
            $msg = "专属二维码 生成失败";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
            F($cacheKey, null);
            exit();
        }

        // 生产二维码基本信息，存入本地文档，获取背景 结束

        // 获取头像信息
        $mark = false; // 是否需要写入将图片写入文件

//        //WechatHelper::responseCustomerServiceText($openid,$vip['headimgurl']);
//        $imageUrl = $vip['headimgurl'];
//        $wxAvatarIp = C('WX_AVATARSERVER_IP');
//        if ($wxAvatarIp) {
//            $imageUrl = str_replace('wx.qlogo.cn', $wxAvatarIp, $imageUrl);
//        }
//        $headimg = NetHelper::request($imageUrl);
//        //WechatHelper::responseCustomerServiceText($openid,$headimg);
//        if (!$headimg) {// 没有头像先从头像库查找，再没有就选择默认头像
//            if (file_exists('./QRcode/headimg/' . $vip['openid'] . '.jpg')) { // 获取不到远程头像，但存在本地头像，需要更新
//                $headimg = file_get_contents('./QRcode/headimg/' . $vip['openid'] . '.jpg');
//            } else {
//                $headimg = file_get_contents('./QRcode/headimg/' . 'default' . '.jpg');
//            }
//            $mark = true;
//        }
//
//        $headimg = imagecreatefromstring($headimg);

        //$headimg = imagecreatefromstring($headimg);
        // 获取头像信息 结束

        // 生成二维码推广图片=======================

        // Combine QRcode and background and HeadImg
        $b_width = imagesx($background);
        $b_height = imagesy($background);

        $q_width = imagesx($qrcode);
        $q_height = imagesy($qrcode);

        imagecopyresampled($background, $qrcode, 128, 950, 0, 0, 150, 150, $q_width, $q_height);

//        $h_width = imagesx($headimg);
//        $h_height = imagesy($headimg);
//        imagecopyresampled($background, $headimg, $b_width * 0.10, 12, 0, 0, 120, 120, $h_width, $h_height);

        // Set Font Type And Color
        $fonttype = './Public/Common/fonts/wqy-microhei.ttc';
        $fontcolor = imagecolorallocate($background, 0x00, 0x00, 0x00);

        //用户的昵称和签名信息
        $displayName = $vip['nickname'];
        $signName = '';
        $contactInfo = '';
        $vipFixed = new ModelMate("vip_fixed");
        $condition = array(
            "openid" => $openid,
        );
        $entity = $vipFixed->find($condition);
        if ($entity) {
            $displayName = $entity['namefixed'];
            $signName = $entity['signname'];
            $contactInfo = $entity['contactinfo'];
        }
        imagettftext($background, 18, 0, 280, 1000, $fontcolor, $fonttype, $displayName);
        if ($contactInfo) {
            imagettftext($background, 18, 0, 280, 1040, $fontcolor, $fonttype, $contactInfo);

            if ($signName) {
                imagettftext($background, 18, 0, 280, 1080, $fontcolor, $fonttype, $signName);
            }
        } else {
            if ($signName) {
                imagettftext($background, 18, 0, 280, 1040, $fontcolor, $fonttype, $signName);
            }
        }


        //日历信息
        imagettftext($background, 26, 0, 180, 750, $fontcolor, $fonttype, date('m'));
        imagettftext($background, 26, 0, 180, 785, $fontcolor, $fonttype, '月');

        imagettftext($background, 26, 0, 180, 825, $fontcolor, $fonttype, '星');
        imagettftext($background, 26, 0, 180, 860, $fontcolor, $fonttype, '期');
        imagettftext($background, 26, 0, 180, 895, $fontcolor, $fonttype, DateHelper::getWeekName('c'));

        $lunar = CalendarHelper::convertSolarToLunar(date('Y'), date('m'), date('d'));
        imagettftext($background, 26, 0, 430, 750, $fontcolor, $fonttype, '农');
        imagettftext($background, 26, 0, 430, 785, $fontcolor, $fonttype, StringHelper::subString($lunar[1], 0, 1));
        imagettftext($background, 26, 0, 430, 820, $fontcolor, $fonttype, '月');
        imagettftext($background, 26, 0, 430, 860, $fontcolor, $fonttype, StringHelper::subString($lunar[2], 0, 1));
        imagettftext($background, 26, 0, 430, 895, $fontcolor, $fonttype, StringHelper::subString($lunar[2], 1, 1));

        imagettftext($background, 136, 0, 230, 850, $fontcolor, $fonttype, date('d'));
        imagettftext($background, 36, 0, 260, 895, $fontcolor, $fonttype, date('H:i'));

        $fontcolor = imagecolorallocate($background, 0x55, 0x55, 0x55);
        imagettftext($background, 14, 0, 190, 930, $fontcolor, $fonttype, "在您朋友圈中,你是今日第($signOrder)位签到成功的用户");

        imagejpeg($background, './Upload/shenqi/qiandao/datas/' . $vip['openid'] . '.jpg');
        // 生成二维码推广图片 结束==================

        // 上传下载相应
        $file = getcwd() . "/Upload/shenqi/qiandao/datas/" . $vip['openid'] . '.jpg';
        if (file_exists($file)) {
            $mediaId = WechatHelper::uploadMedia($file);
            WechatHelper::responseCustomerServiceImage($openid, $mediaId);
        } else {
            $msg = "专属二维码生成失败";
            //self::$_wx->text($msg)->reply();
            WechatHelper::responseCustomerServiceText($openid, $msg);
        }
        // 上传下载相应 结束


        // 过滤连续请求-关闭
        F($cacheKey, null);

        // 后续数据操作（写入头像到本地，更新个人信息）
        if ($mark) {
            $tempvip = self::$_wx->getUserInfo($openid); //$this->apiClient(self::$_revdata['FromUserName']);
            $vip['nickname'] = $tempvip['nickname'];
            $vip['headimgurl'] = $tempvip['headimgurl'];
            $vipModel->save($vip);
        } else {
            // 将头像文件写入
            //imagejpeg($headimg, './QRcode/headimg/' . $vip['openid'] . '.jpg');
        }
    }
}