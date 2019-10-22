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

    public function test()
    {
        $params = $this->params;
        $params = json_encode($params);
        $path = EASYSWOOLE_ROOT . '/Log/test/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . date('Y-m-d') . '.log', 'data : ' . $params . "\r\n", FILE_APPEND);
        return $this->success(0,0,1);
    }

    /**
     * 支付成功异步通知回调
     * 支付成功后,增加用户金额,修改预备订单表状态,订单表添加数据
     * @throws \EasySwoole\Pay\Exceptions\InvalidArgumentException
     * @throws \EasySwoole\Pay\Exceptions\InvalidSignException
     */
    function notify()
    {
        $content = $this->request()->getBody()->__toString();
        $pay = new Pay();
        $data = $pay->weChat($this->wechatConfig)->verify($content);

        $msg = $data->__toString() . "\r\n";
        $status = $data['result_code'] == 'SUCCESS' ? 'bind_succ' : 'bind_fail';
        $upateStatus = (new PaymentPrepare())->changeOrderStatus($data['out_trade_no'], $status,$msg);
        // todo msg信息整合.
        // 修改数据失败
        if ($upateStatus['code'] != ReturnCode::SUCCESS) {
            $errorMsg = $upateStatus['msg'];
            go(function () use ($errorMsg, $msg) {
                $path = EASYSWOOLE_ROOT . '/Log/wx-error/';
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                file_put_contents($path . date('Y-m-d') . '.log', "[" . date('Y-m-d H:i:s') . "]" . 'msg: ' . $errorMsg . ' result: ' . $msg . "\r\n", FILE_APPEND);
            });
            return $this->response()->write($pay->weChat($this->wechatConfig)->fail());
        }
        // 修改数据成功
        go(function () use ($msg) {
            $path = EASYSWOOLE_ROOT . '/Log/wx-success/';
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            file_put_contents($path . date("Y-m-d") . '.log', "[" . date('Y-m-d H:i:s') . "]" . ' result: ' . $msg . "\r\n", FILE_APPEND);
        });
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

    /*
     * 支付成功处理用户金额
     * $check = 0 将用户金额存入余额
     * $check = 1 存入微信
     * $check = 2 存入支付宝
     * $check = 3 存入银行卡
     * */
    protected function handleUser($order, $check = 0)
    {
        switch ($check) {
            case 0:
                $res = $this->transferToBalance($order);
                break;
            case 1:
                break;
            case 2:
                break;
            case 3:
                break;
        }
    }


}