<?php
namespace App\Controller;

use Think\Controller;

class AppController extends Controller
{

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function share()
    {
        $set = M('set')->find();
        //追入分享特效
        $options['appid'] = $set['wxappid'];
        $options['appsecret'] = $set['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);
        //生成JSSDK实例
        $opt['appid'] = $set['wxappid'];
        $opt['token'] = $wx->checkAuth();
        $opt['url'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $jssdk = new \Util\Wx\Jssdk($opt);
        $jsapi = $jssdk->getSignPackage();
        if (!$jsapi) {
            die('未正常获取数据！');
        }
        $this->assign('jsapi', $jsapi);
        dump($jsapi);
        $this->display();
    }

    public function share2()
    {
        $set = M('set')->find();
        //追入分享特效
        $options['appid'] = $set['wxappid'];
        $options['appsecret'] = $set['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);
        $actoken = $wx->checkAuth();
        $jsapi = $this->getSignPackage($options['appid'], $actoken);
        $this->assign('jsapi', $jsapi);
        $this->display();
    }

    public function getSignPackage($appid, $actoken)
    {
        $jsapiTicket = $this->getJsApiTicket($actoken);
        $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string,
        );
        return $signPackage;
    }

    private function getJsApiTicket($accessToken)
    {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = json_decode(file_get_contents("./Data/jsapi_ticket.json"));
        if ($data->expire_time < time()) {
            // $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->http_get($url));
            dump($res);
            $ticket = $res->ticket;
            if ($ticket) {
                $data->expire_time = time() + 7000;
                $data->jsapi_ticket = $ticket;
                //file_put_contents("./Data/jsapi_ticket.json",json_encode($data));
                $fp = fopen("./Data/jsapi_ticket.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            } else {
                die('no ticket!');
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }

        return $ticket;
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}