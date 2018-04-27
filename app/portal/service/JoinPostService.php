<?php
/**
 * Created by PhpStorm.
 * User: zhjigu
 * Date: 2018/4/26
 * Time: 下午2:14
 */

namespace app\portal\service;


use app\portal\model\PortalJoinPostModel;

class JoinPostService
{
    public static function JoinPostInfo($id){
        $join = [
            ['__USER__ u', 'j.user_id = u.id'],
            ['tt_portal_post pp', 'j.post_id = pp.id']
        ];
        $field = 'j.*,u.user_nickname,pp.post_title,pp.post_money';
        $joinPostModel = new PortalJoinPostModel();
        $ret        = $joinPostModel->alias('j')->field($field)
            ->join($join)
            ->find($id);
        return $ret;
    }
    public static function JoinPostList($filter)
    {

        $where = [
            'j.create_time' => ['>=', 0]
        ];

        $join = [
            ['__USER__ u', 'j.user_id = u.id'],
            ['tt_portal_post pp', 'j.post_id = pp.id']
        ];

        $field = 'j.*,u.user_nickname,pp.post_title,pp.post_money';

        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['j.update_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['j.update_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['j.update_time'] = ['<= time', $endTime];
            }
        }

        $joinStatus = empty($filter['join_status']) ? "" : $filter['join_status'];
        if(!empty($joinStatus)){
            if($joinStatus == -1){
                $joinStatus = 0;
            }
            $where['join_status'] = $joinStatus;
        }

        $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
        if (!empty($keyword)) {
            $where['pp.post_title'] = ['like', "%$keyword%"];
        }

        $uid = empty($filter['uid']) ? '' : $filter['uid'];
        if (!empty($uid)) {
            $where['j.user_id'] = $uid;
        }


        $joinPostModel = new PortalJoinPostModel();
        $ret        = $joinPostModel->alias('j')->field($field)
            ->join($join)
            ->where($where)
            ->order('update_time DESC', 'create_time DESC')
            ->paginate(10);

        return $ret;

    }

    public static function setJoinStatus($id,$status,$time=null){
        if(!$id || !$status){
            return false;
        }
        $data['join_status'] = $status;
        if(!empty($time)){
            $data[$time] = time();
        }
        $joinPostModel = new PortalJoinPostModel();
        $ret = $joinPostModel->where(['id'=>$id])->update($data);
        return $ret;
    }
}