<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2013 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP系统钩子实现
 */

/**
 * hinkphp的插件机制主要依靠的是Hook.class.php这个类，官方文档中在行为扩展也主要依靠这个类来实现。
 * 下面我们来具体看看tp是怎么利用这个类来实现行为扩展的。
 * 首先，行为扩展是什么？有wordpress二次开发经验的同学应该很容易明白，其实就是钩子，
 * tp在其内核的执行过程中内置了诸多钩子，这些钩子可以允许我们能够在不改变内核代码的基础上来对内核进行一定程度的修改。
 * tp的钩子机制的实现类就是Hook.class.php。Hook.class.php内部维护了一个数组，这个数组的键就是钩子的名称，值就是类的名称的集合。
 * 我们利用Hook类的add方法可以添加一个钩子，其实就是往这个维护的数组上添加一个键值。tp默认已经定义了很多钩子标签。
    app_init 应用初始化标签位
    path_info PATH_INFO检测标签位
    app_begin 应用开始标签位
    action_name 操作方法名标签位
    action_begin 控制器开始标签位
    view_begin 视图输出开始标签位
    view_parse 视图解析标签位
    template_filter 模板内容解析标签位
    view_filter 视图输出过滤标签位
    view_end 视图输出结束标签位
    action_end 控制器结束标签位
    app_end 应用结束标签位
 */
// 在3.2版本的tp框架中，钩子标签的实现机制是这样的。
/*
 * 首先所有的钩子标签和其对应的类是记录在应用模式文件中。tp默认的应用模式是common，
 * 对应的应用模式文件是Thinkphp/Mode/Common.php文件。在此文件中我们可以看到行为扩展的定义
 */
class Hook {

    /*
     以键值对的形式存储标签和类的映射。 array(
         'app_init'     =>  array(
            'Behavior\BuildLiteBehavior', // 生成运行Lite文件
        ),
        'app_begin'     =>  array(
            'Behavior\ReadHtmlCacheBehavior', // 读取静态缓存
        ),
        'app_end'       =>  array(
            'Behavior\ShowPageTraceBehavior', // 页面Trace显示
        ),
        'view_parse'    =>  array(
            'Behavior\ParseTemplateBehavior', // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
        ),
        'template_filter'=> array(
            'Behavior\ContentReplaceBehavior', // 模板输出替换
        ),
        'view_filter'   =>  array(
            'Behavior\WriteHtmlCacheBehavior', // 写入静态缓存
        ),
    );
        */
    static private  $tags       =   array();

    /**
     * 动态添加插件到某个标签
     * @param string $tag 标签名称
     * @param mixed $name 插件名称
     * @return void
     */
    //其实就是把$tag作为键，$name 作为对应的额值加入到tags数组中。如果$name是一个数组就合并到tags中。
    static public function add($tag,$name) {
        if(!isset(self::$tags[$tag])){
            self::$tags[$tag]   =   array();
        }
        if(is_array($name)){
            self::$tags[$tag]   =   array_merge(self::$tags[$tag],$name);
        }else{
            self::$tags[$tag][] =   $name;
        }
    }

    /**
     * 批量导入插件
     * @param array $data 插件信息
     * @param boolean $recursive 是否递归合并
     * @return void
     */
    // 导入插件的本质还是把数据加入到tags数组中。但是其传入的参数是$data数组，
    // $data本身就是一个类似于tags的东西，它存储的也是标签和类的映射。所以是把$data和$tags合并了。

    static public function import($data,$recursive=true) {
        if(!$recursive){ // 覆盖导入
            self::$tags   =   array_merge(self::$tags,$data);
        }else{ // 合并导入
            foreach ($data as $tag=>$val){
                if(!isset(self::$tags[$tag]))
                    self::$tags[$tag]   =   array();            
                if(!empty($val['_overlay'])){
                    // 可以针对某个标签指定覆盖模式
                    unset($val['_overlay']);
                    self::$tags[$tag]   =   $val;
                }else{
                    // 合并模式
                    self::$tags[$tag]   =   array_merge(self::$tags[$tag],$val);
                }
            }            
        }
    }

    /**
     * 获取插件信息
     * @param string $tag 插件位置 留空获取全部
     * @return array
     */
    static public function get($tag='') {
        if(empty($tag)){
            // 获取全部的插件信息
            return self::$tags;
        }else{
            return self::$tags[$tag];
        }
    }

    /**
     * 监听标签的插件
     * @param string $tag 标签名称
     * @param mixed $params 传入参数
     * @return void
     */
    // 此函数最为重要，其中调用Hook类的另外一个重要方法exec来执行对应钩子标签的类。
    static public function listen($tag, &$params=NULL) {
        if(isset(self::$tags[$tag])) {
            if(APP_DEBUG) {
                G($tag.'Start');
                trace('[ '.$tag.' ] --START--','','INFO');
            }
            foreach (self::$tags[$tag] as $name) {
                APP_DEBUG && G($name.'_start');
                $result =   self::exec($name, $tag,$params);
                if(APP_DEBUG){
                    G($name.'_end');
                    trace('Run '.$name.' [ RunTime:'.G($name.'_start',$name.'_end',6).'s ]','','INFO');
                }

                if(false === $result) {
                    // 如果返回false 则中断插件执行
                    return ;
                }
            }
            if(APP_DEBUG) { // 记录行为的执行日志
                trace('[ '.$tag.' ] --END-- [ RunTime:'.G($tag.'Start',$tag.'End',6).'s ]','','INFO');
            }
        }
        return;
    }

    /**
     * 执行某个插件
     * @param string $name 插件名称
     * @param string $tag 方法名（标签名）     
     * @param Mixed $params 传入的参数
     * @return void
     */
    //  执行插件的原理：其实就是通过标签从tags数组中得到类名的集合，然后拼凑出类文件名称，实例化类，执行类的run方法。
    static public function exec($name, $tag,&$params=NULL) {
        if('Behavior' == substr($name,-8) ){
            // 行为扩展必须用run入口方法
            $tag    =   'run';
        }
        $addon   = new $name();
        return $addon->$tag($params);
    }
}
