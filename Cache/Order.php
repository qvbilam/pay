<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 17:01
 */
namespace App\Cache;

use App\Model\PaymentPrepare;

class Order extends Base
{
    // 校验商户订单号
    public function checkMerchantOrder($mch_id,$no){
        $key =$this->merchant.'order:'.$mch_id.':'.$no;
        if(!$this->redis->exists($key)){
            // getOneData where merchant_order
            $row = (new PaymentPrepare())->getOrderOne($no);
            if($row){
                $this->redis->setex($key,3600,$no);
            }
        }

        return $this->redis->get($key);
    }

    // 生成订单号
    public function createNo(){
        $time =date('YmdHis');
        $key =$this->redis_no.$time;
        $reqNo = $this->redis->incr($key);
        $this->redis->expire($key, 10);
        $reqNo = 100000 + $reqNo; // 补齐订单号长度
        $orderNo = $time  . $reqNo; // 生成订单号
        return $orderNo;
    }

    public function getMerchantOrderState($mch_id,$no){
        $key =$this->merchant.'orderInfo:'.$mch_id.':'.$no;
        if(!$this->redis->exists($key)){
            $row = (new PaymentPrepare())->getOrderOne($no);
            if($row){
                $this->redis->setex($key,3600,$row['result_desc']);
            }
        }

        return $this->redis->get($key);
    }

}