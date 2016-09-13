<?php
namespace Admin\Controller;
use Think\Controller;
class LoginController extends Controller{

    /**
     * 登陆首页
     */
    public function index(){
        $this->display();
    }

    /**
     * 登陆
     */
    public function login(){
//        if(is_login()){
        /*if(true){
            $this->redirect('Index/index');
            return;
        }
        $verify = trim(I('verify'));
        $account  = trim(I('account'));
        $password = trim(I('password'));
        if(!check_verify($verify)){
            return $this->json_response($this->error_arr['verify_error']);
        }
        if(empty($account) || empty($password)){
            return $this->json_response($this->error_arr['empty_data']);
        }*/
    }

    //退出
    public function logout(){
        if(is_login()){
            \Think\Log::record('Logout => user_id :' . session('id') . 'name :' .session('name') ,'INFO', true);
            session(null);
        }
        $this->redirect('Login/index');
    }

    /**
     * 登录密码
     */
    public function verify(){
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    private function json_response($data){
        $this->ajaxReturn(array(
            'code' => empty($data['code']) ? 0 : $data['code'],
            'msg' => empty($data['msg']) ? "" : $data['msg'],
            'data' => empty($data['data']) ? "" : $data['data'],
        ));
    }
}