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
    public static function addCoinLog($data){
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
                'coin' => $user['coin'] + $data_coin['coin'],
            ];
            if(!Db::name('user')->update($data_user)){
                return false;
            }
        }
        return Db::name('coin_log')->insertGetId($data_coin);
    }

}