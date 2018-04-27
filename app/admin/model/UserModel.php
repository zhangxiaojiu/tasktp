<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Db;
use think\Model;

class UserModel extends Model
{


    public static function getChildIds($id){
        $childIds = [];
        $childList = Db::name('user')->where(['pid'=>$id])->select();
        if(count($childList) > 0){
            foreach ($childList as $v){
                $childIds[] = $v['id'];
            }
            $childChildList = Db::name('user')->where(['pid'=>['in',$childIds]])->select();
            if(count($childChildList) > 0){
                foreach ($childChildList as $cv){
                    $childIds[] = $cv['id'];
                }
            }
        }
        return $childIds;
    }
}