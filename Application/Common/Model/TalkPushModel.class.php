<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 陈一枭
 * 创建日期: 6/9/14
 * 创建时间: 2:22 PM
 * 版权所有 嘉兴想天信息科技有限公司(www.ourstu.com)
 */
namespace Common\Model;

class TalkPushModel extends Base {

	public function getAllPush($page = 1, $limit = 500) {
		$new_talks = $this->where(array('uid' => get_uid(), 'status' => 0))->page($page, $limit)->select();
        $src_ids = array();
		foreach ($new_talks as $k => &$v) {
			$src_ids[$v['source_id']][] = $k;
			$v['talk'] = array();
		}
		if ($src_ids) {
			$talks = D('Talk')->where(array('id' => array('in', array_keys($src_ids))))->select();
			if ($talks) {
                $talkM=D('Common/Talk');
				foreach ($talks as $v) {
					foreach ($src_ids[$v['id']] as $key) {
						$new_talks[$key]['talk'] = $v;
			            $uids = $talkM->decodeArrayByRec(explode(',', $new_talks[$key]['talk']['uids']));
			            $user = $talkM->getFirstOtherUser($uids);
			            $new_talks[$key]['talk']['ico'] = $user['avatar64'];
					}
				}
			}
		}
		return $new_talks;
	}
}