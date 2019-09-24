<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-09-23
 * Time: 17:16
 */

namespace App\Cache;

class PayHtml extends Base
{
    public function test($html)
    {
        $this->redis->setex("PayJack_payment_pay:url:123",300,$html);
    }

    public function getTest(){
        return $this->redis->get("PayJack_payment_pay:url:123");
    }

    /*
     * 获取支付页面
     * 先判断是否存在key
     * 获取状态
     * ttl 查看剩余时间
     * -1: 已过期 | 不存在;
     *  0: 订单处理中;
     *  1: ok
     * */
    public function getPayHtml($uuid)
    {
        $key = $this->getPayHtmlKey($uuid);
        $exists = $this->redis->exists($key);
        if (!$exists) {
            return ['code' => -1];
        }
        // 没有设置过期时间
        $ttl = $this->redis->ttl($key);
        if ($ttl == -1) {
            return ['code' => 0];
        }
        $data = $this->redis->hgetall($key);
        return ['code' => 1, 'data' => $data];
    }

    /*
     * 预创建支付页面
     * status = 0
     * */
    public function setPayHtml($uuid)
    {
        $key = $this->getPayHtmlKey($uuid);
        return $this->redis->hset($key, 'status', 0);
    }

    /*
     * 成功获取支付页面
     * 修改status = 1
     * 设置过期时间默认300秒
     * */
    public function updatePayHtml($uuid, $html)
    {
        $time = 300;
        $key = $this->getPayHtmlKey($uuid);
        $this->redis->hset($key, 'status', 1);
        $this->redis->hset($key, 'html', $html);
        // 设置过期时间
        return $this->redis->expire($key, $time);
    }

    /*
     * 直接删除
     * */
    public function delPayHtml($uuid)
    {
        $key = $this->getPayHtmlKey($uuid);
        return $this->redis->del($key);
    }

    // key
    protected function getPayHtmlKey($uuid)
    {
        return $this->pay_html . $uuid;
    }
}