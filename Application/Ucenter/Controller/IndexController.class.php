<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-6-27
 * Time: 下午1:54
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Ucenter\Controller;

class IndexController extends BaseController {
	protected function _initialize() {
		parent::_initialize();
		$uid = isset($_GET['uid']) && ($_GET['uid'] = intval($_GET['uid'])) > 0 ? $_GET['uid'] : is_login();

		//调用API获取基本信息
		$user_info = $this->userInfo($uid);
		$this->_fans_and_following($uid, $user_info);
		$this->_tab_menu();
	}

	public function index($uid = null, $page = 1, $count = 10) {
		$appArr = $this->_tab_menu();
		if (!$appArr) {
			$this->redirect('Ucenter/Index/information', array('uid' => $uid));
		}
		foreach ($appArr as $key => $val) {
			$type = $key;
			break;
		}
		if (!isset($appArr[$type])) {
			$this->error('参数出错！！');
		}
		$this->assign('type', $type);
		$uType = ucfirst($type);
		$className = $uType . 'Protocol';
		$dao = D($uType . '/' . $className);
		$content = $dao->profileContent($uid, $page, $count);
		if (empty($content)) {
			$content = '暂无内容';
		} else {
			$totalCount = $dao->getTotalCount($uid);
			$this->assign('totalCount', $totalCount);
		}
		$this->assign('content', $content);
		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$str_app = '{$appArr[$type]|op_t}';
		$this->setTitle($str . '的个人主页');
		$this->setKeywords($str . '，个人主页，个人' . $str_app);
		$this->setDescription($str . '的个人' . $str_app . '页');
		//四处一词 seo end
		$this->display();
	}

	private function userInfo($uid = null) {
		static $_userInfo = array();
		if (isset($_userInfo[$uid])) {
			return $_userInfo[$uid];
		}
		$user_info = query_user(array('avatar128', 'nickname', 'uid', 'space_url', 'icons_html', 'score', 'title', 'fans', 'following', 'weibocount', 'rank_link', 'signature'), $uid);
		$this->assign('user_info', $user_info);
		$_userInfo[$uid] = &$user_info;
		return $user_info;
	}

	public function information($uid = null) {
		//调用API获取基本信息
		//TODO tox 获取省市区数据
		$user = query_user(array('nickname', 'signature', 'email', 'mobile', 'rank_link', 'sex', 'pos_province', 'pos_city', 'pos_district', 'pos_community'), $uid);
		if ($user['pos_province'] != 0) {
			$user['pos_province'] = D('district')->where(array('id' => $user['pos_province']))->getField('name');
			$user['pos_city'] = D('district')->where(array('id' => $user['pos_city']))->getField('name');
			$user['pos_district'] = D('district')->where(array('id' => $user['pos_district']))->getField('name');
			$user['pos_community'] = D('district')->where(array('id' => $user['pos_community']))->getField('name');
		}
		//显示页面
		$this->assign('user', $user);
		$this->getExpandInfo($uid);
		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的个人资料页');
		$this->setKeywords($str . '，个人资料');
		$this->setDescription($str . '的个人资料页');
		//四处一词 seo end
		$this->display();
	}

	/**
	 * 获取用户扩展信息
	 * @param null $uid
	 * @author 郑钟良<zzl@ourstu.com>
	 */
	public function getExpandInfo($uid = null, $profile_group_id = null) {
		$profile_group_list = $this->_profile_group_list($uid);
		foreach ($profile_group_list as &$val) {
			$val['info_list'] = $this->_info_list($val['id'], $uid);
		}
		$this->assign('profile_group_list', $profile_group_list);
	}

