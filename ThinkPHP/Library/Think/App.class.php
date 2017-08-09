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
 * ThinkPHP 应用程序类 执行应用过程管理
 */
class App {

    /**
     * 应用程序初始化
     * @access public
     * @return void
     */
    static public function init() {
        // 加载动态应用公共文件和配置 主要是用来加载指定路径目录下的各种文件，主要是自定义的函数文件和自定义的配置文件
        // load_ext_file函数用于加载开发人员自定义外部文件
        //  将文件 放置(COMMON_PATH --- Application/Common/)下的Common/**.php
        load_ext_file(COMMON_PATH);
        
        // 定义当前请求的系统常量
        /*
        此处定义了请求开始时候的时间。
        下面说一下time函数和$_SERVER['REQUEST_TIME']的区别：
        比如一个php应用，time获取的时候是time执行时那个时刻的时间。但是后者$_SERVER['REQUEST_TIME']不管在哪里获得的时间都是php应用执行第一行那个时刻的时间。
        */
        define('NOW_TIME',      $_SERVER['REQUEST_TIME']);
        // 获得请求的方法。这里支持四中：get，post，put和delete
        define('REQUEST_METHOD',$_SERVER['REQUEST_METHOD']);
        define('IS_GET',        REQUEST_METHOD =='GET' ? true : false);
        define('IS_POST',       REQUEST_METHOD =='POST' ? true : false);
        define('IS_PUT',        REQUEST_METHOD =='PUT' ? true : false);
        define('IS_DELETE',     REQUEST_METHOD =='DELETE' ? true : false);
        // 可以学习到怎样判断是ajax请求。
        define('IS_AJAX',       ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);

        // URL调度
        // 此类为mvc框架的核心类。主要用于从url中解析出模块，控制器和操作以及参数。为后面的程序执行提供必需的基础。
        Dispatcher::dispatch();

        if(C('REQUEST_VARS_FILTER')){
            // 全局安全过滤
            // array_walk_recursive — 对数组中的每个成员递归地应用用户函数
            // think_filter 过滤函数 过滤查询特殊字符
            array_walk_recursive($_GET,     'think_filter');
            array_walk_recursive($_POST,    'think_filter');
            array_walk_recursive($_REQUEST, 'think_filter');
        }
        
        // URL调度结束标签 在这里tp使用了钩子功能。可以让开发者在url调度后添加自己的自定义代码。
        Hook::listen('url_dispatch');         

        // 日志目录转换为绝对路径
        C('LOG_PATH',   realpath(LOG_PATH).'/'.MODULE_NAME.'/');
        // TMPL_EXCEPTION_FILE 改为绝对地址
        C('TMPL_EXCEPTION_FILE',realpath(C('TMPL_EXCEPTION_FILE')));
        return ;
    }

