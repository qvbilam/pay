<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-09-20
 * Time: 10:50
 */

namespace App\Channel;
use \Ixudra\Curl\CurlService;
use App\Lib\Code\ReturnCode;

class ChinaMobileAction
{
    public function recharge($pay_method,$money)
    {
        // 获取手机号
        $mobile ='13675887695';
        if(!$mobile){
            // 获取手机归属地
            $curl = new CurlService();
            $mobile = '13501294164';
            $url = "https://chongzhi.jd.com/json/order/search_searchPhone.action";
            $contentJson = $curl->to($url)
                ->withData(['mobile' => $mobile])
                ->get();
            $content = iconv('gb2312','utf-8',$contentJson);
            $contentArray = json_decode($content,true);
            if(empty($contentArray['areaName'])){
                return [
                    'code' => ReturnCode::INVALID,
                    'msg' => ReturnCode::getReasonPhrase(ReturnCode::INVALID)
                ];
            }
        }
        // 手机归属地
        $areaName = $contentArray['areaName'];
        switch ($areaName){
            case CMConfig::CITY_ZJ:
                $ret =ChinaMobileZJ::instance()->recharge($pay_method,$mobile,$money);
                break;
            case CMConfig::CITY_JS:

                break;
            case CMConfig::CITY_HUB:
                $ret =ChinaMobileHuBei::instance()->recharge($pay_method,$mobile,$money);
                break;
            case CMConfig::CITY_SD:

                break;
            case CMConfig::CITY_JX:

                break;
            case CMConfig::CITY_HEB:
                $ret =ChinaMobileHeBei::instance()->recharge($pay_method,$mobile,$money);
                break;
            case CMConfig::CITY_GD:

                break;
            case CMConfig::CITY_SX:

                break;
            case CMConfig::CITY_CQ:

                break;
        }

        return $ret;
    }
}