<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use app\admin\model\UserModel;
use app\user\service\WxService;
use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AdminIndexController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用户管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用户管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用户组',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用户组'
 * )
 */
class AdminIndexController extends AdminBaseController
{

    /**
     * 后台本站用户列表
     * @adminMenu(
     *     'name'   => '本站用户',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $where   = [];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['user_login|user_nickname|user_email|mobile']    = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("create_time DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /*
     * 下级列表
     */
    public function childList(){
        $id = input('param.id', 0, 'intval');
        $level = input('param.level', false);
        $childIds = UserModel::getChildIds($id,$level);
        $list = Db::name('user')->where(['id'=>['in',$childIds]])->paginate(10);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 本站用户拉黑
     * @adminMenu(
     *     'name'   => '本站用户拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $this->success("会员拉黑成功！", "adminIndex/index");
            } else {
                $this->error('会员拉黑失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $this->success("会员启用成功！", '');
        } else {
            $this->error('数据传入失败！');
        }
    }

    /*
     * 提现审核
     */
    public function withdraw(){
        $where   = ['type'=>'withdraw'];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['uid'] = intval($request['uid']);
        }
        $where['status'] = 0;
        if (!empty($request['status'])) {
            $where['status'] = intval($request['status']);
        }
  
        $list = Db::name('coin_log')->alias('cl')->join('tt_user u','cl.uid = u.id')->field('cl.*,u.user_nickname')->where($where)->order('status')->paginate(10);
        $this->assign('list',$list);
        return $this->fetch();
    }
    /*
     * 通过提现审核
     */
    public function passWithdraw(){
        $id = input('param.id', 0, 'intval');
        $clInfo = Db::name('coin_log')->find($id);
        $wxInfo = Db::name('third_party_user')->where(['user_id'=>$clInfo['uid']])->find();
        if($wxInfo['openid']){
            $openId = $wxInfo['openid'];
        }else{
            $this->error('未绑定微信');
        }
        $outTradeNo = explode(',',$clInfo['detail'])[0];
        $sendName = "钱多呀";
        $wishing = "欢迎参与活动，请领取红包";
        $actName = "挑战任务赢取现金红包";
        $ret = WxService::cashRedBag($openId,abs($clInfo['coin']),$sendName,$outTradeNo,$wishing,$actName);
        if($ret === true){
            Db::name('coin_log')->where(['id'=>$id])->setField('status',1);
            $this->success('提现成功');
        }else{
            $this->error($ret[0]);
        }
    }

    /*
     * 设置比例
     */
    public function setScale(){
        $id = input('param.id', 0, 'intval');
        if($id <= 1){
            $this->error('参数错误');
        }
        $data['id'] = $id;
        $data['scale_second'] = input('param.scale_second', null);
        $data['scale_third'] = input('param.scale_third', null);
        Db::name('user')->update($data);
        $this->success('设置成功');
    }
}
