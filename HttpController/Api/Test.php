<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 10:23
 */

namespace App\HttpController\Api;

use App\Cache\PayHtml;
use app\command\Task;
use App\Task\CrateOrder;
use EasySwoole\Component\Di;
use App\Model\Test as TestModel;
use App\Model\Merchant as MerchantModel;
use EasySwoole\Pay\Pay;
use App\Cache\Turnover as TurnoverCache;
use App\Model\Turnover as TurnoverModel;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use \Ixudra\Curl\CurlService;
use function PHPSTORM_META\type;


class Test extends Base
{
    protected $redis;
    protected $db;
    protected $tableName = 'merchant';

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

    public function index()
    {
        //$res = (new TestModel())->test();
        $mch_id = 'qvbilam_test';
        // $res =MerchantModel::where('id',$mch_id)->getOne();
        $res = $this->db->where('id', $mch_id)->getOne($this->tableName);
        return $this->writeJson(0, 'ok', $res);
    }

    public function useMysql()
    {
        $res = $this->db->where('id', 'qvbilam_test', '=')->get($this->tableName, 1);
        return $this->success(0, 'ok', $res);
    }

    public function useRedis()
    {
        $this->redis->set("qvbilam", "123");
        $res = $this->redis->get("qvbilam");
        return $this->success(0, 'ok', $res);
    }



    public function hello()
    {
        $merchant = 'qvbilam_test';
        $channel = 301;
        $tableName = 'merchant_money_log';
        $time_month = date("Y-m");
        $result = $this->db
            ->groupBy('merchant_id,channel_id')
            ->where('time', $time_month,">=")
            ->where('merchant_id', $merchant)
            ->where('channel_id', $channel)
            ->getOne($tableName, "sum(money) as money,merchant_id,channel_id");
//            ->getOne('merchant_money_log','sum(money) as money,merchant_id,channel_id');
//        $result = $this->db->getLastQuery();
        return $this->success(0,0,$result);
    }

    public function test()
    {
        $pay_method = 301;
        $curl = new CurlService();
        $mobile = 13675887695;
        $price = 10;
        $data = [];
        $url = 'http://wap.zj.10086.cn/wappay/goPayComponent.do';
        $data['hiddenOtherAmount'] =$price;
        $data['czMobile'] =$mobile;
        $data['proCode'] ='';
        $data['chargeSource'] =2;
        $data['type'] ='';
        $content = $curl->to($url)
            ->withData($data)
            ->post();
        $req_data = [];
        $re = preg_match('/type="hidden" name="xml" value="(.*?)"/i', $content, $matchs);
        if ($re >= 1) {
            $req_data['xml'] = $matchs[1];
        }
        $re = preg_match('/type="hidden" name="charset" value="(.*?)"/i', $content, $matchs);
        if ($re >= 1) {
            $req_data['charset'] = $matchs[1];
        }
        $re = preg_match('/name="enctype" value="(.*?)"/i', $content, $matchs);
        if ($re >= 1) {
            $req_data['enctype'] = $matchs[1];
        }
        $url = 'https://pay-web.zj.chinamobile.com/UnifiedPay';
        $content = $curl->to($url)
            ->withData($req_data)
            ->post();
        $req_data = [];
        $re = preg_match("/pub = eval(.*?);/i", $content, $matchs);
        if ($re >= 1) {
            $t = explode("'", $matchs[1]);
            $req_data['pub'] = json_decode($t[3], true);;
        }

        $re = preg_match("/busi = eval(.*?);/i", $content, $matchs);
        if ($re >= 1) {
            $t = explode("'", $matchs[1]);
            $req_data['busi'] = json_decode($t[3], true);;
        }
        if (!isset($req_data['busi'])) {
            return [
                'code' => -1,
                'msg' => 'no get channel'
            ];
        }
        if ($pay_method == '301') {
            $ret = $this->alipay($req_data);
        }
        (new PayHtml())->test($ret['content']);
        return $this->response()->write($ret['content']);
    }


    function alipay($req_data)
    {
        $payinfo="BankId=".$req_data['busi']['BankId']."&PlatId=".$req_data['pub']['OriginId']."&AccountType=".$req_data['busi']['AccountType'].
            "&AccountCode=".$req_data['busi']['AccountCode']."&AccountName=".$req_data['busi']['AccountName']."&Upg_OrderId=".$req_data['busi']['upgOrderId'].
            "&PayItemType=".$req_data['busi']['PayItemType']."&PayAmount=".$req_data['busi']['PayAmount']."&ProviderId=".$req_data['busi']['ProviderId'].
            "&ProviderName=".$req_data['busi']['ProviderName']."&TransactionId=".$req_data['pub']['TransactionId']."&RegionId=".$req_data['pub']['RegionId'];
        $url ='https://pay-web.zj.chinamobile.com/business/com.asiainfo.aipay.web.DoPayAction?action=doAliPay';

        $curl = new CurlService();
        $r = [];
        $r['content'] = $curl->to($url)
            ->withData($payinfo)
            ->post();
        if($r['content']){
            $r['action_no'] =$req_data['pub']['TransactionId'];
            $r['action_type'] ='zhejiang';
            $r['post_data'] =$payinfo;
            $r['action_url'] =$url;
            $r['action_method'] ='POST';
        }

        return $r;
    }

}