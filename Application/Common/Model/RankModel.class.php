<?php
/**
 * 头衔
 * @author  swh <swh@admpub.com>
 */
namespace Common\Model;

class RankModel extends Base {

	/**
	 * 是否为可申请头衔
	 * @param integer $rank_id 头衔ID
	 * @return bool
	 * @author swh <swh@admpub.com>
	 */
	public function canApply($rank_id) {
		static $_can = array();
		if (!isset($_can[$rank_id])) {
			$_can[$rank_id] = $this->where(array('id' => $rank_id))->getField('types') == 1;
		}
		return $_can[$rank_id];
	}
}