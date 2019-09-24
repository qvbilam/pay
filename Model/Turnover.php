<?php

namespace App\Model;
use App\Lib\Code\ReturnCode;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;

class Turnover extends Base
{
    protected $tablename = 'merchant_money_log';
    protected $merchant_tablename = 'merchant';
    protected $channel_tablename = 'payment_channel';

    /*
     *
     * */
    public function setTurnover($merchant,$channel,$money)
    {
        $date = date("Y-m-d");
        $checkMerchant = $this->checkMerchant($merchant);
        if (!$checkMerchant) {
            return 'request merchant error';
        }
        $checkChannel = $this->checkChannel($channel);
        if(!$checkChannel){
            return 'request channel error';
        }
        $data = [
            'merchant_id' => $merchant,
            'channel_id' => $channel,
            'time' => $date,
            'money' => $money
        ];
        $id = $this->db->insert($this->tablename,$data);
        if(!$id){
            return $this->db->getLastError();
        }
        return ReturnCode::SUCCESS;
    }

    /*
     * 获取商户当月总交易额
     * */
    public function getAllTturnoverMonth($merchant)
    {
        $checkMerchant = $this->checkMerchant($merchant);
        if (!$checkMerchant) {
            return '';
        }
        $time_month = date("Y-m");
        $result = $this->db->groupBy('merchant_id')
            ->where('time', [">=" => $time_month])
            ->where('merchant_id', $merchant)
            ->getOne($this->tablename, "sum(money) as money,merchant_id");
        $result = !empty($result) ? $result : ['money' => 0, 'merchant_id' => $merchant];
        return $result;
    }

    /*
     * 获取商户当日总交易额
     * */
    public function getAllTurnoverlDay($merchant)
    {
        $checkMerchant = $this->checkMerchant($merchant);
        if (!$checkMerchant) {
            return '';
        }
        $time_day = date("Y-m-d");
        $result = $this->db->groupBy('merchant_id')
            ->where('time', $time_day)
            ->where('merchant_id', $merchant)
            ->getOne($this->tablename, "sum(money) as money,merchant_id");
        $result = !empty($result) ? $result : ['money' => 0, 'merchant_id' => $merchant];
        return $result;
    }

    /*
     * 获取商户对应渠道下当月交易额
     * */
    public function getChannelTurnoverMonth($merchant, $channel)
    {
        $checkMerchant = $this->checkMerchant($merchant);
        $checkChannel = $this->checkChannel($channel);
        if (!$checkMerchant || !$checkChannel) {
            return '';
        }
        $time_month = date("Y-m");
        $result = $this->db->groupBy('merchant_id,channel_id')
            ->where('time', [">=" => $time_month])
            ->where('merchant_id', $merchant)
            ->where('channel_id', $channel)
            ->getOne($this->tablename, "sum(money) as money,merchant_id,channel_id");
        $result = !empty($result) ? $result : ['money' => 0, 'merchant_id' => $merchant, 'channel_id' => $channel];
        return $result;
    }

    /*
     * 获取商户对应渠道下当日交易额
     * */
    public function getChannelTurnoverDay($merchant, $channel)
    {
        $checkMerchant = $this->checkMerchant($merchant);
        $checkChannel = $this->checkChannel($channel);
        if (!$checkMerchant || !$checkChannel) {
            return '';
        }
        $time_day = date("Y-m-d");
        $result = $this->db->groupBy('merchant_id,channel_id')
            ->where('time', $time_day)
            ->where('merchant_id', $merchant)
            ->where('channel_id', $channel)
            ->getOne($this->tablename, "sum(money) as money,merchant_id,channel_id");
        $result = !empty($result) ? $result : ['money' => 0, 'merchant_id' => $merchant, 'channel_id' => $channel];
        return $result;
    }

    /*
     * 验证传入商户是否存在
     * */
    protected function checkMerchant($merchant)
    {
        $result = $this->db->where('id', $merchant)->getOne($this->merchant_tablename);
        return $result;
    }

    /*
     * 验证渠道是否存在
     * $status 判断渠道是否开启.0不判断,1判断.
     * */
    protected function checkChannel($channel, $state = 0)
    {
        $result = $this->db->where('id', $channel)->getOne($this->channel_tablename);
        return $result;
    }
}