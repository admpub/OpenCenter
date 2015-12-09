<?php
namespace Addons\LocalComment\Controller;

use Think\Controller;

class IndexController extends Controller {

	public function addComment() {

		$config = get_addon_config('LocalComment');
		$can_guest_comment = $config['can_guest_comment'];
		if (!$can_guest_comment) {
			//不允许游客评论
			if (!is_login()) {
				$this->error('请登录后评论。');
			}
		}

		//获取参数
		$app = I('request.app', '', 'trim,htmlspecialchars');
		$mod = I('request.mod', '', 'trim,htmlspecialchars');
		$row_id = I('request.row_id', 0, 'intval');
		$content = I('request.content', '', 'trim,htmlspecialchars');
		$uid = I('request.uid', 0, 'intval');

		//调用API接口，添加新评论
		$data = array(
			'app' => $app,
			'mod' => $mod,
			'row_id' => $row_id,
			'content' => $content,
			'uid' => is_login(),
		);
		if (!preg_match('/^[\\w]+$/', $app)) {
			$this->error('app的值不正确');
		}
		if (!preg_match('/^[\\w]+$/', $mod)) {
			$this->error('mod的值不正确');
		}
		D($app . '/' . $mod)->where(array('id' => $row_id))->setInc('reply_count');
		$commentModel = D('Addons://LocalComment/LocalComment');
		$data = $commentModel->create($data);
		if (!$data) {
			$this->error('评论失败：' . $commentModel->getError());
		}
		$commentModel->add($data);
		//游客逻辑直接跳过@环节
		if (!is_login()) {
			if ($uid) {
				$title = '游客' . '评论了您';
				$message = '评论内容：' . $content;
				$url = htmlspecialchars($_SERVER['HTTP_REFERER']);
				D('Common/Message')->sendMessage($uid, $message, $title, $url, 0, 0, $app);
			}
			//返回结果
			$this->success('评论成功', 'refresh');
		} else {
			//给评论对象发送消息
			if ($uid) {
				$user = D('Member')->find(get_uid());
				$title = $user['nickname'] . '评论了您';
				$message = '评论内容：' . $content;
				$url = htmlspecialchars($_SERVER['HTTP_REFERER']);
				D('Common/Message')->sendMessage($uid, $message, $title, $url, get_uid(), 0, $app);
			}
		}

		//通知被@到的人
		$uids = get_at_uids($content);
		$uids = array_unique($uids);
		$uids = array_subtract($uids, array($uid));
		foreach ($uids as $uid) {
			$user = D('Member')->find(get_uid());
			$title = $user['nickname'] . '@了您';
			$message = '评论内容：' . $content;
			$url = htmlspecialchars($_SERVER['HTTP_REFERER']);
			D('Common/Message')->sendMessage($uid, $message, $title, $url, get_uid(), 0, $app);
		}

		//返回结果
		$this->success('评论成功', 'refresh');
	}

	public function deleteComment() {
		$aCid = I('post.id', 0, 'intval');
		if ($aCid <= 0) {
			$this->error('删除评论失败。评论不存在。');
		}
		//检查权限
		$canDelete = check_auth('deleteLocalComment') || is_administrator();
		$commentModel = D('Addons://LocalComment/LocalComment');
		$comment = $commentModel->find($aCid);
		$isOnwer = ($comment['uid'] == is_login() and is_login() != 0);
		if ($canDelete || $isOnwer) {
			$result = $commentModel->where(array('id' => $aCid))->delete();
			if ($result) {
				$this->success('删除评论成功。', 'refresh');
			} else {
				$this->error('删除评论失败。' . $commentModel->getError());
			}
		} else {
			$this->error('删除评论失败。' . '权限不足');
		}

	}
}