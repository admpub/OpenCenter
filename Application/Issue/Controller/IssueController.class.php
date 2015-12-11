<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */
namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;

class IssueController extends AdminController {
	protected $issueModel;

	protected function _initialize() {
		parent::_initialize();
		$this->issueModel = D('Issue/Issue');
	}

	public function config() {
		$admin_config = new AdminConfigBuilder();
		$data = $admin_config->handleConfig();

		$admin_config->title('专辑基本设置')
			->keyBool('NEED_VERIFY', '投稿是否需要审核', '默认无需审核')
			->buttonSubmit('', '保存')->data($data);
		$admin_config->display();
	}

	public function issue() {
		//显示页面
		$builder = new AdminTreeListBuilder();
		$attr['class'] = 'btn ajax-post';
		$attr['target-form'] = 'ids';
		$attr1 = $attr;
		$attr1['url'] = $builder->addUrlParam(U('setWeiboTop'), array('top' => 1));
		$attr0 = $attr;
		$attr0['url'] = $builder->addUrlParam(U('setWeiboTop'), array('top' => 0));

		$tree = D('Issue/Issue')->getTree(0, 'id,title,sort,pid,status');

		$builder->title('专辑管理')
			->buttonNew(U('Issue/add'))
			->data($tree)
			->display();
	}

	public function add($id = 0, $pid = 0) {
		if (IS_POST) {
			if ($id != 0) {
				$issue = $this->issueModel->create();
				if ($this->issueModel->save($issue)) {

					$this->success('编辑成功。');
				} else {
					$this->error('编辑失败。');
				}
			} else {
				$issue = $this->issueModel->create();
				if ($this->issueModel->add($issue)) {

					$this->success('新增成功。');
				} else {
					$this->error('新增失败。');
				}
			}

		} else {
			$builder = new AdminConfigBuilder();
			$issues = $this->issueModel->select();
			$opt = array();
			foreach ($issues as $issue) {
				$opt[$issue['id']] = $issue['title'];
			}
			if ($id != 0) {
				$issue = $this->issueModel->find($id);
			} else {
				$issue = array('pid' => $pid, 'status' => 1);
			}

			$builder->title('新增分类')->keyId()->keyText('title', '标题')->keySelect('pid', '父分类', '选择父级分类', array('0' => '顶级分类') + $opt)
				->keyStatus()->keyCreateTime()->keyUpdateTime()
				->data($issue)
				->buttonSubmit(U('Issue/add'))->buttonBack()->display();
		}

	}

	public function issueTrash($page = 1, $r = 20, $model = '') {
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		//读取微博列表
		$map = array('status' => -1);
		$model = $this->issueModel;
		$list = $model->where($map)->page($page, $r)->select();
		$totalCount = $model->where($map)->count();

		//显示页面
		$builder->title('专辑回收站')
			->setStatusUrl(U('setStatus'))->buttonRestore()->buttonClear('Issue/Issue')
			->keyId()->keyText('title', '标题')->keyStatus()->keyCreateTime()
			->data($list)
			->pagination($totalCount, $r)
			->display();
	}

	public function operate($type = 'move', $from = 0) {
		$builder = new AdminConfigBuilder();
		$from = D('Issue')->find($from);

		$opt = array();
		$issues = $this->issueModel->select();
		foreach ($issues as $issue) {
			$opt[$issue['id']] = $issue['title'];
		}
		if ($type === 'move') {
			$builder->title('移动分类')->keyId()->keySelect('pid', '父分类', '选择父分类', $opt)->buttonSubmit(U('Issue/add'))->buttonBack()->data($from)->display();
		} else {
			$builder->title('合并分类')->keyId()->keySelect('toid', '合并至的分类', '选择合并至的分类', $opt)->buttonSubmit(U('Issue/doMerge'))->buttonBack()->data($from)->display();
		}

	}

	public function doMerge($id, $toid) {
		$effect_count = D('IssueContent')->where(array('issue_id' => $id))->setField('issue_id', $toid);
		D('Issue')->where(array('id' => $id))->setField('status', -1);
		$this->success('合并分类成功。共影响了' . $effect_count . '个内容。', U('issue'));
		//TODO 实现合并功能 issue
	}

