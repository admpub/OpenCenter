<?php
// ===============================
// api模块调用函数
// ===============================

/**
 * 获取@的用户名
 * @param $content
 * @return mixed
 */
function match_users($content) {
	$user_pattern = '/\@([^\#|\\s]+)\\s/'; //匹配用户
	preg_match_all($user_pattern, $content, $user_math);
	return $user_math;
}