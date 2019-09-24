<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 12:18
 */
namespace App\Model;

use App\HttpController\Api\Base as BaseController;
use EasySwoole\Component\Di;

class Base extends BaseController
{
    protected $redis;
    protected $db;

    public function __construct()
    {
        // Db
        $db = Di::getInstance()->get("MYSQL");
        if ($db instanceof \MysqliDb) {
            $this->db = $db;
        } else {
            $this->db = new \MysqliDb(\Yaconf::get('pay_api_mysql'));
        }
        // Redis
        $this->redis = Di::getInstance()->get("REDIS");
        parent::__construct();
    }
}