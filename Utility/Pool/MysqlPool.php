<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 11:04
 */

namespace App\Utility\Pool;
use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\Mysqli\Config;
use EasySwoole\Mysqli\Mysqli;

class MysqlPool extends AbstractPool
{
    protected function createObject()
    {
        //当连接池第一次获取连接时,会调用该方法
        //我们需要在该方法中创建连接
        //返回一个对象实例
        //必须要返回一个实现了AbstractPoolObject接口的对象
        $conf = \Yaconf::get('pay_api_mysql');
        $dbConf = new Config($conf);
        return new MysqlObject($dbConf);
        // TODO: Implement createObject() method.
    }
}