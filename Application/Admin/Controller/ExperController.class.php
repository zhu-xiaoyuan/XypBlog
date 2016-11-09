<?php
/**
 * Created by PhpStorm.
 * User: 孝远
 * Date: 2016/4/24
 * Time: 15:27
 */
namespace Admin\Controller;
class ExperController extends BaseController{

    /**
     * 友情链接首页
     */

    public function index(){
        $data = M('exper')->select();
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 添加友情链接
     */

    public function edit(){
        if(IS_GET){
            $id = I('get.id');
            if($id){
                $data = M('exper')->where(array('id'=>$id))->find();
                $this->assign('data',$data);
            }
            $this->display();
        }else{
            $data = I('post.');
            //判断ID是否存在，存在id为更新，不存在为添加。
            if($data['id']){
                $data['time'] = time($data['time']);
                $rel =  M('exper')->where(['id'=>$data['id']])->save($data);
                $this->json_response($rel);
                if($rel){
                    $msg['code'] = 0;
                    $msg['msg'] = '更新成功!';
                }else{
                    $msg['code'] = 1;
                    $msg['msg'] = '更新失败，请重试!';
                }
                $this->json_response($msg);
            }else{
                $data['time'] = time($data['time']);
                $rel =  M('exper')->add($data);
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

    public function delExper()
    {
        $id = trim(I('post.id'));
        if($id){
            $rel = M('exper')->delete($id);
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