<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/4
 * Time: 上午11:24
 */

namespace app\user\service;


class WxService
{
    //本地配置
    public static function getConfig(){
        return [
            'app_id' => 'wxfff266e112b61297',
            'app_secret' => 'cda06aca6c9663073c18f5ae630fb426',
            'token' => 'qianduoyatk',
            'aes_key' => 'IjmXovllF1DutNcyT3qWdI8AyaKFkn7syU22QU5s6bl',
            'mch_id' => '1420231802',
            'key' => 'ceV0nixHOpO2hkVSU7HjvFFiLBYpcwHD',
        ];
    }
    //获取签名
    public static function getSign($token,$timestamp, $nonce){
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $signStr = sha1($tmpStr);
        return $signStr;
    }
    //设置access_token  （通用）
    public static function returnSetAccessToken(){
        cache('cmf_options_qdy_access_token', null);
        $accessToken = cmf_get_option('qdy_access_token');
        $expires_in = isset($accessToken['expires_in'])?$accessToken['expires_in']:0;
        if(time() > $expires_in) {
            //配置参数
            $config = self::getConfig();
            $appId = $config['app_id'];
            $appSecret = $config['app_secret'];

            $params = [
                'grant_type' => 'client_credential',
                'appid' => $appId,
                'secret' => $appSecret
            ];
            $url = "https://api.weixin.qq.com/cgi-bin/token" . '?' . http_build_query($params);
            $ret = request_get($url);
            $data['access_token'] = $ret['access_token'];
            $data['expires_in'] = time() + $ret['expires_in'];
            cmf_set_option('qdy_access_token', $data);
            return $data;
        }
        return $accessToken;
    }
    //获取授权链接
    public static function getAuthUrl($url,$state=0){
        //配置参数
        $config = self::getConfig();
        $appId = $config['app_id'];
        $wxUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appId."&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_userinfo&state=".$state."#wechat_redirect";
        return $wxUrl;
    }
    //获取用户accesstoken
    public static function getAccessToken($code){
        $config = self::getConfig();
        $appId = $config['app_id'];
        $appSecret = $config['app_secret'];

        $params = [
            'appid' => $appId,
            'secret' => $appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token" . '?' . http_build_query($params);
        $ret = request_get($url);
        return $ret;
    }
    //获取用户信息
    public static function getUserInfo($accessToken,$openid){
        $params = [
            'access_token' => $accessToken,
            'openid' => $openid,
            'lang' => 'zh_CN'
        ];
        $url = "https://api.weixin.qq.com/sns/userinfo" . '?' . http_build_query($params);
        $ret = request_get($url);
        return $ret;
    }
    //发送模版消息
    public static function sendTmpMess($params){
        $accessToken = self::returnSetAccessToken();
        $token = $accessToken['access_token'];
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$token;
        $ret = request_post($url,urldecode($params));
        return $ret;
    }
    //模版 账户变更
    public static function tmpAccountChange($openId,$type,$account,$remark){
        switch ($type){
            case 1:
                $title = '分润到账提醒';
                $type = '分润';
                break;
            case 2:
                $title = '机器激活提醒';
                $type = '激活';
                break;
            case 3:
                $title = '提现申请打款提醒';
                $type = '提现';
                break;
            default:
        }
        $data = [
            'first' =>  ['value'=>$title,'color'=>'#000'],
            'account' =>  ['value'=>$account,'color'=>'#000'],
            'time' =>  ['value'=>date("Y-m-d H:i:s",time()),'color'=>'#00f'],
            'type' =>  ['value'=>$type,'color'=>'#000'],
            'remark' =>  ['value'=>$remark,'color'=>'#666']
        ];
        $params = [
            'touser' => $openId,
            'template_id' => 'UP0rah2nHfF1V-45IXtibp63t9Hvm4zKIM6qoIvr8mY',
            'url' => 'http://app.mylabulaka.com/wx/auth',
            'data' => $data
        ];
        $json = json_encode($params);
        $ret = WxService::sendTmpMess($json);
        return $ret;
    }

    //现金红包
    public static function cashRedBag($openId,$totalFee,$sendName,$outTradeNo,$wishing,$actName){
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $config = self::getConfig();
        $unified = array(
            'wxappid' => $config['app_id'],
            'send_name' => $sendName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => create_nonce_str(),
            're_openid' => $openId,
            'mch_billno' => $outTradeNo,
            'client_ip' => get_client_ip(),
            'total_amount' => intval($totalFee * 100),       //单位 转为分
            'total_num'=>1,                 //红包发放总人数
            'wishing'=>$wishing,            //红包祝福语
            'act_name'=>$actName,           //活动名称
            'remark'=>'remark',            //备注信息，如为中文注意转为UTF8编码
            //'scene_id'=>'PRODUCT_2',      //发放红包使用场景，红包金额大于200时必传。https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_4&index=3
        );
        $unified['sign'] = self::getPaySign($unified, $config['key']);
        $responseXml = curlPost($url, arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            return 'parse xml error';
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            return $unifiedOrder->return_msg;
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            return $unifiedOrder->err_code;
        }
        return true;
    }
    public static function getPaySign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}