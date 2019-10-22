<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-10-08
 * Time: 16:18
 */

namespace App\Model;

use App\Lib\Code\ReturnCode;

class MerchantConfig extends Base
{
    protected $tablename = 'merchant_config';

    public function getOneData($merchant, $source)
    {
        $data = $this->db
            ->where('merchant_id', $merchant)
            ->where('source', $source)
            ->getOne($this->tablename);
        if(!$data){
            return ['code' => ReturnCode::DATABASE_EMPTY,'msg' => ReturnCode::getReasonPhrase(ReturnCode::DATABASE_EMPTY)];
        }
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => $data];
    }

    public function insertData($data)
    {
        /*
         * 判断
         * */
        $sqlData = $this->db
            ->where('merchant_id', $data['merchant_id'])
            ->where('source', $data['source'])
            ->getOne($this->tablename);
        if (!$sqlData) {
            $insert = $this->db->insert($this->tablename, $data);
            if (!$insert) {
                $error = 'insert failed: ' . $this->db->getLastError();
                return ['code' => ReturnCode::DATABASE_INSERT_ERROR, 'msg' => $error];
            }
            $sqlData = $data;
        } else {
            $update = $this->db
                ->where('merchant_id', $data['merchant_id'])
                ->where('source', $data['source'])
                ->update($this->tablename, $data);
            if (!$update) {
                $error = 'update failed: ' . $this->db->getLastError();
                return ['code' => ReturnCode::DATABASE_UPDATE_ERROR, 'msg' => $error];
            }
            $sqlData = $data;
        }
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => $sqlData];
    }
}