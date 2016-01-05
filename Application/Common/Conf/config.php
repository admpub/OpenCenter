<?php

$config = array();
if (is_file('./Conf/common.php')) {
	$config = require_once './Conf/common.php';
}

$config['TMPL_ACTION_ERROR'] = COMMON_PATH . 'View/default/Public/error.html'; // 默认错误跳转对应的模板文件
$config['TMPL_ACTION_SUCCESS'] = COMMON_PATH . 'View/default/Public/success.html'; // 默认成功跳转对应的模板文件
$config['TMPL_EXCEPTION_FILE'] = COMMON_PATH . 'View/default/Public/exception.html'; // 异常页面的模板文件

if (isset($config['DATA_CACHE_TYPE'])) {
	return $config;
}

//缓存引擎设置
$config['DATA_CACHE_TYPE'] = 'File'; // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
$config['MEMCACHE_HOST'] = '127.0.0.1';
$config['MEMCACHE_PORT'] = 11211;
$config['DATA_CACHE_TIMEOUT'] = 86400;

/*
=============================================================================
以下代码建议根据需要写在模块的配置文件(位置：模块名/Conf/config.php)中
=============================================================================
//HTML静态文件缓存设置
$config['HTML_CACHE_ON'] = true; // 开启静态缓存
$config['HTML_CACHE_TIME'] = 60; // 全局静态缓存有效期（秒）
$config['HTML_FILE_SUFFIX'] = '.html'; // 设置静态缓存文件后缀
$config['HTML_CACHE_RULES'] = array(
// 定义静态缓存规则 （欲作深入理解请看ThinPHP/Library/Behavior/ReadHtmlCacheBehavior.class.php文件中的requireHtmlCache方法源码）
// 用法1: '无后缀控制器名:控制器方法名' => array('生成的静态文件路径(不带扩展名)', '有效期', '回调函数名'),
// 用法2：'控制器方法名' => '生成的静态文件路径(不带扩展名)',
// 用法3：'无后缀控制器名:' => '生成的静态文件路径(不带扩展名)',
// 用法4：'*' => '生成的静态文件路径(不带扩展名)', //全局规则
// 总结：键只支持上述四种格式，值支持两种标签：
// 第一种：	{:module}/{:controller}/{:action} (分别表示模块、控制器、方法名)
// 第二种：	{$_GET.id|回调函数名} (取$_GET的元素值时可以简写为：{id|回调函数名}或{id})
// 			如果没有回调函数可以直接写为{$_GET.id}
// 			注意：仅能获取$_GET、$_POST、$_REQUEST、$_SERVER、$_SESSION、$_COOKIE这六个数组的元素值
'wap:index' => array('{:module}/{:controller}/{:action}_{p_id}'),
);
// ============================================================================
// 黑魔法：
// ============================================================================
// 	'HTML_CACHE_TIME' => function ($cacheFile) {
// 		if (!empty($_GET['admpub'])) {
// 			return false;//缓存失效，会重新生成缓存
// 		}
// 		return NOW_TIME <= \Think\Storage::get($cacheFile, 'mtime', 'html') + 86400 * 7;
// 	}, // 为数字时为全局静态缓存有效期（秒），为函数时为判断缓存是否失效
// 	'HTML_CACHE_RULES' => array(
// 		'wap:download' => array('wap/download', function ($cacheFile) {
// 			//临时关闭缓存
// 			if (!empty($_GET['confirm'])) {
// 				C('HTML_CACHE_ON', false); //避免写缓存
// 				return false; //避免读缓存
// 			}
// 			return NOW_TIME <= \Think\Storage::get($cacheFile, 'mtime', 'html') + 86400 * 7;
// 		}),
// 	),
 */
return $config;