	/**
	 * 扩展信息分组列表获取
	 * @param null $uid
	 * @return mixed
	 * @author 郑钟良<zzl@ourstu.com>
	 */
	public function _profile_group_list($uid = null) {
		$profile_group_list = array();
		$fields_list = $this->getRoleFieldIds($uid);
		if ($fields_list) {
			$fields_group_ids = D('FieldSetting')->where(array('id' => array('in', $fields_list), 'status' => '1'))->field('profile_group_id')->select();
			if ($fields_group_ids) {
				$fields_group_ids = array_unique(array_column($fields_group_ids, 'profile_group_id'));
				$map['id'] = array('in', $fields_group_ids);

				if (isset($uid) && $uid != is_login()) {
					$map['visiable'] = 1;
				}
				$map['status'] = 1;
				$profile_group_list = D('field_group')->where($map)->order('sort asc')->select();
			}
		}
		return $profile_group_list;
	}

	private function getRoleFieldIds($uid = null) {
		$role_id = get_role_id($uid);
		$fields_list = S('Role_Expend_Info_' . $role_id);
		if (!$fields_list) {
			$map_role_config = getRoleConfigMap('expend_field', $role_id);
			$fields_list = D('RoleConfig')->where($map_role_config)->getField('value');
			if ($fields_list) {
				$fields_list = explode(',', $fields_list);
				S('Role_Expend_Info_' . $role_id, $fields_list, 600);
			}
		}
		return $fields_list;
	}

	/**
	 * 分组下的字段信息及相应内容
	 * @param null $id
	 * @param null $uid
	 * @return null
	 * @author 郑钟良<zzl@ourstu.com>
	 */
	public function _info_list($id = null, $uid = null) {
		$fields_list = $this->getRoleFieldIds($uid);
		$info_list = null;

		if (isset($uid) && $uid != is_login()) {
			//查看别人的扩展信息
			$field_setting_list = D('field_setting')->where(array('profile_group_id' => $id, 'status' => '1', 'visiable' => '1', 'id' => array('in', $fields_list)))->order('sort asc')->select();

			if (!$field_setting_list) {
				return null;
			}
			$map['uid'] = $uid;
		} else if (is_login()) {
			$field_setting_list = D('field_setting')->where(array('profile_group_id' => $id, 'status' => '1', 'id' => array('in', $fields_list)))->order('sort asc')->select();

			if (!$field_setting_list) {
				return null;
			}
			$map['uid'] = is_login();

		} else {
			$this->error('请先登录！');
		}
		foreach ($field_setting_list as &$val) {
			$map['field_id'] = $val['id'];
			$field = D('field')->where($map)->find();
			$val['field_content'] = $field;
			unset($map['field_id']);
			$info_list[$val['id']] = $this->_get_field_data($val);
			//当用户扩展资料为数组方式的处理@MingYangliu
			$vlaa = explode('|', $val['form_default_value']);
			$needle = ':'; //判断是否包含a这个字符
			$tmparray = explode($needle, $vlaa[0]);
			if (count($tmparray) > 1) {
				foreach ($vlaa as $kye => $vlaas) {
					if (count($tmparray) > 1) {
						$vlab[] = explode(':', $vlaas);
						foreach ($vlab as $key => $vlass) {
							$items[$vlass[0]] = $vlass[1];
						}
					}
					continue;
				}
				$info_list[$val['id']]['field_data'] = $items[$info_list[$val['id']]['field_data']];
			}
			//当扩展资料为join时，读取数据并进行处理再显示到前端@MingYang
			if ($val['child_form_type'] == 'join') {
				$j = explode('|', $val['form_default_value']);
				$a = explode(' ', $info_list[$val['id']]['field_data']);
				$info_list[$val['id']]['field_data'] = get_userdata_join($a, $j[0], $j[1]);
			}
		}
		return $info_list;
	}

	public function _get_field_data($data = null) {
		$result = array();
		$result['field_name'] = $data['field_name'];
		$result['field_data'] = '还未设置';
		switch ($data['form_type']) {
		case 'input':
		case 'radio':
		case 'textarea':
		case 'select':
			$result['field_data'] = isset($data['field_content']['field_data']) ? $data['field_content']['field_data'] : '还未设置';
			break;
		case 'checkbox':
			$result['field_data'] = isset($data['field_content']['field_data']) ? implode(' ', explode('|', $data['field_content']['field_data'])) : '还未设置';
			break;
		case 'time':
			$result['field_data'] = isset($data['field_content']['field_data']) ? date('Y-m-d', $data['field_content']['field_data']) : '还未设置';
			break;
		}
		$result['field_data'] = op_t($result['field_data']);
		return $result;
	}

