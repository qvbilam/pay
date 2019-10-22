<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-10-08
 * Time: 15:54
 */

namespace App\HttpController\Api;

use App\Lib\Random\RandomStr;
use App\Lib\Code\ReturnCode;
use App\Model\MerchantConfig;

class User extends Base
{
    /*
     * 必传参数
     * */
    protected $fields = [
        'merchant_id',
        'app_id',
        'mch_id',
        'pay_api_key'
    ];

    /*
     * 处理用户上传的证书
     * */
    public function uploadCert()
    {
        $params = $this->params;
        if (empty($params['merchant']) || empty($params['source'])) {
            return $this->error(ReturnCode::UPLOAD_EMPTY_MERCHANT_OR_SOUCRE, ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_EMPTY_MERCHANT_OR_SOUCRE));
        }
        $merchant = $params['merchant'];
        $source = $params['source'];
        $request = $this->request();
        $file = $request->getUploadedFile('file');
        switch ($source) {
            case 0:
                break;
            case 1:
                $res = $this->uploadWeChatCert($file, $merchant);
                break;
            case 2:
                $res = $this->uploadAliPayCert($file, $merchant);
                break;
        }
        if ($res['code'] != ReturnCode::UPLOAD_CERT_SUCCESS) {
            return $this->error($res['code'], $res['msg']);
        }
        return $this->success(ReturnCode::UPLOAD_CERT_SUCCESS, ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_SUCCESS), $res['data']);
    }

    /*
     * 对用户支付配置的数据进行加密
     * */
    public function setPayConfig()
    {
        // 判断是否包含必要传参
        $checkFields = $this->encryptParams($this->params);
        if ($checkFields['code'] != ReturnCode::SUCCESS) {
            return $this->error($checkFields['code'], $checkFields['msg']);
        }
        $params = $checkFields['data'];
        // 插入数据
        $insert = (new MerchantConfig())->insertData($params);
        if ($insert['code'] != ReturnCode::SUCCESS) {
            return $this->error($insert['code'], $insert['msg']);
        }
        return $this->success(ReturnCode::UPLOAD_CERT_SUCCESS, $insert['msg'], ['data' => $insert['data']]);
    }

    /*
     * 获取用户支付配置,并解密
     * */
    public function getPayConfig($merchant='qvbilam_test', $source=1)
    {
        if (empty($source) || empty($merchant)) {
            return ['code' => ReturnCode::INVALID, 'msg' => 'params must have merchant and source'];
        }
        $merchantConfig = (new MerchantConfig())->getOneData($merchant, $source);
        if ($merchantConfig['code'] != ReturnCode::SUCCESS) {
            return ['code' => $merchantConfig['code'], 'msg' => $merchantConfig['msg']];
        }
        foreach ($merchantConfig['data'] as $key => &$val) {
            if ($key == 'mch_id' || $key == 'app_id' || $key == 'pay_api_key') {
                $val = RandomStr::decryptMerchant($val, $merchant);
            }
        }
        return $this->success($merchantConfig['code'],$merchantConfig['msg'],$merchantConfig['data']);
        // return ['code' => $merchantConfig['code'], 'msg' => $merchantConfig['msg'], 'data' => $merchantConfig['data']];
    }

    // 判断是否包含必要传参
    protected function encryptParams($params)
    {
        foreach ($this->fields as $val) {
            $check = array_key_exists($val, $params);
            if (!$check) {
                return ['code' => ReturnCode::INVALID, 'msg' => $val . ' is not null'];
            }
        }
        $params['app_id'] = RandomStr::encryptMerchant($params['app_id'], $params['merchant_id']);
        $params['mch_id'] = RandomStr::encryptMerchant($params['mch_id'], $params['merchant_id']);
        $params['pay_api_key'] = RandomStr::encryptMerchant($params['pay_api_key'], $params['merchant_id']);
        $params['source'] = empty($params['source']) ? 0 : $params['source'];
        $params['return_url'] = empty($params['return_url']) ? 'http://127.0.0.1/' : $params['return_url'];
        return ['code' => ReturnCode::SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::SUCCESS), 'data' => $params];
    }

    // 微信证书上传
    protected function uploadWeChatCert($file, $merchant)
    {
        $legalFileNames = ['apiclient_key.pem', 'apiclient_cert.pem'];
        $suffix = $file->getClientFilename();
        if (!in_array($suffix, $legalFileNames)) {
            return ['code' => ReturnCode::UPLOAD_CERT_TYPE_ERROR, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_TYPE_ERROR)];
        }
        // $path = "/Users/qvbilam/data/" . $merchant . '/WeChat/';
        $path = \Yaconf::get('pay_api_cert_path.cery_path') . $merchant . '/WeChat/';
        if (!is_dir($path)) {
            $res = mkdir($path, 0777, true);
            if (!$res) {
                return ['code' => ReturnCode::UPLOAD_CERT_MKDIR_ERROR, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_MKDIR_ERROR)];
            }
        }
        $newFile = $path . $suffix;
        $flag = $file->moveTo($newFile);
        if (!$flag) {
            return ['code' => ReturnCode::UPLOAD_CERT_MOVE_ERROR, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_MOVE_ERROR)];
        }
        return ['code' => ReturnCode::UPLOAD_CERT_SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_SUCCESS), 'data' => ['url' => 'WeChat/' . $suffix]];
    }

    // 支付宝证书上传
    protected function uploadAliPayCert($file, $merchant)
    {
        $suffix = $file->getClientFilename();
        $path = \Yaconf::get('pay_api_cert_path.cery_path') . $merchant . '/AliPay/';
        if (!is_dir($path)) {
            $res = mkdir($path, 0777, true);
            if (!$res) {
                return ['code' => ReturnCode::UPLOAD_CERT_MKDIR_ERROR, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_MKDIR_ERROR)];
            }
        }
        $newFile = $path . $suffix;
        $flag = $file->moveTo($newFile);
        if (!$flag) {
            return ['code' => ReturnCode::UPLOAD_CERT_MOVE_ERROR, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_MOVE_ERROR)];
        }
        return ['code' => ReturnCode::UPLOAD_CERT_SUCCESS, 'msg' => ReturnCode::getReasonPhrase(ReturnCode::UPLOAD_CERT_SUCCESS), 'data' => ['url' => 'AliPay/' . $suffix]];
    }
}