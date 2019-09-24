<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-09-17
 * Time: 15:50
 */

namespace App\Cache;

use App\Model\Turnover as TurnoverModel;
use App\Lib\Code\ReturnCode;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;

/*
 * 商户交易额
 * 存入金钱都为 分！防止出现不精确的小数
 * */

// todo 商户余额如何加减钱

class Turnover extends Base
{
    /*
     * 获取商户当日总的交易额
     * */
    public function getAllTurnoverDay($merchant)
    {
        $key = $this->getAllTurnoverDayKey();
        $merchantExexist = $this->redis->zscore($key, $merchant);
        if (!$merchantExexist) {
            $row = (new TurnoverModel())->getAllTurnoverlDay($merchant);
            if (empty($row)) {
                return '';
            }
            $this->redis->zadd($key, bcmul($row['money'], 100), $row['merchant_id']);
        }
        $money = $this->redis->zscore($key, $merchant);
        return $this->returnMoneyType($money);
    }

    /*
     * 获取商户当月总的交易额
     * */
    public function getAllTurnoverMonth($merchant)
    {
        $key = $this->getAllTurnoverMonthKey();
        $merchantExexist = $this->redis->zscore($key, $merchant);
        if (!$merchantExexist) {
            $row = (new TurnoverModel())->getAllTturnoverMonth($merchant);
            if (empty($row)) {
                return '';
            }
            $this->redis->zadd($key, bcmul($row['money'], 100), $row['merchant_id']);
        }
        $money = $this->redis->zscore($key, $merchant);
        return $this->returnMoneyType($money);
    }

    /*
     * 获取商户->渠道当日总交易额
     * */
    public function getChannelTunoverDay($merchant, $channel)
    {
        $key = $this->getChannelAllTurnoverDayKey($channel);
        $merchantExexist = $this->redis->zscore($key, $merchant);
        if (!$merchantExexist) {
            $row = (new TurnoverModel())->getChannelTurnoverDay($merchant, $channel);
            if (empty($row)) {
                return '';
            }
            $this->redis->zadd($key, bcmul($row['money'], 100), $row['merchant_id']);
        }
        $money = $this->redis->zscore($key, $merchant);
        return $this->returnMoneyType($money);
    }

    /*
     * 获取商户->渠道当月总交易额
     * */
    public function getChannelTunoverMonth($merchant, $channel)
    {
        $key = $this->getChannelAllTurnoverMonthKey($channel);
        $merchantExexist = $this->redis->zscore($key, $merchant);
        if (!$merchantExexist) {
            $row = (new TurnoverModel())->getChannelTurnoverMonth($merchant, $channel);
            if (empty($row)) {
                return '';
            }
            $this->redis->zadd($key, bcmul($row['money'], 100), $row['merchant_id']);
        }
        $money = $this->redis->zscore($key, $merchant);
        return $this->returnMoneyType($money);
    }

    /*
     * 设置商户金钱
     * 元转分添加
     * */
    public function setAllMoney($merchant, $channel, $money)
    {
        $redisMoney = bcmul($money, 100);
        $insertSql = (new TurnoverModel())->setTurnover($merchant, $channel, $money);
        if ($insertSql != ReturnCode::SUCCESS) {
            // todo 是否需要邮件短信提醒.
            $date = "[" . date("Y-m-d H:i:s") . "]" . "[WARNING] [setMoneyRedis][all]:";
            $content = 'merchant_id :' . $merchant . ' channel_id :' . $channel . ' money :' . $money . ' error_content :' . $insertSql;
            $msg = $date . $content;
            TaskManager::async(function () use ($msg) {
                file_put_contents(dirname(dirname(__DIR__)) . '/Log/Error-' . date('Y-m-d') . '.log', $msg, FILE_APPEND);
            });
            return ReturnCode::INVALID;
        }
        // Redis 写入数据
        $keyAllDay = $this->getAllTurnoverDayKey();
        $this->redis->zincrby($keyAllDay, $redisMoney, $merchant);
        $keyAllMoneth = $this->getAllTurnoverMonthKey();
        $this->redis->zincrby($keyAllMoneth, $redisMoney, $merchant);
        $keyChannelDay = $this->getChannelAllTurnoverDayKey($channel);
        $this->redis->zincrby($keyChannelDay, $redisMoney, $merchant);
        $keyChannelMonth = $this->getChannelAllTurnoverMonthKey($channel);
        $this->redis->zincrby($keyChannelMonth, $redisMoney, $merchant);
        return ReturnCode::SUCCESS;
    }

    /*
     * 对返回金额的处理
     * 因为存入和取出都是分.所以对后面业务的返回金钱类型做统一处理
     * */
    protected function returnMoneyType($money)
    {
        $money = $money / 100;
        return $money;
    }


    /*
     * 商户当日交易key
     * 前缀_turnover_month:2019-09
     * */
    protected function getAllTurnoverDayKey()
    {
        //前缀_turnover_month:2019-06
        $date = date("Y-m-d");
        $key = $this->all_turnover . '_day:' . $date;
        return $key;
    }

    /*
     * 商户当月交易key
     * 前缀_turnover_month:2019-09
     * */
    protected function getAllTurnoverMonthKey()
    {
        //前缀_turnover_month:2019-06
        $date = date("Y-m");
        $key = $this->all_turnover . '_month:' . $date;
        return $key;
    }

    /*
     * 商户渠道当日交易key
     * 前缀_turnover_channel:402_day:2019-09-17
     * */
    protected function getChannelAllTurnoverDayKey($channel)
    {
        $date = date("Y-m-d");
        $key = $this->channel_turnover . $channel . '_day:' . $date;
        return $key;
    }

    /*
     * 商户渠道当月交易key
     * 前缀_turnover_channel:402_month:2019-09
     * */
    protected function getChannelAllTurnoverMonthKey($channel)
    {
        $date = date("Y-m");
        $key = $this->channel_turnover . $channel . '_month:' . $date;
        return $key;
    }
}