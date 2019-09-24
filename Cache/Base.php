<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 15:01
 */

namespace App\Cache;

use App\HttpController\Api\Base as BaseController;
use EasySwoole\Component\Di;

class Base extends BaseController
{
    protected $redis;
    protected $db;
    protected $prefix ='';
    protected $redis_no ='';
    protected $merchant ='';
    protected $charge ='';
    protected $channel ='';

    public function __construct()
    {
        // DatabaseDi
        $db = Di::getInstance()->get("MYSQL");
        if ($db instanceof \MysqliDb) {
            $this->db = $db;
        } else {
            $this->db = new \MysqliDb(\Yaconf::get('pay_api_mysql'));
        }
        // RedisDi
        $this->redis = Di::getInstance()->get("REDIS");
        // RedisConig
        $this->prefix = \Yaconf::get('pay_api_redis.redis_prefix');
        $this->merchant = $this->prefix.'merchant:';
        $this->charge = $this->prefix.'charge:';
        $this->channel = $this->prefix.'channel:';
        $this->redis_no = $this->prefix.'no:';
        $this->pay_html = $this->prefix . 'html:';
        // turnover 交易额
        /*
         * 商户渠道额度下的统计
         * 使用时后面需要拼接上 _$type:date ($type=month | day)
         * 月: 前缀_turnover_channel:401_month:2019-09
         * 日: 前缀_turnover_channel:401_day:2019-09-17
         * */
        $this->channel_turnover = $this->prefix . 'turnover_channel:';
        /*
         * 商户总额度统计
         * 使用时后面需要拼接上 _$type:date ($type=month | day)
         * 月：前缀_turnover_month:2019-06
         * 日：前缀_turnover_day:2019-06-17
         * */
        $this->all_turnover =$this->prefix . 'turnover';
        parent::__construct();
    }
}