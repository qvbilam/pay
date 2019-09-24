<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-09-09
 * Time: 17:30
 */

namespace App\HttpController\Pay;

use EasySwoole\Pay\WeChat\Config;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\RequestBean\Scan;
use EasySwoole\Pay\WeChat\RequestBean\Wap;
use EasySwoole\Pay\WeChat\RequestBean\OrderFind;

class Wechat extends Base
{
    protected $product_id = '123456789';
    protected $scan_body = '二滑大魔王扫码付款';
    protected $wap_body = '二滑大魔王-WAP测试';
    protected $order = 'CN201909091817355457';  // 找回钱的订单。


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

    /**
     * scan扫码支付
     * 传入元-> 转成分
     */
    public function scan($money = 100)
    {
        $money = $money / 100;
        $outTradeNo = 'CN' . date('YmdHis') . rand(1000, 9999);
        $bean = new Scan();
        $bean->setOutTradeNo($outTradeNo);
        $bean->setProductId($this->product_id);
        $bean->setBody($this->scan_body);
        $bean->setTotalFee($money);
        $bean->setSpbillCreateIp($this->request()->getHeader('x-real-ip')[0]);
        $pay = new Pay();
        $data = $pay->weChat($this->wechatConfig)->scan($bean);
        $url2 = $data->getCodeUrl();
        $image = $this->qrcode($url2);
        echo "WAP--- " . $outTradeNo . "\r\n";
        $str = '<html><head><meta http-equiv="content-type" content="text/html;charset=utf-8"/>';
        $str .= '<meta name="viewport" content="width=device-width, initial-scale=1" />';
        $str .= '<title>微信-扫码支付</title></head>';
        $str .= '<body><div style="margin-left: 10px;color:#556B2F;font-size:30px;font-weight: bolder;">微信扫码支付</div><br/>';
        //$str .= '<img alt="微信扫码支付" src="/index/qrcode?data=' . urlencode($url1) . '" style="width:150px;height:150px;"/>';
        $str .= '<img alt="微信扫码支付" src="' . $image . '" style="width:150px;height:150px;"/>';
        $str .= '</body></html>';
        $this->response()->write($str);
    }

    /**
     * 网页支付
     */
    public function wap($money = 100)
    {
        $money = $money / 100;
        $outTradeNo = 'CN' . date('YmdHis') . rand(1000, 9999);
        $wap = new Wap();
        $wap->setOutTradeNo($outTradeNo);
        $wap->setBody($this->wap_body);
        $wap->setTotalFee(1);
        $wap->setSpbillCreateIp('xxxxx');
        $pay = new \EasySwoole\Pay\Pay();
        $params = $pay->weChat($this->wechatConfig)->wap($wap);
        return $this->success(0,0,$params);
    }


    /**
     * 支付成功异步通知回调
     */
    public function notify()
    {
        $content = $this->request()->getBody()->__toString();
        $pay = new Pay();
        $data = $pay->weChat($this->wechatConfig)->verify($content);
        $msg = "[" . date('Y-m-d H:i:s') . "]" . $data->__toString() . "\r\n";
        // 将通知成功的信息写入到通知表里.修改订单表的状态
        $status = $data['result_code'] == 'SUCCESS' ? 'bind_succ' : 'bind_fail';
        // 修改订单表状态
        $upateStatus = (new PaymentPrepare())->changeOrderStatus($data['out_trade_no'], $status);
        if ($upateStatus['code'] != ReturnCode::SUCCESS) {
            file_put_contents(dirname(dirname(__DIR__)) . '/Log/wx-error-' . date('Y-m-d') . '.log', $msg, FILE_APPEND);
            return $this->response()->write($pay->weChat($this->wechatConfig)->fail());
        }
        // 写日志到NotifyLog目录下
        file_put_contents(dirname(dirname(__DIR__)) . '/Log/wx-notify-' . date('Y-m-d') . '.log', $msg, FILE_APPEND);
        return $this->response()->write($pay->weChat($this->wechatConfig)->success());
    }

