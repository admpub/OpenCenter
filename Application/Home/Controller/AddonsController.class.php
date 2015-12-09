<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Common\Controller\Base;

/**
 * 扩展控制器
 * 用于调度各个扩展的URL访问需求
 */
class AddonsController extends Base {

	protected function _initialize() {
		parent::_initialize();
	}

	protected $addons = null;

	public function execute($_addons = null, $_controller = null, $_action = null) {
		if (empty($_addons) || empty($_controller) || empty($_action)) {
			return $this->error('没有指定插件名称，控制器或操作！');
		}

		if (C('URL_CASE_INSENSITIVE')) {
			$_addons = ucfirst(parse_name($_addons, 1));
			$_controller = parse_name($_controller, 1);
		}

		$rightNameFormat = '/^[\\w]+$/';
		$wrongFormatMsg = '的值格式不正确，不能包含除了字母、数字、下划线以外的字符。';
		if (!preg_match($rightNameFormat, $_addons)) {
			return $this->error('_addons' . $wrongFormatMsg);
		}
		if (!preg_match($rightNameFormat, $_controller)) {
			return $this->error('_controller' . $wrongFormatMsg);
		}
		if (!preg_match($rightNameFormat, $_action)) {
			return $this->error('_action' . $wrongFormatMsg);
		}

		$TMPL_PARSE_STRING = C('TMPL_PARSE_STRING');
		$TMPL_PARSE_STRING['__ADDONROOT__'] = __ROOT__ . '/Addons/' . $_addons;
		C('TMPL_PARSE_STRING', $TMPL_PARSE_STRING);
		$addon = A('Addons://' . $_addons . '/' . $_controller);
		if (!is_object($addon)) {
			return $this->error('插件控制器不存在');
		}
		if (!is_callable(array($addon, $_action))) {
			return $this->error('插件控制器中无此方法或该方法不支持外部调用(非public)');
		}
		$Addons = $addon->$_action();
		return $Addons;
	}

}
