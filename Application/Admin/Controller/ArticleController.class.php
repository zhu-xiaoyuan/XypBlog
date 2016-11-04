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
        $this->display();
    }

    public function edit()
    {
        $this->display();
    }
}