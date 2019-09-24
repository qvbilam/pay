<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-30
 * Time: 12:29
 */

namespace App\HttpController\Pay;

use EasySwoole\Pay\Pay;
use App\Model\PaymentPrepare;
use App\Lib\Code\ReturnCode;

class Notify extends Base
{

    /**
     * 支付成功异步通知回调
     * @throws \EasySwoole\Pay\Exceptions\InvalidArgumentException
     * @throws \EasySwoole\Pay\Exceptions\InvalidSignException
     */
    function notify()
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
            file_put_contents(dirname(dirname(__DIR__)) . '/NotifyLog/wx-error-' . date('Y-m-d') . '.log', $msg, FILE_APPEND);
            return $this->response()->write($pay->weChat($this->wechatConfig)->fail());
        }
        // 写日志到NotifyLog目录下
        file_put_contents(dirname(dirname(__DIR__)) . '/NotifyLog/wx-notify-' . date('Y-m-d') . '.log', $msg, FILE_APPEND);
        return $this->response()->write($pay->weChat($this->wechatConfig)->success());
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