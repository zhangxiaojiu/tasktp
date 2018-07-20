<?php
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use app\user\service\ActiveService;

class ApiController extends HomeBaseController
{
    public function openRotate(){
	$num = mt_rand(0,9);
	$ret = ActiveService::openNumber($num);
	echo $ret;
    }
    
}
