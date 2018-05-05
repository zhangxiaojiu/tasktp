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
namespace app\portal\controller;

use app\portal\service\ImageService;
use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\service\PostService;
use app\portal\model\PortalPostModel;
use think\Db;

class ArticleController extends HomeBaseController
{
    public function index()
    {

        $portalCategoryModel = new PortalCategoryModel();
        $postService         = new PostService();

        $articleId  = $this->request->param('id', 0, 'intval');
        $categoryId = $this->request->param('cid', 0, 'intval');
        $article    = $postService->publishedArticle($articleId, $categoryId);

        if (empty($article)) {
            abort(404, '文章不存在!');
        }


        $prevArticle = $postService->publishedPrevArticle($articleId, $categoryId);
        $nextArticle = $postService->publishedNextArticle($articleId, $categoryId);

        $tplName = 'article';

        if (empty($categoryId)) {
            $categories = $article['categories'];

            if (count($categories) > 0) {
                $this->assign('category', $categories[0]);
            } else {
                abort(404, '文章未指定分类!');
            }

        } else {
            $category = $portalCategoryModel->where('id', $categoryId)->where('status', 1)->find();

            if (empty($category)) {
                abort(404, '文章不存在!');
            }

            $this->assign('category', $category);

            $tplName = empty($category["one_tpl"]) ? $tplName : $category["one_tpl"];
        }

        Db::name('portal_post')->where(['id' => $articleId])->setInc('post_hits');

        hook('portal_before_assign_article', $article);

        $joinPost = [];
        $uid = session('user.id')?session('user.id'):null;
        if(!empty($uid)){
            $joinPost = Db::name('portal_join_post')->where(['user_id'=>$uid,'post_id'=>$articleId])->find();
        }
        $this->assign('join_post',$joinPost);
        $this->assign('article', $article);
        $this->assign('prev_article', $prevArticle);
        $this->assign('next_article', $nextArticle);

        $tplName = empty($article['more']['template']) ? $tplName : $article['more']['template'];

        return $this->fetch("/$tplName");
    }

    // 文章点赞
    public function doLike()
    {
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');


        $canLike = cmf_check_user_action("posts$articleId", 1);

        if ($canLike) {
            Db::name('portal_post')->where(['id' => $articleId])->setInc('post_like');

            $this->success("赞好啦！");
        } else {
            $this->error("您已赞过啦！");
        }
    }

    //任务参与
    public function joinTask(){
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');
        $data['user_id'] = $where['user_id'] = session('user.id');
        $data['post_id'] = $where['post_id'] = $articleId;
        $data['create_time'] = time();
        if(Db::name('portal_join_post')->where($where)->find()){
            $joinContent = $this->request->param('join_content', null);
            $file   = $this->request->file('join_img');
            $joinImages = '';
            if(count($file)>0) {
                foreach ($file as $v) {
                    $ret = self::uploadImg($v);
                    if ($ret['code'] == 1) {
                        $src = $ret['data']['file'];
                        $joinImages .= '<img style="width:100%;" src="/upload/task/' . $src . '" />';
                    } else {
                        $this->error($ret['msg']);
                    }
                }
            }
            if(empty($joinContent) && empty($joinImages)) {
                $this->error('已经参与，请补交材料');
            }else{
                $joinData['join_content'] = htmlspecialchars($joinContent);
                $joinData['join_images'] = htmlspecialchars($joinImages);
                $joinData['join_status'] = 1;
                $joinData['update_time'] = time();
                Db::name('portal_join_post')->where($where)->update($joinData);
                $this->success('提交成功，请等待审核');
            }
        }else{
            Db::name('portal_join_post')->insert($data);
            $this->success('参与成功，请补交材料');
        }

    }

    /*
     * 上传图片
     */
    private static function uploadImg($file){
        $result = $file->validate([
            'ext'  => 'jpg,jpeg,png',
            'size' => 1024 * 1024*10
        ])->move('.' . DS . 'upload' . DS . 'task' . DS);
        if ($result) {
            $saveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            //压缩图片
            $scale = 0.2;
            $fileInfo = $file->getInfo();
            $size = $fileInfo['size'];
            if($size < 200*1024){
                $scale = 1;
            }
            $src = "./upload/task/".$saveName;
            $image = new ImageService($src);
            $image->percent = $scale;
            $image->openImage();
            $image->thumpImage();
            $image->saveImage($src,true);
            return [
                'code' => 1,
                "msg"  => "上传成功",
                "data" => ['file' => $saveName],
                "url"  => ''
            ];
        } else {
            return [
                'code' => 0,
                "msg"  => $file->getError(),
                "data" => "",
                "url"  => ''
            ];
        }
    }

}
