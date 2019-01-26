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
use app\portal\model\PortalCategoryModel;
use think\Db;

class ListController extends HomeBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->checkUserLogin();
    }
    public function index()
    {
        $portalCategoryModel = new PortalCategoryModel();
        $id = $this->request->param('id', 0, 'intval');
	$pid = $portalCategoryModel->where('name','任务大厅')->value('id');

	$categoryList = $portalCategoryModel->where('status', 1)->where('parent_id',$pid)->select()->toArray();
	$category = $portalCategoryModel->where('id', $id)->where('status', 1)->find();
        $this->assign('category', $category);
        $this->assign('category_list', $categoryList);

        $listTpl = empty($category['list_tpl']) ? 'list' : $category['list_tpl'];

        return $this->fetch('/' . $listTpl);
    }
    public function vip()
    {
	$uid = cmf_get_current_user_id();
	$uInfo = Db::name('user')->find($uid);
	if(!$uInfo['is_vip']){
	    $this->redirect('portal/vip/index');
	}
        $portalCategoryModel = new PortalCategoryModel();
        $id = $this->request->param('id', 0, 'intval');
	$pid = $portalCategoryModel->where('name','VIP大厅')->value('id');

	$categoryList = $portalCategoryModel->where('status', 1)->where('parent_id',$pid)->select()->toArray();
	$category = $portalCategoryModel->where('id', $id)->where('status', 1)->find();
        $this->assign('category', $category);
        $this->assign('category_list', $categoryList);

        $listTpl = 'vip';

        return $this->fetch('/' . $listTpl);
    }


}