	public function contents($page = 1, $r = 10) {
		//读取列表
		$map = array('status' => 1);
		$model = M('IssueContent');
		$list = $model->where($map)->page($page, $r)->select();
		unset($li);
		$totalCount = $model->where($map)->count();

		//显示页面
		$builder = new AdminListBuilder();
		$attr['class'] = 'btn ajax-post';
		$attr['target-form'] = 'ids';

		$builder->title('内容管理')
			->setStatusUrl(U('setIssueContentStatus'))
			->buttonDisable('', '审核不通过')
			->buttonDelete()
			->buttonNew(U('content_add'))
			->keyId()->keyLink('title', '标题', 'Issue/Index/issueContentDetail?id=###')->keyUid()->keyCreateTime()->keyStatus()
			->data($list)
			->pagination($totalCount, $r)
			->display();
	}
	public function verify($page = 1, $r = 10) {
		//读取列表
		$map = array('status' => 0);
		$model = M('IssueContent');
		$list = $model->where($map)->page($page, $r)->select();
		unset($li);
		$totalCount = $model->where($map)->count();

		//显示页面
		$builder = new AdminListBuilder();
		$attr['class'] = 'btn ajax-post';
		$attr['target-form'] = 'ids';

		$builder->title('审核内容')
			->setStatusUrl(U('setIssueContentStatus'))->buttonEnable('', '审核通过')->buttonDelete()
			->keyId()->keyLink('title', '标题', 'Issue/Index/issueContentDetail?id=###')->keyUid()->keyCreateTime()->keyStatus()
			->data($list)
			->pagination($totalCount, $r)
			->display();
	}

	public function setIssueContentStatus() {
		$ids = I('ids');
		$status = I('get.status', 0, 'intval');
		$builder = new AdminListBuilder();
		if ($status == 1) {
			foreach ($ids as $id) {
				$content = D('IssueContent')->find($id);
				D('Common/Message')->sendMessage($content['uid'], "管理员审核通过了您发布的内容。现在可以在列表看到该内容了。", $title = '专辑内容审核通知', U('Issue/Index/issueContentDetail', array('id' => $id)), is_login(), 2);
				/*同步微博*/
				/*  $user = query_user(array('nickname', 'space_link'), $content['uid']);
					                $weibo_content = '管理员审核通过了@' . $user['nickname'] . ' 的内容：【' . $content['title'] . '】，快去看看吧：' ."http://$_SERVER[HTTP_HOST]" .U('Issue/Index/issueContentDetail',array('id'=>$content['id']));
					                $model = D('Weibo/Weibo');
				*/
				/*同步微博end*/
			}

		}
		$builder->doSetStatus('IssueContent', $ids, $status);

	}

	public function contentTrash($page = 1, $r = 10, $model = '') {
		//读取微博列表
		$builder = new AdminListBuilder();
		$builder->clearTrash($model);
		$map = array('status' => -1);
		$model = D('IssueContent');
		$list = $model->where($map)->page($page, $r)->select();
		$totalCount = $model->where($map)->count();

		//显示页面

		$builder->title('内容回收站')
			->setStatusUrl(U('setIssueContentStatus'))->buttonRestore()->buttonClear('IssueContent')
			->keyId()->keyLink('title', '标题', 'Issue/Index/issueContentDetail?id=###')->keyUid()->keyCreateTime()->keyStatus()
			->data($list)
			->pagination($totalCount, $r)
			->display();
	}

