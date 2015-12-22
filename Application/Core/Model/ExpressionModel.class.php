<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-10
 * Time: PM9:01
 */
namespace Core\Model;
#use Common\Model\Base;

class ExpressionModel {
	public $pkg = '';
	protected $_rootPath;

	public function __construct() {
		$this->_initialize();
	}

	protected function _initialize() {
		#parent::_initialize();
		$this->pkg = modC('EXPRESSION', 'miniblog', 'EXPRESSION');
		$this->_rootPath = str_replace('/Application/Core/Model/ExpressionModel.class.php', '', str_replace('\\', '/', __FILE__));
	}

	/**
	 * 获取当前主题包下所有的表情
	 * @param boolean $flush 是否更新缓存，默认为false
	 * @return array 返回表情数据
	 */
	public function getAllExpression() {
		if ($this->pkg == 'all') {
			return $this->getAll();
		} else {
			return $this->getExpression($this->pkg);
		}

	}

	public function getExpression($pkg) {
		if ($pkg == 'miniblog') {
			$filepath = '/Application/Core/Static/image/expression/' . $pkg;
		} else {
			$filepath = '/Uploads/expression/' . $pkg;
		}
		$list = $this->myreaddir($this->_rootPath . $filepath);
		$res = array();
		foreach ($list as $value) {
			$file = explode('.', $value);
			$temp['title'] = $file[0];
			$temp['emotion'] = $pkg == 'miniblog' ? '[' . $file[0] . ']' : '[' . $file[0] . ':' . $pkg . ']';
			$temp['filename'] = $value;
			$temp['type'] = $pkg;
			$temp['src'] = __ROOT__ . $filepath . '/' . $value;
			$res[$temp['emotion']] = $temp;
		}

		return $res;
	}

	/**
	 * getAll 获取所有主题的所有表情
	 * @return array
	 * @author:xjw129xjt xjt@ourstu.com
	 */
	public function getAll() {
		$res = $this->getExpression('miniblog');
		$ExpressionPkg = $this->_rootPath . '/Uploads/expression';
		$pkgList = $this->myreaddir($ExpressionPkg);
		foreach ($pkgList as $v) {
			$res = array_merge($res, $this->getExpression($v));
		}
		return $res;
	}

	public function myreaddir($dir) {
		$file = scandir($dir, 0);
		$i = 0;
		foreach ($file as $v) {
			if (($v != '.') and ($v != '..')) {
				$list[$i] = $v;
				$i = $i + 1;
			}
		}
		return $list;
	}

	/**
	 * 将表情格式化成HTML形式
	 * @param string $data 内容数据
	 * @return string 转换为表情链接的内容
	 */
	public function parse($data) {
		$data = preg_replace('/img{data=([^}]*)}/', '<img src="$1" data="$1">', $data);
		return $data;
	}

	public function getCount($dir) {
		$list = $this->myreaddir($dir);
		return count($list);
	}
}
