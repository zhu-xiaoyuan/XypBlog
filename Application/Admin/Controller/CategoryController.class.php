<?php
/**
 * Created by PhpStorm.
 * User: 孝远
 * Date: 2016/4/24
 * Time: 15:27
 */
namespace Admin\Controller;
class CategoryController extends BaseController{

    /**
     * 类别管理首页
     */

    public function index(){
        $data = M('category')->select();
        $this->assign('data',$data);
        $this->display();
    }

    public function addCate(){

        $pcate = M('category')->where(array('pid'=>'0'))->select();
        if(IS_GET){
            $id = I('get.id');
            if($id != ''){
                $data = M('category')->where(array('id'=>$id))->find();
                $this->assign('data',$data);
            }

            $this->assign('pcate',$pcate);
            $this->display();
        }else{
            $data = I('post.');
            if($data['id']){
                $rel = M('category')->save($data);
                if($rel !== false){
                    $msg['code'] = 0;
                    $msg['msg'] = '更新成功!';
                }else{
                    $msg['code'] = 1;
                    $msg['msg'] = '更新失败，请重试!';
                }
            }else{
                $rel = M('category')->add($data);
                if($rel){
                    $msg['code'] = 0;
                    $msg['msg'] = '添加成功!';
                }else{
                    $msg['code'] = 1;
                    $msg['msg'] = '添加失败，请重试!';
                }
            }

            $this->json_response($msg);
        }
    }

    public function delCate()
    {
        $id = trim(I('post.id'));
        if($id){
           $rel = M('category')->delete($id);
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