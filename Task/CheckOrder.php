<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-10-17
 * Time: 12:50
 */

namespace App\Task;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use App\Model\PaymentPrepare;
use App\Model\PaymentChannelAction;
use App\HttpController\Pay\Wechat;


class CheckOrder extends AbstractCronTask
{
    public static function getRule(): string
    {
        // TODO: Implement getRule() method.
        // 定时周期 （每5分钟一次）
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        // TODO: Implement getTaskName() method.
        // 定时任务名称
        return 'checkNotSuccessOrder';
    }

    static function run(\swoole_server $server, int $taskId, int $fromWorkerId, $flags = null)
    {
        // 定时任务处理逻辑：五分钟查询一次订单,失败x次就算对该订单停止.
        $failNumber = 5;    // 默认失败次数
        $failStatus = ['waiting'];
        for ($i = 1; $i < $failNumber; $i++) {
            array_push($failStatus, $i);
        }
        $data = (new PaymentPrepare())->getAllFailOrder();
        foreach ($data as $val) {
            $action = (new PaymentChannelAction)->getActionOne($val['pay_method']);
            $source = $action['source'];
            switch ($source) {
                case 'wechat':
                    if ($val['result_desc'] != 5) {
                        $res = (new Wechat())->orderQuery($val['id']);
                        if (empty($res) || $res['return_code'] != 'SUCCESS') {
                            echo $val['id'] . '失败次数+1' . PHP_EOL;
                            // 如果不是指定错误字段,则设置状态为1.即失败次数1.
                            $changeData = (!in_array($val['result_desc'], $failStatus)) ? ['result_desc' => 1] : ['result_desc' => $val['result_desc'] + 1];
                            (new PaymentPrepare())->updateBataById($val['id'], $changeData);
                        }
                    } else {
                        // 说明系统决定了失败.
                        echo $val['id'] . '已经失败' . PHP_EOL;
                        $changeData = ['result_desc' => 'bind_fail'];
                        (new PaymentPrepare())->updateBataById($val['id'], $changeData);
                        // todo 退款?
                    }
                    break;
                case
                'alipay':
                    // echo $val['id'] . '--------' . 'alipay' . PHP_EOL;
                    break;
            }
        }
    }
}