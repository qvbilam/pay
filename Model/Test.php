<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 12:21
 */

namespace App\Model;

class Test extends Base
{
    protected $tablename = 'test';

    public function test()
    {
        $res = $this->db->where('id',1,'=')->get($this->tablename,1);
        return $res;
    }
}