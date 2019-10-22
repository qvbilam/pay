<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-29
 * Time: 16:03
 */

namespace App\HttpController\Pay;

use App\HttpController\Api\Base as BaseController;
use \Yaconf;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\Config;
use EasySwoole\Pay\WeChat\RequestBean\Biz;
use EasySwoole\Pay\WeChat\RequestBean\OfficialAccount;
use EasySwoole\Pay\WeChat\ResponseBean\NativeResponse;
use EasySwoole\Pay\WeChat\Utility;
use EasySwoole\Pay\WeChat\WeChatPay\MiniProgram;
use EasySwoole\Pay\WeChat\WeChatPay\Scan;
use EasySwoole\Spl\SplArray;
use Swoole\Buffer;

require_once dirname(__DIR__) . '/../Lib/phpqrcode/phpqrcode.php';

class Base extends BaseController
{
    // 微信配置
    protected $wechatConfig;
    // 支付宝配置
    protected $zfbConfig;

    public function __construct()
    {
        $wechatConfig = new Config();
        $wechatConfig->setAppId('wxf67e5d6039607945');
        $wechatConfig->setMchId('1497029642');
        $wechatConfig->setKey('wvlzXVG1xbYjclNDgvvTB7AkcEH9gizx');
        $wechatConfig->setNotifyUrl("https://127.0.0.1/notify");
        $wechatConfig->setApiClientCert('/Users/qvbilam/data/apiclient_cert.pem');
        $wechatConfig->setApiClientKey('/Users/qvbilam/data/apiclient_key.pem');
        $this->wechatConfig = $wechatConfig;
        parent::__construct();
    }

    public function index()
    {
        // = =写啥好呢
    }

    public function qrcode($data = 'http://www.helloweba.com')
    {
        $serverPath = EASYSWOOLE_ROOT;
        $filePath = '/public/Image/';
        $file = rand(00000, 99999) . '.png';
        $path = $serverPath . $filePath . $file;
        // $path = EASYSWOOLE_ROOT . '/public/Image/' . rand(00000, 99999) . '.png';
        \QRcode::png($data, $path);
        return $filePath . $file;
    }

    /**
     * 参数数组转换为url参数
     * @param $urlObj
     * @return string
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }

        $buff = trim($buff, "&");
        return $buff;
    }


}