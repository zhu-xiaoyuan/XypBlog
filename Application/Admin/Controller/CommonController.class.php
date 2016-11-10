<?php
namespace Admin\Controller;
class CommonController extends BaseController{
    public function upload(){

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      './Admin/'; // 设置附件上传目录    // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功
            $data['data'] = $info['Filedata']['savepath'].$info['Filedata']['savename'];
            $this->json_response($data);
//            $this->success('上传成功！');
        }
    }
}
