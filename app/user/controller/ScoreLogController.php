<?php
namespace app\user\controller;

use think\Db;
use cmf\controller\UserBaseController;

class ScoreLogController extends UserBaseController
{
    public function index(){
        $uid = session('user.id');
        $coinLog = Db::name('user_score_log')->where(['user_id'=>$uid])->order('create_time desc')->paginate(10);
        $this->assign('list', $coinLog);
	return $this->fetch();
    } 

}
