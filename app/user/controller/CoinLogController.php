<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/27
 * Time: 下午5:39
 */

namespace app\user\controller;


use app\admin\service\CoinLogService;
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
    public function withdraw(){
        $uid = session('user.id');
        $userInfo = Db::name('user')->find($uid);
        if($userInfo['balance'] <= 1){
            $this->error('提现金额必须大于1的整数');
        }
        if(Db::name('coin_log')->where(['uid'=>$uid,'type'=>'withdraw','create_time'=>['>',strtotime(date('Y-m-d 00:00:00',time()))]])->find()){
            $this->error('一天只能提现一次');
        }
        $wxInfo = Db::name('third_party_user')->where(['user_id'=>$uid])->find();
        if($wxInfo['openid']){
            $nickName = $wxInfo['nickname'];
        }else{
            $this->error('未绑定微信');
        }
        $outTradeNo = "QDY".$uid.'OT'.date("YmdHis",time()).mt_rand(100,999);
        $clData = [
            'uid' => $uid,
            'coin' => 0-floor($userInfo['balance']),
            'type' => 'withdraw',
            'detail' => $outTradeNo.','.$nickName.',提现',
            'status' => 0
        ];
        if(!CoinLogService::addCoinLog($clData)){
            $this->error('失败');
        }
        $this->success('成功');
    }
}