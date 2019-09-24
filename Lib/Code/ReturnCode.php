<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 13:43
 */

namespace App\Lib\Code;

use EasySwoole\Http\Message\Status;

class ReturnCode
{
    // 通用返回码
    const SUCCESS = 0;
    const INVALID = -1;


    /*
     * 通用必传参数返回码
     * */
    const EMPTY_SIGN = -1000;                           // 签名
    const EMPTY_MCH_ID = -1001;                         // 商户号
    const EMPTY_NOTIFY_URL = -1002;                     // 异步通知地址
    const EMPTY_TRADE_NO = -1003;                       // 订单号空
    const EMPTY_PAY_TYPE = -1004;                       // 支付方式
    const EMPTY_MONEY = -1005;                          // 订单金额
    const EMPTY_TIME = -1006;                           // 时间戳
    const EMPTY_PRODUCT_ID = -1007;                     // 商品ID
    const EMPTY_PRODUCT_NAME = -1008;                   // 商品名称
    const EMPTY_RETURN_URL = -1009;                     // 返回接口

    /*
     * 通用验证传参返回码
     * */
    const CHECK_SIGN = -1010;                           // 签名不正确
    const CHECK_MERCHANT = -1011;                       // 商户不存在
    const CHECK_NOTIFY_URL = -1012;                     // 异步通知地址格式不对
    const CHECK_ORDER = -1013;                          // 订单不存在
    // 通道部分
    const CHECK_CHARGE = -1014;                         // 商户未开通charge(通道)
    const CHECK_CHARGE_STATE = -1015;                   // 通道关闭
    const CHECK_CHARGE_MAX_MONEY = -1016;               // 单笔交易最大限额错误
    const CHECK_CHARGE_MAX_DAY_MONEY = -1017;           // 每日单笔交易最大限额错误
    const CHECK_CHARGE_MAX_MONTH_MONEY = -1018;         // 每月单笔交易最大额度错误
    // 渠道部分
    const CHECK_ACTION_STATE = -1019;                   // 渠道关闭
    const CHECK_ACTION_LEVEL = -1020;                   // 渠道等级大于通道等级
    const CHECK_ACTION_CHARGE = -1021;                  // 渠道费率 大于等于  通道费率 + 中介
    const CHECK_ACTION_MAX_MONEY = -1022;               // 渠道单笔最大限额错误
    const CHECK_ACTION_MAX_DAY_MONEY = -1023;           // 渠道每日限额最大错误
    const CHECK_ACTION_MAX_MONTH_MONEY = -1024;         // 渠道每月最大限额错误
    // 金额部分
    const CHARGE_GT_BALANCE = -1025;                    // 交易额度的费率大于充值额度,无法交易

    /*
     * 交易接口返回码
     * */
    const EMPTY_PAY_URL = -2001;                        // 获取交易地址错误
    const INSERT_DATA_PREPARE = -2002;                  // 插入预备订单表失败
    const OPEN_TIME = -2003;                            // 不在开放时间内
    /*
     * 订单查询返回码
     * */

    static public $msg = [
        0 => 'ok',
        -1 => 'error',
        // 1xxx
        -1000 => 'params empty sign.',
        -1001 => 'params empty mch_id.',
        -1002 => 'params empty notify_url.',
        -1003 => 'params empty trade_no.',
        -1004 => 'params empty pay_type.',
        -1005 => 'params empty money.',
        -1006 => 'params empty time.',
        -1007 => 'params empty product_id.',
        -1008 => 'params empty product_name.',
        -1009 => 'params empty return_url.',
        -1010 => 'signature err.',
        -1011 => 'empty mch_id.',
        -1012 => 'notify_url err.',
        -1013 => 'trade_no resend',
        -1014 => 'charge err',
        -1015 => 'charge is close',
        -1016 => 'money_max_err',
        -1017 => 'money_max_day_eror',
        -1018 => 'money_max_month_err',
        -1019 => 'state is 0',
        -1020 => 'level error',
        -1021 => 'charge error',
        -1022 => 'money_max_err',
        -1023 => 'money_max_day_eror',
        -1024 => 'money_max_month_err',
        -1025 => 'charge_money gt balance_money',
        // 2xxx
        -2001 => 'pay_url err',
        -2002 => 'money_max_month err',
        -2003 => 'time not in starttiem',
    ];

    // 获取空值的code
    static function getEmptyCode($param)
    {
        $data = [
            'sign' => self::EMPTY_SIGN,
            'mch_id' => self::EMPTY_MCH_ID,
            'notify_url' => self::EMPTY_NOTIFY_URL,
            'trade_no' => self::EMPTY_TRADE_NO,
            'pay_type' => self::EMPTY_PAY_TYPE,
            'money' => self::EMPTY_MONEY,
            'time' => self::EMPTY_TIME,
            'product_name' => self::EMPTY_PRODUCT_ID,
            'product_name' => self::EMPTY_PRODUCT_NAME,
            'return_url' => self::EMPTY_RETURN_URL
        ];
        $rst = isset($data[$param]) ?$data[$param]: self::INVALID;
        return $rst;
    }

    static function getReasonPhrase($statusCode)
    {
        if (isset(self::$msg[$statusCode])) {
            return self::$msg[$statusCode];
        } else {
            return null;
        }
    }
}