	/**
	 * 提交内容
	 * @param  integer $id       [description]
	 * @param  integer $cover_id [description]
	 * @param  string  $title    [description]
	 * @param  string  $content  [description]
	 * @param  integer $issue_id [description]
	 * @param  string  $url      [description]
	 * @return void
	 */
	public function content_post($id = 0, $cover_id = 0, $title = '', $content = '', $issue_id = 0, $url = '') {
		if (!check_auth('addIssueContent')) {
			$this->error('抱歉，您不具备投稿权限。');
		}
		$issue_id = intval($issue_id);
		if (!is_login()) {
			$this->error('请登陆后再投稿。');
		}
		$cover_id = intval($cover_id);
		// if ($cover_id<=0) {
		// 	$this->error('请上传封面。');
		// }
		if (($title = trim(op_t($title))) == '') {
			$this->error('请输入标题。');
		}
		if (($content = trim(op_h($content))) == '') {
			$this->error('请输入内容。');
		}
		if ($issue_id == 0) {
			$this->error('请选择分类。');
		}
		$url = trim(op_h($url));
		$data = array();
		$data['content'] = &$content;
		$data['title'] = &$title;
		$data['url'] = &$url; //新增链接框
		$data['issue_id'] = &$issue_id;
		$data['cover_id'] = &$cover_id;
		$data = D('Issue/IssueContent')->create($data);
		if ($data === false) {
			$this->error(D('Issue/IssueContent')->getError());
		}

		if ($id) {
			$temp = D('Issue/IssueContent')->find($id);
			if (!check_auth('editIssueContent')) {
				//不是管理员则进行检测
				if ($temp['uid'] != is_login()) {
					$this->error('不可操作他人的内容。');
				}
			}
			$data['uid'] = $temp['uid']; //权限矫正，防止被改为管理员
			$rs = D('Issue/IssueContent')->save($data);
			if ($rs) {
				$this->success('编辑成功。', U('contents'));
			} else {
				$this->success('编辑失败。', '');
			}
		} else {
			if (modC('NEED_VERIFY', 0) && !is_administrator()) {
				//需要审核且不是管理员
				$data['status'] = 0;
				$tip = '但需管理员审核通过后才会显示在列表中，请耐心等待。';
				$user = query_user(array('nickname'), is_login());
				$admin_uids = explode(',', C('USER_ADMINISTRATOR'));
				foreach ($admin_uids as $admin_uid) {
					D('Common/Message')->sendMessage($admin_uid, "{$user['nickname']}向专辑投了一份稿件，请到后台审核。", '专辑投稿提醒', U('Admin/Issue/verify'), is_login(), 2);
				}
			}
			$rs = D('Issue/IssueContent')->add($data);
			if ($rs) {
				$this->success('投稿成功。' . $tip, U('contents'));
			} else {
				$this->success('投稿失败。', '');
			}
		}
	}

	/**
	 * 打开内容添加表单
	 * @param  integer $id 内容id
	 * @return void
	 */
	public function content_add($issue_id = 0) {
		if (!check_auth('addIssueContent')) {
			$this->error('抱歉，您不具备投稿权限。');
		}
		$issue_id = intval($issue_id);
		$issue = D('Issue/Issue')->find($issue_id);
		$issues = D('Issue/Issue')->where(array(
			//'allow_post' => 1,
			'status' => 1,
			'pid' => 0,
		))->order('sort')->getField('id,title');
		$builder = new AdminConfigBuilder();
		$this->setTitle('添加文章');
		$builder->title('添加文章');
		$builder->keyId()
			->keyRelationSelect('issue_id', '分类', null, $issues, U('issue_list'), array('cat_0' => '', 'cat_1' => ''))
			->keyText('title', '标题')
			->keySingleImage('covert_id', '封面图片')
			->keyText('url', '网址')
			->keyEditor('content', '内容');
		$builder->buttonSubmit(U('content_post'))->buttonBack()->display();
	}

	public function issue_list() {
		$ret = array('status' => 1, 'data' => array());
		$ret['data'] = D('Issue/Issue')->where(array(
			//'allow_post' => 1,
			'status' => 1,
			'pid' => I('get.value', 0, 'intval'),
		))->order('sort')->getField('id,title');
		$this->ajaxr($ret);
	}

	/**
	 * 打开内容修改表单
	 * @param  integer $id 内容id
	 * @return void
	 */
	public function content_edit($id) {
		if (!check_auth('addIssueContent') && !check_auth('editIssueContent')) {
			$this->error('抱歉，您不具备投稿权限。');
		}
		$issue_content = D('Issue/IssueContent')->find($id);
		if (!$issue_content) {
			$this->error('404 not found');
		}
		if (!check_auth('editIssueContent')) {
			//不是管理员则进行检测
			if ($issue_content['uid'] != is_login()) {
				$this->error('404 not found');
			}
		}

		$issue = D('Issue/Issue')->find($issue_content['issue_id']);

		$this->assign('top_issue', $issue['pid'] == 0 ? $issue['id'] : $issue['pid']);
		$this->assign('issue_id', $issue['id']);
		$issue_content['user'] = query_user(array('id', 'nickname', 'space_url', 'space_link', 'avatar64', 'rank_html', 'signature'), $issue_content['uid']);
		$this->assign('content', $issue_content);
		$this->display();
	}
}
