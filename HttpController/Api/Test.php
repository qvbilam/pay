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
use App\Lib\Code\ReturnCode;
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
use App\Lib\Random\RandomStr;
use App\HttpController\Api\User;
use App\Model\PaymentPrepare;
use App\Model\PaymentChannelAction;
use App\HttpController\Pay\Wechat;
use App\Lib\Sign\Sign;

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
        $url = 'http://192.168.3.152:6346/api/withdraw';
        $params['money'] = 10000;       // 1分钱
        $params['app_tx_id'] = 'TX-ahqw23093--201910221929333510';
        $params['notify_url'] = 'http://127.0.0.1:9501';
        $params['app_key'] = 'kkpay';
        $params['bank_info'] = '回龙观支行';
        $params['bank_name'] = '333';
        $params['bank_user_name'] = '333';
        $params['card_no'] = '111';
        $params['card_id'] = '';
        $app_secret = '123456';
        /*
         *  money：金额
            app_tx_id：提现订单号
            notify_url：通知地址
            app_key：kkpay
            sign：加密
            bank_info:开户行名称
            bank_name:银行
            bank_user_name：用户姓名
            card_no
         * */
        // $sign = 'f8dd90b9016131ba8613218f28744239';
        $sign = Sign::getSign($app_secret, $params);
        $params['sign'] = $sign;
        print_r($params);
        $res = (new CurlService())->to($url)
            ->withData( $params )
            ->post();
        print_r($res);

        return $this->success(0,0,'');
//        $url = 'http://192.168.3.152/api/withdraw';
//        $params['money'] = 1;       // 1分钱
//        $params['app_tx_id'] = 'CN' . date('YmdHis') . rand(1000, 9999);
//        $params['notify_url'] = 'http://127.0.0.1';
//        $params['app_key'] = 'kkpay';
//        $app_secret = '123456';
//        $sign = Sign::getSign($app_secret, $params);
//        $params['sign'] = $sign;
//
//        $res = (new CurlService())->to($url)
//            ->withData( $params )
//            ->post();
//        print_r($res);
//        print_r($params);
//        return $this->success(0,0,$res);








//        $failNumber = 5;    // 默认失败次数
//        $failStatus = ['waiting'];
//        for ($i = 1;$i<$failNumber;$i++){
//            array_push($failStatus,$i);
//        }
//        return $this->success(0,0,$failStatus);
//        $data = (new PaymentPrepare())->getAllFailOrder();
//        foreach ($data as $val){
//            $action = (new PaymentChannelAction)->getActionOne($val['pay_method']);
//            $source = $action['source'];
//            switch ($source){
//                case 'wechat':
//                    $res = (new Wechat())->orderQuery($val['id']);
//                    if(empty($res) || $res['return_code'] != 'SUCCESS'){
//                        // 失败5次决定为失败
//                        if($val['result_desc'] != 5){
//                            $failStatus = ['waiting','bind_fail',1,2,3,4];
//                            $changeData = (!in_array($val['result_desc'],$failStatus))?['result_desc' => 1]:['result_desc' => $val['result_desc'] + 1];
//                            $changeRes = (new PaymentPrepare())->updateBataById($val['id'],$changeData);
//                        }
//                    }
//                    break;
//                case 'alipay':
//                    break;
//            }
//        }
//        return $this->success(0,0,$data);
//        /* 加减 */
//        $data = ['category_id' => $this->db->dec(2)];
//        // $data = ['category_id' => $this->db->inc(-2)];
//        $res = $this->db->where('id',2)->update('test',$data);
//        if(!$res){
//            return $this->error(-4,$this->db->getLastError());
//        }
//        return $this->success(0,'1ok');
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
        $scoure = 1;
        $data = (new User())->getPayConfig($merchant,$scoure);
        if($data['code'] != ReturnCode::SUCCESS){
            return $this->error($data['code'],$data['msg']);
        }
        return $this->success($data['code'],$data['msg'],$data['data']);
    }

    public function upload()
    {
        $params = $this->params;
        $request=  $this->request();
        $img_file = $request->getUploadedFile('img');//获取一个上传文件,返回的是一个\EasySwoole\Http\Message\UploadFile的对象
        //$data = $request->getUploadedFiles();
        $data = $request->getUploadedFile('file');
        print_r($data->getClientFilename());
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