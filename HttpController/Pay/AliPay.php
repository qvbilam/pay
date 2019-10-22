<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-09-04
 * Time: 17:56
 */

namespace App\HttpController\Pay;

use \EasySwoole\Pay\AliPay\Config as AlipayConfig;
use \EasySwoole\Pay\AliPay\GateWay;
use \EasySwoole\Pay\Pay;
use \EasySwoole\Pay\AliPay\RequestBean\Scan;
use App\HttpController\Api\Base as BaseController;
use Ixudra\Curl\CurlService;

class AliPay extends BaseController
{
    public $method = [
        'web' => '电脑支付',
        'wap' => '手机网站支付',
        'app' => 'APP 支付',
        'pos' => '刷卡支付',
        'scan' => '扫码支付',
        'transfer' => '账户转账',
        'mini' => '小程序支付'
    ];

    /*
     * 创建订单 直接调起的app支付
     * */
    public function alipay($req_data)
    {
        $payinfo = "BankId=" . $req_data['busi']['BankId'] . "&PlatId=" . $req_data['pub']['OriginId'] . "&AccountType=" . $req_data['busi']['AccountType'] .
            "&AccountCode=" . $req_data['busi']['AccountCode'] . "&AccountName=" . $req_data['busi']['AccountName'] . "&Upg_OrderId=" . $req_data['busi']['upgOrderId'] .
            "&PayItemType=" . $req_data['busi']['PayItemType'] . "&PayAmount=" . $req_data['busi']['PayAmount'] . "&ProviderId=" . $req_data['busi']['ProviderId'] .
            "&ProviderName=" . $req_data['busi']['ProviderName'] . "&TransactionId=" . $req_data['pub']['TransactionId'] . "&RegionId=" . $req_data['pub']['RegionId'];
        $url = 'https://pay-web.zj.chinamobile.com/business/com.asiainfo.aipay.web.DoPayAction?action=doAliPay';

        $curl = new CurlService();
        $r = [];
        $r['content'] = $curl->to($url)
            ->withData($payinfo)
            ->post();
        //save action
        if (isset($r['content'])) {
            $r['action_no'] = $req_data['pub']['TransactionId'];
            $r['action_type'] = 'zhejiang';
            $r['post_data'] = $payinfo;
            $r['action_url'] = $url;
            $r['action_method'] = 'POST';
        }
        return $r;
    }


    // 接口文档https://docs.open.alipay.com/api_1/alipay.trade.precreate
    public function scan()
    {
        $aliConfig = new AlipayConfig();
        $aliConfig->setGateWay(GateWay::SANDBOX);
        $aliConfig->setAppId('2016091800538339');
        $aliConfig->setPublicKey('阿里公钥');
        $aliConfig->setPrivateKey('阿里私钥');
        $pay = new Pay();
        $order = new Scan();
        $order->setSubject('测试');
        $order->setTotalAmount('0.01');
        $order->setOutTradeNo(time());
        $aliPay = $pay->aliPay($aliConfig);
        $data = $aliPay->scan($order)->toArray();
        $response = $aliPay->preQuest($data);
        var_dump($response);
        // qr_code 当前预下单请求生成的二维码码串，可以用二维码生成工具根据该码串值生成对应的二维码  https://qr.alipay.com/bavh4wjlxf12tper3a
    }


}