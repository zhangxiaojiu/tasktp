<?php
namespace app\user\service;
use think\Db;
use app\user\service\CoinLogService;
use app\user\service\WxService;

class ActiveService
{
    public static function openNumber($num){
	$time = date('Y-m-d',time());
	$list =  Db::name('user_rotate')->where(['create_time' => $time,'status' => -1])->select();
	foreach($list as $v){
	    //goal
	    if($num == $v['num']){
		$money = $v['multiple']/2;
		$cData = [
		    'uid' => $v['user_id'],
		    'coin' => $money,
		    'type' => 'rotate',
		    'detail' => '幸运转盘中奖奖励',
		    'status' => 1
		];
		CoinLogService::addCoinLog($cData);
		$title = '恭喜你 【幸运转盘】中奖通知';
		$ret = '中奖,金额¥'.$money;
	    }else{
		$title = '很遗憾 【幸运转盘】没有中奖';
		$ret = '未中奖';
	    }
            $wxInfo = Db::name('third_party_user')->where(['user_id'=>$v['user_id']])->find();
	    self::wxRotateTmp($wxInfo['openid'],$title,$v['num'],$num,$ret);
	    
	}
	Db::name('user_rotate')->where(['create_time' => $time,'status' => -1])->setField('status',$num);
	return true;
    }

    //wxtmp
    public static function wxRotateTmp($openId,$title,$num,$status,$ret){
	$template = "tpH9wCSFOObS899Dtb4dL3jcuGot_1ndQHjs0owLyHU";
        $data = [
            'first' =>  ['value'=>$title,'color'=>'#000'],
            'keyword1' =>  ['value'=>'幸运转盘','color'=>'#000'],
            'keyword2' =>  ['value'=>$num,'color'=>'#000'],
            'keyword3' =>  ['value'=>$status,'color'=>'#000'],
            'keyword4' =>  ['value'=>date("Y-m-d H:i:s",time()),'color'=>'#00f'],
            'keyword5' =>  ['value'=>$ret,'color'=>'#00f'],
            'remark' =>  ['value'=>'感谢您的参与～','color'=>'#666']
        ];
        $params = [
            'touser' => $openId,
            'template_id' => $template,
            'url' => 'www.qianduoya.com',
            'data' => $data
        ];
        $json = json_encode($params);
        $ret = WxService::sendTmpMess($json);
	return $ret;
    }
}
