<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/27
 * Time: 下午5:39
 */

namespace app\user\controller;


use app\user\service\WxService;
use cmf\controller\UserBaseController;
use think\Db;

class CoinLogController extends UserBaseController
{
    function _initialize()
    {
        parent::_initialize();
    }

    /*
     * 我的钱包
     */
    public function index(){
        $uid = session('user.id');
        $coinLog = Db::name('coin_log')->where(['uid'=>$uid])->order('create_time desc')->paginate(5);
        $this->assign('list', $coinLog);
        return $this->fetch();
    }

    /*
     * 提现
     */
    public function withdraw_test(){
        $uid = session('user.id');
        $userInfo = Db::name('user')->find($uid);
        if($userInfo['balance'] <= 0){
            $this->error('没有可提现金额');
        }
        $wxInfo = Db::name('third_party_user')->where(['user_id'=>$uid])->find();
        if($wxInfo['openid']){
            $openId = $wxInfo['openid'];
        }else{
            $this->error('未绑定微信');
        }
        $sendName = "钱多呀";
        $outTradeNo = "QDY".$uid.'OT'.date("YmdHis",time()).mt_rand(100,999);
        $wishing = "欢迎参与活动，请领取红包";
        $actName = "挑战任务赢取现金红包";
        $ret = WxService::cashRedBag($openId,'1',$sendName,$outTradeNo,$wishing,$actName);
        p($ret,0);
        if($ret){
            $this->success('提现成功');
        }else{
            $this->error($ret);
        }
    }
}