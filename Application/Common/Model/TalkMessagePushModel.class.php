<?php
/**
 * 所属项目 OnePlus.
 * 开发者: 陈一枭
 * 创建日期: 6/9/14
 * 创建时间: 2:22 PM
 * 版权所有 嘉兴想天信息科技有限公司(www.ourstu.com)
 */

namespace Common\Model;

class TalkMessagePushModel extends Base {

	/**
	 * 取得全部的推送消息
	 * @return mixed
	 * @auth 陈一枭
	 */
	public function getAllPush($page = 1, $limit = 500) {
		$new_talks = $this->where(array('uid' => get_uid(), 'status' => 0))->page($page, $limit)->select();
		$src_ids = array();
		foreach ($new_talks as $k => &$v) {
			$src_ids[$v['source_id']][] = $k;
			//$message = D('TalkMessage')->find($v['source_id']);
			//$talk=D('Talk')->find($message['talk_id']);
			$v['talk_message'] = array();
		}
		if ($src_ids) {
			$messages = D('TalkMessage')->where(array('id' => array('in', array_keys($src_ids))))->select();
			if ($messages) {
				foreach ($messages as $v) {
					foreach ($src_ids[$v['id']] as $key) {
						$new_talks[$key]['talk_message'] = $v;
					}
				}
			}
		}
		return $new_talks;
	}
}