﻿(function(e,t){"use strict";function y(e){if(e._done)return;e(),e._done=1}function b(e){var t=e.split("/"),n=t[t.length-1],r=n.indexOf("?");return r!=-1?n.substring(0,r):n}function w(e){var t;if(typeof e=="object")for(var n in e)e[n]&&(t={name:n,url:e[n]});else t={name:b(e),url:e};var r=l[t.name];return r&&r.url===t.url?r:(l[t.name]=t,t)}function E(e,t){if(!e)return;typeof e=="object"&&(e=[].slice.call(e));for(var n=0;n<e.length;n++)t.call(e,e[n],n)}function S(e){return Object.prototype.toString.call(e)=="[object Function]"}function x(e){e=e||l;var t;for(var n in e){if(e.hasOwnProperty(n)&&e[n].state!=g)return!1;t=!0}return t}function T(e){e.state=d,E(e.onpreload,function(e){e.call()})}function N(e,n){e.state===t&&(e.state=v,e.onpreload=[],k({src:e.url,type:"cache"},function(){T(e)}))}function C(e,t){if(e.state==g)return t&&t();if(e.state==m)return p.ready(e.name,t);if(e.state==v)return e.onpreload.push(function(){C(e,t)});e.state=m,k(e.url,function(){e.state=g,t&&t(),E(f[e.name],function(e){y(e)}),x()&&o&&E(f.ALL,function(e){y(e)})})}function k(e,t){var r=n.createElement("script");r.type="text/"+(e.type||"javascript"),r.src=e.src||e,r.async=!1,r.onreadystatechange=r.onload=function(){var e=r.readyState;!t.done&&(!e||/loaded|complete/.test(e))&&(t.done=!0,t())},(n.body||i).appendChild(r)}function L(){o||(o=!0,E(u,function(e){y(e)}))}var n=e.document,r=e.navigator,i=n.documentElement,s,o,u=[],a=[],f={},l={},c=n.createElement("script").async===!0||"MozAppearance"in n.documentElement.style||e.opera,h=e.head_conf&&e.head_conf.head||"head",p=e[h]=e[h]||function(){p.ready.apply(null,arguments)},d=1,v=2,m=3,g=4;c?p.js=function(){var e=arguments,t=e[e.length-1],n={};return S(t)||(t=null),E(e,function(r,i){r!=t&&(r=w(r),n[r.name]=r,C(r,t&&i==e.length-2?function(){x(n)&&y(t)}:null))}),p}:p.js=function(){var e=arguments,t=[].slice.call(e,1),n=t[0];return s?(n?(E(t,function(e){S(e)||N(w(e))}),C(w(e[0]),S(n)?n:function(){p.js.apply(null,t)})):C(w(e[0])),p):(a.push(function(){p.js.apply(null,e)}),p)},p.ready=function(e,t){if(e==n)return o?y(t):u.push(t),p;S(e)&&(t=e,e="ALL");if(typeof e!="string"||!S(t))return p;var r=l[e];if(r&&r.state==g||e=="ALL"&&x()&&o)return y(t),p;var i=f[e];return i?i.push(t):i=f[e]=[t],p},p.ready(n,function(){x()&&E(f.ALL,function(e){y(e)}),p.feature&&p.feature("domloaded",!0)});if(e.addEventListener)n.addEventListener("DOMContentLoaded",L,!1),e.addEventListener("load",L,!1);else if(e.attachEvent){n.attachEvent("onreadystatechange",function(){n.readyState==="complete"&&L()});var A=1;try{A=e.frameElement}catch(O){}!A&&i.doScroll&&function(){try{i.doScroll("left"),L()}catch(e){setTimeout(arguments.callee,1);return}}(),e.attachEvent("onload",L)}!n.readyState&&n.addEventListener&&(n.readyState="loading",n.addEventListener("DOMContentLoaded",handler=function(){n.removeEventListener("DOMContentLoaded",handler,!1),n.readyState="complete"},!1)),setTimeout(function(){s=!0,E(a,function(e){e()})},300)})(window);
$(document).ready(function(){
	$(".menus").click(function() 
	{ 
		$(this).siblings('ul').toggle(); 
		if($(this).children('.icon-chevron-down').length>0)
			$(this).children('.icon-chevron-down').attr('Class','icon-chevron-up'); 
		else
			$(this).children('.icon-chevron-up').attr('Class','icon-chevron-down');
	});
	$(".block .title a:first-child").click(function() { 
		$(this).parent().siblings('.control-group').toggle(); 
		if($(this).children('.icon-chevron-down').length>0)
			$(this).children('.icon-chevron-down').attr('Class','icon-chevron-up'); 
		else
			$(this).children('.icon-chevron-up').attr('Class','icon-chevron-down');
	
	});

	$('.close').click(function() {
   		$('.alert').hide();
	});

});

//重定义alert
function alert(msg){
	$('#alert').html("<div style='margin-left:30%;margin-top:30px' class='alert message fade in hide'><a class='dismiss close' data-dismiss='alert'>×</a><label>"
		+ msg + "</label></div>"
    );
    $(".alert").show();
    $(".alert").delay(2000).fadeIn(1000).fadeOut(500);
}

