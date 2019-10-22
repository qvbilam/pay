<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-27
 * Time: 12:35
 */
namespace App\Model;

class PaymentChannelAction extends Base
{
    public $tablename = 'payment_channel_action';

    public function getActionOne($channel_id)
    {
        // return $this->db->where('action',$action)->getOne($this->tablename);
        return $this->db->where('channel_id',$channel_id)->getOne($this->tablename);
    }


}