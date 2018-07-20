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

use app\portal\model\UserModel;
use app\user\service\UserService;
use cmf\controller\HomeBaseController;
use app\user\service\ScoreLogService;
use think\Db;

class ActiveController extends HomeBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->checkUserLogin();
    }

    public function index()
    {
	$name = '活动';
	$Id = Db::name('slide')->where(['name'=>$name])->value('id');
        $list  = Db::name('slideItem')->where(['slide_id' => $Id])->select()->toArray();
	$this->assign('list',$list);
	return $this->fetch(':active');
    }

    public function rotate(){
	$userId = session('user.id');
	$time = date('Y-m-d',time());
	$ret = Db::name('user_rotate')->where(['user_id'=>$userId,'create_time'=>$time])->find();
	$this->assign('ret',$ret);
	return $this->fetch();
    }
    public function ajaxRunRotate(){
	$userId = session('user.id');
	$hour = date('H');
	if($hour > 22){
	    die('timeout');
	}
	$score = Db::name('user')->where(['id' => $userId])->value('score');
	if($score >= 2){
	    $sData = [
		'user_id' => $userId,
		'score' => -2,
		'action' => 'rotate',
		'detail' => '幸运转盘竞猜消耗积分'
	    ];
	    ScoreLogService::addScoreLog($sData);
	    die('ok');
	}else{
	    die('no');
	}
    }
    public function subRotate(){
	$data['user_id'] = session('user.id');
	$data['num'] = isset($_POST['num'])?$_POST['num']:'';
	$data['multiple'] = isset($_POST['multiple'])?$_POST['multiple']:'';
	$data['create_time'] = date('Y-m-d',time());
	if($data['multiple'] > 1){
	    $useScore = ($data['multiple'] - 1)*2;
	    $sData = [
		'user_id' => $data['user_id'],
		'score' => 0-$useScore,
		'action' => 'rotates',
		'detail' => '幸运转盘加倍竞猜消耗积分'
	    ];
	    ScoreLogService::addScoreLog($sData);
	}
	Db::name('user_rotate')->insert($data);
	$this->success('成功');
    }
}
