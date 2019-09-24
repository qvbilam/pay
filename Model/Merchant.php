<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 15:04
 */

namespace App\Model;

class Merchant extends Base
{
    protected $tablename = 'merchant';

    public function getMerchantOne($merchant_id)
    {
        return $this->db->where('id', $merchant_id)->getOne($this->tablename);
    }

}