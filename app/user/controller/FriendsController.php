<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/27
 * Time: 下午5:39
 */

namespace app\user\controller;


use app\admin\model\UserModel;
use cmf\controller\UserBaseController;
use think\Db;

class FriendsController extends UserBaseController
{
    function _initialize()
    {
        parent::_initialize();
    }

    /*
     * 我的好友
     */
    public function index(){
        $uid = session('user.id');
        $childIds = UserModel::getChildIds($uid);
        $list = Db::name('user')->where(['id'=>['in',$childIds]])->paginate(5);
        $this->assign('list',$list);
        return $this->fetch();
    }
}