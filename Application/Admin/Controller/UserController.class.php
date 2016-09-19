<?php
/**
 * Created by PhpStorm.
 * User: 孝远
 * Date: 2016/4/24
 * Time: 15:27
 */
namespace Admin\Controller;
class UserController extends BaseController{

    /**
     * 修改密码
     */
    public function change_passwd(){
        $account = session('account');
        $this->assign('account', $account);
        $this->assign('route', '修改密码');
        $this->display();
    }

    /**
     * 添加一个用户
     * @request account  账号
     * @request oid      对应机构id
     */
    public function insert_user(){
        $db = D('User');
        $account = trim(I('account'));
        $oid = (int)I('oid');
        $result = $db->insert_user($account, $oid);

        if($result){
            $this->json_response(array('code' => 0,'data' => $result));
        }else{
            $this->json_response($this->error_arr['unknown']);
        }
    }

    /**
     * 修改一个用户的账号
     * @request account  新账号
     * @request id      用户id
     */
    public function update_user(){
        $db = D('User');
        $account = trim(I('account'));
        $id = (int)I('id');
        $result = $db->update_user($account, $id);

        if($result === false){
            $this->json_response($this->error_arr['unknown']);
        }else{
            $this->json_response(array('code' => 0));
        }
    }

    /**
     * 删除一个用户
     * @request id 用户id
     */
    public function delete_user(){
        $db = D('User');
        $id = (int)I('id');
        $result = $db->delete_user($id);

        if($result){
            $this->json_response(array('code' => 0));
        }else{
            $this->json_response($this->error_arr['unknown']);
        }
    }

    /**
     * 得到一个区的所用账号
     * @request id 区的id 即 机构id
     */
    public function get_area_account(){
        $id = (int)I('id');
        if(empty($id)){
            $this->json_response(array('code' => 1,'data' => '参数错误'));
        }
        $result = M('user')->field('id,account')->where(array('oid' => $id, 'status' => 0))->select();
        if($result === false){
            $this->json_response(array('code' => 1));
        }else{
            $this->json_response(array('code' => 0,'data' => $result));
        }
    }

    /**
     * 修改密码
     */
    public function update_pass(){
        $old_pass = trim(I('pass'));
        $new_pass = trim(I('new_pass'));
        if(empty($old_pass) ||empty($new_pass)){
            return $this->json_response($this->error_arr['empty_pass']);
        }

        if(!$this->checkPass($new_pass)){
            return;
        }

        $result = M('user')->where(array('id' => session('id') , 'password' => md5($old_pass)))->find();
        if(empty($result)){
            return $this->json_response($this->error_arr['pass_error']);
        }

        $result = M('user')->where(array('id' => session('id')))->save(array('password' => md5($new_pass)));

        if($result === false){
            return $this->json_response($this->error_arr['unknown']);
        }

        return $this->json_response();
    }

    /**
     * 重置密码
     */
    public function repass(){
        $account = trim(I('account'));
        $pass = trim(I('pass'));

        if(!$this->checkPass($pass)){
            return;
        }
        $m = M('user');
        $rank = (int)session('rank');
        if($rank != PRIMARY_RANK && $rank != CITY_RANK){
            $this->json_response($this->error_arr['rank_error']);
        }
        if($rank == PRIMARY_RANK){
            $result = $m->table('user')
                ->join('organ on organ.id = user.oid')
                ->where(array('user.account' => $account,'organ.pid' => session('oid') ,'organ.status' => 0, 'user.status' => 0))
                ->find();
            if(empty($result)){
                return $this->json_response($this->error_arr['user_error']);
            }
        }
        $result = $m->table('user')
            ->where(array('account' => $account, 'status' => 0))
            ->save(array('pass' => md5(md5($pass)), 'updatetime' => time()));

        if($result === false){
            $this->json_response($this->error_arr['repass_error']);
        }else{
            $this->json_response();
        }
    }

    /**
     * 判断 密码的合法性
     * @param $pass  密码
     * @return bool|void 格式是否正确
     */
    private function checkPass($pass){
        if(empty($pass)){
            return $this->json_response($this->error_arr['empty_pass']);
        }
        if(!preg_match('/^[a-zA-z0-9_]{6,18}$/', $pass)){
            return $this->json_response($this->error_arr['pass_format_error']);
        }
        return true;
    }

    private $error_arr = [
        'repass_error' => array('code' => 0x9011, 'msg' => '重置失败'),
        'rank_error'   => array('code' => 0x9021, 'msg' => '没用权限'),
        'user_error'   => array('code' => 0x9031, 'msg' => '用户不存在'),
        'empty_pass'   => array('code' => 0x9041, 'msg' => '密码不能为空'),
        'old_pass_error'   => array('code' => 0x9042, 'msg' => '原密码输入错误'),
        'pass_format_error'   => array('code' => 0x9043, 'msg' => '密码格式错误 应为 6 - 18 位字母数字或下划线'),
        'unknown'   => array('code' => 0x9001, 'msg' => '未知错误'),
    ];

    public $route = [
        'change_passwd' => 'all',
        'update_pass' => 'all',
        'insert_user' => ['allow' => [CITY_RANK]],
        'update_user' => ['allow' => [CITY_RANK]],
        'delete_user' => ['allow' => [CITY_RANK]],
        'get_area_account' => ['allow' => [CITY_RANK]],
        'repass' => ['allow' => [CITY_RANK,PRIMARY_RANK]]
    ];

}