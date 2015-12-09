<?php
/**
 * 用户头衔
 * @author  swh <swh@admpub.com>
 */
namespace Common\Model;

class RankUserModel extends Base {

	/**
	 * 根据用户ID，获取该用户的所有头衔
	 * @param integer $uid UID
	 * @return array
	 * @author swh <swh@admpub.com>
	 */
	public function getAllByUid($uid) {
		$ranks = $this->field('a.*,b.title,c.path AS logo_url')->alias('a')->where(array(
			'a.uid' => $uid,
			'a.status' => 1,
			'_string' => 'a.expire_time=0 OR a.expire_time>' . NOW_TIME,
		))->join('LEFT JOIN __RANK__ b ON b.id=a.rank_id LEFT JOIN __PICTURE__ c ON c.id=b.logo')->select();
		return $ranks;
	}

	/**
	 * 根据用户ID，获取该用户的所有头衔
	 * @param integer $uid UID
	 * @return array
	 * @author swh <swh@admpub.com>
	 */
	public function getByUid($uid, $id, $idIsRankId = false) {
		$condition = array(
			'a.uid' => $uid,
			'a.status' => 1,
			'_string' => 'a.expire_time=0 OR a.expire_time>' . NOW_TIME,
		);
		if ($idIsRankId) {
			$condition['rank_id'] = $id;
		} else {
			$condition['id'] = $id;
		}
		$ranks = $this->field('a.*,b.title,c.path AS logo_url')->alias('a')->where($condition)->join('LEFT JOIN __RANK__ b ON b.id=a.rank_id LEFT JOIN __PICTURE__ c ON c.id=b.logo')->find();
		return $ranks;
	}
}