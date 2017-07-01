<?php
// ===============================
// 缓存功能函数
// ===============================

/**
 * 自动缓存
 * @param $key 缓存id
 * @param $func 函数，生成待缓存的数据
 * @param $interval 缓存有效期
 * @return mixed 返回数据
 */
function op_cache($key, $func, $interval) {
	$result = S($key);
	if (!$result) {
		$result = $func();
		S($key, $result, $interval);
	}
	return $result;
}

/**
 * 清理全部缓存
 * @auth 陈一枭
 */
function clean_all_cache() {
	$dirname = '.'.DIRECTORY_SEPARATOR.'Runtime';

	//清文件缓存
	$dirs = array($dirname);
	rmdirr($dirname,true,array(
		file_path_join($dirname,'.gitkeep')
	));
}

/**
 * 删除文件夹
 * @param $dirname 文件夹名
 * @param $onlyChildren 是否仅仅删除其内的项目，不删除当前项目
 * @param $excludeFiles 保留的文件
 * @param $excludeDirs 保留的文件夹
 */
function rmdirr($dirname,$onlyChildren=false,$excludeFiles=array(),$excludeDirs=array()) {
	if (!file_exists($dirname)) {
		return false;
	}
	if (is_file($dirname) || is_link($dirname)) {
		if ($excludeFiles) {
			foreach($excludeFiles as $fileName){
				if($dirname==$fileName) return true;
			}
		}
		return unlink($dirname);
	}
	if ($excludeDirs) {
		foreach($excludeDirs as $dirName){
			if($dirname==$dirName) return true;
		}
	}
	$dir = dir($dirname);
	if ($dir) {
		while (false !== ($entry = $dir->read())) {
			if ($entry == '.' || $entry == '..') {
				continue;
			}
			rmdirr($dirname . DIRECTORY_SEPARATOR . $entry,false,$excludeFiles,$excludeDirs);
		}
	}
	$dir->close();
	if ($onlyChildren) return true;
	return rmdir($dirname);
}

/**
 * 生成符合当前操作系统的文件路径
 * @usage file_path_join('a','b','c') 在windows会生成“a\b\c”;在linux会生成“a/b/c”
 */
function file_path_join() {
	return implode(DIRECTORY_SEPARATOR,func_get_args());
}
