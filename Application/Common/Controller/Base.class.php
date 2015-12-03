<?php
namespace Common\Controller;
use Think\Controller;

/**
 * 为Application使用的所有控制器的基类
 * @author shenwenhui  <swh@admpub.com>
 */
class Base extends Controller {
	public $_seo = array();

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
