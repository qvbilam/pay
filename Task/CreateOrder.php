<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-09-23
 * Time: 12:45
 */

namespace App\Task;

use EasySwoole\EasySwoole\Swoole\Task\AbstractAsyncTask;
use \Ixudra\Curl\CurlService;
use App\HttpController\Pay\AliPay;
use App\Model\PaymentPrepare;
use App\Model\PaymentActionLog;
use App\Cache\PayHtml;

class CreateOrder extends AbstractAsyncTask
{
    /*
     * 先预创建订单
     * $tastData['mobile']
     * $tastData['money']
     * $tastData['orderId'] 订单Id,PaymentPrepare表 id;PaymentActionLog表 order_id
     * */
    public function run($taskData, $taskId, $fromWorkerId, $flags = null)
    {
        echo 'task start' . PHP_EOL;
        // 订单id的判断
        if (!isset($taskData['orderId'])) {
            return ['code' => -1];
        }
        $orderId = $taskData['orderId'];
        $returnData = ['orderId' => $orderId];
        // uuid的判断
        if(!isset($taskData['uuid'])){
            return ['code' => 0, 'msg' => 'Not Getting Uuid', 'data' => $returnData];
        }
        $uuid = $taskData['uuid'];
        $returnData['uuid'] = $uuid;
        // 交易额的判断
        if (!isset($taskData['money'])) {
            return ['code' => 0, 'msg' => 'Not Getting Money', 'data' => $returnData];
        }
        $money = $taskData['money'];
        if (!isset($taskData['mobile'])) {
            return ['code' => 0, 'msg' => 'Not Getting Mobile', 'data' => $returnData];
        }
        $mobile = $taskData['mobile'];
        // 预创建的页面缓存.
        echo 'setOrder' . PHP_EOL;
        $this->setOrder($uuid);
        $pay_method = 301;
        $curl = new CurlService();
        $url = "http://wap.zj.10086.cn/wappay/goPayComponent.do";
        $data = [];
        $data['hiddenOtherAmount'] = $money;
        $data['czMobile'] = $mobile;
        $data['proCode'] = '';
        $data['chargeSource'] = 2;
        $data['type'] = '';
        $content = $curl->to($url)
            ->withData($data)
            ->post();
        $req_data = [];
        $re = preg_match('/type="hidden" name="xml" value="(.*?)"/i', $content, $matchs);
        if ($re >= 1) {
            $req_data['xml'] = $matchs[1];
        }
        $re = preg_match('/type="hidden" name="charset" value="(.*?)"/i', $content, $matchs);
        if ($re >= 1) {
            $req_data['charset'] = $matchs[1];
        }
        $re = preg_match('/name="enctype" value="(.*?)"/i', $content, $matchs);
        if ($re >= 1) {
            $req_data['enctype'] = $matchs[1];
        }
        $url = 'https://pay-web.zj.chinamobile.com/UnifiedPay';
        $content = $curl->to($url)
            ->withData($req_data)
            ->post();
        $req_data = [];
        $re = preg_match("/pub = eval(.*?);/i", $content, $matchs);
        if ($re >= 1) {
            $t = explode("'", $matchs[1]);
            $req_data['pub'] = json_decode($t[3], true);;
        }

        $re = preg_match("/busi = eval(.*?);/i", $content, $matchs);
        if ($re >= 1) {
            $t = explode("'", $matchs[1]);
            $req_data['busi'] = json_decode($t[3], true);;
        }
        if (!isset($req_data['busi'])) {
            return ['code' => 0, 'msg' => 'Not getting Channel', 'data' => $returnData];
        }
        if ($pay_method == '301') {
            $ret = (new AliPay())->alipay($req_data);
            $returnData['data'] = $ret;
        } elseif ($pay_method == '201') {
            // empty
        }
        return ['code' => 1, 'msg' => 'ok', 'data' => $returnData];
    }

    /*
     * 任务完成回调
     * 设置redis 过期时间,修改订单状态
     * */
    public function finish($result, $task_id)
    {
        // 为获取到订单id
        if ($result['code'] == -1) {
            echo $result['code'] . PHP_EOL;
        }
        // 失败
        if ($result['code'] == 0) {
            $this->DelOrder($result['data']['orderId'],$result['data']['uuid']);
            // todo 日志？
        }
        // 成功
        if($result['code'] == 1){
            $this->updateOrder($result['data']['uuid'],$result['data']['data']['content']);
            // todo 日志？
        }
        echo 'finnish' . PHP_EOL;
    }

    /*
     * 预创建订单
     * */
    protected function setOrder($uuid)
    {
        $cache = new PayHtml();
        return $cache->setPayHtml($uuid);
    }

    /*
     * 成功修改订单状态
     * */
    protected function updateOrder($uuid,$html)
    {
        $cache = new PayHtml();
        return $cache->updatePayHtml($uuid,$html);
    }

    /*
     * 失败删除订单
     * */
    protected function DelOrder($orderId,$uuid)
    {
        // 删除数据库中添加的数据
        $actionLog =  new PaymentActionLog();
        $existLog = $actionLog->getDataById($orderId);
        if($existLog){
            $actionLog->delOrderById($orderId);
        }
        $paymentPrepare = new PaymentPrepare();
        $existPaymentPrepare = $paymentPrepare->getDataById($orderId);
        if($existPaymentPrepare){
            $paymentPrepare->delOrderById($orderId);
        }
        // 删除redis
        $cache = new PayHtml();
        return $cache->delPayHtml($uuid);
    }

}