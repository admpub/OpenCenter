<?php
// ===============================
// 用户查询
// ===============================

/**
 * 支持的字段有
 * member表中的所有字段，ucenter_member表中的所有字段
 * 等级：title
 * 头像：avatar32 avatar64 avatar128 avatar256 avatar512
 * 个人中心地址：space_url
 * 认证图标：icons_html
 *
 * @param 	array|string    $fields  如果是数组，则返回数组。如果不是数组，则返回对应的值
 * @param 	null 			$uid
 * @return 	array|null
 */
function query_user($fields = null, $uid = null, $temped = true) {
	static $_user = array();
	$cached = true;
	if ($fields === null) {
		$fields = array('nickname', 'space_url', 'avatar64', 'avatar128', 'uid');
	}
	//如果fields不是数组，则返回值也不是数组
	if (!is_array($fields)) {
		$result = query_user(array($fields), $uid);
		return $result[$fields];
	}

	$cachedFields = $cacheResult = $result = array();

	//默认获取自己的资料
	$uid = $uid ? $uid : is_login();
	if (!$uid) {
		return null;
	}

	if (is_numeric($idx = array_search('score', $fields))) {
		$fields[$idx] = 'score1';
	}

	if (isset($_user[$uid])) {
		foreach ($fields as $key => $field) {
			if (array_key_exists($field, $_user[$uid])) {
				unset($fields[$key]);
				$cacheResult[$field] = $_user[$uid][$field];
				$cachedFields[] = $field;
			}
		}
	}

	if (empty($fields)) {
		return $cacheResult;
	}

	#dump(array($_user, $fields, $cacheResult));
	if ($cached) {
		//查询缓存，过滤掉已缓存的字段
		foreach ($fields as $field) {
			if (in_array($field, array('icons_html', 'title')) || substr($field, 0, 5) == 'score') {
				continue;
			}
			$cache = read_query_user_cache($uid, $field);
			if (!empty($cache)) {
				$cacheResult[$field] = $cache;
				$cachedFields[] = $field;
			}
		}

	}
	//去除已经缓存的字段
	$fields = array_diff($fields, $cachedFields);
	$fieldKeys = array_flip($fields); //键值互换
	//获取两张用户表格中的所有字段
	$homeModel = M('Member');
	$ucenterModel = UCenterMember();
	$homeFields = $homeModel->getDbFields();
	$ucenterFields = $ucenterModel->getDbFields();

	//分析每个表格分别要读取哪些字段
	$avatarFields = array('avatar32', 'avatar64', 'avatar128', 'avatar256', 'avatar512');
	$avatarFields = array_intersect($avatarFields, $fields); //获取两个数组的交集
	$homeFields = array_intersect($homeFields, $fields);
	$ucenterFields = array_intersect($ucenterFields, $fields);

	//查询需要的字段
	$homeResult = array();
	$ucenterResult = array();
	if ($homeFields) {
		$homeResult = D('Home/Member')->where(array('uid' => $uid))->field($homeFields)->find();
	}
	if ($ucenterFields) {
		$model = UCenterMember();
		$ucenterResult = $model->where(array('id' => $uid))->field($ucenterFields)->find();
	}
	if ($avatarFields) {
		//读取头像数据
		$avatarObject = new \Ucenter\Widget\UploadAvatarWidget();

		$check = file_exists('./api/uc_login.lock');
		if ($check) {
			include_once './api/uc_client/client.php';
		}
		foreach ($avatarFields as $e) {
			$avatarSize = intval(substr($e, 6));
			$avatarUrl = $avatarObject->getAvatar($uid, $avatarSize);
			if ($check) {
				$avatarUrl = UC_API . '/avatar.php?uid=' . $uid . '&size=big';
			}
			$result[$e] = $avatarUrl;
		}
	}
	//读取等级数据
	if (isset($fieldKeys['title'])) {
		$titleModel = D('Ucenter/Title');
		$title = $titleModel->getTitle($uid);
		$result['title'] = $title;
	}

	//读取用户名拼音
	if (isset($fieldKeys['pinyin'])) {
		$result['pinyin'] = D('Pinyin')->pinYin($result['nickname']);
	}

	//获取个人中心地址
	$spaceUrlResult = array();
	if (isset($fieldKeys['space_url'])) {
		$result['space_url'] = U('Ucenter/Index/index', array('uid' => $uid));
	}

	if (in_array('nickname', $fields)) {
		$ucenterResult['nickname'] = op_t($ucenterResult['nickname']);
	}

	//获取昵称链接
	if (isset($fieldKeys['space_link'])) {
		if (!$ucenterResult['nickname']) {
			$res = query_user(array('nickname'), $uid);
			$ucenterResult['nickname'] = $res['nickname'];
		}
		$result['space_link'] = '<a ucard="' . $uid . '" target="_blank" href="' . U('Ucenter/Index/index', array('uid' => $uid)) . '">' . $ucenterResult['nickname'] . '</a>';
	}

	//获取用户头衔链接
	if (isset($fieldKeys['rank_link'])) {
		$rank_List = D('RankUser')->getAllByUid($uid);
		if ($rank_List) {
			$num = 0;
			foreach ($rank_List as $key => $val) {
				$val['logo_url'] = $val['logo_url'] ? fixAttachUrl($val['logo_url']) : '';
				if ($val['is_show']) {
					$num = 1;
				}
				$rank_List[$key] = $val;
			}
			$rank_List[0]['num'] = $num;
			$result['rank_link'] = $rank_List;
			unset($rank_List, $key, $val);
		} else {
			$result['rank_link'] = array();
		}

	} elseif (isset($cacheResult['rank_link']) && is_array($cacheResult['rank_link'])) {
		//验证是否已经过期，如果过期查询数据库是否有更新
		foreach ($cacheResult['rank_link'] as $key => $val) {
			if ($val['expire_time'] > 0 && $val['expire_time'] <= NOW_TIME) {
				$_rank = D('RankUser')->getByUid($uid, $val['id']);
				if ($_rank) {
					$_rank['logo_url'] = fixAttachUrl($_rank['logo_url']);
					$cacheResult['rank_link'][$key] = $_rank;
				} else {
					unset($cacheResult['rank_link'][$key]);
				}
			}
			#clean_query_user_cache($uid, 'rank_link');
		}
		unset($key, $val);
	}

	//获取用户认证图标
	if (isset($fieldKeys['icons_html'])) {
		//判断是否有手机图标
		$static = C('TMPL_PARSE_STRING.__STATIC__');
		$iconUrls = array();
		$user = query_user(array('mobile'), $uid);
		if ($user['mobile']) {
			$iconUrls[] = $static . '/oneplus/images/mobile-bind.png';
		}
		//生成结果
		$result['icons_html'] = '<span class="usercenter-verify-icon-list">';
		foreach ($iconUrls as $e) {
			$result['icons_html'] .= '<img src="' . $e . '" title="对方已绑定手机"/>';
		}
		$result['icons_html'] .= '</span>';
		$result['icons_html'];
	}
	//expand_info:用户扩展字段信息
	if (isset($fieldKeys['expand_info'])) {
		$map['status'] = 1;
		$field_group = D('field_group')->where($map)->select();
		$field_group_ids = array_column($field_group, 'id');
		$map['profile_group_id'] = array('in', $field_group_ids);
		$fields_list = D('field_setting')->where($map)->getField('id,field_name,form_type,visiable');
		$fields_list = array_combine(array_column($fields_list, 'field_name'), $fields_list);
		$map_field['uid'] = $uid;
		if ($fields_list) {
			foreach ($fields_list as $key => $val) {
				$map_field['field_id'] = $val['id'];
				$field_data = D('field')->where($map_field)->getField('field_data');
				if ($field_data == null || $field_data == '') {
					unset($fields_list[$key]);
				} else {
					if ($val['form_type'] == 'checkbox') {
						$field_data = explode('|', $field_data);
					}
					$fields_list[$key]['data'] = $field_data;
				}
			}
			$result['expand_info'] = $fields_list;
			unset($fields_list, $key, $val);
		} else {
			$result['expand_info'] = array();
		}

	}

	//粉丝数、关注数、微博数
	if (isset($fieldKeys['fans'])) {
		$result['fans'] = D('Follow')->where(array('follow_who' => $uid))->count();
	}
	if (isset($fieldKeys['following'])) {
		$result['following'] = D('Follow')->where(array('who_follow' => $uid))->count();
	}

	//是否关注、是否被关注
	if (isset($fieldKeys['is_following'])) {
		$follow = D('Follow')->where(array('who_follow' => get_uid(), 'follow_who' => $uid))->find();
		$result['is_following'] = $follow ? true : false;
	}
	if (isset($fieldKeys['is_followed'])) {
		$follow = D('Follow')->where(array('who_follow' => $uid, 'follow_who' => get_uid()))->find();
		$result['is_followed'] = $follow ? true : false;
	}

	//TODO 在此加入扩展字段的处理钩子
	//↑↑↑ 新增字段应该写在在这行注释以上 ↑↑↑

	//合并结果，不包括缓存
	$result = array_merge($ucenterResult, $homeResult, $spaceUrlResult, $result);

	//写入缓存
	if ($result) {
		foreach ($result as $field => $value) {
			if (in_array($field, array('icons_html', 'title')) || in_array(substr($field, 0, 5), array('score', 'money'))) {
				$_user[$uid][$field] = $value;
				continue;
			}
			if (!in_array($field, array('rank_link', 'icons_html', 'space_link', 'expand_info'))) {
				$value = str_replace('"', '', op_t($value));
			}
			$result[$field] = $value;
			$_user[$uid][$field] = $value;
			if ($cached) {
				write_query_user_cache($uid, $field, str_replace('"', '', $value));
			}

		}
	}

	//合并结果，包括缓存
	$result = array_merge($result, $cacheResult);

	$_user[$uid]['score'] = $result['score'] = isset($result['score1']) ? $result['score1'] : null;
	#dump($result);
	//返回结果
	return $result;
}

function read_query_user_cache($uid, $field) {
	#return false;
	return S("query_user_{$uid}_{$field}");
}

function write_query_user_cache($uid, $field, $value) {
	return S("query_user_{$uid}_{$field}", $value, 1800);
}

/**
 * 清理用户数据缓存，即时更新query_user返回结果。
 * @param $uid
 * @param $field
 * @auth 陈一枭
 */
function clean_query_user_cache($uid, $field) {
	if (is_array($field)) {
		foreach ($field as $field_item) {
			S("query_user_{$uid}_{$field_item}", null);
		}
		return;
	}
	S("query_user_{$uid}_{$field}", null);
}