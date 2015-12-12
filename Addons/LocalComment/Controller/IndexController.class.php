<?php
namespace Addons\LocalComment\Controller;

use Think\Controller;

class IndexController extends Controller {

	/**
	 * 验证签名
	 * @param  string  $app        模块名
	 * @param  string  $mod        模型名
	 * @param  integer $row_id     模型表中的主键id
	 * @param  integer $author_uid 作者udi
	 * @return boolean
	 * @author swh <swh@admpub.com>
	 */
	public function _verifyToken($app, $mod, $row_id, $author_uid) {
		$token = I('request.token', '');
		$right = $this->_makeToken($app, $mod, $row_id, $author_uid);
		return !$right || $right == $token;
	}

	/**
	 * 生成签名。外部调用方式：
	 *	$lc = addonA('LocalComment/Index/_makeToken', array('add', 'dd', 10, $uid));
	 *	var_dump($lc);exit;
	 * @param  string  $app        模块名
	 * @param  string  $mod        模型名
	 * @param  integer $row_id     模型表中的主键id
	 * @param  integer $author_uid 作者udi
	 * @return string
	 * @author swh <swh@admpub.com>
	 */
	public function _makeToken($app, $mod, $row_id, $author_uid = null) {
		$authKey = C('DATA_AUTH_KEY');
		if (!$authKey) {
			return '';
		}
		if (is_null($app)) {
			$app = MODULE_NAME;
		}
		return md5(substr($authKey, 0, strlen($authKey) / 2) . '|' . $app . '|' . $mod . '|' . $row_id . '|' . $author_uid);
	}

	/**
	 * 提交评论
	 * 提交网址：./addComment?app=&mod=&row_id=&uid=&pid=&token=
	 */
	public function addComment() {

		$config = get_addon_config('LocalComment');
		$can_guest_comment = $config['can_guest_comment'];
		$post_uid = get_uid();
		if (!$can_guest_comment) {
			//不允许游客评论
			if (!$post_uid) {
				$this->error('请登录后评论。');
			}
		}

		//获取参数
		$app = I('request.app', '', 'trim,htmlspecialchars');
		$mod = I('request.mod', '', 'trim,htmlspecialchars');
		$row_id = I('request.row_id', 0, 'intval');
		$content = I('request.content', '', 'trim,htmlspecialchars');
		$pid = I('request.pid', 0, 'intval');
		$uid = I('request.uid', 0, 'intval');

		if ( /*!is_administrator() && */!$this->_verifyToken($app, $mod, $row_id, $uid)) {
			$this->error('参数被篡改。');
		}

		//调用API接口，添加新评论
		$data = array(
			'app' => $app,
			'mod' => $mod,
			'row_id' => $row_id,
			'content' => $content,
			'uid' => $post_uid,
			'pid' => $pid,
		);
		if ($post_uid > 0) {
			$data['nickname'] = D('Member')->where(array('uid' => $post_uid))->getField('nickname');
		} else {
			$data['nickname'] = I('request.nickname', '', 'trim,htmlspecialchars');
			if ($data['nickname'] === '') {
				$this->error('请请输入您的名称。');
			}
		}
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
		$referer_url = htmlspecialchars($_SERVER['HTTP_REFERER']);
		//游客逻辑直接跳过@环节
		if ($post_uid <= 0) {
			if ($uid) {
				$title = '游客「' . $data['nickname'] . '」评论了您';
				$message = '评论内容：' . $content;
				D('Common/Message')->sendMessage($uid, $message, $title, $referer_url, 0, 0, $app);
			}
			//返回结果
			$this->success('评论成功', 'refresh');exit;
		} else {
			//给评论对象发送消息
			$title = '用户「' . $data['nickname'] . '」评论了您';
			$message = '评论内容：' . $content;
			D('Common/Message')->sendMessage($uid, $message, $title, $referer_url, $post_uid, 0, $app);
		}

		//通知被@到的人
		$uids = get_at_uids($content);
		if ($uids) {
			foreach ($uids as $_uid) {
				if ($_uid == $uid || $_uid == $post_uid) {
					continue;
				}
				$title = '用户「' . $data['nickname'] . '」@了您';
				$message = '评论内容：' . $content;
				D('Common/Message')->sendMessage($_uid, $message, $title, $referer_url, $post_uid, 0, $app);
			}
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
			$this->error('删除评论失败。权限不足');
		}

	}
}