<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\RouteModel;
use app\user\service\WxService;
use cmf\controller\AdminBaseController;
use app\portal\service\ImageService;

use think\Db;

/**
 * Class SettingController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   =>'设置',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 0,
 *     'icon'   =>'cogs',
 *     'remark' =>'系统设置入口'
 * )
 */
class SettingController extends AdminBaseController
{

    /**
     * 网站信息
     * @adminMenu(
     *     'name'   => '网站信息',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '网站信息',
     *     'param'  => ''
     * )
     */
    public function site()
    {
        $noNeedDirs     = [".", "..", ".svn", 'fonts'];
        $adminThemesDir = config('cmf_admin_theme_path') . config('cmf_admin_default_theme') . '/public/assets/themes/';
        $adminStyles    = cmf_scan_dir($adminThemesDir . '*', GLOB_ONLYDIR);
        $adminStyles    = array_diff($adminStyles, $noNeedDirs);
        $cdnSettings    = cmf_get_option('cdn_settings');
        $cmfSettings    = cmf_get_option('cmf_settings');
        $adminSettings  = cmf_get_option('admin_settings');
        $taskSettings  = cmf_get_option('task_settings');

        $this->assign(cmf_get_option('site_info'));
        $this->assign("admin_styles", $adminStyles);
        $this->assign("templates", []);
        $this->assign("cdn_settings", $cdnSettings);
        $this->assign("admin_settings", $adminSettings);
        $this->assign("cmf_settings", $cmfSettings);
        $this->assign("task_settings", $taskSettings);

        return $this->fetch();
    }

    /**
     * 网站信息设置提交
     * @adminMenu(
     *     'name'   => '网站信息设置提交',
     *     'parent' => 'site',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '网站信息设置提交',
     *     'param'  => ''
     * )
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'SettingSite');
            if ($result !== true) {
                $this->error($result);
            }

            $options = $this->request->param('options/a');
            cmf_set_option('site_info', $options);

            $tasks = $this->request->param('task/a');
            cmf_set_option('task_settings', $tasks);

            $cmfSettings = $this->request->param('cmf_settings/a');

            $bannedUsernames                 = preg_replace("/[^0-9A-Za-z_\\x{4e00}-\\x{9fa5}-]/u", ",", $cmfSettings['banned_usernames']);
            $cmfSettings['banned_usernames'] = $bannedUsernames;
            cmf_set_option('cmf_settings', $cmfSettings);

            $cdnSettings = $this->request->param('cdn_settings/a');
            cmf_set_option('cdn_settings', $cdnSettings);

            $adminSettings = $this->request->param('admin_settings/a');

            $routeModel = new RouteModel();
            if (!empty($adminSettings['admin_password'])) {
                $routeModel->setRoute($adminSettings['admin_password'].'$', 'admin/Index/index', [], 2, 5000);
            } else {
                $routeModel->deleteRoute('admin/Index/index', []);
            }

            $routeModel->getRoutes(true);

            cmf_set_option('admin_settings', $adminSettings);

            $this->success("保存成功！", '');

        }
    }

    /**
     * 密码修改
     * @adminMenu(
     *     'name'   => '密码修改',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '密码修改',
     *     'param'  => ''
     * )
     */
    public function password()
    {
        return $this->fetch();
    }

    /**
     * 密码修改提交
     * @adminMenu(
     *     'name'   => '密码修改提交',
     *     'parent' => 'password',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '密码修改提交',
     *     'param'  => ''
     * )
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();
            if (empty($data['old_password'])) {
                $this->error("原始密码不能为空！");
            }
            if (empty($data['password'])) {
                $this->error("新密码不能为空！");
            }

            $userId = cmf_get_current_admin_id();

            $admin = Db::name('user')->where(["id" => $userId])->find();

            $oldPassword = $data['old_password'];
            $password    = $data['password'];
            $rePassword  = $data['re_password'];

            if (cmf_compare_password($oldPassword, $admin['user_pass'])) {
                if ($password == $rePassword) {

                    if (cmf_compare_password($password, $admin['user_pass'])) {
                        $this->error("新密码不能和原始密码相同！");
                    } else {
                        Db::name('user')->where('id', $userId)->update(['user_pass' => cmf_password($password)]);
                        $this->success("密码修改成功！");
                    }
                } else {
                    $this->error("密码输入不一致！");
                }

            } else {
                $this->error("原始密码不正确！");
            }
        }
    }

    /**
     * 上传限制设置界面
     * @adminMenu(
     *     'name'   => '上传设置',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置',
     *     'param'  => ''
     * )
     */
    public function upload()
    {
        $uploadSetting = cmf_get_upload_setting();
        $this->assign($uploadSetting);
        return $this->fetch();
    }

    /**
     * 上传限制设置界面提交
     * @adminMenu(
     *     'name'   => '上传设置提交',
     *     'parent' => 'upload',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '上传设置提交',
     *     'param'  => ''
     * )
     */
    public function uploadPost()
    {
        if ($this->request->isPost()) {
            //TODO 非空验证
            $uploadSetting = $this->request->post();

            cmf_set_option('upload_setting', $uploadSetting);
            $this->success('保存成功！');
        }

    }

