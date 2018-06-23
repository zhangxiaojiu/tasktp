<?php
namespace app\user\service;

use think\Db;

class UserService
{
    public static function getChildIds($id, $level=false){
        $childIds = $seconds = $thirds = [];
        $childList = Db::name('user')->where(['pid'=>$id])->select();
        if(count($childList) > 0){
            foreach ($childList as $v){
                $childIds[] = $v['id'];
                $seconds[] = $v['id'];
            }
            $childChildList = Db::name('user')->where(['pid'=>['in',$childIds]])->select();
            if(count($childChildList) > 0){
                foreach ($childChildList as $cv){
                    $childIds[] = $cv['id'];
                    $thirds[] = $cv['id'];
                }
            }
        }
        if($level == "second"){
            return $seconds;
        }
        if($level == "third"){
            return $thirds;
        }
        return $childIds;
    }
}
