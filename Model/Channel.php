<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-27
 * Time: 12:03
 */

namespace App\Model;

use App\Cache\Channel as ChannelCache;
use App\Lib\Code\ReturnCode;

class Channel extends Base
{
    public function getPayUrl($action,$url)
    {
        $ret['self_check'] = 0;
        switch ($action) {
            case 'chinamobile':
                // $ret =ChinaMobileAction::instance()->recharge($charge['pay_method'],$charge['money']);
                $ret['self_check'] = 1;
                $ret['pay_url'] = $url;
                break;
            case '22':
                $ret['pay_url'] = $url;
                break;
            case '33':
                $ret['pay_url'] = $url;
                break;
        }
        if (!isset($ret['pay_url'])) {
            return ['code' => ReturnCode::EMPTY_PAY_URL, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::EMPTY_PAY_URL)];
        }
        $ret['action_id'] = $action;
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => $ret];
    }


    public function getAction($charge)
    {
        $ret['self_check'] = 0;
        // 获取渠道信息 payment_channel_action
        $acInfo = (new ChannelCache())->getAction($charge['pay_method']);
        $action = '';
        $day = strtotime(date('Ymd'));
        if ($acInfo['state'] == 0) {
            return ['code' => ReturnCode::CHECK_ACTION_STATE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_STATE)];
        }
        if ($acInfo['level'] > $charge['level']) {
            return ['code' => ReturnCode::CHECK_ACTION_LEVEL, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_LEVEL)];
        }
        // 渠道费率 小于  通道费率 + 中介
        if ($acInfo['charge'] >= $charge['charge'] + $charge['rebate']) {
            return ['code' => ReturnCode::CHECK_ACTION_CHARGE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_CHARGE)];
        }
        // todo 时间限额: 商户-》渠道下 交易金额统计对比。
        if ($acInfo['money_max'] > 0 && $acInfo['money_max'] > $charge['money']) {
            return ['code' => ReturnCode::CHECK_ACTION_MAX_MONEY, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_MAX_MONEY)];
        }
        $actionMoneyDay = (new ChannelCache())->getActionMoneyDay($action);
        if ($acInfo['money_max_day'] > 0 && $acInfo['money_max_day'] + $charge['money'] > $actionMoneyDay) {
            return ['code' => ReturnCode::CHECK_ACTION_MAX_DAY_MONEY, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_MAX_DAY_MONEY)];
        }
        $actionMoneyMonth = (new ChannelCache())->getActionMoneyMonth($action);
        if ($acInfo['money_max_month'] > 0 && $acInfo['money_max_month'] + $charge['money'] > $actionMoneyMonth) {
            return ['code' => ReturnCode::CHECK_ACTION_MAX_MONTH_MONEY, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_MAX_MONTH_MONEY)];
        }
        if ($acInfo['open_time'] != 0 || $acInfo['close_time'] != 0) {
            $stime = $day + $acInfo['open_time'];
            $etime = $day + $acInfo['close_time'];
            if ($stime > $charge['time'] || $charge['time'] > $etime) {
                return ['code' => ReturnCode::OPEN_TIME, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::OPEN_TIME)];
            }
        }
        $ret['code'] = 0;
        $action_array = explode(',', $acInfo['action']);
        $action = $action_array[array_rand($action_array, 1)];
        switch ($action) {
            case 'chinamobile':
                // $ret =ChinaMobileAction::instance()->recharge($charge['pay_method'],$charge['money']);
                $ret['self_check'] = 1;
                $ret['pay_url'] = 'http://192.168.7.10:8000/pay-web?id=15589fd8-769e-4cce-a3d2-ae1715c58dde';
                break;
            case '22':
                $ret['pay_url'] = 'http://192.168.7.10:8000/pay-web?id=15589fd8-769e-4cce-a3d2-ae1715c58dde';
                break;
            case '33':
                $ret['pay_url'] = 'http://192.168.7.10:8000/pay-web?id=15589fd8-769e-4cce-a3d2-ae1715c58dde';
                break;
        }
        if (!isset($ret['pay_url'])) {
            return ['code' => ReturnCode::EMPTY_PAY_URL, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::EMPTY_PAY_URL)];
        }
        $ret['action_id'] = $action;
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => $ret];
    }

}