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
 * ThinkPHP 视图类
 */
class View {
    /**
     * 模板输出变量
     * @var tVar
     * @access protected
     */ 
    protected $tVar     =   array();

    /**
     * 模板主题
     * @var theme
     * @access protected
     */ 
    protected $theme    =   '';

    /**
     * 模板变量赋值
     * @access public
     * @param mixed $name
     * @param mixed $value
     */
    public function assign($name,$value=''){
        if(is_array($name)) {
            $this->tVar   =  array_merge($this->tVar,$name);
        }else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * 取得模板变量的值
     * @access public
     * @param string $name
     * @return mixed
     */
    public function get($name=''){
        if('' === $name) {
            return $this->tVar;
        }
        return isset($this->tVar[$name])?$this->tVar[$name]:false;
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @access public
     * @param string $templateFile 模板文件名
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀
     * @return mixed
     */
    /*
        dispaly方法接受一个templateFile参数，调用parseTemplate方法根据这个参数去侦测模板文件的位置，
        结合主题组合出一个模板的地址，执行view_parse标签行为，在行为类里面去调用模板引擎的fetch方法去解析模板，
        返回编译后的内容。
     */
    public function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        G('viewStartTime');
        // 视图开始标签
        Hook::listen('view_begin',$templateFile);
        // 解析并获取模板内容
        $content = $this->fetch($templateFile,$content,$prefix);
        // 输出模板内容
        $this->render($content,$charset,$contentType);
        // 视图结束标签
        Hook::listen('view_end');
    }

    /**
     * 输出内容文本可以包括Html
     * @access private
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @return mixed
     */
    private function render($content,$charset='',$contentType=''){
        if(empty($charset))  $charset = C('DEFAULT_CHARSET');
        if(empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
        // 网页字符编码
        header('Content-Type:'.$contentType.'; charset='.$charset);
        header('Cache-control: '.C('HTTP_CACHE_CONTROL'));  // 页面缓存控制
        header('X-Powered-By:ThinkPHP');
        // 输出模板文件
        echo $content;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀
     * @return string
     */
    /* 接下来就是调用模板引擎来解析这个模板。
     * 在view类的fetch方法中并没有直接调用模板引擎template类的的方法去解析模板，而是去调用了一个view_parse标签，
     * 在这个标签上绑定了行为模式扩展类ParseTemplateBehavior，模板的解析就是在这个类的run方法中进行的，
     * 这个类总我们不仅而已使用tp自带的模板引擎，还可以使用其他开源第三方的模板引擎类，具有很好的高扩展性。
     */
    public function fetch($templateFile='',$content='',$prefix='') {
        if(empty($content)) {
            // 返回具体的模板文件路径
            $templateFile   =   $this->parseTemplate($templateFile);
            // 模板文件不存在直接返回
            if(!is_file($templateFile)) E(L('_TEMPLATE_NOT_EXIST_').':'.$templateFile);
        }
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        if('php' == strtolower(C('TMPL_ENGINE_TYPE'))) { // 使用PHP原生模板，是直接引入
            $_content   =   $content;
            // 模板阵列变量分解成为独立变量
            extract($this->tVar, EXTR_OVERWRITE);
            // 直接载入PHP模板
            empty($_content)?include $templateFile:eval('?>'.$_content);
        }else{
            // 视图解析标签
            $params = array('var'=>$this->tVar,'file'=>$templateFile,'content'=>$content,'prefix'=>$prefix);
            Hook::listen('view_parse',$params);
        }
        // 获取并清空缓存
        $content = ob_get_clean();
        // 内容过滤标签
        Hook::listen('view_filter',$content);
        // 输出模板文件
        return $content;
    }

    /**
     * 自动定位模板文件
     * @access protected
     * @param string $template 模板文件规则
     * @return string
     */
    public function parseTemplate($template='') {
        //如果$template是一个文件地址的话，那么就直接返回该地址。这是最简单的一种使用方式。
        //这里的文件地址是一个相对文件路径地址，要注意模板文件位置是相对于项目的入口文件

        if(is_file($template)) {  // './Template/Public/menu.html' true
            return $template;
        }

        //获取模板文件的分隔符，模板文件CONTROLLER_NAME与ACTION_NAME之间的分割符 --- /
        $depr       =   C('TMPL_FILE_DEPR');
        //将冒号替换成分隔符
        $template   =   str_replace(':', $depr, $template);
        // 获取当前主题名称
        $theme = $this->getTemplateTheme();

        /*我们来看一下tp的模板侦测逻辑。首先tp要求我们传入一个地址表达式。格式如下：
        [模块@][控制器:][操作]  比如：  m@c:a  表示m模块下的c控制器下的a方法
        我们只要给此方法传入模块，控制器和方法三个参数，这个方法就能给我们侦测出对应的模板文件地址。
        具体来说，他会先定义出截止到模块这个阶段的目录，然后在得到控制器到最后的模板文件地址，
        最后组合起来形成最终的文件地址。1：先定义模块的目录
        首先解析地址表达式，如果有@，表示传入了模块地址，解析她，赋值给module变量。我们的控制器文件夹和方法存放在哪里呢？
        如果定义了视图目录，就存放在视图目录中，如果没有定义，就看看是否定义了模板路径，
        如果定义了就存放在该路径下的对应模块目录下，如果没有定义模板路径，
        默认就存放在应用文件夹下的对应模块文件夹下的默认视图层下。
        最后我们会得到一个THEME_PATH表示控制器模板存放的目录。如果有主题的话加上主题
*/
        // 获取当前模块
        $module   =  MODULE_NAME;
        // [模块@][控制器:][操作]
        if(strpos($template,'@')){ // 跨模块调用模版文件
            list($module,$template)  =   explode('@',$template);
        }
        // 获取当前主题的模版路径
        if(!defined('THEME_PATH')){ // 不存在模板默认路径时，使用每个模块下的View目录作为模板目录
            if(C('VIEW_PATH')){ // 模块设置独立的视图目录
                $tmplPath   =   C('VIEW_PATH');
            }else{  // 定义TMPL_PATH 改变全局的视图目录到模块之外
                // 'DEFAULT_V_LAYER' ---  'View', // 默认的视图层名称
                $tmplPath   =   defined('TMPL_PATH')? TMPL_PATH.$module.'/' : APP_PATH.$module.'/'.C('DEFAULT_V_LAYER').'/';
            }
            // $tmplPath --- ./Application/Admin/View/
            // $theme 具体的模板文件名
            define('THEME_PATH', $tmplPath.$theme);

        }

        // 分析模板文件规则
        /*
        分析地址表达式中的模板规则，如果地址表达式为空，或者只是传了一个模块名，
        那么这里的template就为空，那么默认的地址就是默认控制器下的默认方法
        */
        if('' == $template) {
            // 如果模板文件名为空 按照默认规则定位 默认是使用控制器下的方法名作为模板名称。
            // display();
            $template = CONTROLLER_NAME . $depr . ACTION_NAME;
        }elseif(false === strpos($template, $depr)){ // 通过判断传入的参数是否是一个路径。
            // dispaly('edit');
            $template = CONTROLLER_NAME . $depr . $template; // 如果是传入具体的模板文件名，拼接上具体的控制器名称。
        }

        // 如果传入的是模板文件的路径，直接使用。
        // display('./Template/Public/menu.html');
        $file   =   THEME_PATH.$template.C('TMPL_TEMPLATE_SUFFIX');
        if(C('TMPL_LOAD_DEFAULTTHEME') && THEME_NAME != C('DEFAULT_THEME') && !is_file($file)){
            // 找不到当前主题模板的时候定位默认主题中的模板
            $file   =   dirname(THEME_PATH).'/'.C('DEFAULT_THEME').'/'.$template.C('TMPL_TEMPLATE_SUFFIX');
        }
        return $file;
    }

    /**
     * 设置当前输出的模板主题
     * @access public
     * @param  mixed $theme 主题名称
     * @return View
     */
    public function theme($theme){
        $this->theme = $theme;
        return $this;
    }

    /**
     * 获取当前的模板主题
     * @access private
     * @return string
     */
    private function getTemplateTheme() {
        if($this->theme) { // 指定模板主题
            $theme = $this->theme;
        }else{
            /* 获取模板主题名称 */
            $theme =  C('DEFAULT_THEME'); // 默认模板主题名称
            if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题 false
                $t = C('VAR_TEMPLATE'); // 默认模板切换变量 t
                if (isset($_GET[$t])){
                    $theme = $_GET[$t];
                }elseif(cookie('think_template')){
                    $theme = cookie('think_template');
                }
                if(!in_array($theme,explode(',',C('THEME_LIST')))){
                    $theme =  C('DEFAULT_THEME');
                }
                cookie('think_template',$theme,864000);
            }
        }

        defined('THEME_NAME') || define('THEME_NAME',   $theme);                  // 当前模板主题名称
        return $theme?$theme . '/':'';
    }

}