<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 17:15
 */

namespace App\Cache;

use App\Model\MerchantCharge;

class Charge extends Base
{
    protected function getChargeKey($mch_id, $pay_type)
    {
        return $this->merchant . $mch_id . ':' . $pay_type;
    }

    public function getCharge($mch_id, $pay_type)
    {
        if (!$this->redis->exists($this->getChargeKey($mch_id, $pay_type))) {
            $row = (new MerchantCharge())->getChartOne($mch_id,$pay_type);
            if ($row) {
                $this->redis->hmset($this->getChargeKey($mch_id, $pay_type), $row);
            }
        }
        return $this->redis->hgetall($this->getChargeKey($mch_id, $pay_type));
    }
}