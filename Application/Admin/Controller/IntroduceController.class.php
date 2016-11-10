<?php
/**
 * Created by PhpStorm.
 * User: 孝远
 * Date: 2016/4/24
 * Time: 15:27
 */
namespace Admin\Controller;
class IntroduceController extends BaseController{

    /**
     * 个人信息首页
     */

    public function index(){
        $data = M('introduce')->find();
        $data["introduction"] = htmlspecialchars_decode($data["introduction"]);
        $this->assign('data',$data);
        $this->display();
    }

    public function addIntroduce()
    {
        $data = I('post.');
        //判断ID是否存在，存在id为更新，不存在为添加。
        if($data['id']){
            $rel =  M('introduce')->where(['id'=>$data['id']])->save($data);
            $this->json_response($rel);
            if($rel){
                $msg['code'] = 0;
                $msg['msg'] = '保存成功!';
            }else{
                $msg['code'] = 1;
                $msg['msg'] = '保存失败，请重试!';
            }
            $this->json_response($msg);
        }else{
            $rel =  M('introduce')->add($data);
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