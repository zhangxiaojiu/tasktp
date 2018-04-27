<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/27
 * Time: 下午5:39
 */

namespace app\user\controller;


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
        echo "提现";
    }
}