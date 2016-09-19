<?php
namespace Admin\Controller;
use Think\Controller;
class LoginController extends Controller{

    //错误类型
    private $error_arr = [
        'verify_error' => ['code' => 0xC011, 'msg' => '验证码输入错误'],
        'empty_data' => ['code' => 0xC021, 'msg' => '无效数据'],
        'account_error' => ['code' => 0xC031, 'msg' => '用户名不存在'],
        'pass_error' => ['code' => 0xC041, 'msg' => '密码错误'],
        'organ_error' =>['code' => 0xC051, 'msg' => '没有此账号对应的机构'],
    ] ;

    /**
     * 登陆首页
     */
    public function index(){
        if(is_login()){
            $this->redirect('Index/index');
            return;
        }
        $this->display();
    }
    public function test(){
        echo md5("123456");
//        var_dump($user);
    }
    /**
     * 登陆
     */
    public function login(){
        $verify = trim(I('verify'));
        $account  = trim(I('account'));
        $password = trim(I('password'));
        if(!check_verify($verify)){
            return $this->json_response($this->error_arr['verify_error']);
        }
        if(empty($account) || empty($password)){
            return $this->json_response($this->error_arr['empty_data']);
        }

        $user = M('user')->field('id, password,name')->where(array('account' => $account, 'status' => 0))->find();
        if($user){
            if( $user['password'] == md5($password) ){
                session('id', $user['id']);
                session('account', $account);
                session('name', $user['name']);
                // 登录日志
                \Think\Log::record('Login => user_id :' . $user['id'] . 'name :' . $user['name'] ,'INFO',true);
                $data['code'] = 0;
                $data['msg'] = '成功!';
                $data['data'] = "";
                 $this->json_response($data);
            }else{
                 $this->json_response($this->error_arr['pass_error']);
            }
        }else{
             $this->json_response($this->error_arr['account_error']);
        }
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