	public function appList($uid = null, $page = 1, $count = 10, $tab = null) {

		$appArr = $this->_tab_menu();

		$type = op_t($_GET['type']);
		if (!isset($appArr[$type])) {
			$this->error('参数出错！！');
		}
		$this->assign('type', $type);
		$className = ucfirst($type) . 'Protocol';
		$dao = D(ucfirst($type) . '/' . $className);
		$content = $dao->profileContent($uid, $page, $count, $tab);
		if (empty($content)) {
			$content = '暂无内容';
		} else {
			$totalCount = $dao->getTotalCount($uid, $tab);
			$this->assign('totalCount', $totalCount);
		}
		$this->assign('content', $content);

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$str_app = '{$appArr[$type]|op_t}';
		$this->setTitle($str . '的个人' . $str_app . '页');
		$this->setKeywords($str . '，个人主页，个人' . $str_app);
		$this->setDescription($str . '的个人' . $str_app . '页');
		//四处一词 seo end

		$this->display('index');
	}

	/**
	 * 个人主页标签导航
	 * @return void
	 */
	public function _tab_menu() {
		static $apps = null;
		if (is_null($apps)) {
			$appList = D('Module')->getAll();
			$apps = array();
			// 获取APP的HASH数组
			foreach ($appList as $appName => $module) {
				$className = $appName;
				if (!$this->protocol_exists($className)) {
					continue;
				}

				$dao = D($className . '/' . $className . 'Protocol');
				if (method_exists($dao, 'profileContent') && $module['is_setup']) {
					$appName = strtolower($appName);
					$apps[$appName] = $dao->getModelInfo();
				}
				unset($dao);
			}
			$apps = $this->sortApps($apps);
		}
		$this->assign('appArr', $apps);

		return $apps;
	}

	public function protocol_exists($className) {
		#echo APP_PATH.$className.'/Model/'.$className . 'ProtocolModel.class.php<br/>';
		return file_exists(APP_PATH . $className . '/Model/' . $className . 'ProtocolModel.class.php');
	}

	public function _fans_and_following($uid = null, &$user_info = array()) {
		$map_follow = $map = array();
		$uid = isset($uid) ? $uid : is_login();
		//我的粉丝展示
		$map['follow_who'] = $uid;
		$fans_default = D('Follow')->where($map)->field('who_follow')->order('create_time desc')->limit(8)->select();
		$fans_totalCount = $user_info && isset($user_info['fans']) ? $user_info['fans'] : D('Follow')->where($map)->count();
		foreach ($fans_default as &$user) {
			$user['user'] = query_user(array('avatar64', 'uid', 'nickname', 'fans', 'following', 'weibocount', 'space_url', 'title'), $user['who_follow']);
		}
		unset($user);
		$this->assign('fans_totalCount', $fans_totalCount);
		$this->assign('fans_default', $fans_default);

		//我关注的展示
		$map_follow['who_follow'] = $uid;
		$follow_default = D('Follow')->where($map_follow)->field('follow_who')->order('create_time desc')->limit(8)->select();
		$follow_totalCount = $user_info && isset($user_info['following']) ? $user_info['following'] : D('Follow')->where($map_follow)->count();
		foreach ($follow_default as &$user) {
			$user['user'] = query_user(array('avatar64', 'uid', 'nickname', 'fans', 'following', 'weibocount', 'space_url', 'title'), $user['follow_who']);
		}
		unset($user);
		$this->assign('follow_totalCount', $follow_totalCount);
		$this->assign('follow_default', $follow_default);
	}

