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
use think\Db;

class IndexController extends HomeBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        //$this->checkUserLogin();
    }
    public function index()
    {
	$site= cmf_get_option('site_info');
	//notice
	$notice = $site['site_notice'];
	//ad
	$ad = Db::name('slideItem')->where(['title'=>'首页广告'])->find();
	$bad = Db::name('slideItem')->where(['title'=>'底部广告'])->select();
	//invite
	$list = UserService::getLieInviteList();
	$this->assign('list',$list);
	$this->assign('notice',$notice);
	$this->assign('ad',$ad);
	$this->assign('bad',$bad);
	return $this->fetch(':index');
    }

}
