<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/3/29
 * Time: 上午10:07
 */

namespace app\admin\service;


use think\Db;

class CoinLogService
{
    /*
     * 资金记录
     */
    public static function addCoinLog($data,$other=false){
        $data_coin = [
            'uid' => isset($data['uid'])?$data['uid']:0,
            'coin' => isset($data['coin'])?$data['coin']:0,
            'type' => isset($data['type'])?$data['type']:'',
            'detail' => isset($data['detail'])?$data['detail']:'',
            'status' => isset($data['status'])?$data['status']:0,
            'create_time' => time()
        ];

        if($data['coin'] != 0){
            $user = Db::name('user')->where('id',$data['uid'])->find();
            $data_user = [
                "id" => $data_coin['uid'],
                'balance' => $user['balance'] + $data_coin['coin'],
            ];
            if($other){
                $data_user[$other] = $user[$other]+$data_coin['coin'];
            }
            if(!Db::name('user')->update($data_user)){
                return false;
            }
        }
        return Db::name('coin_log')->insertGetId($data_coin);
    }

    /*
     * 任务奖励上级
     */
    public static function taskUserParents($id,$money){
        $uInfo = Db::name('user')->find($id);
        if($uInfo['pid']>0){
            $taskSet = cmf_get_option('task_settings');

            $pInfo = Db::name('user')->find($uInfo['pid']);
            if($pInfo['scale_second'] > 0){
                $scaleSecond = $pInfo['scale_second'];
            }else{
                $scaleSecond = $taskSet['one'];
            }

            $clData = [
                'uid' => $uInfo['pid'],
                'coin' => $money * $scaleSecond,
                'type' => 'task',
                'detail' => '二级用户「'.$uInfo['user_nickname'].'」完成任务奖励¥'.$money * $scaleSecond,
                'status' => 1
            ];
            self::addCoinLog($clData,'other_coin');
            if($pInfo['pid']>0) {
                $ppInfo = Db::name('user')->find($pInfo['pid']);
                if($ppInfo['scale_third'] > 0){
                    $scaleThird = $ppInfo['scale_third'];
                }else{
                    $scaleThird = $taskSet['two'];
                }
                $clData = [
                    'uid' => $pInfo['pid'],
                    'coin' => $money * $scaleThird,
                    'type' => 'task',
                    'detail' => '三级用户「' . $uInfo['user_nickname'] . '」完成任务奖励¥' . $money * $scaleThird,
                    'status' => 1
                ];
                self::addCoinLog($clData,'other_coin');
            }
        }
        return true;
    }

}