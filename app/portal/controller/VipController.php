<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class VipController extends HomeBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->checkUserLogin();
    }
    public function index()
    {
	$uid = cmf_get_current_user_id();
	$uInfo = Db::name('user')->find($uid);
	$this->assign('user_info',$uInfo);
	return $this->fetch();
    }
    public function topup(){
	require_once "../lib/WxPay.Api.php";
	require_once "WxPay.JsApiPay.php";
	require_once "WxPay.Config.php";
	require_once 'log.php';
	$taskSettings  = cmf_get_option('task_settings');
	$vipFee = $taskSettings['vip']*100;
	//初始化日志
	$logHandler= new \CLogFileHandler("../logs/".date('Y-m-d').'.log');
	$log = \Log::Init($logHandler, 15);


	//①、获取用户openid
	try{
	    $tools = new \JsApiPay();
	    $uid = cmf_get_current_user_id();
	    $openId =Db::name('third_party_user')->where('user_id',$uid)->value('openid'); 

	    //②、统一下单
	    $input = new \WxPayUnifiedOrder();
	    $input->SetBody("VIP");
	    $input->SetAttach("VIP");
	    $input->SetOut_trade_no("sdkphp".date("YmdHis"));
	    $input->SetTotal_fee($vipFee);
	    $input->SetTime_start(date("YmdHis"));
	    $input->SetTime_expire(date("YmdHis", time() + 600));
	    $input->SetGoods_tag("test");
	    $input->SetNotify_url("http://paysdk.weixin.qq.com/notify.php");
	    $input->SetTrade_type("JSAPI");
	    $input->SetOpenid($openId);
	    $config = new \WxPayConfig();
	    $order = \WxPayApi::unifiedOrder($config, $input);
	    $jsApiParameters = $tools->GetJsApiParameters($order);

	    //获取共享收货地址js函数参数
	    $editAddress = $tools->GetEditAddressParameters();
	} catch(Exception $e) {
	    Log::ERROR(json_encode($e));
	}
	$this->assign('jsApiParameters',$jsApiParameters);
	$this->assign('vip_fee',$vipFee);
	$this->assign('editAddress',$editAddress);
	return $this->fetch();
    }
    public function ajaxVip(){
	$uid = cmf_get_current_user_id();
	$ret = Db::name('user')->where(['id'=>$uid])->setField('is_vip',1);
	pr($uid,1);
    }
}