    /**
     * 清除缓存
     * @adminMenu(
     *     'name'   => '清除缓存',
     *     'parent' => 'default',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '清除缓存',
     *     'param'  => ''
     * )
     */
    public function clearCache()
    {
        cmf_clear_cache();
        return $this->fetch();
    }

    public function wxSet(){
        $menu = WxService::getMenu();

	$button = isset($menu['menu']["button"])?$menu['menu']["button"]:[];
        $num = count($button);
        $emptyNum = 3-$num;
        $emptyList = [];
        if($emptyNum > 0){
            for ($i = 4-$emptyNum; $i <= 3; $i++)
            $emptyList[] = $i;
        }
        $this->assign('emptyList', $emptyList);
        $this->assign('menu',$button);
        return $this->fetch();
    }
    public function doWxSet()
    {
        if ($this->request->isPost()) {
	    $data = $this->request->param();
            foreach ($data as $key=>$v){
                if(!empty($v['name']) && !empty($v['type'])){
                    $menu = [
                        'type' => $v['type'],
                        'name' => $v['name']
                    ];
                    switch ($v['type']){
                        case "view":
                            $menu['url'] = $v['content'];
                            break;
                        case "click":
                            $clickEvent['button'.$key] = $v['content'];
                            $menu['key'] = 'button'.$key;
                            break;
                        case "media_id":
                            $menu['media_id'] = $v['content'];
                            break;
                        default:
                            $this->error('类型存在错误');
                    }
                    if(!empty($v['childName'])){
                        $num = count($v['childName']);
                        for ($i =0; $i<$num; $i++){
                            if(!empty($v['childName'][$i]) && !empty($v['childType'][$i]) && !empty($v['childContent'][$i])){
                                $subButton = [
                                    'type' => $v['childType'][$i],
                                    'name' => $v['childName'][$i]
                                ];
                                switch ($v['childType'][$i]){
                                    case "view":
                                        $subButton['url'] = $v['childContent'][$i];
                                        break;
                                    case "click":
                                        $clickEvent['button'.$key.$i] = $v['childContent'][$i];
                                        $subButton['key'] = 'button'.$key.$i;
                                        break;
                                    case "media_id":
                                        $subButton['media_id'] = $v['childContent'][$i];
                                        break;
                                    default:
                                        $this->error('类型存在错误');
                                }
                                unset($menu['type']);
                                unset($menu['key']);
                                unset($menu['url']);
                                unset($menu['media_id']);
                                $menu['sub_button'][]=$subButton;
                            }
                        }
                    }
                    $button[] = $menu;
                }elseif ($key == "event"){
                    $clickEvent['msg_reply'] = $v['msg_reply'];
                    $clickEvent['follow_reply'] = $v['follow_reply'];
                }
            }
	    $param['button'] = $button;
            $ret = WxService::createMenu($param);
            if($ret['errcode'] == 0){
		cmf_set_option('click_event',$clickEvent);
                $this->success("保存成功！", '');
            }else{
                $this->error($ret['errmsg']);
            }
        }
    }
    /*
     * 创建微信菜单test
     */
    public function createWxMenu(){
        $ret = WxService::createMenu();
        p($ret);
    }

    /*
     * 微信永久图片素材列表
     */
    public function wxImg(){
	$file   = $this->request->file('upload_img');
	if($file){
	    $ret = self::uploadImg($file);
	    if($ret['code'] == 1){
		$filepath = $_SERVER['DOCUMENT_ROOT']."/upload/sys/".$ret['data']['file'];
		$wxret = self::addWxImg($filepath);
		if(!$wxret['media_id']){
		    pr($wxret,1);    
		}
	    }else{
		$this->error("上传失败");
	    }
	}
	$page = input('get.page',0);
	//允许显示的素材数量
    	$view_num = 5;
	$begin = $page * $view_num;
	$data = [
	    "type" => "image",
	    "offset" => $begin,
	    "count" => $view_num
	];
	$ret = WxService::getWxImg($data);
	$this->assign('list',$ret);
	return $this->fetch();
    }
    private static function addWxImg($filepath){
	$type = "image";
	if(class_exists('\CURLFile')){
	    $data['media'] = new \CURLFile($filepath);
	}else{
	    $data['media'] = '@'.$filepath;
	}
	$ret = WxService::upWximg($data,$type);
	return $ret;
    }

    /*
     * 上传图片
     */
    private static function uploadImg($file){
        $result = $file->validate([
            'ext'  => 'jpg,png',
            'size' => 1024 * 1024*10
        ])->move('.' . DS . 'upload' . DS . 'sys' . DS);
        if ($result) {
            $saveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            //压缩图片
            $scale = 0.2;
            $fileInfo = $file->getInfo();
            $size = $fileInfo['size'];
            if($size < 200*1024){
                $scale = 1;
            }
            $src = "./upload/sys/".$saveName;
            $image = new ImageService($src);
            $image->percent = $scale;
            $image->openImage();
            $image->thumpImage();
            $image->saveImage($src,true);
            return [
                'code' => 1,
                "msg"  => "上传成功",
                "data" => ['file' => $saveName],
                "url"  => ''
            ];
        } else {
            return [
                'code' => 0,
                "msg"  => $file->getError(),
                "data" => "",
                "url"  => ''
            ];
        }
    }
}
