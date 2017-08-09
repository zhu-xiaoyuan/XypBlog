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
 * ThinkPHP路由解析类
 */

/*
 *  在ThinkPHP框架中，是支持URL路由功能，要启用路由功能，需要设置ROUTER_ON 参数为true。
 开启路由功能后，系统会自动进行路由检测，如果在路由定义里面找到和当前URL匹配的路由名称，就会进行路由解析和重定向。
 在thinkphp中，程序会先从请求的url中解析出来一串字符，如果没有开启路由的话，
 那么tp就会从这串字符中解析出来模块，控制器和方法以及参数。如果开启路由的话，那么tp会遍历路由规则数组，
 然后用从url解析出来的这串字符依次和路由表达式进行正则匹配或者规则匹配，会优先匹配到一个路由表达式，
 找到该路由表达式对应的路由地址，从路由地址从解析出来控制器，方法以及参数。
 系统在执行Dispatch解析时，会判断当前URL是否存在定义的路由名称，如果有就会按照定义的路由规则来进行URL解析。
 */
class Route {
    
    // 路由检测
    /*
    路由检测的基本思路：
    1:首先获得pathinfo的url值
    2：先进行静态路由的判断，就是用处理后的url值和静态路由规则进行比较，如果有相同的就说明匹配成功，返回true。
    3：如果静态路由匹配不成功，就进行动态路由匹配。动态路由分为正则路由和规则路由，先匹配正则路由，后匹配规则路由。
    */
    public static function check(){

        // PATHINFO模式下，各参数之间的分割符号 /
        $depr   =   C('URL_PATHINFO_DEPR');
        // 控制器/方法名
        $regx   =   preg_replace('/\.'.__EXT__.'$/i','',trim($_SERVER['PATH_INFO'],$depr));

        // 分隔符替换 确保路由定义使用统一的分隔符
        if('/' != $depr){
            $regx = str_replace($depr,'/',$regx);
        }


        // URL映射定义（静态路由）
        //如果静态路由规则中有和这个url匹配的。那么得到路由地址，然后用parseurl对真正的url进行解析，
        // 解析出需要的变量返回。
        $maps   =   C('URL_MAP_RULES');
        if(isset($maps[$regx])) {
            // 使用静态路由解析规范的路由地址
             /* $var = [
                   'a'=>'操作'，
                    'm'=>'模块',
                    'c'=>'控制器',
                    '参数名'=>值,
                    '参数名'=>值,
                    .....
                ];*/
            $var    =   self::parseUrl($maps[$regx]);
            $_GET   =   array_merge($var, $_GET);
            return true;                
        }        
        // 动态路由处理
        /*
        如果静态路由没有匹配成功，那么进入动态路由匹配。
        首先从配置文件中获得路由规则数组。
        这个数组的中规则的写法有两种：
        第一种是  '路由表达式'=>'路由地址和传入参数'
        第二种是   array('路由表达式','路由地址','传入参数')
        所以我们遍历这个规则数组的时候需要区分对待。
        通过判断$rule变量是不是数字来判断是第一种还是第二种。is_numeric($rule)
        如果是第二种，那么规则数组的键应该是数字，当是第二种的时候，我们就从数组中出栈，
        取出第一个元素即路由表达式赋值给$rule.
        如果是第一种，那么什么都不用做，$rule自然就是路由表达式。
        至此为止，我们的$rule变量里面存储的就是路由表达式了。
        */
        $routes =   C('URL_ROUTE_RULES');
        if(!empty($routes)) {
            foreach ($routes as $rule=>$route){
                if(is_numeric($rule)){
                    // 支持 array('rule','adddress',...) 定义路由
                    $rule   =   array_shift($route);
                }
                /*
                下面还是对数组写法的判断。
                接着上面的来，如果你的规则数组是数组格式array('路由表达式','路由地址','传入参数')
                经过上面的处理，第一个元素是规则表达式并且已经出栈。
                那么现在route这个数组的第一个元素是url，第二个元素是参数，第三个元素是选项。
                这里其实是实现文档中所说的：当路由地址采用数组方式定义的时候，还可以传入额外的路由参数。
                我们得到第三个元素的值，通过这个可以进行url后缀检测、请求类型检测，最nb的是如果你这个参数
                是一个函数，你还可以自定义函数检测。
                'blog/:id'=>array('blog/read','status=1&app_id=5',array('callback'=>'checkFun')),
                就可以自定义定义checkFun函数来检测是否生效，如果函数返回false则表示不生效。
                */
                if(is_array($route) && isset($route[2])){
                    // 路由参数
                    $options    =   $route[2];
                    if(isset($options['ext']) && __EXT__ != $options['ext']){
                        // URL后缀检测
                        continue;
                    }
                    if(isset($options['method']) && REQUEST_METHOD != $options['method']){
                        // 请求类型检测
                        continue;
                    }
                    // 自定义检测
                    if(!empty($options['callback']) && is_callable($options['callback'])) {
                        if(false === call_user_func($options['callback'])) {
                            continue;
                        }
                    }                    
                }

                /*
                上面的检测完成后，下面进入正则路由。
                判断路由表达式是否是正则路由的条件是其第一个字符必须是/,并且$regx值必须符合这个路由表达式的正则规则
                如果判断是正则路由，那么就来处理对应的路由地址。
                tp中的路由地址有多种形式，其中比较特殊的一种是函数形式。也就是说当我们的url匹配到一个路由表达式后，
                就会去执行路由表达式对应的函数，而不是去解析平常的路由地址。tp在这里使用了php5.3的闭包。
                首先会根据$route instanceof \Closure来判断是不是一个闭包函数，如果是的话，就调用invokeRegx方法去
                调用执行闭包函数并返回结果。
                如果不是闭包函数，那么就是路由地址了。那么就调用parseRegex方法去解析这个路由地址，解析出控制器和方法以及参数等。
                如果不是正则路由，那么就是规则路由了。
                对于规则路由的判断是如下逻辑：
                首先计算出$regx值中的/的数量
                然后计算出路由表达式中/的数量
                如果是前者大于或者等于后者或者后者包含[
                */
                if(0===strpos($rule,'/') && preg_match($rule,$regx,$matches)) { // 正则路由
                    if($route instanceof \Closure) {
                        // 执行闭包
                        $result = self::invokeRegx($route, $matches);
                        // 如果返回布尔值 则继续执行
                        return is_bool($result) ? $result : exit;
                    }else{
                        return self::parseRegex($matches,$route,$regx);
                    }
                }else{ // 规则路由
                    $len1   =   substr_count($regx,'/');
                    $len2   =   substr_count($rule,'/');
                    if($len1>=$len2 || strpos($rule,'[')) {
                        if('$' == substr($rule,-1,1)) {// 完整匹配
                            if($len1 != $len2) {
                                continue;
                            }else{
                                $rule =  substr($rule,0,-1);
                            }
                        }
                        $match  =  self::checkUrlMatch($regx,$rule);
                        if(false !== $match)  {
                            if($route instanceof \Closure) {
                                // 执行闭包
                                $result = self::invokeRule($route, $match);
                                // 如果返回布尔值 则继续执行
                                return is_bool($result) ? $result : exit;
                            }else{
                                return self::parseRule($rule,$route,$regx);
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    // 检测URL和规则路由是否匹配
    /*
    此函数主要用来检测规则路由和url是否匹配。传入的第一个参数是url，第二个参数是规则路由。
    基本逻辑：
    首先用explore函数把要检测的url值和规则路由按照/进行拆分。
    遍历拆分后的规则路由数组，一一处理每个元素。
    按照先特殊后普通的原则。
    首先实现路由匹配的可选定义[:month\d]变量用[ ]包含起来后就表示该变量是路由匹配的可选变量。
    如果要检测的元素字符串以[:开头，就截取出中括号中间的字符串赋值给$val，即:month\d.
    下面是规则排除的实现逻辑。
    如果$val是以：开头的，就检测$val字符串中|的位置
    */
    private static function checkUrlMatch($regx,$rule) {
        $m1 = explode('/',$regx);
        $m2 = explode('/',$rule);
        $var = array();         
        foreach ($m2 as $key=>$val){
            if(0 === strpos($val,'[:')){
                $val    =   substr($val,1,-1);
            }
                
            if(':' == substr($val,0,1)) {// 动态变量
                if($pos = strpos($val,'|')){
                    // 使用函数过滤
                    $val   =   substr($val,1,$pos-1);
                }
                if(strpos($val,'\\')) {
                    $type = substr($val,-1);
                    if('d'==$type) {
                        if(isset($m1[$key]) && !is_numeric($m1[$key]))
                            return false;
                    }
                    $name = substr($val, 1, -2);
                }elseif($pos = strpos($val,'^')){
                    $array   =  explode('-',substr(strstr($val,'^'),1));
                    if(in_array($m1[$key],$array)) {
                        return false;
                    }
                    $name = substr($val, 1, $pos - 1);
                }else{
                    $name = substr($val, 1);
                }
                $var[$name] = isset($m1[$key])?$m1[$key]:'';
            }elseif(0 !== strcasecmp($val,$m1[$key])){
                return false;
            }
        }
        // 成功匹配后返回URL中的动态变量数组
        return $var;
    }

    // 解析规范的路由地址
    // 静态路由的路由地址 只支持字符串，格式：[控制器/操作?]参数1=值1&参数2=值2
    /*
        该函数的主要功能是从url中解析出来控制器，操作和传递的参数。
        首先检测url中是否含有?，如果函数有的话，使用parse_url去处理url，
        本函数解析一个 URL 并返回一个关联数组，包含在 URL 中出现的各种组成部分。
         本函数不是用来验证给定 URL 的合法性的，只是将其分解为下面列出的部分。不完整的 URL 也被接受。
        $path将得到域名后面，？之前的字符串。使用/分割$path.
        $info[query]得到的是？后面的get参数字符串。我们这里使用parse_url函数可以把这个get参数字符串
        分割成一个数组。
    */
    private static function parseUrl($url) {

        $var  =  array();

        // 带参数
        if(false !== strpos($url,'?')) { // [控制器/操作?]参数1=值1&参数2=值2...
            $info   =  parse_url($url);
            $path   = explode('/',$info['path']); // ['控制器'，操作 ];
            parse_str($info['query'],$var); // $info['query']参数字符串，解析成$var参数数组
        // 不带参数
        }elseif(strpos($url,'/')){ // [控制器/操作]
            $path = explode('/',$url);
        }else{ // 参数1=值1&参数2=值2...
            parse_str($url,$var);
        }
        //在上面处理后，检测如果$path存在，就返回$path数组的最后一个元素为操作方法。
        //再次检测$path变量，如果还存在，就再次返回最后一个元素作为控制器。
        //再次检测$path变量，如果还存在，就再次返回作为模块名称。
        //例如$path=array('home','index','init');那么按照下面的操作，action是init，控制器是index。模块就是home
        //分别赋值给$var数组并返回$var.这样我么就从url中解析处理模块，控制器，操作以及其他get参数。

        if(isset($path)) {
            $var[C('VAR_ACTION')] = array_pop($path);
            if(!empty($path)) {
                $var[C('VAR_CONTROLLER')] = array_pop($path);
            }
            if(!empty($path)) {
                $var[C('VAR_MODULE')]  = array_pop($path);
            }
        }
        return $var;
    }

    // 解析规则路由
    // '路由规则'=>'[控制器/操作]?额外参数1=值1&额外参数2=值2...'
    // '路由规则'=>array('[控制器/操作]','额外参数1=值1&额外参数2=值2...')
    // '路由规则'=>'外部地址'
    // '路由规则'=>array('外部地址','重定向代码')
    // 路由规则中 :开头 表示动态变量
    // 外部地址中可以用动态变量 采用 :1 :2 的方式
    // 'news/:month/:day/:id'=>array('News/read?cate=1','status=1'),
    // 'new/:id'=>array('/new.php?id=:1',301), 重定向
    private static function parseRule($rule,$route,$regx) {
        // 获取路由地址规则
        $url   =  is_array($route)?$route[0]:$route;
        // 获取URL地址中的参数
        $paths = explode('/',$regx);
        // 解析路由规则
        $matches  =  array();
        $rule =  explode('/',$rule);
        foreach ($rule as $item){
            $fun    =   '';
            if(0 === strpos($item,'[:')){
                $item   =   substr($item,1,-1);
            }
            if(0===strpos($item,':')) { // 动态变量获取
                if($pos = strpos($item,'|')){ 
                    // 支持函数过滤
                    $fun  =  substr($item,$pos+1);
                    $item =  substr($item,0,$pos);                    
                }
                if($pos = strpos($item,'^') ) {
                    $var  =  substr($item,1,$pos-1);
                }elseif(strpos($item,'\\')){
                    $var  =  substr($item,1,-2);
                }else{
                    $var  =  substr($item,1);
                }
                $matches[$var] = !empty($fun)? $fun(array_shift($paths)) : array_shift($paths);
            }else{ // 过滤URL中的静态变量
                array_shift($paths);
            }
        }

        if(0=== strpos($url,'/') || 0===strpos($url,'http')) { // 路由重定向跳转
            if(strpos($url,':')) { // 传递动态参数
                $values = array_values($matches);
                $url = preg_replace_callback('/:(\d+)/', function($match) use($values){ return $values[$match[1] - 1]; }, $url);
            }
            header("Location: $url", true,(is_array($route) && isset($route[1]))?$route[1]:301);
            exit;
        }else{
            // 解析路由地址
            $var  =  self::parseUrl($url);
            // 解析路由地址里面的动态参数
            $values  =  array_values($matches);
            foreach ($var as $key=>$val){
                if(0===strpos($val,':')) {
                    $var[$key] =  $values[substr($val,1)-1];
                }
            }
            $var   =   array_merge($matches,$var);
            // 解析剩余的URL参数
            if(!empty($paths)) {
                preg_replace_callback('/(\w+)\/([^\/]+)/', function($match) use(&$var){ $var[strtolower($match[1])]=strip_tags($match[2]);}, implode('/',$paths));
            }
            // 解析路由自动传入参数
            if(is_array($route) && isset($route[1])) {
                if(is_array($route[1])){
                    $params     =   $route[1];
                }else{
                    parse_str($route[1],$params);
                }                
                $var   =   array_merge($var,$params);
            }
            $_GET   =  array_merge($var,$_GET);
        }
        return true;
    }

    // 解析正则路由
    // '路由正则'=>'[控制器/操作]?参数1=值1&参数2=值2...'
    // '路由正则'=>array('[控制器/操作]?参数1=值1&参数2=值2...','额外参数1=值1&额外参数2=值2...')
    // '路由正则'=>'外部地址'
    // '路由正则'=>array('外部地址','重定向代码')
    // 参数值和外部地址中可以用动态变量 采用 :1 :2 的方式
    // '/new\/(\d+)\/(\d+)/'=>array('News/read?id=:1&page=:2&cate=1','status=1'),
    // '/new\/(\d+)/'=>array('/new.php?id=:1&page=:2&status=1','301'), 重定向
    private static function parseRegex($matches,$route,$regx) {
        // 获取路由地址规则
        $url   =  is_array($route)?$route[0]:$route;
        $url   =  preg_replace_callback('/:(\d+)/', function($match) use($matches){return $matches[$match[1]];}, $url); 
        if(0=== strpos($url,'/') || 0===strpos($url,'http')) { // 路由重定向跳转
            header("Location: $url", true,(is_array($route) && isset($route[1]))?$route[1]:301);
            exit;
        }else{
            // 解析路由地址
            $var  =  self::parseUrl($url);
            // 处理函数
            foreach($var as $key=>$val){
                if(strpos($val,'|')){
                    list($val,$fun) = explode('|',$val);
                    $var[$key]    =   $fun($val);
                }
            }
            // 解析剩余的URL参数
            $regx =  substr_replace($regx,'',0,strlen($matches[0]));
            if($regx) {
                preg_replace_callback('/(\w+)\/([^\/]+)/', function($match) use(&$var){
                    $var[strtolower($match[1])] = strip_tags($match[2]);
                }, $regx);
            }
            // 解析路由自动传入参数
            if(is_array($route) && isset($route[1])) {
                if(is_array($route[1])){
                    $params     =   $route[1];
                }else{
                    parse_str($route[1],$params);
                }
                $var   =   array_merge($var,$params);
            }
            $_GET   =  array_merge($var,$_GET);
        }
        return true;
    }

    // 执行正则匹配下的闭包方法 支持参数调用
    static private function invokeRegx($closure, $var = array()) {
        $reflect = new \ReflectionFunction($closure);
        $params  = $reflect->getParameters();
        $args    = array();
        array_shift($var);
        foreach ($params as $param){
            if(!empty($var)) {
                $args[] = array_shift($var);
            }elseif($param->isDefaultValueAvailable()){
                $args[] = $param->getDefaultValue();
            }
        }
        return $reflect->invokeArgs($args);
    }

    // 执行规则匹配下的闭包方法 支持参数调用
    static private function invokeRule($closure, $var = array()) {
        $reflect = new \ReflectionFunction($closure);
        $params  = $reflect->getParameters();
        $args    = array();
        foreach ($params as $param){
            $name = $param->getName();
            if(isset($var[$name])) {
                $args[] = $var[$name];
            }elseif($param->isDefaultValueAvailable()){
                $args[] = $param->getDefaultValue();
            }
        }
        return $reflect->invokeArgs($args);
    }

}