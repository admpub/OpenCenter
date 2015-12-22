<?php

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;

/**
 * Class ConfigController   后台表情管理
 * @package Admin\Controller
 * @author:xjw129xjt xjt@ourstu.com
 */
class ExpressionController extends AdminController {
	protected $_rootPath = '';

	protected function _initialize() {
		parent::_initialize();
		$this->_rootPath = str_replace('/Application/Admin/Controller/ExpressionController.class.php', '', str_replace('\\', '/', __FILE__));
	}

	public function index() {
		$ExpressionPkg = $this->_rootPath . '/Uploads/expression';
		$pkgList = D('Core/Expression')->myreaddir($ExpressionPkg);
		$pkg['all'] = '全部';
		$pkg['miniblog'] = 'miniblog';
		foreach ($pkgList as $v) {
			$pkg[$v] = $v;
		}
		unset($v);
		$admin_config = new AdminConfigBuilder();
		$data = $admin_config->handleConfig();
		$admin_config->title('表情基本设置')
			->keySelect('EXPRESSION', '表情包选择', '', $pkg)
			->buttonSubmit('', '保存')->data($data);
		$admin_config->display();
	}

	public function package() {
		$ExpressionPkg = $this->_rootPath . '/Uploads/expression';
		$pkgList = D('Core/Expression')->myreaddir($ExpressionPkg);

		$list = array();
		$list[] = array(
			'name' => 'miniblog',
			'title' => 'miniblog',
			'count' => D('Core/Expression')->getCount($this->_rootPath . '/Public/static/image/expression/miniblog'),
		);
		foreach ($pkgList as $v) {
			$list[] = array(
				'name' => $v,
				'title' => $v,
				'count' => D('Core/Expression')->getCount($this->_rootPath . '/Uploads/expression/' . $v),
			);
		}

		$builder = new AdminListBuilder();
		$builder->title('表情包列表')
			->buttonNew(U('admin/expression/add'))
			->keyLink('title', '标题', 'Admin/Expression/expressionList?title={$name}')
			->keyText('count', '表情数量')->keyDoAction('Admin/Expression/delPackage?title={$name}', '删除')
			->data($list)
			->display();
	}

	public function add() {
		$this->setTitle('上传表情包');
		$this->display('add');
	}

	public function upload() {
		$config = array(
			'maxSize' => 3145728,
			'rootPath' => './Uploads/',
			'savePath' => 'expression/',
			'saveName' => '',
			'exts' => array('zip', 'rar'),
			'autoSub' => true,
			'subName' => '',
			'replace' => true,
		);
		$upload = new \Think\Upload($config); // 实例化上传类
		$info = $upload->upload($_FILES);

		if (!$info) {
			// 上传错误提示错误信息
			$this->error($upload->getError());
		} else {
			// 上传成功
			$this->_unCompress($info['pkg']['savename']);
			$this->success('上传成功！', U('admin/expression/package'));
		}

	}

	public function expressionList() {
		$title = I('get.title', '', 'op_t');
		if (strpos($title, '..') !== false) {
			$this->error('非法参数');
		}
		$list = D('Core/Expression')->getExpression($title);
		foreach ($list as &$v) {
			$v['image'] = '<img src="' . $v['src'] . '"/>';
		}
		unset($v);
		$builder = new AdminListBuilder();
		$builder->title('表情列表')
			->keyText('title', '标题')
			->keyText('image', '表情图片')->keyDoAction('Admin/Expression/delExpression?title={$filename}&pkg=' . $title, '删除')
			->data($list)
			->display();

	}

	protected function _unCompress($filename) {
		$ExpressionPkg = $this->_rootPath . '/Uploads/expression/';
		@chmod($ExpressionPkg, 0666);
		require_once './ThinkPHP/Library/OT/PclZip.class.php';
		$pcl = new \PclZip($ExpressionPkg . $filename);
		if ($pcl->extract($ExpressionPkg)) {
			$result = $this->delFile($ExpressionPkg . $filename);
			if ($result) {
				return true;
			}
		}
		return false;
	}

	public function delPackage() {
		$title = I('get.title', '', 'op_t');
		if (strpos($title, '..') !== false) {
			$this->error('非法参数');
		}
		if ($title == 'miniblog') {
			$path = $this->_rootPath . '/Public/static/image/expression/miniblog';
		} else {
			$path = $this->_rootPath . '/Uploads/expression/' . $title . '/';
		}

		$res = $this->deldir($path);
		if ($res) {
			$this->success('删除成功');
		} else {
			$this->error('删除失败');
		}

	}

	public function delExpression() {
		$title = I('get.title', '', 'op_t');
		$pkg = I('get.pkg', '', 'op_t');
		if (strpos($title, '..') !== false) {
			$this->error('非法参数');
		}
		if (strpos($pkg, '..') !== false) {
			$this->error('非法参数');
		}
		if ($pkg == 'miniblog') {
			$path = $this->_rootPath . '/Public/static/image/expression/miniblog/' . $title;
		} else {
			$path = $this->_rootPath . '/Uploads/expression/' . $pkg . '/' . $title;
		}
		$res = $this->delFile($path);
		if ($res) {
			$this->success('删除成功');
		} else {
			$this->error('删除失败');
		}

	}

	private function deldir($dir) {
		//先删除目录下的文件：
		$dh = opendir($dir);
		while ($file = readdir($dh)) {
			if ($file != '.' && $file != '..') {
				$fullpath = $dir . '/' . $file;
				if (!is_dir($fullpath)) {
					unlink($fullpath);
				} else {
					$this->deldir($fullpath);
				}
			}
		}

		closedir($dh);
		//删除当前文件夹：
		if (rmdir($dir)) {
			return true;
		} else {
			return false;
		}
	}

	private function delFile($path) {
		$result = @unlink($path);
		if ($result) {
			return true;
		} else {
			return false;
		}

	}

}
