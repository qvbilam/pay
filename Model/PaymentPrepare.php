<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 17:03
 */

namespace App\Model;

class PaymentPrepare extends Base
{
    protected $tablename = 'payment_prepare';
    protected $notifytablename = 'result_notify';

    public function getOrderOne($no)
    {
        return $this->db->where('merchant_order', $no)->getOne($this->tablename);
    }

    public function createPaymentPrepare($data)
    {
        return $this->db->insert($this->tablename, $data);
    }

    /*
     * 通过id查询订单
     * */
    public function getDataById($orderId)
    {
        return $this->db->where('id',$orderId)->getOne($this->tablename);
    }

    /*
     * 通过Id删除订单
     * */
    public function delOrderById($id)
    {
        return $this->db->where('id',$id)->delete($this->tablename);
    }

    // 支付成功后回调,更改订单状态
    public function changeOrderStatus($merchant_order, $status)
    {
        $this->db->startTransaction();
        // 查看该订单的信息
        $paymentPrepare = $this->db->where('merchant_order', $merchant_order)->getOne($this->tablename);
        if (!$paymentPrepare) {
            return ['code' => -1, 'msg' => 'no merchant_order'];
        }
        $update = ['result_desc' => $status];
        // 相等说明订单已经处理过.无需再处理. 也说明通知表添加过数据.
        if ($paymentPrepare['result_desc'] == $status) {
            return ['code' => 0, 'msg' => 'handled ' . $status];
        }
        $res = $this->db->where('merchant_order', $merchant_order)->update($this->tablename, $update);
        if ($res) {
            $id = $paymentPrepare['id'];
            if (!$id) {
                $this->db->rollback();
                return ['code' => -1, 'msg' => 'no order_id.error :' . json_encode($this->db->getLastError()),];
            }
            $addNotify = $this->addNotify($id);
            if ($addNotify['code'] != 0) {
                $this->db->rollback();
                return ['code' => $addNotify['code'], 'msg' => $addNotify['msg']];
            }
            $this->db->commit();
            return ['code' => 0, 'msg' => 'success1', 'data' => $id];
        }
        $this->db->rollback();
        return ['code' => -1, 'msg' => 'update error', 'data' => json_encode($this->db->getLastError()),];
    }

    protected function addNotify($id)
    {
        // 查看通知表是否存在.存在返回成功.
        $check = $this->db->where('payment_order', $id)->getOne($this->notifytablename);
        if ($check) {
            return ['code' => 0, 'msg' => 'notify existence'];
        }
        $notifyData = [
            'payment_order' => $id,
            'start_notify' => date("Y-m-d H:i:s", time()),
            'notify_times' => 0,
        ];
        $insertRes = $this->db->insert($this->notifytablename, $notifyData);
        if ($insertRes) {
            return ['code' => 0, 'msg' => 'success', 'data' => $id];
        }
        return ['code' => -1, 'msg' => 'insert notify error', 'error:' . json_encode($this->db->getLastError())];
    }
}
