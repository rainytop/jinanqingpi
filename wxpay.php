<?php
define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);

if (!IS_CLI) {
    // 当前文件名
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            //CGI/FASTCGI模式下
            $_temp = explode('.php', $_SERVER['PHP_SELF']);
            define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }
    }
    if (!defined('__ROOT__')) {
        $_root = rtrim(dirname(_PHP_FILE_), '/');
        define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
    }
}

$wOpt = $_GET;
$wOpt['package'] = 'prepay_id=' . $wOpt['package'];
//var_dump("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
?>
<script type="text/javascript">
    document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
        WeixinJSBridge.invoke('getBrandWCPayRequest', {
            'appId': '<?php echo $wOpt['appId'];?>',
            'timeStamp': '<?php echo $wOpt['timeStamp'];?>',
            'nonceStr': '<?php echo $wOpt['nonceStr'];?>',
            'package': '<?php echo $wOpt['package'];?>',
            'signType': '<?php echo $wOpt['signType'];?>',
            'paySign': '<?php echo $wOpt['paySign'];?>'
        }, function (res) {
            if (res.err_msg == 'get_brand_wcpay_request:ok') {
                //alert('您已成功付款！ ');
                window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'];?>/lqd/index.php?s=/App/Shop/orderList/sid/0/';
            } else {
                //alert('启动微信支付失败, 请检查你的支付参数. 详细错误为: ' + res.err_msg);
                window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'];?>/lqd/index.php?s=/App/Shop/orderList/sid/0/';
            }
        });
    }, false);
</script>