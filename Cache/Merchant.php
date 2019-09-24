<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 15:00
 */

namespace App\Cache;

use App\Model\Merchant as MerchantModel;
use App\Model\PaymentPrepare;


class Merchant extends Base
{
    // 获取商户信息
    public function getMerchant($mch_id)
    {
        {
            $key = $this->getMerchantKey($mch_id);
            if (!$this->redis->exists($key)) {
                $row = (new MerchantModel())->getMerchantOne($mch_id);
                if ($row) {
                    $this->addMerchant($row);
                }
            }
            return $this->redis->hgetall($key);
        }
    }

    // 获取商户缓存key
    protected function getMerchantKey($mch_id)
    {
        return $this->merchant . $mch_id;
    }

    // 设置商户信息的缓存
    protected function addMerchant($merchant)
    {
        $this->redis->hmset($this->getMerchantKey($merchant['id']), $merchant);
    }


    public function checkMerchantOrder($mch_id, $no)
    {
        $key = $this->merchant . 'order:' . $mch_id . ':' . $no;
        if (!$this->redis->exists($key)) {
            $row = (new PaymentPrepare())->getOrderOne($no);
            if ($row) {
                $this->redis->setex($key, 3600, $no);
            }
        }

        return $this->redis->get($key);
    }

    protected function getMerchantMoneyDayKey($mch_id)
    {
        $day = date('Y-m-d', time());
        $key = $this->merchant . 'money:' . $mch_id . ':' . $day;

        return $key;
    }

    public function getMerchantMoneyDay($mch_id)
    {
        $key = $this->getMerchantMoneyDayKey($mch_id);

        if (!$this->redis->exists($key)) {
            return 0;
        }

        return $this->redis->get($key);
    }

    protected function getMerchantMoneyMonthKey($mch_id)
    {
        $day = date('Y-m', time());
        $key = $this->merchant . 'money:' . $mch_id . ':' . $day;

        return $key;
    }

    public function getMerchantMoneyMonth($mch_id)
    {
        $key = $this->getMerchantMoneyMonthKey($mch_id);

        if (!$this->redis->exists($key)) {
            return 0;
        }

        return $this->redis->get($key);
    }

}