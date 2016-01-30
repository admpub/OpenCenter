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

/**
 * 获取插件中的模型
 * @param  string 	$addon 插件英文名(首字母大写)
 * @param  string 	$model 模型名(首字母大写)
 * @return object
 * @author swh <swh@admpub.com>
 */
function addonD($addon, $model = null) {
	static $_addonM = array();
	$k = $addon;
	if ($model) {
		$k .= '/' . $model;
	}
	isset($_addonM[$k]) || $_addonM[$k] = D('Addons://' . $k);
	return $_addonM[$k];
}

function baseM($name = '', $tablePrefix = '', $connection = '') {
	return M('Common\\Model\\Base:' . $name, $tablePrefix, $connection);
}

if (!function_exists('moduleDomains')) {
	function moduleDomains() {
		static $_moduleDomains = null;
		if (is_null($_moduleDomains)) {
			$_moduleDomains = array();
			if (C('APP_SUB_DOMAIN_DEPLOY')) {
				$rules = C('APP_SUB_DOMAIN_RULES');
				if ($rules) {
					foreach ($rules as $key => $value) {
						$value = strtolower($value);
						$_moduleDomains[$value] = $key;
					}
				}
			}
		}
		return $_moduleDomains;
	}
}

/**
 * 根据模块及是否绑定域名来返回合适的首页网址
 */
function homeUrl() {
	$domains = moduleDomains();
	$module = strtolower(MODULE_NAME);
	if (!empty($domains[$module . '/' . strtolower(CONTROLLER_NAME)])) {
		return U('index');
	}
	if (!empty($domains[$module])) {
		return U('Index/index');
	}
	return U('Home/Index/index');
}

/**
 * 检查远程文件是否存在
 * @param  string $url 远程文章完整网址
 * @return bool
 */
function check_remote_file_exists($url) {
	$curl = curl_init($url);
	// 不取回数据
	curl_setopt($curl, CURLOPT_NOBODY, true);
	// 发送请求
	$result = curl_exec($curl);
	$found = false;
	// 如果请求没有发送失败
	if ($result !== false) {
		// 再检查http响应码是否为200
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($statusCode == 200) {
			$found = true;
		}
	}
	curl_close($curl);
	return $found;
}

/**
 * 缓存快捷函数
 * @param  string  $cachedId 缓存key
 * @param  mixed   $func     获取新数据的功能函数
 * @param  integer $lifeTime 生存时间(秒)
 * @return mixed
 */
function cached($cachedId, $func, $lifeTime = 86400) {
	$r = $lifeTime > 0 ? S($cachedId) : null;
	if (!$r) {
		$r = $func();
		$lifeTime && S($cachedId,$r,$lifeTime);
	}
	return $r;
}
