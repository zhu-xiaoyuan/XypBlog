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
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 */
class Dispatcher {

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    /*MVC模式的重要一环就是url的解析，然后从中分析中模块，控制器和操作。然后调用执行。那么此类主要做的就是这个。
    由于url形式多变，不同的url形式获得模块，控制器和操作以及其他参数的规则也是不同的。tp提供了四种不同的url模式供我们选择。
    URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
    tp分析url的思路：
    1：首先看是不是兼容模式，如果是的话，就取兼容参数赋值给PATH_INFO变量
    2：其次看是不是cli模式，如果是的话，取其参数赋值改PATH_INFO
    3: 是否开启子域名部署，如果开启了，从中分析出模块，控制器和参数。
    4: 开始分析pathinfo得到控制器，模块和操作*/
    static public function dispatch() {

        // VAR_PATHINFO --- 兼容模式PATHINFO(s)获取变量例如 ?s=/module/action/id/1 后面的参数取决于(
        //   URL_PATHINFO_DEPR(/) -- PATHINFO模式下，各参数之间的分割符号)
        $varPath        =   C('VAR_PATHINFO');
        $varAddon       =   C('VAR_ADDON'); // VAR_ADDON(addon) --- 默认的插件控制器命名空间变量
        $varModule      =   C('VAR_MODULE'); // VAR_MODULE(m) --- 默认模块获取变量
        $varController  =   C('VAR_CONTROLLER'); // VAR_CONTROLLER(c) --- 默认控制器获取变量
        $varAction      =   C('VAR_ACTION'); // VAR_ACTION(a) --- 默认操作获取变量
        $urlCase        =   C('URL_CASE_INSENSITIVE'); //  默认true 表示URL区分大小写 true则表示不区分大小写

        if(isset($_GET[$varPath])) { // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO'] = $_GET[$varPath]; //  /module/action/id/1
            unset($_GET[$varPath]);
        }elseif(IS_CLI){ // CLI模式下 index.php module/controller/action/params/...
            $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        }
        // 开启子域名部署
        if(C('APP_SUB_DOMAIN_DEPLOY')) { // APP_SUB_DOMAIN_DEPLOY(false) --- 是否开启子域名部署
            $rules      = C('APP_SUB_DOMAIN_RULES'); // APP_SUB_DOMAIN_RULES(空array) --- 子域名部署规则
            if(isset($rules[$_SERVER['HTTP_HOST']])) { // 完整域名或者IP配置
                define('APP_DOMAIN',$_SERVER['HTTP_HOST']); // 当前完整域名
                $rule = $rules[APP_DOMAIN];
            }else{  // 未获取完整域名，需要拼接出完整域名
                // APP_DOMAIN_SUFFIX('') --- 域名后缀 如果是com.cn net.cn 之类的后缀必须设置
                if(strpos(C('APP_DOMAIN_SUFFIX'),'.')){ // com.cn net.cn
                    $domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -3);
                }else{
                    $domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -2);
                }

                if(!empty($domain)) {
                    $subDomain = implode('.', $domain);
                    define('SUB_DOMAIN',$subDomain); // 当前完整子域名
                    $domain2   = array_pop($domain); // 二级域名

                    if($domain) { // 存在三级域名
                        $domain3 = array_pop($domain);
                    }
                    // 若是存在对应的子域名部署规则
                    if(isset($rules[$subDomain])) { // 子域名
                        $rule = $rules[$subDomain];
                    }elseif(isset($rules['*.' . $domain2]) && !empty($domain3)){ // 泛三级域名部署规则
                        $rule = $rules['*.' . $domain2];
                        $panDomain = $domain3;
                    }elseif(isset($rules['*']) && !empty($domain2) && 'www' != $domain2 ){ // 泛二级域名部署规则
                        $rule      = $rules['*'];
                        $panDomain = $domain2;
                    }
                }                
            }

