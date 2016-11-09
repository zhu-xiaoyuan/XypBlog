<?php
/**
 * Created by PhpStorm.
 * User: 孝远
 * Date: 2016/4/24
 * Time: 15:27
 */
namespace Admin\Controller;
class NavsController extends BaseController{

    /**
     * 导航首页
     */

    public function index(){
        $data = M('navs')->select();
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 添加导航
     */

    public function addNav(){
        if(IS_GET){
            $id = I('get.id');
            if($id){
                $data = M('navs')->where(array('id'=>$id))->find();
                $this->assign('data',$data);
            }
            $this->display();
        }else{
            $data = I('post.');
            //判断ID是否存在，存在id为更新，不存在为添加。
            if($data['id']){
                $rel =  M('navs')->where(['id'=>$data['id']])->save($data);
                if($rel){
                    $msg['code'] = 0;
                    $msg['msg'] = '更新成功!';
                }else{
                    $msg['code'] = 1;
                    $msg['msg'] = '更新失败，请重试!';
                }
                $this->json_response($msg);
            }else{
                $rel =  M('navs')->add($data);
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

    public function delNav()
    {
        $id = trim(I('post.id'));
        if($id){
            $rel = M('navs')->delete($id);
            if($rel){
                $msg['code'] = 0;
                $msg['msg'] = '删除成功!';
            }else{
                $msg['code'] = 1;
                $msg['msg'] = '删除失败，请重试!';
            }
            $this->json_response($msg);
        }
    }
}