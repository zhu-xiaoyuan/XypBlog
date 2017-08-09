<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Think;
/**
 * ThinkPHP 引导类
 */
class Think {

    // 类映射
    private static $_map      = array();

    // 实例化对象
    private static $_instance = array();

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    static public function start() {
      // 注册AUTOLOAD方法
      spl_autoload_register('Think\Think::autoload');      
      // 设定错误和异常处理
      register_shutdown_function('Think\Think::fatalError');    //在PHP进程结束前会去调用。配合error_get_last可以很好的捕获致命错误。
      set_error_handler('Think\Think::appError'); //设置一个用户的函数(error_handler)来处理脚本中出现的错误。
      set_exception_handler('Think\Think::appException'); //设置默认的异常处理程序，用于没有用 try/catch 块来捕获的异常。 在 exception_handler 调用后异常会中止。

      // 初始化文件存储方式
      // Storage类相当于一个接口，可以使用相同方法操作不平台下的文件。
      // STORAGE_TYPE --- File/SAE   默认File
      Storage::connect(STORAGE_TYPE);
      // RUNTIME_PATH --- ./Application/Runtime/
      // APP_MODE --- common
      // $runtimefile --- ./Application/Runtime/common~runtime.php
      $runtimefile  = RUNTIME_PATH.APP_MODE.'~runtime.php';
      // 若不是调试模式 并且 存在运行时文件 直接加载运行时文件
      if(!APP_DEBUG && Storage::has($runtimefile)){
          Storage::load($runtimefile);
      }else{
          // 若是调试模式 并且 存在运行时文件 删除运行时文件 重新构建文件。
          if(Storage::has($runtimefile))
              Storage::unlink($runtimefile);
          $content =  '';
          // 读取应用模式
          // CONF_PATH ---- Application/Common/Conf/
          // MODE_PATH ThinkPHP/Mode/
          // APP_MODE --- common
          // 先去app应用下的Common/Conf里查找core文件，不存在则去引入ThinkPHP/Mode下的common.php文件
          // common.php是普通模式配置惯例，包括config(配置文件)，alias(别名定义)，core(函数和类文件)，tags(行为扩展定义)
          $mode   =   include is_file(CONF_PATH.'core.php')?CONF_PATH.'core.php':MODE_PATH.APP_MODE.'.php';
          // 加载核心文件
          /*
              core(函数和类文件) --- array(
              ThinkPHP/Common/functions.php,  Think系统函数库
              Application/Common/Common/function.php, 开发人员自定义的应用函数库
              ThinkPHP\Library/Think/Hook.class.php,  ThinkPHP系统钩子实现类
              ThinkPHP\Library/Think/App.class.php    ThinkPHP应用程序类
              ThinkPHP\Library/Think/Dispatcher.class.php  Dispatcher类完成URL解析、路由和调度
              ThinkPHP\Library/Think/Route.class.php    ThinkPHP路由解析类
              ThinkPHP\Library/Think/Controller.class.php  ThinkPHP 控制器基类(抽象类)
              ThinkPHP\Library/Think/View.class.php     ThinkPHP视图类
              ThinkPHP\Library/Behavior/BuildLiteBehavior.class.php     创建Lite运行文件可以替换框架入口文件运行
              ThinkPHP\Library/Behavior/ParseTemplateBehavior.class.php  系统行为扩展：模板解析
              ThinkPHP\Library/Behavior/ContentReplaceBehavior.class.php  系统行为扩展：模板内容输出替换
             );
           */
          foreach ($mode['core'] as $file){

              if(is_file($file)) {
                include $file;
                if(!APP_DEBUG) $content   .= compile($file);
              }
          }


          // 加载应用模式配置文件
          /*
           config(配置文件) --- array(
                ThinkPHP/Conf/convention.php,   // 系统惯例配置
                Application/Common/Conf/config.php, // 应用公共配置
          );
        */
          foreach ($mode['config'] as $key=>$file){
              is_numeric($key)?C(load_config($file)):C($key,load_config($file));
          }

          // 读取当前应用模式对应的配置文件
          if('common' != APP_MODE && is_file(CONF_PATH.'config_'.APP_MODE.CONF_EXT))
              C(load_config(CONF_PATH.'config_'.APP_MODE.CONF_EXT));
          // 加载模式别名定义
           /*
           'alias'     =>  array(
              'Think\Log'               => CORE_PATH . 'Log'.EXT,
              'Think\Log\Driver\File'   => CORE_PATH . 'Log/Driver/File'.EXT,
              'Think\Exception'         => CORE_PATH . 'Exception'.EXT,
              'Think\Model'             => CORE_PATH . 'Model'.EXT,
              'Think\Db'                => CORE_PATH . 'Db'.EXT,
              'Think\Template'          => CORE_PATH . 'Template'.EXT,
              'Think\Cache'             => CORE_PATH . 'Cache'.EXT,
              'Think\Cache\Driver\File' => CORE_PATH . 'Cache/Driver/File'.EXT,
              'Think\Storage'           => CORE_PATH . 'Storage'.EXT,
            )
            */

          if(isset($mode['alias'])){
              self::addMap(is_array($mode['alias'])?$mode['alias']:include $mode['alias']);
          }
          // 加载应用别名定义文件
          //  CORE_PATH  --- \ThinkPHP\Library/Think/
          if(is_file(CONF_PATH.'alias.php'))
              self::addMap(include CONF_PATH.'alias.php');
          // 加载模式行为定义
           /*
             tags(别名定义) --- array(
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
          // 通过Hook::import方法把这些标签和类的映射加载到了Hook内部为的tags数组中。
          if(isset($mode['tags'])) {
              Hook::import(is_array($mode['tags'])?$mode['tags']:include $mode['tags']);
          }

          // 加载应用行为定义
          if(is_file(CONF_PATH.'tags.php'))
              // 允许应用增加开发模式配置定义
              Hook::import(include CONF_PATH.'tags.php');   

          // 加载框架底层语言包 错误提示语等
          L(include THINK_PATH.'Lang/'.strtolower(C('DEFAULT_LANG')).'.php');

          if(!false){
              // DEBUG模式的话构架生成运行文件。
              $content  .=  "\nnamespace { Think\Think::addMap(".var_export(self::$_map,true).");";
              $content  .=  "\nL(".var_export(L(),true).");\nC(".var_export(C(),true).');Think\Hook::import('.var_export(Hook::get(),true).');}';
              Storage::put($runtimefile,strip_whitespace('<?php '.$content));
          }else{
            // 调试模式加载系统默认的配置文件
            C(include THINK_PATH.'Conf/debug.php');
            // 读取应用调试配置文件
            if(is_file(CONF_PATH.'debug'.CONF_EXT))
                C(include CONF_PATH.'debug'.CONF_EXT);           
          }
      }

      // 读取当前应用状态对应的配置文件
      if(APP_STATUS && is_file(CONF_PATH.APP_STATUS.CONF_EXT))
          C(include CONF_PATH.APP_STATUS.CONF_EXT);   

      // 设置系统时区
      date_default_timezone_set(C('DEFAULT_TIMEZONE'));

      // 检查应用目录结构 如果不存在则自动创建
      if(C('CHECK_APP_DIR')) {
          $module     =   defined('BIND_MODULE') ? BIND_MODULE : C('DEFAULT_MODULE');
          if(!is_dir(APP_PATH.$module) || !is_dir(LOG_PATH)){
              // 检测应用目录结构
              Build::checkDir($module);
          }
      }
      // 记录加载文件时间
      G('loadTime');
      // 运行应用
      App::run();
    }

    // 注册classmap
    static public function addMap($class, $map=''){
        if(is_array($class)){
            self::$_map = array_merge(self::$_map, $class);
        }else{
            self::$_map[$class] = $map;
        }        
    }

    // 获取classmap
    static public function getMap($class=''){
        if(''===$class){
            return self::$_map;
        }elseif(isset(self::$_map[$class])){
            return self::$_map[$class];
        }else{
            return null;
        }
    }

    /**
     * 类库自动加载
     * @param string $class 对象类名
     * @return void
     */
    public static function autoload($class) {
        // 检查是否存在映射
        if(isset(self::$_map[$class])) {
            include self::$_map[$class];
        }elseif(false !== strpos($class,'\\')){
          $name           =   strstr($class, '\\', true);
          if(in_array($name,array('Think','Org','Behavior','Com','Vendor')) || is_dir(LIB_PATH.$name)){ 
              // Library目录下面的命名空间自动定位
              $path       =   LIB_PATH;
          }else{
              // 检测自定义命名空间 否则就以模块为命名空间
              $namespace  =   C('AUTOLOAD_NAMESPACE');
              $path       =   isset($namespace[$name])? dirname($namespace[$name]).'/' : APP_PATH;
          }
          $filename       =   $path . str_replace('\\', '/', $class) . EXT;

          if(is_file($filename)) {
              // Win环境下面严格区分大小写
              if (IS_WIN && false === strpos(str_replace('/', '\\', realpath($filename)), $class . EXT)){
                  return ;
              }
              include $filename;
          }
            //APP_USE_NAMESPACE (true) --- 应用类库是否使用命名空间
        }elseif (!C('APP_USE_NAMESPACE')) {
            // 自动加载的类库层
            //APP_AUTOLOAD_LAYER (Controller,Model) ---  自动加载的应用类库层 关闭APP_USE_NAMESPACE后有效
            foreach(explode(',',C('APP_AUTOLOAD_LAYER')) as $layer){
                if(substr($class,-strlen($layer))==$layer){
                    // MODULE_PATH --- Application/Admin(Home)/Controller(Model)
                    if(require_cache(MODULE_PATH.$layer.'/'.$class.EXT)) {
                        return ;
                    }
                }
            }
            // 根据自动加载路径设置进行尝试搜索
            foreach (explode(',',C('APP_AUTOLOAD_PATH')) as $path){
                if(import($path.'.'.$class))
                    // 如果加载类成功则返回
                    return ;
            }
        }
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param string $class 对象类名
     * @param string $method 类的静态方法名
     * @return object
     */
    static public function instance($class,$method='') {
        $identify   =   $class.$method;
        if(!isset(self::$_instance[$identify])) {
            if(class_exists($class)){
                $o = new $class();
                if(!empty($method) && method_exists($o,$method))
                    self::$_instance[$identify] = call_user_func(array(&$o, $method));
                else
                    self::$_instance[$identify] = $o;
            }
            else
                self::halt(L('_CLASS_NOT_EXIST_').':'.$class);
        }
        return self::$_instance[$identify];
    }

    /**
     * 自定义异常处理
     * @access public
     * @param mixed $e 异常对象
     */
    static public function appException($e) {
        $error = array();
        $error['message']   =   $e->getMessage();
        /*
        [0] => array(4) {
            ["file"] => string(63) "filename"
            ["line"] => int(173)
            ["function"] => string(1) "E"
            ["args"] => array(1) {
                [0] => string(43) "Think\Controller:dispslay方法不存在！"
            }
        }
         */
        $trace =  $e->getTrace();

        // 程序调用E()函数抛出错误
        if('E'==$trace[0]['function']) {
            $error['file']  =   $trace[0]['file'];
            $error['line']  =   $trace[0]['line'];
        }else{
            $error['file']  =   $e->getFile();
            $error['line']  =   $e->getLine();
        }
        $error['trace']     =   $e->getTraceAsString();

        // 记录日志
        Log::record($error['message'],Log::ERR);
        // 发送404信息
        header('HTTP/1.1 404 Not Found');
        header('Status:404 Not Found');
        // 输出错误
        self::halt($error);
    }

    /**
     * 自定义错误处理
     * @access public
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     * @return void
     */
    static public function appError($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
          case E_ERROR:
          case E_PARSE:
          case E_CORE_ERROR:
          case E_COMPILE_ERROR:
          case E_USER_ERROR:
            ob_end_clean(); /*清空输出缓冲, 其实就是把php默认的错误输出给清除掉*/
            $errorStr = "$errstr ".$errfile." 第 $errline 行.";
            // 根据LOG_RECORD值，决定是否写入错误日志 ，默认不记录日志
            if(C('LOG_RECORD')) Log::write("[$errno] ".$errorStr,Log::ERR);
            self::halt($errorStr);
            break;
          default:
            $errorStr = "[$errno] $errstr ".$errfile." 第 $errline 行.";
            self::trace($errorStr,'','NOTIC');
            break;
      }
    }
    
    // 致命错误捕获
    static public function fatalError() {
        Log::save(); // 保存日志信息
        // error_get_last — 获取最后发生的错误  返回一个数组
        if ($e = error_get_last()) {
            switch($e['type']){
              case E_ERROR:     // 1,致命的运行时错误。
              case E_PARSE:     // 4,编译时语法解析错误。
              case E_CORE_ERROR:    // 16,在PHP初始化启动过程中发生的致命错误。
              case E_COMPILE_ERROR: // 64，致命编译时错误。
              case E_USER_ERROR:   //256,用户产生的错误信息。
                ob_end_clean(); /*清空输出缓冲, 把php默认的错误输出给清除掉*/
                self::halt($e);
                break;
            }
        }
    }

    /**
     * 错误输出
     * @param mixed $error 错误
     * @return void
     */
    static public function halt($error) {
        $e = array();

        if (APP_DEBUG || IS_CLI) {
            //调试模式下输出错误信息
            if (!is_array($error)) {
                $trace          = debug_backtrace();
                $e['message']   = $error;
                $e['file']      = $trace[0]['file'];
                $e['line']      = $trace[0]['line'];
                ob_start();
                debug_print_backtrace();
                $e['trace']     = ob_get_clean();
            } else {
                $e              = $error;
            }
            if(IS_CLI){
                exit(iconv('UTF-8','gbk',$e['message']).PHP_EOL.'FILE: '.$e['file'].'('.$e['line'].')'.PHP_EOL.$e['trace']);
            }
        } else {
            //否则定向到错误页面
            $error_page         = C('ERROR_PAGE');
            if (!empty($error_page)) {
                redirect($error_page);
            } else {
                $message        = is_array($error) ? $error['message'] : $error;
                $e['message']   = C('SHOW_ERROR_MSG')? $message : C('ERROR_MESSAGE');
            }
        }


        // 包含异常页面模板
        $exceptionFile =  C('TMPL_EXCEPTION_FILE',null,THINK_PATH.'Tpl/think_exception.tpl');

        include $exceptionFile;
        exit;
    }

    /**
     * 添加和获取页面Trace记录
     * @param string $value 变量
     * @param string $label 标签
     * @param string $level 日志级别(或者页面Trace的选项卡)
     * @param boolean $record 是否记录日志
     * @return void
     */
    static public function trace($value='[think]',$label='',$level='DEBUG',$record=false) {
        static $_trace =  array();
        if('[think]' === $value){ // 获取trace信息
            return $_trace;
        }else{
            $info   =   ($label?$label.':':'').print_r($value,true);
            $level  =   strtoupper($level);
            
            if((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE')  || $record) {
                Log::record($info,$level,$record);
            }else{
                if(!isset($_trace[$level]) || count($_trace[$level])>C('TRACE_MAX_RECORD')) {
                    $_trace[$level] =   array();
                }
                $_trace[$level][]   =   $info;
            }
        }
    }
}