	public function fans($uid = null, $page = 1) {
		$uid = isset($uid) ? $uid : is_login();
		//调用API获取基本信息
		$user_info = $this->userInfo($uid);

		$this->assign('tab', 'fans');
		$fans = D('Follow')->getFans($uid, $page, array('avatar128', 'uid', 'nickname', 'fans', 'following', 'weibocount', 'space_url', 'title'), $user_info['fans']);
		$this->assign('fans', $userInfo['fans']);
		$this->assign('totalCount', $user_info['fans']);

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的个人粉丝页');
		$this->setKeywords($str . '，个人粉丝');
		$this->setDescription($str . '的个人粉丝页');
		//四处一词 seo end

		$this->display();
	}

	public function following($uid = null, $page = 1) {
		$uid = isset($uid) ? $uid : is_login();
		//调用API获取基本信息
		$user_info = $this->userInfo($uid);

		$following = D('Follow')->getFollowing($uid, $page, array('avatar128', 'uid', 'nickname', 'fans', 'following', 'weibocount', 'space_url', 'title'), $user_info['following']);
		$this->assign('following', $following);
		$this->assign('totalCount', $user_info['following']);
		$this->assign('tab', 'following');

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的个人关注页');
		$this->setKeywords($str . '，个人关注');
		$this->setDescription($str . '的个人关注页');
		//四处一词 seo end

		$this->display();
	}

	public function rank($uid = null) {
		$uid = isset($uid) ? $uid : is_login();

		$rankList = D('rank_user')->where(array('uid' => $uid, 'status' => 1, '_string' => 'expire_time=0 OR expire_time>' . NOW_TIME))->field('rank_id,reason,create_time,expire_time')->select();
		if ($rankList) {
			foreach ($rankList as &$val) {
				$rank = D('rank')->where(array('id' => $val['rank_id']))->find();
				$val['title'] = $rank['title'];
				$val['logo'] = $rank['logo'];
			}
			unset($val);
		}

		$this->assign('rankList', $rankList);
		$this->assign('tab', 'rank');

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的头衔列表页');
		$this->setKeywords($str . '，个人头衔');
		$this->setDescription($str . '的头衔列表页');
		//四处一词 seo end

		$this->display('rank');
	}

	public function rankVerifyFailure() {
		$uid = is_login();

		$rankList = D('rank_user')->where(array('uid' => $uid, 'status' => -1))->field('id,rank_id,reason,create_time')->select();
		if ($rankList) {
			foreach ($rankList as &$val) {
				$rank = D('rank')->where(array('id' => $val['rank_id']))->find();
				$val['title'] = $rank['title'];
				$val['logo'] = $rank['logo'];
			}
			unset($val);
		}

		$this->assign('rankList', $rankList);
		$this->assign('tab', 'rankVerifyFailure');

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的被驳回头衔申请列表页');
		$this->setKeywords($str . '，个人头衔');
		$this->setDescription($str . '的被驳回头衔申请列表页');
		//四处一词 seo end

		$this->display('rank');
	}

	public function rankVerifyWait() {
		$uid = is_login();

		$rankList = D('rank_user')->where(array('uid' => $uid, 'status' => 0))->field('rank_id,reason,create_time')->select();
		if ($rankList) {
			foreach ($rankList as &$val) {
				$rank = D('rank')->where(array('id' => $val['rank_id']))->find();
				$val['title'] = $rank['title'];
				$val['logo'] = $rank['logo'];
			}
			unset($val);
		}

		$this->assign('rankList', $rankList);
		$this->assign('tab', 'rankVerifyWait');

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的待审核头衔申请列表页');
		$this->setKeywords($str . '，个人头衔');
		$this->setDescription($str . '的待审核头衔申请列表页');
		//四处一词 seo end

		$this->display('rank');
	}

