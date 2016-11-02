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
        $id = I('get.id');
        $pcate = M('category')->where(array('pid'=>'0'))->select();
        if($id){
            $data = M('category')->where(array('id'=>$id))->find();
            $this->assign('data',$data);
        }
        $this->assign('pcate',$pcate);
        $this->display();
    }
}