    /**
     * 订单查询
     */
    public function orderQuery()
    {
        $wechatConfig = $this->wechatConfig;
        $order = $this->order;
        go(function () use ($wechatConfig,$order) {
            $orderFind = new OrderFind();
            $orderFind->setOutTradeNo($order);
            $pay = new Pay();
            $info = $pay->weChat($wechatConfig)->orderFind($orderFind);
            print_r((array)$info);
            //Array
            //(
            //    [return_code] => SUCCESS
            //    [return_msg] => OK
            //    [appid] => wxf67e5d6039607945
            //    [mch_id] => 1497029642
            //    [nonce_str] => QBXWmiroY55upKlc
            //    [sign] => 5A02013DDDF1BA3F38D73B443921EC41
            //    [result_code] => SUCCESS
            //    [openid] => oJeEe5T3ahYKTD3vIYYc62-GKxAs
            //    [is_subscribe] => N
            //    [trade_type] => NATIVE
            //    [bank_type] => CFT
            //    [total_fee] => 1
            //    [fee_type] => CNY
            //    [transaction_id] => 4200000389201909093389846998
            //    [out_trade_no] => CN201909091817355457
            //    [attach] => Array
            //        (
            //        )
            //
            //    [time_end] => 20190909181811
            //    [trade_state] => SUCCESS
            //    [cash_fee] => 1
            //    [trade_state_desc] => 支付成功
            //    [cash_fee_type] => CNY
            //)
        });
    }

    /**
     * 订单退款
     */
    public function refund()
    {
        $wechatConfig = $this->wechatConfig;
        $order = $this->order;
        go(function () use ($wechatConfig,$order) {
            $refund = new \EasySwoole\Pay\WeChat\RequestBean\Refund();
            $refund->setOutTradeNo($order);
            $refund->setOutRefundNo('TK' . date('YmdHis') . rand(1000, 9999));
            $refund->setTotalFee(1);
            $refund->setRefundFee(1);
            $refund->setNotifyUrl('https://weixin.qq.com/notify/');
            $pay = new \EasySwoole\Pay\Pay();
            $info = $pay->weChat($wechatConfig)->refund($refund);
            print_r($info);
            //EasySwoole\Spl\SplArray Object
            //(
            //    [storage:ArrayObject:private] => Array
            //        (
            //            [return_code] => SUCCESS
            //            [return_msg] => OK
            //            [appid] => wxf67e5d6039607945
            //            [mch_id] => 1497029642
            //            [nonce_str] => VoS3TQIZiS5CVD45
            //            [sign] => EFDF9B06FA14223D4C790650D63A609D
            //            [result_code] => SUCCESS
            //            [transaction_id] => 4200000389201909093389846998
            //            [out_trade_no] => CN201909091817355457
            //            [out_refund_no] => TK201909091819478724
            //            [refund_id] => 50000601872019090912203192698
            //            [refund_channel] => Array
            //                (
            //                )
            //
            //            [refund_fee] => 1
            //            [coupon_refund_fee] => 0
            //            [total_fee] => 1
            //            [cash_fee] => 1
            //            [coupon_refund_count] => 0
            //            [cash_refund_fee] => 1
            //        )
            //
            //)
        });
    }

    /**
     * 订单退款查询
     */
    public function refundquery()
    {
        $wechatConfig = $this->wechatConfig;
        $order = $this->order;
        go(function () use ($wechatConfig,$order) {
            $refundFind = new \EasySwoole\Pay\WeChat\RequestBean\RefundFind();
            $refundFind->setOutTradeNo($order);
            $pay = new \EasySwoole\Pay\Pay();
            $info = $pay->weChat($wechatConfig)->refundFind($refundFind);
            print_r((array)$info);
        });
        //Array
        //(
        //    [appid] => wxf67e5d6039607945
        //    [cash_fee] => 1
        //    [mch_id] => 1497029642
        //    [nonce_str] => 9ZKdua4Otw6cxdnt
        //    [out_refund_no_0] => TK201909091819478724
        //    [out_trade_no] => CN201909091817355457
        //    [refund_account_0] => REFUND_SOURCE_UNSETTLED_FUNDS
        //    [refund_channel_0] => ORIGINAL
        //    [refund_count] => 1
        //    [refund_fee] => 1
        //    [refund_fee_0] => 1
        //    [refund_id_0] => 50000601872019090912203192698
        //    [refund_recv_accout_0] => 支付用户的零钱
        //    [refund_status_0] => SUCCESS
        //    [refund_success_time_0] => 2019-09-09 18:19:52
        //    [result_code] => SUCCESS
        //    [return_code] => SUCCESS
        //    [return_msg] => OK
        //    [sign] => B779C166794B5785F6C01AE784AF3143
        //    [total_fee] => 1
        //    [transaction_id] => 4200000389201909093389846998
        //)
    }


    public static function XmlToArray($xml)
    {
        if (!$xml) {
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }
}