    /**
     * 执行应用程序
     * @access public
     * @return void
     */
    static public function exec() {
        /*
        控制器名称的安全监测。
        经过url调度类解析后会从url中解析出控制器名称。那么这里就是要对解析出来的控制器名称做安全监测。
        ^[A-Za-z](\/|\w)*$可以匹配形如asas/asasasasas/  这样的形式
        如果匹配不到，就把$module设置为false
        */
        if(!preg_match('/^[A-Za-z](\/|\w)*$/',CONTROLLER_NAME)){ // 安全检测
            $module  =  false;
        }elseif(C('ACTION_BIND_CLASS')){
            //下面是tp的功能'操作绑定到类'的实现代码。
            /*
            主要思路：
            一般http://serverName/Home/Index/index这种url解析出来的模块名是Home。控制器名是Index，操作名称index。
            组合出的路径应该是Application/Home/Controller/IndexController.class.php里面定义的index方法
            但是我们一旦定义了操作绑定到类，类似上述的url解析出来的模块名是Home。控制器名是Index，操作名称index。
            这样的话组合的路径有差别：Application/Home/Controller/Index/index.class.php中的run方法。
            注意差别，如果我们定义了操作绑定到类，那么我们就得在指定的控制器文件夹下创建一个同操作名称相同的类。
            在类里面定义run方法用于执行。
            */
            // 操作绑定到类：模块\Controller\控制器\操作
            $layer  =   C('DEFAULT_C_LAYER'); //  默认的控制器层名称 默认是Controller
            if(is_dir(MODULE_PATH.$layer.'/'.CONTROLLER_NAME)){ //
                $namespace  =   MODULE_NAME.'\\'.$layer.'\\'.CONTROLLER_NAME.'\\';
            }else{
                // 空控制器
                $namespace  =   MODULE_NAME.'\\'.$layer.'\\_empty\\';                    
            }
            $actionName     =   strtolower(ACTION_NAME);
            //组合出实际调用了类路径。如果没有找到，那么定义空操作。那么就会执行空操作方法
            if(class_exists($namespace.$actionName)){
                $class   =  $namespace.$actionName;
            /*
             *  操作方法绑定到类后，一样可以支持空控制器，我们可以创建 Application/Home/Controller/_empty目录，
             *  即表示如果找不到当前的控制器的话，会到_empty控制器目录下面定位操作方法。
             */
            }elseif(class_exists($namespace.'_empty')){
                // 空操作
                $class   =  $namespace.'_empty';
            }else{
                E(L('_ERROR_ACTION_').':'.ACTION_NAME);
            }
            $module  =  new $class;
            // 操作绑定到类后 固定执行run入口
            $action  =  'run';
        }else{
            //创建控制器实例
            $module  =  controller(CONTROLLER_NAME,CONTROLLER_PATH);
        }
        /*
            到此为止，我们应该可以得到一个实例化的类了。
            如果是操作绑定到类的话，那么$mouble类应该是  控制器/类。action=run
            如果不是，那么$moudle类他应该是控制器类。action=url解析出的参数方法
        */
        // 如果没有匹配到正确的控制器名称或者没有不存在要实力话的类，那么执行空控制器操作。
        if(!$module) {
            if('4e5e5d7364f443e28fbf0d3ae744a59a' == CONTROLLER_NAME) {
                header("Content-type:image/png");
                exit(base64_decode(App::logo()));
            }

            // 是否定义Empty控制器
            $module = A('Empty');
            if(!$module){
                E(L('_CONTROLLER_NOT_EXIST_').':'.CONTROLLER_NAME);
            }
        }

        // 获取当前操作名 支持动态路由
        if(!isset($action)){
            // ACTION_NAME --- 具体的方法
            //ACTION_SUFFIX('') --- 操作方法后缀
            $action    =   ACTION_NAME.C('ACTION_SUFFIX');
        }
        try{
            if(!preg_match('/^[A-Za-z](\w)*$/',$action)){
                // 非法操作
                throw new \ReflectionException();
            }
            //执行当前操作
            // 利用反射得到控制器类的方法信息  反射作用：一个是对对象进行调试，另一个是获取类的信息。
            // 常用于对象反射有三个类 ReflectionClass  ReflectionObject ReflectionMethod
            // ReflectionClass多用于反射类声明时的结构而不是类实例化后的结构，所以使用ReflectionClass去获得
            // 对象实例化后的属性是获取不到了，ReflectionObject就是反射类实例化之后的结构。
            // ReflectionMethod 获取一个类/对象中一个方法的有关信息
            $method =   new \ReflectionMethod($module, $action);
            if($method->isPublic() && !$method->isStatic()) {
                $class  =   new \ReflectionClass($module);
                // 前置操作
                if($class->hasMethod('_before_'.$action)) {
                    $before =   $class->getMethod('_before_'.$action);
                    if($before->isPublic()) {
                        $before->invoke($module); //执行一个反射的方法。
                    }
                }
                // URL参数绑定检测
                /*
                    如果方法的参数大于0，并且设置了参数绑定（什么是参数绑定，参照官方文档）
                    如果当真设置了参数绑定，那么经过url解析后会把url中的参数解析到$_GET变量中。
                    所以这里仅仅需要把post和put提交的参数合并到get即可。
                */
                //  URL_PARAMS_BIND --- URL变量绑定到Action方法参数
                if($method->getNumberOfParameters()>0 && C('URL_PARAMS_BIND')){
                    switch($_SERVER['REQUEST_METHOD']) {
                        case 'POST':
                            $vars    =  array_merge($_GET,$_POST);
                            break;
                        case 'PUT':
                            parse_str(file_get_contents('php://input'), $vars);
                            break;
                        default:
                            $vars  =  $_GET;
                    }
                    $params =  $method->getParameters();
                    //C('URL_PARAMS_BIND_TYPE') --- URL变量绑定的类型 0 按变量名绑定 1 按变量顺序绑定
                    $paramsBindType     =   C('URL_PARAMS_BIND_TYPE');
                    foreach ($params as $param){
                        $name = $param->getName();
                        if( 1 == $paramsBindType && !empty($vars) ){
                            $args[] =   array_shift($vars);
                        }elseif( 0 == $paramsBindType && isset($vars[$name])){
                            $args[] =   $vars[$name];
                        }elseif($param->isDefaultValueAvailable()){
                            $args[] =   $param->getDefaultValue();
                        }else{
                            E(L('_PARAM_ERROR_').':'.$name);
                        }   
                    }
                    // 开启绑定参数过滤机制
                    /* 这里使用了array_walk_recursive函数。首先tp默认会对传进来的args参数调用函数filter_exp进行
                    过滤检测。其次，还允许开发者定义自己的过滤函数，tp会依次调用。这里还用到了tp自己内置的
                    一个函数array_map_recursive。里面主要是递归对参数进行过滤。以后分析。*/
                    if(C('URL_PARAMS_SAFE')){
                        //
                        // DEFAULT_FILTER --- htmlspecialchars默认参数过滤方法 用于I函数...
                        $filters     =   C('URL_PARAMS_FILTER')?:C('DEFAULT_FILTER');
                        if($filters) {
                            $filters    =   explode(',',$filters);
                            foreach($filters as $filter){
                                $args   =   array_map_recursive($filter,$args); // 参数过滤
                            }
                        }                        
                    }

                    array_walk_recursive($args,'think_filter');
                    $method->invokeArgs($module,$args);
                }else{

                    $method->invoke($module);
                }
                // 后置操作
                if($class->hasMethod('_after_'.$action)) {
                    $after =   $class->getMethod('_after_'.$action);
                    if($after->isPublic()) {
                        $after->invoke($module);
                    }
                }
            }else{
                // 操作方法不是Public 抛出异常
                throw new \ReflectionException();
            }

        } catch (\ReflectionException $e) { 
            // 方法调用发生异常后 引导到__call方法处理
            $method = new \ReflectionMethod($module,'__call');
            $method->invokeArgs($module,array($action,''));
        }
        return ;
    }

