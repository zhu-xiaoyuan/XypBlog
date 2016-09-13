<?php
/**
 * Created by PhpStorm.
 * User: ling
 * Date: 2016/5/30
 * Time: 17:35
 */
namespace Admin\Controller;
use Think\Controller;
class EmptyController extends Controller{
    public function index(){
        header('HTTP/1.0 404 Not Found');
        $this->display('Base:404');
    }
}