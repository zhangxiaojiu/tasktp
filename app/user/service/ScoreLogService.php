<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/3/29
 * Time: 上午10:07
 */

namespace app\user\service;


use think\Db;

class ScoreLogService
{
    /*
     * 资金记录
     */
    public static function addScoreLog($data){
        $data_coin = [
            'user_id' => isset($data['user_id'])?$data['user_id']:0,
            'score' => isset($data['score'])?$data['score']:0,
            'action' => isset($data['action'])?$data['action']:'',
            'detail' => isset($data['detail'])?$data['detail']:'',
            'create_time' => time()
        ];

        if($data['score'] != 0){
            $user = Db::name('user')->where('id',$data['user_id'])->find();
            $data_user = [
                "id" => $data_coin['user_id'],
                'score' => $user['score'] + $data_coin['score']
            ];
            if(!Db::name('user')->update($data_user)){
                return false;
            }
        }
        return Db::name('user_score_log')->insertGetId($data_coin);
    }
}
