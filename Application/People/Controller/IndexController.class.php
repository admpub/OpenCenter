<?php
namespace People\Controller;
use Common\Controller\Base;

class IndexController extends Base {
	/**
	 * 业务逻辑都放在 WeiboApi 中
	 * @var
	 */
	protected function _initialize() {
		parent::_initialize();
	}

	public function index($page = 1, $keywords = '') {
		$map = $this->setMap();
		$map['status'] = 1;
		$map['last_login_time'] = array('neq', 0);
		$page = I('page', 1, 'intval');
		$page < 1 && $page = 1;
		$cachedPage = 10;
		$cachedId = 'People_peoples_' . $page . '_' . serialize($map);
		$peoples = $page > $cachedPage || !empty($_REQUEST['keywords']) || !empty($_REQUEST['tag']) ? null : S($cachedId);
		#$peoples = null;
		if (empty($peoples)) {
			$peoples = D('Member')->where($map)->field('uid,reg_time,last_login_time')->order('last_login_time desc')->findPage(20);
			$userConfigModel = D('Ucenter/UserConfig');
			$titleModel = D('Ucenter/Title');
			foreach ($peoples['data'] as &$v) {
				$v['user'] = query_user(array('title', 'avatar128', 'nickname', 'uid', 'space_url', 'icons_html', 'score', 'title', 'fans', 'following', 'rank_link', 'is_following'), $v['uid']);
				$v['level'] = $titleModel->getCurrentTitleInfo($v['uid']);
				//获取用户封面id
				$where = getUserConfigMap('user_cover', '', $v['uid']);
				$where['role_id'] = 0;
				$model = $userConfigModel;
				$cover = $model->where($where)->find();
				$v['cover_id'] = $cover['value'];
				$v['cover_path'] = getThumbImageById($cover['value'], 273, 80);
			}
			$page <= $cachedPage && S($cachedId, $peoples, 300);
		}
		#dump($peoples);exit;
		$this->assign('tab', 'index');
		$this->assign('lists', $peoples);
		$this->display();
	}

	public function find($page = 1, $keywords = '') {
		return $this->index($page, $keywords);
	}

	private function setMap() {
		$aTag = I('tag', 0, 'intval');
		$map = array();
		if ($aTag) {
			$map_uids['tags'] = array('like', '%[' . $aTag . ']%');
			$links = D('Ucenter/UserTagLink')->getListByMap($map_uids);
			$uids = array_column($links, 'uid');
			$map['uid'] = array('in', $uids);
			$this->assign('tag_id', $aTag);
		}
		$userTagModel = D('Ucenter/UserTag');
		$tag_list = $userTagModel->getTreeList();
		$this->assign('tag_list', $tag_list);

		$nickname = I('keywords', '', 'op_t');
		if ($nickname != '') {
			$map['nickname'] = array('like', '%' . $nickname . '%');
			$this->assign('nickname', $nickname);
		}
		return $map;
	}
}