<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 12:30
 */

namespace App\HttpController\Api;

use app\model\MerchantCharge;
use App\Model\PaymentPrepare;
use App\Task\CreateOrder;
use EasySwoole\Component\Di;
use App\Lib\Code\ReturnCode;
use App\Cache\Merchant as MerchantCache;
use App\Lib\Sign\Sign;
use App\Cache\Order;
use App\Cache\Charge;
use App\Model\Channel;
use App\Model\PaymentActionLog;
use App\Cache\Turnover as TurnoverCache;
use App\Cache\Channel as ChannelCache;
use App\Lib\Random\RandomStr;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use App\Cache\PayHtml;


class Index extends Base
{
    // unifiedorder 必传参数
    protected $unifiedorderData = [
        'trade_no',         // 商户系统订单编号
        'money',            // 订单⾦金金额
        'product_id',       // 商品编号
        'product_name',     // 商品名称
        'pay_type',         // ⽀付⽅方式
        'notify_url',       // ⽀付完成后异步通知商户的接⼝
        'return_url',       // ⽀付完成后返回到商户系统的接⼝
        'time',             // 时间戳
        'sign',             // 签名
        'mch_id',           // 商户号
    ];

    // checkOrderStatus 必传参数
    protected $checkOrderStatusData = [
        'trade_no',          // 商户系统订单编号
        'time',              // 时间戳
        'sign',              // 签名
        'mch_id'             // 商户号
    ];


    /*
     * 获取订单支付接口
     * */
    public function getPay()
    {
        $params = $this->params;
        if (!isset($params['uuid'])) {
            return $this->error(0, '为获取到uuid');
        }
        $uuid = $params['uuid'];
        $data = (new PayHtml())->getPayHtml($uuid);
        if ($data['code'] < 0) {
            return $this->error(-1, '不存在');
        }
        if ($data['code'] == 0) {
            return $this->error(-1, '订单处理中');
        }
        if (!isset($data['data']['html'])) {
            return $this->error(-1, '获取地址失败');
        }
        $html = $data['data']['html'];
        // $html = htmlspecialchars_decode($data['data']['html']);
        echo htmlspecialchars_decode($data['data']['html']);
        if (filter_var($html, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
            // 输出
            return $this->response()->write($html);
        }
        return $this->response()->write($html);
        // 跳转
        return $this->response()->redirect($html);
    }

    /*
     * 创建订单接口
     * 返回uuid
     * */
    public function unifiedorder()
    {
        // todo 设置域名
        $serverName = empty($_SERVER['SERVER_NAME']) ? '127.0.0.1' : $_SERVER['SERVER_NAME'];
        $host = $serverName . '/api/index/getPay';
        $checkData = $this->checkUnifiedorder($this->params);
        if ($checkData['code'] != ReturnCode::SUCCESS) {
            return $this->error($checkData['code'], $checkData['msg']);
        }
        /*
         * 投递生产支付页面的异步任务
         * 预生成订单号,以保证异步任务出错后能销毁数据.
         * PaymentPrepare表 id
         * PaymentActionLog表 order_id
         * */
        $orderId = (new Order())->createNo();
        $uuid = RandomStr::uuid();
        $taskData = [
            'mobile' => '13675887695',
            'uuid' => $uuid,
            'orderId' => $orderId,
            'money' => 10
        ];
        $task = new CreateOrder($taskData);
        TaskManager::async($task);
        // 获取随机返回的通道名称:chinamobile | 22 | 33
        $actionId = $checkData['data']['action'];
        // 通过通道名称获取支付地址
        $url = 'http://' . $host . '?uuid=' . $uuid;
        $getPayUrl = (new Channel())->getPayUrl($actionId, $url);
        if ($getPayUrl['code'] != ReturnCode::SUCCESS) {
            return $this->error($getPayUrl['code'], $getPayUrl['msg']);
        }
        // 设置写入数据
        $action = $getPayUrl['data'];
        $setData = $this->params;
        $setData['create_time'] = intval($this->params['time']);
        $setData['pay_action'] = $action['action_id'];
        $request = $this->request()->getServerParams();
        $setData['ip'] = $request['remote_addr'];
        $setData['self_check'] = $action['self_check'];
        // 创建订单
        $res = $this->settlePaymentPrepareData($orderId, $setData, $action);
        if ($res['code'] != ReturnCode::SUCCESS) {
            return $this->error($res['code'], $res['msg']);
        }
//        return $this->success($res['code'], $res['msg'], $action['pay_url']);
        return $this->success($res['code'], $res['msg'], $url);

    }

    // 查询订单状态
    public function checkOrderStatus()
    {
        $checkParams = $this->checkRequestData($this->checkOrderStatusData);
        if ($checkParams['code'] != ReturnCode::SUCCESS) {
            return $this->error($checkParams['code'], $checkParams['msg']);
        }
        $params = $this->params;
        $sign = $params['sign'];
        unset($params['sign']);
        $merchant = (new MerchantCache())->getMerchant($params['mch_id']);
        if (!$merchant) {
            return $this->error(ReturnCode::CHECK_MERCHANT, ReturnCode::getReasonPhrase(ReturnCode::CHECK_MERCHANT));
        }
        $key = $merchant['app_secret'];
        $signature = Sign::getSign($key, $params);
        if ($sign != $signature) {
            return $this->error(ReturnCode::CHECK_SIGN, ReturnCode::getReasonPhrase(ReturnCode::CHECK_SIGN) . 'rely sign:' . $signature);
        }
        $ret = (new Order())->getMerchantOrderState($params['mch_id'], $params['trade_no']);
        if (!$ret) {
            return $this->error(ReturnCode::CHECK_ORDER, ReturnCode::getReasonPhrase(ReturnCode::CHECK_ORDER));
        }
        return $this->success(ReturnCode::SUCCESS, ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), $ret);
    }

