<?php

namespace Addons\LocalComment;

use Common\Controller\Addon;

/**
 * 本地评论插件
 * @author caipeichao
 */
class LocalCommentAddon extends Addon {

	public $info = array(
		'name' => 'LocalComment',
		'title' => '本地评论',
		'description' => '本地评论插件，不依赖社会化评论平台',
		'status' => 1,
		'author' => 'caipeichao',
		'version' => '0.1',
	);

	public function install() {
		$prefix = C('DB_PREFIX');
		D()->execute("DROP TABLE IF EXISTS `{$prefix}local_comment`");
		D()->execute(<<<SQL
CREATE TABLE `{$prefix}local_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(11) unsigned NOT NULL COMMENT '用户UID',
  `nickname` char(32) NOT NULL COMMENT '用户名称',
  `app` varchar(100) NOT NULL COMMENT '模块名',
  `mod` varchar(100) NOT NULL COMMENT '模型名',
  `row_id` int(11) unsigned NOT NULL COMMENT '模型行id',
  `parse` tinyint(2) unsigned NOT NULL COMMENT '内容解析方式',
  `content` varchar(1000) NOT NULL COMMENT '评论内容',
  `create_time` int(11) unsigned NOT NULL COMMENT '创建时间',
  `pid` int(11) unsigned NOT NULL COMMENT '被回复的评论ID',
  `status` tinyint(1) unsigned NOT NULL COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='内置评论功能表';
SQL
		);
		return true;
	}

	public function uninstall() {
		$prefix = C('DB_PREFIX');
		D()->execute("DROP TABLE IF EXISTS `{$prefix}local_comment`");
		return true;
	}

	//实现的documentDetailAfter钩子方法
	/**
	 * @param $path string 例如 Travel/detail/12
	 * @param $uid int 评论给谁？
	 * @author caipeichao
	 */
	public function localComment($param) {
		$path = $param['path'];
		$uid = $param['uid'];

		//获取参数
		$p = !empty($_REQUEST['page']) ? max(1, (int) $_REQUEST['page']) : 1;
		$path = explode('/', $path);
		$app = $path[0];
		$mod = $path[1];
		$row_id = $path[2];
		$count = 10;

		//调用接口获取评论列表
		$list = $this->getCommentList($app, $mod, $row_id, $p, $count);
		$total_count = $this->getCommentCount($app, $mod, $row_id);

		//增加用户信息
		foreach ($list as &$e) {
			$e['user'] = query_user(array('uid', 'avatar64', 'nickname', 'space_url'), $e['uid']);
		}
		unset($e);

		//显示页面
		$this->assign('list', $list);
		$this->assign('total_count', $total_count);
		$this->assign('count', $count);
		$this->assign('app', $app);
		$this->assign('mod', $mod);
		$this->assign('row_id', $row_id);
		$this->assign('uid', $uid);
		$this->display('comment');
	}

	public function getCommentList($app, $mod, $row_id, $page, $count) {
		$model = $this->getCommentModel();
		$map = array('app' => $app, 'mod' => $mod, 'row_id' => $row_id, 'status' => 1);
		$list = $model->where($map)->order('create_time desc')->page($page, $count)->select();
		return $list;
	}

	public function getCommentCount($app, $mod, $row_id) {
		$model = $this->getCommentModel();
		$map = array('app' => $app, 'mod' => $mod, 'row_id' => $row_id, 'status' => 1);
		$result = $model->where($map)->count();
		return $result;
	}

	public function getCommentModel() {
		return D('Addons://LocalComment/LocalComment');
	}

	//实现的AdminIndex钩子方法
	public function AdminIndex($param) {
		$config = $this->getConfig();
		$this->assign('addons_config', $config);
		if ($config['display']) {
			$this->display('widget');
		}

	}
}