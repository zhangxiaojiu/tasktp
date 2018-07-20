<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\user\service\ActiveService;

class ActiveController extends AdminBaseController
{
    public function rotate(){
	$time = date('Y-m-d',time());
	for($i=0;$i<10;$i++){
	    $list[$i] = 0;
	}
	$ret = Db::name('user_rotate')->where(['create_time' => $time,'status' => -1])->select();
	foreach($ret as $v){
	    $list[$v['num']] += $v['multiple']*2;
	}
	$this->assign('list',$list);
	return $this->fetch();
    }
    public function openNum(){
	$num = $_POST['num'];
	$arr = [0,1,2,3,4,5,6,7,8,9];
	if(!in_array($num,$arr)){
	    $this->error('请输入正确的开奖号码');
	}
	if(ActiveService::openNumber($num)){
	    $this->success('成功');
	}
    }
}
