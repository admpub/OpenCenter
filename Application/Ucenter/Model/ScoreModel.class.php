<?php
namespace Ucenter\Model;
use Common\Model\Base;

/**
 * Class ScoreModel   用户积分模型
 * @package Ucenter\Model
 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
 */
class ScoreModel extends Base {
	protected $tableName = 'ucenter_score_type';

	protected function _initialize() {
		parent::_initialize();
	}

	/**
	 * getTypeList  获取类型列表
	 * @param string $map
	 * @return mixed
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function getTypeList($map = '') {
		$list = $this->where($map)->order('id asc')->select();

		return $list;
	}

	/**
	 * getType  获取单个类型
	 * @param string $map
	 * @return mixed
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function getType($map = '') {
		$type = $this->where($map)->find();
		return $type;
	}

	/**
	 * addType 增加积分类型
	 * @param $data
	 * @return mixed
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function addType($data) {
		$db_prefix = C('DB_PREFIX');
		$res = $this->add($data);
		$query = "ALTER TABLE  `{$db_prefix}member` ADD  `score" . $res . "` FLOAT NOT NULL DEFAULT '0' COMMENT '" . $data['title'] . "'";
		D()->execute($query);
		return $res;
	}

	/**
	 * delType  删除分类
	 * @param $ids
	 * @return mixed
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function delType($ids) {
		$db_prefix = C('DB_PREFIX');
		$res = $this->where(array('id' => array(array('in', $ids), array('gt', 4), 'and')))->delete();
		foreach ($ids as $v) {
			if ($v > 4) {
				$query = "ALTER TABLE `{$db_prefix}member` DROP COLUMN score" . $v;
				D()->execute($query);
			}
		}
		return $res;
	}

	/**
	 * editType  修改积分类型
	 * @param $data
	 * @return mixed
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function editType($data) {
		$db_prefix = C('DB_PREFIX');
		$res = $this->save($data);
		$query = "ALTER TABLE `{$db_prefix}member` MODIFY COLUMN `score" . $data['id'] . "` FLOAT NOT NULL DEFAULT '0' comment '" . $data['title'] . "';";
		D()->execute($query);
		return $res;
	}

	/**
	 * getUserScore  获取用户的积分
	 * @param int $uid
	 * @param int $type
	 * @return mixed
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function getUserScore($uid, $type) {
		$model = D('Member');
		$score = $model->where(array('uid' => $uid))->getField('score' . $type);
		return $score;
	}

	/**
	 * setUserScore  设置用户的积分
	 * @param $uids
	 * @param $score
	 * @param $type
	 * @param string $action
	 * @author:xjw129xjt(肖骏涛) xjt@ourstu.com
	 */
	public function setUserScore($uids, $score, $type, $action = 'inc') {

		$model = D('Member');
		switch ($action) {
		case '+':
		case 'inc':
			$score = abs($score);
			$res = $model->where(array('uid' => array('in', $uids)))->setInc('score' . $type, $score);
			break;
		case '-':
		case 'dec':
			$score = abs($score);
			$res = $model->where(array('uid' => array('in', $uids)))->setDec('score' . $type, $score);
			break;
		case 'to':
			$res = $model->where(array('uid' => array('in', $uids)))->setField('score' . $type, $score);
			break;
		default:
			$res = false;
			break;
		}
		foreach ($uids as $val) {
			clean_query_user_cache($val, 'score' . $type);
		}
		unset($val);
		return $res;
	}

}