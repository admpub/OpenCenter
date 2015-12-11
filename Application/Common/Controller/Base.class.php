<?php
namespace Common\Controller;
use Think\Controller;

/**
 * 为Application使用的所有控制器的基类
 * @author shenwenhui  <swh@admpub.com>
 */
class Base extends Controller {
	public $_seo = array();
	static private $_freeInstall = array(); //是否是免安装模块
	static protected $_moduleMdl = null;
	static protected $_once = false;

	protected function _initialize() {
		if (self::$_once) {
			return;
		}
		$this->_onceInit();
		self::$_once = true;
	}

	protected function _onceInit() {
		/* 读取数据库中的配置 */
		$config = S('DB_CONFIG_DATA');
		if (!$config) {
			$config = api('Config/lists');
			S('DB_CONFIG_DATA', $config);
		}
		C($config); //添加配置

		self::$_moduleMdl = D('Module');
		if (!self::isFreeInstall(MODULE_NAME)) {
			self::$_moduleMdl->checkCanVisit(MODULE_NAME, $this);
		}
	}

	/**
	 * 注册免安装模块
	 * @param  string $moduleName 模块名称
	 * @return void
	 */
	static public function registerFreeInstall($moduleName) {
		self::$_freeInstall[$moduleName] = true;
	}

	/**
	 * 取消注册免安装模块
	 * @param  string $moduleName 模块名称
	 * @return void
	 */
	static public function deleteFreeInstall($moduleName) {
		unset(self::$_freeInstall[$moduleName]);
	}

	/**
	 * 是否是免安装模块
	 * @param  string  $moduleName 模块名称
	 * @return boolean
	 */
	static public function isFreeInstall($moduleName) {
		return isset(self::$_freeInstall[$moduleName]);
	}

	public function moduleMdl() {
		self::$_moduleMdl || self::$_moduleMdl = D('Module');
		return self::$_moduleMdl;
	}

	public function setTitle($title) {
		$this->_seo['title'] = $title;
		$this->assign('seo', $this->_seo);
	}

	public function setKeywords($keywords) {
		$this->_seo['keywords'] = $keywords;
		$this->assign('seo', $this->_seo);
	}

	public function setDescription($description) {
		$this->_seo['description'] = $description;
		$this->assign('seo', $this->_seo);
	}

	/**
	 * 操作错误跳转的快捷方法
	 * @access protected
	 * @param string $message 错误信息
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @return void
	 */
	public function errMsg($message = '', $jumpUrl = '', $ajax = false) {
		$this->error($message, $jumpUrl, $ajax);
	}

	/**
	 * 操作成功跳转的快捷方法
	 * @access protected
	 * @param string $message 提示信息
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @return void
	 */
	public function sucMsg($message = '', $jumpUrl = '', $ajax = false) {
		$this->success($message, $jumpUrl, $ajax);
	}

	/**
	 * Ajax方式返回数据到客户端
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type AJAX返回数据格式
	 * @param int $json_option 传递给json_encode的option参数
	 * @return void
	 */
	public function ajaxr($data, $type = '', $json_option = 0) {
		$this->ajaxReturn($data, $type, $json_option);
	}

}