            // 根据部署规则取出模块名，控制器，参数等。
            if(!empty($rule)) {
                // 子域名部署规则 '子域名'=>array('模块名[/控制器名]','var1=a&var2=b');
                if(is_array($rule)){
                    list($rule,$vars) = $rule; //$rule---模块名[/控制器名]   $vars---var1=a&var2=b
                }
                // 将域名和控制器名分隔开
                $array      =   explode('/',$rule);
                // 模块绑定
                define('BIND_MODULE',array_shift($array));
                // 控制器绑定         
                if(!empty($array)) {
                    $controller  =   array_shift($array);
                    if($controller){
                        define('BIND_CONTROLLER',$controller);
                    }
                }
                if(isset($vars)) { // 传入参数
                    // 将字符串解析成多个变量 没有返回值
                    parse_str($vars,$parms);  // parms['var1']=a  parms['var2']=b
                    if(isset($panDomain)){
                        $pos = array_search('*', $parms);
                        if(false !== $pos) {
                            // 泛域名作为参数
                            $parms[$pos] = $panDomain;
                        }                         
                    }
                    // 将url中get转递来的参数与子域名部署规则中的参数进行合并
                    $_GET   =  array_merge($_GET,$parms);
                }
            }
        }
        // 分析PATHINFO信息
        // 这里的判断是否为空是针对上面的兼容模式的。如果不是兼容模式的话，那么这里肯定是没有设置的，那么下面条件里的代码果断执行。
        // 如果是兼容模式，PATH_INFO我们就得到了，就不需要执行下面条件中的代码了。
        /*
        下面我们来看看不使用兼容模式情况下的代码执行情况。这里仍然是再做兼容，为了支持更广泛的主机环境下正确获得pathinfo
        首先获得配置项URL_PATHINFO_FETCH，分离出函数名然后执行。配置项中函数的命名格式是 :函数名
        所以这里会先检测是否包含：，如果包含，那么说明用户定义了获得pathinfo的函数，执行执行获得后退出即可。
        如果没有包含，说明用户没有执行，那么就按照tp默认执行的三个服务器端参数有没有。
        */
        if(!isset($_SERVER['PATH_INFO'])) {
            // URL_PATHINFO_FETCH(ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL) --- 用于兼容判断PATH_INFO 参数的SERVER替代变量列表
            $types   =  explode(',',C('URL_PATHINFO_FETCH'));

            foreach ($types as $type){
                if(0===strpos($type,':')) {// 支持函数判断
                    $_SERVER['PATH_INFO'] =   call_user_func(substr($type,1));
                    break;
                }elseif(!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME']))?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME']))   :  $_SERVER[$type];
                    break;
                }
            }
        }

        //URL_PATHINFO_DEPR(/) --- PATHINFO模式下，各参数之间的分割符号
        $depr = C('URL_PATHINFO_DEPR');
        define('MODULE_PATHINFO_DEPR',  $depr);


        if(empty($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = '';
            define('__INFO__','');
            define('__EXT__','');
        }else{  //pathinfo模式下取模块名

            //首先去除pathinfo字符串前后的/(如果有的话)，定义常量
            define('__INFO__',trim($_SERVER['PATH_INFO'],'/'));

            // URL后缀 （如果url是.html或者其他的有后缀的话就拿到这个后缀）
            define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION)));

            // 反过来再次把处理后(去掉前后空格)的pathinfo的值赋给$_SERVER['PATH_INFO']
            $_SERVER['PATH_INFO'] = __INFO__;
            // 开始从pathinfo字符串中去解析mvc。在解析前首先确定有值并且没有定义模块名称和多模块。
            if (__INFO__ && !defined('BIND_MODULE') && C('MULTI_MODULE')){ // 获取模块名
                //解析出模块名称
                $paths      =   explode($depr,__INFO__,2);
                $allowList  =   C('MODULE_ALLOW_LIST'); // 允许的模块列表
                //   一般我们的url例如/moudles/con/action/这样的形式我们解析出的$paths就会是moudles，所以下面这句就没有啥作用。
                //   但是如果解析出的pathinfo是有扩展名称的，例如moudles.html.
                //   那么我们要解析出模块名称的话，那么就需下面一句去掉扩展名称。
                $module     =   preg_replace('/\.' . __EXT__ . '$/i', '',$paths[0]); //模块名

                //解析出模块后，就要验证是不是我们允许的模块，如果是的话我们就把它放到$_GET中。并且重新定义pathinfo的值，
                // 这个时候的pathinfo的值应该是去掉模块名称字符串以后的值。重新赋给$_SERVER['PATH_INFO'] 。
                // 自此为止，我们已经解析出了模块的名称。

                if( empty($allowList) || (is_array($allowList) && in_array_case($module, $allowList))){
                    $_GET[$varModule]       =   $module;
                    $_SERVER['PATH_INFO']   =   isset($paths[1])?$paths[1]:'';
                }
            }                   
        }

        // URL常量
        define('__SELF__',strip_tags($_SERVER[C('URL_REQUEST_URI')]));

        // 获取模块名称
        // 如果是子域名模式 则定义了BIND_MODULE常量，不是的话，就是pathino模式。使用方法获取到模块名
        define('MODULE_NAME', defined('BIND_MODULE')? BIND_MODULE : self::getModule($varModule));

        // 检测模块是否存在
        if( MODULE_NAME && (defined('BIND_MODULE') || !in_array_case(MODULE_NAME,C('MODULE_DENY_LIST')) ) && is_dir(APP_PATH.MODULE_NAME)){


            // 定义当前模块路径  Application/模块名/"
            define('MODULE_PATH', APP_PATH.MODULE_NAME.'/');


            // 定义当前模块的模版缓存路径  Application/Runtime/Cache/"
            C('CACHE_PATH',CACHE_PATH.MODULE_NAME.'/');

            // 模块检测
            Hook::listen('module_check');

            // 加载模块配置文件  Application/模块名/Conf/config.php
            if(is_file(MODULE_PATH.'Conf/config'.CONF_EXT))
                C(load_config(MODULE_PATH.'Conf/config'.CONF_EXT));
            // 加载应用模式对应的配置文件  Application/模块名/Conf/config_common.php
            if('common' != APP_MODE && is_file(MODULE_PATH.'Conf/config_'.APP_MODE.CONF_EXT))
                C(load_config(MODULE_PATH.'Conf/config_'.APP_MODE.CONF_EXT));
            // 当前应用状态对应的配置文件 用于动态加载一下配置文件
            if(APP_STATUS && is_file(MODULE_PATH.'Conf/'.APP_STATUS.CONF_EXT))
                C(load_config(MODULE_PATH.'Conf/'.APP_STATUS.CONF_EXT));

            // 加载模块别名定义 Application/模块名/Conf/alias.php
            if(is_file(MODULE_PATH.'Conf/alias.php'))
                Think::addMap(include MODULE_PATH.'Conf/alias.php');
            // 加载模块tags文件定义 Application/模块名/Conf/tags.php
            if(is_file(MODULE_PATH.'Conf/tags.php'))
                Hook::import(include MODULE_PATH.'Conf/tags.php');
            // 加载模块函数文件  Application/模块名/Common/function.php
            if(is_file(MODULE_PATH.'Common/function.php'))
                include MODULE_PATH.'Common/function.php';
            // 加载模块的扩展配置文件
            load_ext_file(MODULE_PATH);
        }else{
            E(L('_MODULE_NOT_EXIST_').':'.MODULE_NAME);
        }

        if(!defined('__APP__')){
            // URL_MODEL ---  URL访问模式,可选参数0、1、2、3,代表以下四种模式：
            // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式
	        $urlMode        =   C('URL_MODEL');

	        if($urlMode == URL_COMPAT ){// 兼容模式判断
	            define('PHP_FILE',_PHP_FILE_.'?'.$varPath.'=');
	        }elseif($urlMode == URL_REWRITE ) { // REWRITE模式
	            $url    =   dirname(_PHP_FILE_);
	            if($url == '/' || $url == '\\')
	                $url    =   '';
	            define('PHP_FILE',$url);
	        }else {
	            define('PHP_FILE',_PHP_FILE_);
	        }
	        // 当前应用地址
	        define('__APP__',strip_tags(PHP_FILE));
	    }
        // 模块URL地址
        $moduleName    =   defined('MODULE_ALIAS')? MODULE_ALIAS : MODULE_NAME;
        define('__MODULE__',(defined('BIND_MODULE') || !C('MULTI_MODULE'))? __APP__ : __APP__.'/'.($urlCase ? strtolower($moduleName) : $moduleName));

        // URL_ROUTER_ON(false) --- 是否开启URL路由
        // !C('URL_ROUTER_ON') --- >true
        if('' != $_SERVER['PATH_INFO'] && (!true ||  !Route::check()) ){   // 检测路由规则 如果没有则按默认规则调度URL
            Hook::listen('path_info');
            // 检查禁止访问的URL后缀
            if(C('URL_DENY_SUFFIX') && preg_match('/\.('.trim(C('URL_DENY_SUFFIX'),'.').')$/i', $_SERVER['PATH_INFO'])){
                send_http_status(404);
                exit;
            }
            
            // 去除URL后缀
            $_SERVER['PATH_INFO'] = preg_replace(C('URL_HTML_SUFFIX')? '/\.('.trim(C('URL_HTML_SUFFIX'),'.').')$/i' : '/\.'.__EXT__.'$/i', '', $_SERVER['PATH_INFO']);

            $depr   =   C('URL_PATHINFO_DEPR'); //  PATHINFO模式下，各参数之间的分割符号 /

            /*array(2) {
                [0] => string(5) Controller
                [1] => string(5) action
            }*/
            $paths  =   explode($depr,trim($_SERVER['PATH_INFO'],$depr));


            if(!defined('BIND_CONTROLLER')) {// 获取控制器
                if(C('CONTROLLER_LEVEL')>1){ // 控制器层次
                    $_GET[$varController]   =   implode('/',array_slice($paths,0,C('CONTROLLER_LEVEL')));
                    $paths  =   array_slice($paths, C('CONTROLLER_LEVEL'));
                }else{
                    $_GET[$varController]   =   array_shift($paths);
                }
            }
            // 获取操作
            if(!defined('BIND_ACTION')){
                $_GET[$varAction]  =   array_shift($paths);
            }
            // 解析剩余的URL参数
            $var  =  array();
            // URL_PARAMS_BIND --- URL变量绑定到Action方法参数
            // URL_PARAMS_BIND_TYPE --- URL变量绑定的类型 0 按变量名绑定 1 按变量顺序绑定
            if(C('URL_PARAMS_BIND') && 1 == C('URL_PARAMS_BIND_TYPE')){
                // URL参数按顺序绑定变量
                $var    =   $paths;
            }else{
                preg_replace_callback('/(\w+)\/([^\/]+)/', function($match) use(&$var){$var[$match[1]]=strip_tags($match[2]);}, implode('/',$paths));
            }
            $_GET   =  array_merge($var,$_GET);
        }
        // 获取控制器的命名空间（路径）

        define('CONTROLLER_PATH',   self::getSpace($varAddon,$urlCase));
        // 获取控制器和操作名

        define('CONTROLLER_NAME',   defined('BIND_CONTROLLER')? BIND_CONTROLLER : self::getController($varController,$urlCase));
        define('ACTION_NAME',       defined('BIND_ACTION')? BIND_ACTION : self::getAction($varAction,$urlCase));

        // 当前控制器的UR地址
        $controllerName    =   defined('CONTROLLER_ALIAS')? CONTROLLER_ALIAS : CONTROLLER_NAME;
        define('__CONTROLLER__',__MODULE__.$depr.(defined('BIND_CONTROLLER')? '': ( $urlCase ? parse_name($controllerName) : $controllerName )) );

        // 当前操作的URL地址
        define('__ACTION__',__CONTROLLER__.$depr.(defined('ACTION_ALIAS')?ACTION_ALIAS:ACTION_NAME));

        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
     * 获得控制器的命名空间路径 便于插件机制访问
     */
    static private function getSpace($var,$urlCase) {
        $space  =   !empty($_GET[$var])?ucfirst($var).'\\'.strip_tags($_GET[$var]):'';

        unset($_GET[$var]);
        return $space;
    }

    /**
     * 获得实际的控制器名称
     */
    static private function getController($var,$urlCase) {
        $controller = (!empty($_GET[$var])? $_GET[$var]:C('DEFAULT_CONTROLLER'));
        unset($_GET[$var]);
        if($maps = C('URL_CONTROLLER_MAP')) {
            if(isset($maps[strtolower($controller)])) {  // 存在控制器别名使用 别名
                // 记录当前别名
                define('CONTROLLER_ALIAS',strtolower($controller));
                // 获取实际的控制器名
                return   ucfirst($maps[CONTROLLER_ALIAS]);
            }elseif(array_search(strtolower($controller),$maps)){
                // 禁止访问原始控制器
                return   '';
            }
        }
        if($urlCase) {
            // URL地址不区分大小写
            // 智能识别方式 user_type 识别到 UserTypeController 控制器
            $controller = parse_name($controller,1);
        }
        return strip_tags(ucfirst($controller));
    }

    /**
     * 获得实际的操作名称
     */

    static private function getAction($var,$urlCase) {
        $action   = !empty($_POST[$var]) ?
            $_POST[$var] :
            (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_ACTION'));
        unset($_POST[$var],$_GET[$var]);
        // URL_ACTION_MAP参数是一个二维数组，每个数组项表示：
        /*
         * '实际模块名'=>array(
                '操作映射名1'=>'实际操作名1'
                '操作映射名2'=>'实际操作名2'
                ...... )
         */
        if($maps = C('URL_ACTION_MAP')) {
            if(isset($maps[strtolower(CONTROLLER_NAME)])) {
                $maps =   $maps[strtolower(CONTROLLER_NAME)];
                if(isset($maps[strtolower($action)])) {
                    // 记录当前别名
                    define('ACTION_ALIAS',strtolower($action));
                    // 获取实际的操作名
                    if(is_array($maps[ACTION_ALIAS])){
                        parse_str($maps[ACTION_ALIAS][1],$vars);
                        $_GET   =   array_merge($_GET,$vars);
                        return $maps[ACTION_ALIAS][0];
                    }else{
                        return $maps[ACTION_ALIAS];
                    }
                    
                }elseif(array_search(strtolower($action),$maps)){
                    // 禁止访问原始操作
                    return   '';
                }
            }
        }
        return strip_tags( $urlCase? strtolower($action) : $action );
    }

    /**
     * 获得实际的模块名称
     */
    static private function getModule($var) {
        // GET中存在模块名直接获取，不存在使用默认模块名Home
        $module   = (!empty($_GET[$var])?$_GET[$var]:C('DEFAULT_MODULE'));
        unset($_GET[$var]);
        // 若设置MODULE的映射，则返回对应映射
        // '模块映射名'=>'实际模块名'
        if($maps = C('URL_MODULE_MAP')) {
            if(isset($maps[strtolower($module)])) {  // 以$module作为key存在与之映射的别名
                // 记录当前别名
                define('MODULE_ALIAS',strtolower($module));
                // 获取实际的模块名
                return   ucfirst($maps[MODULE_ALIAS]);
            }elseif(array_search(strtolower($module),$maps)){ // $module作为值没有与之映射的别名
                // 若只有传过来的模块名，map中没有与之映射的别名。认为此模块是禁止访问的。
                // 禁止访问原始模块
                return   '';
            }
        }
        return strip_tags(ucfirst($module)); //从字符串中去除 HTML 和 PHP 标记
    }

}
