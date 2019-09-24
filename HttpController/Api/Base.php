<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 10:17
 */

namespace App\HttpController\Api;

use EasySwoole\Http\AbstractInterface\Controller;
use App\Lib\Code\ReturnCode;

class Base extends Controller
{
    // 请求的参数数据
    public $params = [];

    public function index()
    {

    }

    public function onRequest(?string $action): ?bool
    {
        // 如果浏览器没有传uid则任务没有权限。返回无权限
        // $uid = $this->request()->getRequestParam('uid');
        // if(!$uid){
        //     $this->writeJson(201,'无权限');
        //     return false;
        // }
        // 通过验证执行下面逻辑
        $this->getParmas();
        return true;
    }

    /*获取参数值*/
    public function getParmas()
    {
        $params = $this->request()->getRequestParam();
        $this->params = $params;
    }

    public function success($statusCode = 200, $msg = null, $result = null)
    {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code" => $statusCode,
                "msg" => $msg,
                "data" => $result
            );
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        } else {
            return false;
        }
    }

    public function error($statusCode = 0, $msg = null)
    {
        if (!$this->response()->isEndResponse()) {
            $data = Array(
                "code" => $statusCode,
                "msg" => $msg
            );
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        } else {
            return false;
        }
    }

    /*
     * 验证必传参数是否存在
     * $function : 匹配的成员属性
     * */
    protected function checkRequestData($data)
    {
        foreach ($data as $val) {
            $res = array_key_exists($val, $this->params);
            if (!$res) {
                // 返回msg
                // return 'empty ' . $val;
                $code = ReturnCode::getEmptyCode($val);
                $msg = ReturnCode::getReasonPhrase($code);
                return ['code' => $code, 'msg' => $msg];
            }
        }
        // 通过验证.
        $code = ReturnCode::SUCCESS;
        return ['code' => $code];
    }
}

