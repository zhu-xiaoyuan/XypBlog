<?php
function is_login(){
    if(session('id')){
        return true;
    }else{
        return false;
    }
}

function getDefualtPass(){
    return md5(md5(C('DEFAULT_PASS')));
}

function blog($code , $msg){
    Think\Log::record('USER','0x'.strtoupper(dechex($code)) . ':' . $msg);
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 */
function check_verify($code, $id = 1){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * @param $time 时间戳
 * @return int  $time的下一天早上的0:00的时间戳
 */
function next_date($time){
    return strtotime(date('Y-m-d',$time + 3600 * 24)) ;
}