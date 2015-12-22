<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Core\Controller;

/**
 * Class PublicController  公共控制器
 * @package Core\Controller
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
class PublicController extends Core {
	/**
	 * atWhoJson
	 * @author:陈一枭
	 */
	public function atWhoJson() {
		exit(json_encode($this->getAtWhoUsersCached()));
	}

	private function getAtWhoUsersCached() {
		$cacheKey = 'weibo_at_who_users';
		$atusers = S($cacheKey);
		if (empty($atusers[get_uid()])) {
			$atusers[get_uid()] = $this->getAtWhoUsers();
			S($cacheKey, $atusers, 600);
		}
		return $atusers[get_uid()];
	}

	/**
	 * getAtWhoUsers  获取@列表
	 * @return array
	 * @author:陈一枭
	 */
	private function getAtWhoUsers() {
		//获取能AT的人，UID列表
		$uid = get_uid();
		$follows = D('Follow')->where(array('who_follow' => $uid, 'follow_who' => $uid, '_logic' => 'or'))->select();
		$uids = array();
		foreach ($follows as &$e) {
			$uids[] = $e['who_follow'];
			$uids[] = $e['follow_who'];
		}
		unset($e);
		$uids = array_unique($uids);

		//加入拼音检索
		$users = array();
		foreach ($uids as $uid) {
			$user = query_user(array('nickname', 'id', 'avatar32'), $uid);
			$user['search_key'] = $user['nickname'] . D('PinYin')->Pinyin($user['nickname']);
			$users[] = $user;
		}

		//返回at用户列表
		return $users;
	}
}
