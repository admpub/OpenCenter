<?php
/**
 *load: css, js 静态文件
 *启用 gz压缩、缓存处理、过期处理、文件合并等优化操作
 *@modified by swh <swh@admpub.com>
 */
error_reporting(0);

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
// header ( 'cache-control: must-revalidate' );
header('cache-control: max-age=' . $offset);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . 'GMT');
header('Pragma: max-age=' . $offset);
header('Expires:' . gmdate('D, d M Y H:i:s', time() + $offset) . ' GMT');
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

function gen_etag() {
	return time() . '|' . md5($_SERVER['REQUEST_URI']);
}

function set_cache_limit($second = 1) {
	$second = intval($second);
	if ($second == 0) {
		return;
	}

	if (!isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
		header('Etag:' . gen_etag(), true, 200);
		return;
	}

	$id = $_SERVER['HTTP_IF_NONE_MATCH'];

	list($time, $uri) = explode('|', $id, 2);

	if ($time < (time() - $second)) {
		//过期了，发送新tag
		header('Etag:' . gen_etag(), true, 200);
	} else {
		//未过期，发送旧tag
		header('Etag:' . $id, true, 304);
		exit(-1);
	}
}

function ext_name($file) {
	return strtolower(substr($file, strrpos($file, '.') + 1));
}

foreach ($getfiles as $file) {
	if ($gettype == ext_name($file)) {
		readfile($file);
	} else {
		echo PHP_EOL . '/* not allowed file type:' . $file . ' */' . PHP_EOL;
	}
}
//输出buffer中的内容，即压缩后的css文件
if (extension_loaded('zlib')) {
	ob_end_flush();
}