    /**
     * 运行应用实例 入口文件使用的快捷方法
     * @access public
     * @return void
     */
    static public function run() {
        // 应用初始化标签  此钩子允许应用开发者在应用加载之前做一些事情
        Hook::listen('app_init');
        //开始加载应用
        App::init();
        // 应用开始标签
        /**
         * Hook::listen(‘app_begin’);就是一个监听。当程序执行到此处代码的时候，这个代码会去执行listen方法，
         * 此方法会去检测Hook持有的tags数组中是否含有app_begin标签，如果有的话就去看其对应的类文件，
         * 并到ThinkPHP\Library\Behavior目录下去寻找对应的类文件并加载实例化。然后就去调用实例化对象的run方法并执行。
         * 由此可见，如果我们想要在应用执行开始的时候加一些我们自己的实现逻辑，只需要写一个带有run方法的行为类，
         * 这个类一般继承自Behavior类，然后在run方法中写入自己的逻辑，然后把我们写好的类名加到模式文件中，
         * 这样就可以轻松的做到扩展核心代码了。
         */
        Hook::listen('app_begin');
        // Session初始化
        // SESSION_OPTIONS --- session 配置数组 支持type name id path expire domain 等参数
        if(!IS_CLI){
            session(C('SESSION_OPTIONS'));
        }
        // 记录应用初始化时间
        G('initTime');
        App::exec();
        // 应用结束标签
        Hook::listen('app_end');
        return ;
    }

    static public function logo(){
        return 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyBpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNSBXaW5kb3dzIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjVERDVENkZGQjkyNDExRTE5REY3RDQ5RTQ2RTRDQUJCIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjVERDVENzAwQjkyNDExRTE5REY3RDQ5RTQ2RTRDQUJCIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NURENUQ2RkRCOTI0MTFFMTlERjdENDlFNDZFNENBQkIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NURENUQ2RkVCOTI0MTFFMTlERjdENDlFNDZFNENBQkIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5fx6IRAAAMCElEQVR42sxae3BU1Rk/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';
    }
}