	public function rankVerifyCancel($rank_id = null) {
		$rank_id = intval($rank_id);
		if (is_login() && $rank_id) {
			$map['rank_id'] = $rank_id;
			$map['uid'] = is_login();
			$map['status'] = 0;
			$result = D('rank_user')->where($map)->delete();
			if ($result) {
				D('Message')->sendMessageWithoutCheckSelf(is_login(), '头衔申请取消成功', '取消头衔申请', U('Ucenter/Message/message', array('tab' => 'system')));
				$this->success('取消成功', U('Ucenter/Index/rankVerifyWait'));
			} else {
				$this->error('取消失败');
			}
		}
	}

	public function rankVerify($rank_user_id = null) {
		$uid = is_login();

		$rank_user_id = intval($rank_user_id);
		$map_already['uid'] = $uid;
		//重新申请头衔
		if ($rank_user_id) {
			$model = D('rank_user')->where(array('id' => $rank_user_id));
			$old_rank_user = $model->field('id,rank_id,reason')->find();
			if (!$old_rank_user || !D('rank')->canApply($old_rank_user['rank_id'])) {
				$this->error('请正确选择要重新申请的头衔');
			}
			$this->assign('old_rank_user', $old_rank_user);
			$map_already['id'] = array('neq', $rank_user_id);
			D('Message')->sendMessageWithoutCheckSelf(is_login(), '你将进行头衔的重新申请', '头衔重新申请', U('Ucenter/Message/message', array('tab' => 'system')));
		}
		$alreadyRank = D('rank_user')->where($map_already)->field('rank_id')->select();
		$alreadyRank = array_column($alreadyRank, 'rank_id');
		if ($alreadyRank) {
			$map['id'] = array('not in', $alreadyRank);
		}
		$map['types'] = 1;
		$rankList = D('rank')->where($map)->select();
		$this->assign('rankList', $rankList);
		$this->assign('tab', 'rankVerify');

		//四处一词 seo
		$str = '{$user_info.nickname|op_t}';
		$this->setTitle($str . '的头衔申请页');
		$this->setKeywords($str . '，个人头衔，头衔申请');
		$this->setDescription($str . '的头衔申请页');
		//四处一词 seo end

		$this->display('rank_verify');
	}

	public function verify($rank_id = null, $reason = null, $rank_user_id = 0) {
		$rank_id = intval($rank_id);
		$reason = op_t($reason);
		$rank_user_id = intval($rank_user_id);
		if (!$rank_id) {
			$this->error('请选择要申请的头衔');
		}
		if ($reason == null || $reason == '') {
			$this->error('请填写申请理由');
		}
		$data = array();
		$data['rank_id'] = $rank_id;
		$data['reason'] = $reason;
		$data['uid'] = is_login();
		$data['is_show'] = 1;
		$data['create_time'] = time();
		$data['status'] = 0;
		if ($rank_user_id) {
			$model = D('rank_user')->where(array('id' => $rank_user_id));
			if (!($old_rank_user = $model->find()) || !D('rank')->canApply($old_rank_user['rank_id'])) {
				$this->error('请正确选择要重新申请的头衔');
			}
			$result = D('rank_user')->where(array('id' => $rank_user_id))->save($data);
		} else {
			$result = D('rank_user')->add($data);
		}
		if ($result) {
			D('Message')->sendMessageWithoutCheckSelf(is_login(), '头衔申请成功,等待管理员审核', '头衔申请', U('Ucenter/Message/message', array('tab' => 'system')));
			$this->success('申请成功,等待管理员审核', U('Ucenter/Index/rankVerify'));
		} else {
			$this->error('申请失败');
		}
	}

	/**
	 * @param $apps
	 * @param $vals
	 * @return mixed
	 * @auth 陈一枭
	 */
	private function sortApps($apps) {
		return $this->multi_array_sort($apps, 'sort', SORT_DESC);
	}

	public function multi_array_sort($multi_array, $sort_key, $sort = SORT_ASC) {
		$key_array = array();
		if (is_array($multi_array)) {
			foreach ($multi_array as $row_array) {
				if (is_array($row_array)) {
					$key_array[] = $row_array[$sort_key];
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
		array_multisort($key_array, $sort, $multi_array);
		return $multi_array;
	}
}