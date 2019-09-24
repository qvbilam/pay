<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-28
 * Time: 11:25
 */

namespace App\Model;

class PaymentActionLog extends Base
{
    public $tablename = 'payment_action_log';

    public function CreateData($data)
    {
        return $this->db->insert($this->tablename,$data);
    }

    /*
     * 通过订单id查询数据
     * */
    public function getDataById($orderId)
    {
        return $this->db->where('order_id',$orderId)->getOne($this->tablename);
    }

    /*
     * 通过订单id删除数据
     * */
    public function delOrderById($orderId)
    {
        return $this->db->where('order_id',$orderId)->delete($this->tablename);
    }
}