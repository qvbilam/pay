<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-27
 * Time: 12:29
 */

namespace App\Cache;

use App\Model\PaymentChannelAction;

class Channel extends Base
{
    protected function getActionKey($action)
    {
        return $this->channel . $action;
    }

    public function getAction($action)
    {
        $key = $this->getActionKey($action);
        if (!$this->redis->exists($key)) {
            $row = (new PaymentChannelAction())->getActionOne($action);
            if ($row) {
                $this->redis->hmset($key, $row);
            }
        }

        return $this->redis->hgetall($key);
    }

    protected function getActionMoneyDayKey($action)
    {
        $day = date('Y-m-d', time());
        $key = $this->channel . 'money:' . $action . ':' . $day;

        return $key;
    }

    public function getActionMoneyDay($action)
    {
        $key = $this->getActionMoneyDayKey($action);

        if (!$this->redis->exists($key)) {
            return 0;
        }

        return $this->redis->get($key);
    }

    protected function getActionMoneyMonthKey($action)
    {
        $day = date('Y-m', time());
        $key = $this->channel . 'money:' . $action . ':' . $day;

        return $key;
    }


    public function getActionMoneyMonth($action){
        $key =$this->getActionMoneyMonthKey($action);

        if(!$this->redis->exists($key)){
            return 0;
        }

        return $this->redis->get($key);
    }

}