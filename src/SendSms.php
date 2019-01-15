<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2018/12/28
 * Time: 14:01
 */

namespace xuezhitech\aliyunsms;

use think\facade\Cache;
use xuezhitech\aliyunsms\SignatureHelper;

class SendSms
{
    protected $config = [
        'accessKeyId'       => '',
        'accessKeySecret'   => '',
        'security'          => false,
        'domain'            => 'dysmsapi.aliyuncs.com',
        'security'          => false,
        'expire'            => 60,
        'SignName'          => '',
        'TemplateCode'      => '',
        'RegionId'          => 'cn-hangzhou',
        'Action'            => 'SendSms',
        'Version'           => '2017-05-25',
    ];

    protected $result = [
        'status'=>false,
        'msg'=>''
    ];

    public function __construct( $config=[] ){
        $this->config = array_merge($this->config,$config);
    }

    //发短信
    public function sendSms( $phone ){

        $params = [];

        //配置是否已设置
        if ( !$this->checkConfig() ){
            return  $this->result;
        }
        //参数是否已设置
        if ( empty($phone) ){
            $this->result['status'] = false;
            $this->result['msg'] = 'PhoneNumbers(手机号)不能为空';
            return  $this->result;
        }
        //手机是否已发送过
        if ( $this->check($phone) ) {
            $this->result['status'] = false;
            $this->result['msg'] = '该手机号已发送过短信';
            return  $this->result;
        }
        $params['TemplateParam'] = ["code" => $this->getRandomString(6)];
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        //拼params
        $params['PhoneNumbers'] = $phone;
        $params['SignName'] = $this->config['SignName'];
        $params['TemplateCode'] = $this->config['TemplateCode'];
        $params['RegionId'] = $this->config['RegionId'];
        $params['Action'] = $this->config['Action'];
        $params['Version'] = $this->config['Version'];

        $helper = new SignatureHelper();
        //此处可能会抛出异常，注意catch
        $content = $helper->request(
            $this->config['accessKeyId'],$this->config['accessKeySecret'],
            $this->config['domain'],
            $params,
            $this->config['security']
        );

        if ( !$content ){
            $this->result['status'] = false;
            $this->result['msg'] = '短信发送失败';
            return  $this->result;
        }

        //缓存 1分钟
        $this->setCache($phone);

        $this->result['msg']        = $content;
        $this->result['status']     = true;

        return $this->result;
    }

    private function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "0123456789";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    //check
    private function check($phone){
        $key = 'sms_'.$phone;
        $is_send = Cache::get($key);
        if ( $is_send ){
            return true;
        }else{
            return false;
        }
    }

    private function setCache($phone){
        $key = 'sms_'.$phone;
        Cache::set($key,$phone,$this->config['expire']);
    }

    private function checkConfig(){
        $this->result['status'] = true;
        foreach ( $this->config as $key=>$value){
            if ( !isset($value) ){
                $this->result['msg'] = "{$key}不能为空!";
                $this->result['status'] = false;
                break;
            }
        }
        return $this->result['status'];
    }
}