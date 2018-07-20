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
    public static function getInviteList(){
	$users = Db::name('user')->field('id,user_nickname,pid,avatar')->select();
	$p = [];
	foreach($users as $v){
	    if(!isset($p[$v['pid']])){
		 $p[$v['pid']] = 0;
	    }
	    $p[$v['pid']]++; 
	}
	foreach($users as $val){
	    $val['cnum'] = isset($p[$val['id']])?$p[$val['id']]:0;
	    $ret[] = $val;
	    $cnum[] = $val['cnum'];
	}
	array_multisort($cnum, SORT_DESC,$ret);
	return $ret;
    }
    public static function getSignData($id){
	$data = Db::name('user_sign')->where(['user_id'=>$id])->value('data');
	return $data;
    }
    public static function putSignData($id,$arr){
	$dbData = ['user_id'=>$id];
	$data = self::getSignData($id);
	if(empty($data)){
	    $dbData['data'] = json_encode($arr);
	    Db::name('user_sign')->insert($dbData);
	    return true;
	}else{
	    $signArr = json_decode($data,true);
	    foreach($arr as $key => $val){
		$nowData = $val[0];
	    }
	    $hasMonth = 0;
	    foreach($signArr as $k => $v){
		if ($key == $k){
		    if(in_array($nowData,$v)){
			return false;
		    }else{
			array_push($v,$nowData);
		    }
		    $hasMonth = 1;
		}
		$dataArr[$k] = $v;
	    }
	    if(!$hasMonth){
		$dataArr[$key] = $val;
	    }
	    $dbData['data'] = json_encode($dataArr);
	    Db::name('user_sign')->where(['user_id'=>$id])->update($dbData);
	    return true;
	}
    }
}
