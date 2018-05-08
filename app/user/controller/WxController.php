<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/4
 * Time: 上午11:21
 */

namespace app\user\controller;

use app\user\service\WxService;
use cmf\controller\HomeBaseController;
use think\Db;

class WxController extends HomeBaseController
{
    public function index(){
        //get参数
        $echoStr = $_GET['echostr'];
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        //配置参数
        $config = WxService::getConfig();
        $token = $config['token'];
        //获取签名
        $sign = WxService::getSign($token,$timestamp,$nonce);
        //签名正确 返回
        if($sign == $signature && $echoStr){
            echo $echoStr;
            cmf_set_option('test',['reback'=>'signtrue']);
            exit;
        }else{
            cmf_set_option('test',['reback'=>'signfalse']);
            $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = (string)$postObj->FromUserName;
            cmf_set_option('test',['from'=>$fromUsername]);
            $EventKey = trim((string)$postObj->EventKey);
            $keyArray = explode("_", $EventKey);
            if (count($keyArray) == 1){//已关注者扫描
                cmf_set_option('test',['reback'=>$EventKey]);

            }else {//未关注者关注后推送事件
                cmf_set_option('test', ['reback' => $keyArray[1]]);
            }
        }
    }

    //微信用户签名
    public function auth()
    {
        $code = isset($_GET['code'])?$_GET['code']:false;
        if($code) {
            $state = isset($_GET['state'])?$_GET['state']:0;//传递参数用
            $ret = WxService::getAccessToken($code);
            if (isset($ret['errcode'])) {
                p($ret, 0);
            } else {
                $data['access_token'] = $ret['access_token'];
                $data['expire_time'] = time() + $ret['expires_in'];

                $userRet = WxService::getUserInfo($data['access_token'],$ret['openid']);
                $data['openid'] = $userRet['openid'];
                $data['nickname'] = $userRet['nickname'];
                $data['union_id'] = isset($userRet['unionid'])?$userRet['unionid']:'';
                //本地用户数据
                $userData['sex'] = $userRet['sex'];
                $userData['avatar'] = $userRet['headimgurl'];
                $userData['user_nickname'] = $userRet['nickname'];
                $userData['last_login_time'] = time();

                $info = Db::name('third_party_user')->where(['openid' => $data['openid']])->find();

                if(empty(session('user')['id'])){
                    if(empty($info['user_id'])) {
                        $userData['pid'] = $state;
                        $userData['user_type'] = 2;
                        $userData['user_status'] = 1;
                        $userData['create_time'] = time();
                        $uid = Db::name('user')->insertGetId($userData);
                        $data['user_id'] = $uid;

                        $uInfo = Db::name('user')->find($uid);
                    }else{
                        $uInfo = Db::name('user')->find($info['user_id']);
                    }
                    session('user',$uInfo);
                }else{
                    $data['user_id'] = session('user')['id'];
                    $userData['id'] = session('user')['id'];
                    Db::name('user')->update($userData);
                }


                if (empty($info)) {
                    Db::name('third_party_user')->insert($data);
                } else {
                    Db::name('third_party_user')->where(['openid' => $data['openid']])->update($data);
                }
                $this->redirect('/');
            }
        }else{
            $redirect_uri = "http://www.qianduoya.com/user/wx/auth";
            $pid = isset($_GET['pid'])?$_GET['pid']:0;
            $url = WxService::getAuthUrl($redirect_uri,$pid);
            $this->redirect($url);
        }
    }
}