    // 处理预备订单数据
    protected function settlePaymentPrepareData($orderId, $data, $action)
    {

        $insert_data = [];
        $insert_data['id'] = $orderId;
        $insert_data['merchant_id'] = $data['mch_id'];
        $insert_data['merchant_order'] = $data['trade_no'];
        $insert_data['money'] = $data['money'];
        $insert_data['product_id'] = $data['product_id'];
        $insert_data['product_name'] = $data['product_name'];
        $insert_data['pay_method'] = $data['pay_type'];
        $insert_data['notify_url'] = $data['notify_url'];
        $insert_data['return_url'] = $data['return_url'];
        $insert_data['create_time'] = date('Y-m-d H:i:s');
        $insert_data['attach_data'] = isset($data['ext_data']) ? $data['ext_data'] : '';
        $insert_data['merchant_post_data'] = json_encode($data);
        $insert_data['merchant_ip'] = $data['ip'];
        $insert_data['pay_action'] = isset($data['action_id']) ? $data['action_id'] : '';
        $insert_res = (new PaymentPrepare())->createPaymentPrepare($insert_data);
        if ($insert_res) {
            if ($data['self_check']) {
                $actionLog['order_id'] = $insert_data['id'];
                $actionLog['action_id'] = $action['action_id'];
                $actionLog['action_no'] = isset($action['action_no']) ? $action['action_no'] : '';
                $actionLog['action_type'] = isset($action['action_type']) ? $action['action_type'] : '';
                $actionLog['post_data'] = isset($action['post_data']) ? $action['post_data'] : '';
                $actionLog['action_url'] = isset($action['action_url']) ? $action['action_url'] : '';
                $actionLog['self_check'] = $action['self_check'];
                $actionLog['action_method'] = isset($action['action_method']) ? $action['action_method'] : '';
                (new PaymentActionLog())->CreateData($actionLog);
            }
            return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => $action['pay_url']];
        }
        return ['code' => ReturnCode::INSERT_DATA_PREPARE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::INSERT_DATA_PREPARE)];
    }


    /*
     * 交易接口数据验证
     * */
    protected function checkUnifiedorder($params)
    {
        // 验证传参
        $checkParams = $this->checkRequestData($this->unifiedorderData);
        if ($checkParams['code'] != ReturnCode::SUCCESS) {
            return ['code' => $checkParams['code'], 'msg' => $checkParams['msg']];
        }
        // 验证商户
        $merchant = (new MerchantCache())->getMerchant($params['mch_id']);
        if (!$merchant) {
            return ['code' => ReturnCode::CHECK_MERCHANT, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_MERCHANT)];
        }
        // 验证地址
        $params['notify_url'] = urldecode($params['notify_url']);
        if (!filter_var($params['notify_url'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
            return ['code' => ReturnCode::CHECK_NOTIFY_URL, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_NOTIFY_URL)];
        }
        // 验证sign
        $key = $merchant['app_secret'];
        $params['return_url'] = urldecode($params['return_url']);
        $sign = $params['sign'];
        unset($params['sign']);
        $signature = Sign::getSign($key, $params);
        if ($sign != $signature) {
            return ['code' => ReturnCode::CHECK_SIGN, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_SIGN) . 'rely sign:' . $signature];
        }
        // 验证订单
