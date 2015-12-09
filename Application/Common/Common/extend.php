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