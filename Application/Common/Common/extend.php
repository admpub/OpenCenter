<?php
// ===============================
// 自定义扩展函数
// ===============================

/**
 * id列表转换成数字
 * @param  string|array &$ids      id列表。比如：1,2,3 或 array('1','2','3')
 * @param  string 		$delimiter 当$ids是字符串时，id之间的分隔符
 * @return string|array
 * @author swh <swh@admpub.com>
 */
function toInt(&$ids, $delimiter = ',') {
	if (!is_array($ids)) {
		$ids = array_map('intval', explode($delimiter, $ids));
		$ids = array_unique($ids);
		$ids = implode($delimiter, $ids);
	} else {
		$ids = array_map('intval', $ids);
		$ids = array_unique($ids);
	}
	return $ids;
}

/**
 * 调用插件控制器中的方法
 * @param  string 		$addon      插件英文名(首字母大写)
 * @param  string|array $controller 插件控制器名(首字母大写)，
 * 当为数组时则表示这是方法的参数，同时意味着第一个参数是由“插件名/控制器名/方法名”构成的字符串
 * @param  string 		$action     插件方法名
 * @param  array  		$params     传递给方法的参数
 * @return mixed
 * @author swh <swh@admpub.com>
 */
function addonA($addon, $controller = array(), $action = null, $params = array()) {
	static $_addon = array();
	if (is_array($controller)) {
		$params = $controller;
		$p = explode('/', $addon);
		isset($p[2]) && $action = $p[2];
		isset($p[1]) && $controller = $p[1];
		$addon = $p[0];
	}
	$k = $addon . '/' . $controller;
	isset($_addon[$k]) || $_addon[$k] = A('Addons://' . $k);
	$f = array($_addon[$k], $action);
	if (!is_object($_addon[$k])) {
		return false;
	}
	if (is_null($action)) {
		return $_addon[$k];
	}
	if (!is_callable($f)) {
		return false;
	}
	return call_user_func_array($f, $params);
}