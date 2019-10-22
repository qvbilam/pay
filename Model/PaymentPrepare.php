<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 17:03
 */

namespace App\Model;

use App\Lib\Code\ReturnCode;

class PaymentPrepare extends Base
{
    protected $tablename = 'payment_prepare';
    protected $notifytablename = 'result_notify';   // 通知表
    protected $merchantCharge = 'merchant_charge';  // 费率表
    protected $merchant = 'merchant';               // 商户表
    protected $proxyUser = 'proxy_user';            // 代理用户表
    protected $record = 'payment_record';           // 订单结果表

    public function getOrderOne($no)
    {
        return $this->db->where('merchant_order', $no)->getOne($this->tablename);
    }

    public function createPaymentPrepare($data)
    {
        $res = $this->db->insert($this->tablename, $data);
        if (!$res) {
            return ['code' => ReturnCode::INSERT_DATA_PREPARE, 'msg' => $this->db->getLastError()];
        }
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS)];
    }

    /*
     * 通过id查询订单
     * */
    public function getDataById($orderId)
    {
        return $this->db->where('id', $orderId)->getOne($this->tablename);
    }

    public function updateBataById($id,$data)
    {
        return $this->db->where('id',$id)->update($this->tablename,$data);
    }

    /*
     * 通过Id删除订单
     * */
    public function delOrderById($id)
    {
        return $this->db->where('id', $id)->delete($this->tablename);
    }

    /*
     * 查询失败订单
     * $number:验证失败的次数
     * */
    public function getAllFailOrder()
    {
        $fields = ['id','merchant_id','merchant_order','pay_method','pay_action','pay_action','result_desc'];
        return $this->db
            ->where('result_desc','bind_succ','!=')
            ->where('result_desc','bind_fail','!=')
            ->get($this->tablename,null,$fields);
    }

    // 支付成功后回调,更改订单状态
    public function changeOrderStatus($orderId, $status, $msg)
    {
        $this->db->startTransaction();
        // 查看该订单的信息
        $paymentPrepare = $this->db->where('id', $orderId)->getOne($this->tablename);
        if (!$paymentPrepare) {
            // 订单不存在
            return ['code' => ReturnCode::DATABASE_EMPTY, 'msg' => 'no merchant_order'];
        }
        $update = ['result_desc' => $status, 'result_data' => $msg];
        // 相等说明订单已经处理过.无需再处理. 也说明通知表添加过数据.
        if ($paymentPrepare['result_desc'] == $status) {
            return ['code' => 0, 'msg' => 'handled ' . $status];
        }
        $res = $this->db->where('id', $orderId)->update($this->tablename, $update);
        if ($res) {
            $id = $paymentPrepare['id'];
            // 查看通知表中是否已经存在改订单
            $check = $this->db->where('payment_order', $id)->getOne($this->notifytablename);
            if (!$check) {
                $notifyData = [
                    'payment_order' => $id,
                    'start_notify' => date("Y-m-d H:i:s", time()),
                    'notify_times' => 0,
                ];
                // 通知表添加数据
                $insertRes = $this->db->insert($this->notifytablename, $notifyData);
                if (!$insertRes) {
                    $this->db->rollback();
                    return ['code' => ReturnCode::INSERT_DATA_PREPARE, 'msg' => 'insert notify error:' . $this->db->getLastError()];
                }
            }
            // 获取用户的该渠道下应扣除的费率
            $merchantCharge = $this->db->where('merchant_id', $paymentPrepare['merchant_id'])->where('pay_method', $paymentPrepare['pay_method'])->getOne($this->merchantCharge);
            if (!$merchantCharge) {
                $this->db->rollback();
                return ['code' => ReturnCode::DATABASE_EMPTY, 'msg' => 'select merchant charge empty'];
            }
            //
            $paymentPrepare['money'] = $paymentPrepare['money'] * 100;  // 元转换成分,方便计算
            // $charge = bcmul("'" . $paymentPrepare['money'] . "'", "'" . $merchantCharge['charge'] . "'"); // 精确乘法
            $charge = $paymentPrepare['money'] * $merchantCharge['charge']; // 分
            // $rebate = bcmul("'" . $paymentPrepare['money'] . "'", "'" . $merchantCharge['rebate'] . "'"); // 精确乘法
            $rebate = $paymentPrepare['money'] * $merchantCharge['rebate']; // 分
            // $userMoney = bcsub($paymentPrepare['money'], $charge);
            $userMoney = $paymentPrepare['money'] - $charge; // 分
            // 获取用户商户信息
            $merchant = $this->db->where('id', $paymentPrepare['merchant_id'])->getOne($this->merchant);
            if (!$merchant) {
                // 没有商户就不需要给用户钱了。订单也是成功的
                $this->db->commit();
                return ['code' => -1, 'msg' => 'no merchant'];
            }
            $userMoney = $userMoney / 100; // 分转换成圆
            $updateData = ['withdraw_balance' => $this->db->inc($userMoney)];
            $addMoneyToMechant = $this->db->where('id', $merchant['id'])->update($this->merchant, $updateData);
            if (!$addMoneyToMechant) {
                $this->db->rollback();
                return ['code' => -1, 'msg' => 'merchant add money error'];
            }
            // 代理用户分成添加
            if ($rebate != 0) {
                // 查询用户的代理用户
                $proxyUser = $this->db->where('user_id', $merchant['user_id'])->getOne($this->proxyUser);
                if ($proxyUser) {
                    // 查询代理的商户号
                    $proxyMerchant = $this->db->where('user_id', $proxyUser['proxy_user_id'])->getOne($this->merchant);
                    if ($proxyMerchant) {
                        // 为代理商户添加钱
                        $rebate = $rebate / 100; // 分转换成圆
                        $updateData = ['withdraw_balance' => $this->db->inc($rebate)];
                        $updateRes = $this->db->where('id', $proxyMerchant['id'])->update($this->merchant, $updateData);
                        if (!$updateRes) {
                            $this->db->rollback();
                            return ['code' => -1, 'msg' => 'proxy add money error'];
                        }
                    }
                }
            }
            // todo 订单表修改状态
            $isOrder = $this->db->where('merchant_id', $paymentPrepare['merchant_order'])->getOne($this->record);
            $data = [
                'merchant_id' => $paymentPrepare['merchant_id'],
                'merchant_order' => $paymentPrepare['merchant_order'],
                'pay_method' => $paymentPrepare['pay_method'],
                'pay_action' => $paymentPrepare['pay_action'],
                'money' => $paymentPrepare['money'],
                'product_id' => $paymentPrepare['product_id'],
                'product_name' => $paymentPrepare['product_name'],
                'notify_url' => $paymentPrepare['notify_url'],
                'return_url' => $paymentPrepare['return_url'],
                'create_time' => $paymentPrepare['create_time'],
                'attach_data' => $paymentPrepare['attach_data'],
                'finish_time' => date("Y-m-d H:i:s"),
                'result_status' => ($paymentPrepare['result_desc'] == 'bind_succ') ? 'succ' : 'fail',
                'result_data' => $msg,
                'complete_notify' => date("Y-m-d H:i:s"),
                'complete_settle' => date("Y-m-d H:i:s"),
                'settle_result' => $userMoney,
                'settle_fee' => $charge
            ];
            if (!$isOrder) {
                $res = $this->db->insert($this->record, $data);
            } else {
                $res = $this->db->where()->update($this->record, $data);
            }
            if (!$res) {
                $this->db->rollback();
                return ['code' => -1, 'msg' => $this->db->getLastError()];
            }
            $this->db->commit();
            return ['code' => 0, 'msg' => 'success1', 'data' => $id];
        }
        $this->db->rollback();
        return ['code' => -1, 'msg' => 'update error', 'data' => json_encode($this->db->getLastError()),];
    }
}
