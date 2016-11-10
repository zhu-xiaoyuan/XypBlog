<?php
/**
 * Created by PhpStorm.
 * User: 孝远
 * Date: 2016/4/24
 * Time: 15:27
 */
namespace Admin\Controller;
class ArticleController extends BaseController{

    /**
     * 文章管理首页
     */
    public function index(){
        $data = M('article')->select();
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 文章编辑
     */
    public function edit()
    {
        if(IS_GET){
            $id = I('get.id');
            if($id){
                $data = M('article')->where(array('id'=>$id))->find();
                $data["content"] = htmlspecialchars_decode($data["content"]);
                $this->assign('data',$data);
            }
            $this->display();
        }else{
            $data = I('post.');
            //判断ID是否存在，存在id为更新，不存在为添加。
            $data['create_time'] = time();
            if($data['id']){
                $rel =  M('article')->where(['id'=>$data['id']])->save($data);
                if($rel){
                    $msg['code'] = 0;
                    $msg['msg'] = '更新成功!';
                }else{
                    $msg['code'] = 1;
                    $msg['msg'] = '更新失败，请重试!';
                }
                $this->json_response($msg);
            }else{
                $rel =  M('article')->add($data);
                $this->json_response($rel);
                if($rel){
                    $msg['code'] = 0;
                    $msg['msg'] = '添加成功!';
                }else{
                    $msg['code'] = 1;
                    $msg['msg'] = '添加失败，请重试!';
                }
                $this->json_response($msg);
            }
        }
    }
}