//        $checkOrder = (new Order())->checkMerchantOrder($params['mch_id'], $params['trade_no']);
//        if (!$checkOrder) {
//            return ['code' => ReturnCode::CHECK_ORDER, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ORDER)];
//        }
        // 获取商户在渠道下的费率
        $charge = (new Charge())->getCharge($params['mch_id'], $params['pay_type']);
        if (!$charge) {
            return ['code' => ReturnCode::CHECK_CHARGE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_CHARGE)];
        }
        // 验证商户->渠道是否开通
        if ($charge['state'] == 0) {
            return ['code' => ReturnCode::CHECK_CHARGE_STATE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_CHARGE_STATE)];
        }
        // 验证 费率和回扣是否 小于等于 商户充值余额
        if ($charge['charge'] != 0) {
            $chargeMoeny = bcmul($charge['charge'], $params['money']);
            $rebateMoeny = bcmul($charge['rebate'], $params['money']);
            $allChargeMoney = bcadd($chargeMoeny, $rebateMoeny);
            // 高精度比较两数.返回-1，0，1
            $checkMoney = bccomp($allChargeMoney, $merchant['balance']);
            if ($checkMoney > 0) {
                return ['code' => ReturnCode::CHARGE_GT_BALANCE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHARGE_GT_BALANCE)];
            }
        }
        /*
         * 验证交易额度
         * */
        // 单次额度限制
        if ($charge['money_max'] > 0 && $params['money'] > $charge['money_max']) {
            return ['code' => ReturnCode::CHECK_CHARGE_MAX_MONEY, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_CHARGE_MAX_MONEY)];
        }
        // 单日额度限制,获取商户渠道下交易额的缓存
        $channelDayMoney = (new TurnoverCache())->getChannelTunoverDay($params['mch_id'], $params['pay_type']);
        // 高精度想加减计算.bcadd+,bcsub-
        $checkMaxDay = bcsub(bcadd($channelDayMoney, $params['money']), $charge['money_max_day']);
        if ($charge['money_max_day'] > 0 && $checkMaxDay < 0) {
            return ['code' => ReturnCode::CHECK_CHARGE_MAX_DAY_MONEY, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_CHARGE_MAX_DAY_MONEY)];
        }
        $channelMonthMoney = (new TurnoverCache())->getChannelTunoverMonth($params['mch_id'], $params['pay_type']);
        $checkMaxMonth = bcsub(bcadd($params['money'], $channelMonthMoney), $charge['money_max_month']);
        if ($charge['money_max_month'] > 0 && $checkMaxMonth < 0) {
            return ['code' => ReturnCode::CHECK_CHARGE_MAX_MONTH_MONEY, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_CHARGE_MAX_MONTH_MONEY)];
        }
        // 获取渠道信息 payment_channel_action
        $acInfo = (new ChannelCache())->getAction($charge['pay_method']);
        $action = '';
        $day = strtotime(date('Ymd'));
        // 判断渠道是否开启
        if ($acInfo['state'] == 0) {
            return ['code' => ReturnCode::CHECK_ACTION_STATE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_STATE)];
        }
        // 判断渠道等级是否小于等于通道等级
        if ($acInfo['level'] > $merchant['level']) {
            return ['code' => ReturnCode::CHECK_ACTION_LEVEL, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_LEVEL)];
        }
        // 渠道费率 小于  通道费率 + 中介
        if ($acInfo['charge'] >= $charge['charge'] + $charge['rebate']) {
            return ['code' => ReturnCode::CHECK_ACTION_CHARGE, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::CHECK_ACTION_CHARGE)];
        }
        // 判断开放时间
        if ($acInfo['open_time'] != 0 || $acInfo['close_time'] != 0) {
            $stime = $day + $acInfo['open_time'];
            $etime = $day + $acInfo['close_time'];
            if ($stime > intval($params['time']) || intval($params['time']) > $etime) {
                return ['code' => ReturnCode::OPEN_TIME, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::OPEN_TIME) . '-' . $stime];
            }
        }
        // 随机返回一个通道
        $action_array = explode(',', $acInfo['action']);
        $action = $action_array[array_rand($action_array, 1)];
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => ['action' => $action]];
    }
}