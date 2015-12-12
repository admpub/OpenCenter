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

$config['DATA_CACHE_TYPE'] = 'File'; // 数据缓存类型,支持:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
$config['MEMCACHE_HOST'] = '127.0.0.1';
$config['MEMCACHE_PORT'] = 11211;
$config['DATA_CACHE_TIMEOUT'] = 86400;

return $config;