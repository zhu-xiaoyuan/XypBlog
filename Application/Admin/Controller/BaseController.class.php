<?php
namespace Admin\Controller;
use Think\Controller;
class BaseController extends Controller {
    public function __construct(){
        parent::__construct();
       if(!is_login()){
        //if(false){
            $this->redirect('Admin/Login/index');
        }

//        if(!$this->allow_route()){
            //404页面
//            $this->to404();
//        }
        if(IS_GET){
            //加载 导航
            $nav = $this->get_nav();
            if($nav == null){
                //404页面
                $this->to404();
            }
            $this->assign("nav", $nav);
        }

        //加载 setting表中的配置
//        $this->config = $this->get_config();
    }

    /**
     * 空操作
     * @param $name
     */
    public function _empty($name){
        $this->to404($name);
    }

    /**
     * 404页
     */
    public function to404(){
        if(IS_AJAX){
            $this->error('','',true,array('code' => 0x0001,'msg' => '没有找到'));
        }else{
            $this->redirect('Admin/Empty/index');
        }

    }

    /**
     * 是否越权
     * @return bool
     */
    public function allow_route(){
        $rank = session('rank');
        $arr = $this->route[ACTION_NAME];

        if(empty($arr)){
            return true;
        }
        if($arr == 'all'){
            return true;
        }

        if( !empty($arr['deny']) &&  in_array( $rank , $arr['deny'])){
            return false;
        }
        if( !empty($arr['allow']) &&  !in_array( $rank , $arr['allow'])){
            return false;
        }
        return true;
    }

    /**
     * json的统一返回形式
     * @param $data   code 错误码   msg 信息  data 数据
     */
    public function json_response($data){
        $this->ajaxReturn(array(
            'code' => empty($data['code']) ? 0 : $data['code'],
            'msg' => empty($data['msg']) ? "" : $data['msg'],
            'data' => empty($data['data']) ? "" : $data['data'],
        ));
    }

    /**
     * 根据用户角色返回导航
     * @return array
     */
    private function get_nav(){
        $rank = session('rank');
            $nav = [
                ['name'=>'首页','url'=>U('Index/index'), 'ico'=>'glyphicon glyphicon-home'],
                ['name'=>'类别管理','url'=>U('Category/index'), 'ico'=>'glyphicon glyphicon-star'],
                ['name'=>'友情链接','url'=>U('Links/index'), 'ico'=>'glyphicon glyphicon-edit'],
                ['name'=>'文章管理','url'=>U('Article/index'), 'ico'=>'glyphicon glyphicon-question-sign'],
                ['name'=>'碎言碎语','url'=>U('Exper/index'), 'ico'=>'glyphicon glyphicon-user'],
                ['name'=>'导航设置','url'=>U('Navs/index'), 'ico'=>'glyphicon glyphicon-info-sign'],
                ['name'=>'个人信息','url'=>U('Introduce/index'), 'ico'=>'glyphicon glyphicon-fire'],
//                ['name'=>'系统设置','url'=>U('Config/index'), 'ico'=>'glyphicon glyphicon-cog'],
            ];
        return $nav;
    }

    /**
     * 得到 setting表中的配置
     */
//    public function get_config(){
//        $config = M('Setting')->find();
//        return $config;
//    }
}