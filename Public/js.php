<?php
/**
 *load: css, js 静态文件
 *启用 gz压缩、缓存处理、过期处理、文件合并等优化操作
 *@modified by swh <swh@admpub.com>
 */
error_reporting(0);
defined('NOW_TIME') || define('NOW_TIME', time());
if (extension_loaded('zlib')) {
	//检查服务器是否开启了zlib拓展
	ob_start('ob_gzhandler');
}

$allowed_content_types = array('js', 'css');

$_GET['f'] = strip_tags($_GET['f']);

$gettype = ext_name($_GET['f']);

$offset = 60 * 60 * 24 * 7; //过期7天

$content_type = '';
if ($gettype == 'css') {
	$content_type = 'text/css';
} elseif ($gettype == 'js') {
	$content_type = 'application/x-javascript';
} else {
	echo PHP_EOL . '/* not allowed file type:' . $gettype . ' */' . PHP_EOL;
	exit();
}

header('content-type: ' . $content_type . '; charset=utf-8'); //注意修改到你的编码
set_cache_limit($offset);

if (strpos($_GET['f'], '://') !== false) {
	exit('/* "://" is deny. */');
}

$getfiles = explode(',', $_GET['f']);
ob_start('compress');

function compress($buffer) {
	//去除文件中的注释
	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	return $buffer;
}

function set_cache_limit($second = 1) {
	$second = intval($second);
	if ($second == 0) {
		return;
	}
	if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) < NOW_TIME - $second)) {
		//过期了
		header('Cache-Control: max-age=' . $second);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', NOW_TIME) . ' GMT');
		header('Pragma: max-age=' . $second);
		header('Expires:' . gmdate('D, d M Y H:i:s', NOW_TIME + $second) . ' GMT');
	} else {
		header('Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304);
		exit;
	}
}

function ext_name($file) {
	return strtolower(substr($file, strrpos($file, '.') + 1));
}

foreach ($getfiles as $file) {
	if ($gettype == ext_name($file)) {
		if ($file[0] == '/') {
			$file = __DIR__ . '/..' . $file;
		}
		readfile($file);
	} else {
		echo PHP_EOL . '/* not allowed file type:' . $file . ' */' . PHP_EOL;
	}
}
//输出buffer中的内容，即压缩后的css文件
if (extension_loaded('zlib')) {
	ob_end_flush();
}