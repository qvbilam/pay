<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-10-10
 * Time: 16:01
 */

namespace App\HttpController\Api;

class Payment extends Base
{
    /*
         * 调起支付
         * money: 订单金额(元)
         * channel: 支付渠道
         * check: 是否使用自己的信息
         *
         * */
    public function toPayment()
    {
        // 生成订单号
        $outTradeNo = 'CN' . date('YmdHis') . rand(1000, 9999);
        $checkData = $this->checkUnifiedorder($this->params);
        if ($checkData['code'] != ReturnCode::SUCCESS) {
            return $this->error($checkData['code'], $checkData['msg']);
        }
        $orderId = (new Order())->createNo();
        $uuid = RandomStr::uuid();

    }

}