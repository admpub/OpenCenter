<?php
namespace Common\Controller;
use Think\Controller;

/**
 * 为Application使用的所有控制器的基类
 * @author shenwenhui  <swh@admpub.com>
 */
class Base extends Controller {
	public $_seo = array();
	static protected $_once = false;

	protected function _initialize() {
		$this->_onceInit();
	}

	protected function _onceInit() {
		if (self::$_once) {
			return;
		}

		self::$_once = true;

		/* 读取数据库中的配置 */
		$config = S('DB_CONFIG_DATA');
		if (!$config) {
			$config = api('Config/lists');
			S('DB_CONFIG_DATA', $config);
		}
		C($config); //添加配置
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

}
