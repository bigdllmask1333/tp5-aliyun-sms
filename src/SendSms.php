<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 2018/12/28
 * Time: 14:01
 */

namespace xuezhitech\aliyunsms;

use think\facade\Cache;
use think\facade\Log;
use xuezhitech\aliyunsms\SignatureHelper;

class SendSms
{
    protected $config = [
        'accessKeyId'       => '',
        'accessKeySecret'   => '',
        'security'          => false,
        'domain'            => 'dysmsapi.aliyuncs.com',
        'security'          => false,
        'expire'            => 60
    ];
    protected $params = [
        'RegionId'          => 'cn-hangzhou',
        'Action'            => 'SendSms',
        'Version'           => '2017-05-25',
        'PhoneNumbers'      => '',
        'SignName'          => '',
        'TemplateCode'      => '',
    ];

    protected $result = [
        'status'=>false,
        'msg'=>''
    ];

    public function __construct( $config=[],$params=[] ){
        $this->config = array_merge($this->config,$config);
        $this->params = array_merge($this->params,$params);
    }

    //发短信
    public function sendSms( $params=[] ){
        $this->params = array_merge($this->params,$params);
        //配置是否已设置
        if ( !$this->checkConfig() ){
            $this->result['msg'] = 'accessKeyId或accessKeySecret 不能为空';
            return  $this->result;
        }
        //参数是否已设置
        if ( !$this->checkParams() ){
            $this->result['msg'] = 'PhoneNumbers(手机号)/SignName(短信签名)/TemplateCode(短信模板Code) 不能为空';
            return  $this->result;
        }
        //手机是否已发送过
        if ( !$this->check() ) {
            $this->result['msg'] = '该手机号已发送过短信';
            return  $this->result;
        }
        $helper = new SignatureHelper();
        //此处可能会抛出异常，注意catch
        $content = $helper->request(
            $this->config['accessKeyId'],$this->config['accessKeySecret'],
            $this->config['domain'],
            $this->params,
            $this->config['security']
        );
        if ( !$content ){
            $this->result['msg'] = '短信发送失败';
            return  $this->result;
        }
        //缓存 1分钟
        $this->setCache();

        $this->result['msg']        = $content;
        $this->result['status']     = true;

        return $this->result;
    }
    //check
    private function check(){
        $key = 'sms_'.$this->params['PhoneNumbers'];
        $phone = Cache::get($key);
        if ( !empty($phone) ){
            return false;
        }
    }
    private function setCache(){
        $key = 'sms_'.$this->params['PhoneNumbers'];
        Cache::set($key,$this->params['PhoneNumbers'],$this->config['expire']);
    }
    private function checkConfig(){
        if ( empty($this->config['accessKeyId']) || empty($this->config['accessKeySecret'])){
            Log::write('参数accessKeyId或accessKeySecret为空！');
            return false;
        }
        return true;
    }
    private function checkParams(){
        if ( empty($this->params['PhoneNumbers']) || empty($this->params['SignName']) || empty($this->params['TemplateCode'])){
            Log::write('参数PhoneNumbers或SignName或TemplateCode为空！');
            return false;
        }
        return true;
    }
}