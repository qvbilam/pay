<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 10:45
 */

namespace App\Lib\Redis;

use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Config;
use \Yaconf;

class Redis
{
    use Singleton;

    public $redis = null;

    private function __construct()
    {
        /*判断有没有安装redis拓展*/
        if (!extension_loaded('redis')) {
            throw new \Exception('redis拓展不存在');
        }
        try {
            $this->redis = new \Redis();
            $link = $this->redis->connect(Yaconf::get('pay_api_redis.host'), Yaconf::get('pay_api_redis.port'), Yaconf::get('pay_api_redis.time_out'));
        } catch (\Exception $e) {
            /*
             * 因为$e->getmaessage会把详细信息输入上去
             * 这些数据是比较隐蔽的。我们不能让别人看到
             * throw new \Exception($e->getMessage());
            */
            throw new \Exception('redis服务异常');
        }
        if (!$link) {
            throw new \Exception('redis链接失败');
        }
    }

    public function __call($name, $arguments)
    {
        return $this->redis->$name(...$arguments);
    }
}