<?php

$config = array();
if (is_file('./Conf/common.php')) {
	$config = require_once './Conf/common.php';
}

$config['TMPL_ACTION_ERROR'] = COMMON_PATH . 'View/default/Public/error.html'; // 默认错误跳转对应的模板文件
$config['TMPL_ACTION_SUCCESS'] = COMMON_PATH . 'View/default/Public/success.html'; // 默认成功跳转对应的模板文件
$config['TMPL_EXCEPTION_FILE'] = COMMON_PATH . 'View/default/Public/exception.html'; // 异常页面的模板文件
return $config;