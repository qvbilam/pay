<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 17:44
 */

namespace App\Model;

class MerchantCharge extends Base
{
    protected $tablename = 'merchant_charge';

    public function getChartOne($mch_id,$pay_type)
    {
        return $this->db->where('merchant_id', $mch_id)->where('pay_method', $pay_type)->getOne($this->tablename);
    }
}