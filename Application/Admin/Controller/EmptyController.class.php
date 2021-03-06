<?php
namespace Admin\Controller;

use Common\Controller\Base;

class EmptyController extends Base {

	public function _empty($name, $args) {
		$errMsg = '404，您访问的页面不存在。';
		try {
			if ($name[0] == '_') {
				$this->error($errMsg);
			}
			if (!self::isFreeInstall(CONTROLLER_NAME)) {
				$this->moduleMdl()->checkCanVisit(CONTROLLER_NAME, $this);
			}
			$file = APP_PATH . CONTROLLER_NAME . '/Controller/' . CONTROLLER_NAME . 'Controller.class.php';
			if (file_exists($file) == false) {
				throw new \Exception('Not Found:' . $file, 500);
			}
			require_once $file;
			$controller = A('Admin/' . CONTROLLER_NAME);
			$controller->viewInstance($this->view);

			$method = new \ReflectionMethod($controller, $name);
			// URL参数绑定检测

			if ($method->getNumberOfParameters() > 0 && C('URL_PARAMS_BIND')) {
				switch ($_SERVER['REQUEST_METHOD']) {
				case 'POST':
					$vars = array_merge($_GET, $_POST);
					break;
				case 'PUT':
					parse_str(file_get_contents('php://input'), $vars);
					break;
				default:
					$vars = $_GET;
				}
				$params = $method->getParameters();

				$paramsBindType = C('URL_PARAMS_BIND_TYPE');
				foreach ($params as $param) {
					$name = $param->getName();
					if (1 == $paramsBindType && !empty($vars)) {
						$args[] = array_shift($vars);
					} elseif (0 == $paramsBindType && isset($vars[$name])) {
						$args[] = $vars[$name];
					} elseif ($param->isDefaultValueAvailable()) {
						$args[] = $param->getDefaultValue();
					} else {
						E(L('_PARAM_ERROR_') . ':' . $name);
					}
				}
				// 开启绑定参数过滤机制
				if (C('URL_PARAMS_SAFE')) {
					array_walk_recursive($args, 'filter_exp');
					$filters = C('URL_PARAMS_FILTER') ?: C('DEFAULT_FILTER');
					if ($filters) {
						$filters = explode(',', $filters);
						foreach ($filters as $filter) {
							$args = array_map_recursive($filter, $args); // 参数过滤
						}
					}
				}
				$method->invokeArgs($controller, $args);
			} else {
				$method->invoke($controller);
			}
		} catch (\ReflectionException $e) {
			#echo $e->getMessage();exit;
			$this->error($errMsg);
		}
	}
}