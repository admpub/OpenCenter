<?php
/**
 * 所属项目 110.
 * 开发者: 陈一枭
 * 创建日期: 2014-11-18
 * 创建时间: 10:27
 * 版权所有 想天软件工作室(www.ourstu.com)
 */
namespace Common\Model;

class ModuleModel extends Base {

	protected $tableName = 'module';

	public function getAll() {

		$module = S('module_all');
		if (empty($module)) {
			$module = array();
			$dir = $this->getFile(APP_PATH);
			foreach ($dir as $subdir) {
				if (file_exists(APP_PATH . '/' . $subdir . '/Info/info.php')) {
					//$info = $this->getInfo($subdir);
					$info = array();
					$module[ucfirst($subdir)] = $info;
				}
			}
			if ($module) {
				$_module = $this->getModule(array_keys($module));
				$module = array();
				if ($_module) {
					foreach ($_module as $name => $value) {
						$module[$name] = $value;
					}
				}
			}
			S('module_all', $module);
		}

		return $module;
	}

	public function checkCanVisit($name) {
		$modules = $this->getAll();
		$name = ucfirst($name);
		if (!isset($modules[$name]) || (isset($modules[$name]['is_setup']) && $modules[$name]['is_setup'] == 0)) {
			header('Content-Type: text/html; charset=utf-8');
			exit('您所访问的模块未安装，禁止访问。');
		}

		/*/====以下为旧代码=======
			foreach ($modules as $m) {
				if (isset($m['is_setup']) && $m['is_setup'] == 0 && $m['name'] == ucfirst($name)) {
					header('Content-Type: text/html; charset=utf-8');
					exit('您所访问的模块未安装，禁止访问。');
				}
			}
		*/
	}

	private function cleanModulesCache() {
		S('module_all', null);
	}

	public function uninstall($id) {
		$module = $this->find($id);
		if (!$module || $module['is_setup'] == 0) {
			return array('error_code' => '模块未安装。');
		}
		$uninstallSql = APP_PATH . '/' . $module['name'] . '/Info/uninstall.sql';
		$res = $this->executeSqlFile($uninstallSql);

		if ($res === true) {
			$module['is_setup'] = 0;
			$this->save($module);
		}
		$this->cleanModulesCache();
		return $res;
	}

	public function install($id) {
		$module = $this->find($id);
		if ($module && $module['is_setup'] == 1) {
			return array('error_code' => '模块已安装。');
		}
		$uninstallSql = APP_PATH . '/' . $module['name'] . '/Info/install.sql';
		$res = $this->executeSqlFile($uninstallSql);

		if ($res === true) {
			$module['is_setup'] = 1;
			$this->save($module);
		}
		clean_all_cache(); //清除全站缓存
		return $res;
	}

	/**
	 * 检查模块是否已安装
	 * @param $name
	 * @auth 陈一枭
	 */
	public function getModule($name) {
		if (is_array($name)) {
			return $this->getModules($name);
		}

		static $_old = array();
		if (!isset($_old[$name])) {
			$module = $this->where(array('name' => $name))->find();
			if (!$module) {
				$m = $this->getInfo($name);
				if (!$m) {
					#echo('Not Found Module: '.$name);
					return;
				}
				$m['can_uninstall'] = file_exists(APP_PATH . '/' . $name . '/Info/uninstall.sql');
				if ($m['can_uninstall']) {
					$m['is_setup'] = 0; //默认设为已安装，防止已安装的模块反复安装。
				} else {
					$m['is_setup'] = 1;
				}
				$m['id'] = $this->add($m);
				$_old[$name] = $m;
			} else {
				$_old[$name] = $module;
			}
		}
		return $_old[$name];
	}

	public function getModules($names) {
		static $_old = array();
		$_names = $names;
		$names = array_diff($names, $_old);
		if ($names) {
			$arr = $this->where(array('name' => array('in', $names)))->select();
			if (!$arr) {
				$nofounds = &$names;
			} else {
				foreach ($arr as $val) {
					$_old[$val['name']] = $val;
				}
				$nofounds = array_diff($names, array_keys($_old));
			}

			if ($nofounds) {
				foreach ($nofounds as $name) {
					$m = $this->getInfo($name);
					if (!$m) {
						#echo('Not Found Module: '.$name);
						continue;
					}
					$m['can_uninstall'] = file_exists(APP_PATH . '/' . $name . '/Info/uninstall.sql');
					if ($m['can_uninstall']) {
						$m['is_setup'] = 0; //默认设为已安装，防止已安装的模块反复安装。
					} else {
						$m['is_setup'] = 1;
					}
					$m['id'] = $this->add($m);
					$_old[$name] = $m;
				}
			}
		}
		$result = array();
		foreach ($_names as $name) {
			if (!empty($_old[$name])) {
				$result[$name] = $_old[$name];
			}

		}
		return $result;
	}

	private function getInfo($name) {
		if (file_exists(APP_PATH . '/' . $name . '/Info/info.php')) {
			$module = require APP_PATH . '/' . $name . '/Info/info.php';
			return $module;
		} else {
			return array();
		}

	}

	/**
	 * 获取文件列表
	 */
	private function getFile($folder) {
		//打开目录
		$fp = opendir($folder);
		//阅读目录
		while (false != $file = readdir($fp)) {
			//列出所有文件并去掉'.'和'..'
			if ($file != '.' && $file != '..') {
				//$file="$folder/$file";
				$file = "$file";

				//赋值给数组
				$arr_file[] = $file;

			}
		}
		//输出结果
		if (is_array($arr_file)) {
			while (list($key, $value) = each($arr_file)) {
				$files[] = $value;
			}
		}
		//关闭目录
		closedir($fp);
		return $files;

	}

	public function isInstalled($name) {
		$module = $this->getModule($name);
		if ($module['is_setup']) {
			return true;
		} else {
			return false;
		}
	}
}