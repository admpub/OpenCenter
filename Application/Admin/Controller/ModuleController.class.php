<?php
/**
 * 所属项目 110.
 * 开发者: 陈一枭
 * 创建日期: 2014-11-18
 * 创建时间: 10:09
 * 版权所有 想天软件工作室(www.ourstu.com)
 */

namespace Admin\Controller;
use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;

class ModuleController extends AdminController {
	protected $moduleModel;

	protected function _initialize() {
		$this->moduleModel = D('Module');
		parent::_initialize();
	}

	public function lists() {

		$listBuilder = new AdminListBuilder();

		/*刷新模块列表时清空缓存*/
		$aRefresh = I('get.refresh', 0, 'intval');
		if ($aRefresh) {
			S('module_all', null);
		}
		/*刷新模块列表时清空缓存 end*/

		$modules = $this->moduleModel->getAll();

		foreach ($modules as &$m) {
			$name = $m['name'];
			$m['alias'] = '<i class="icon-' . $m['icon'] . '"></i> ' . $m['alias'];
			empty($m['admin_entry']) || $m['admin_entry'] = '<a href="' . U($m['admin_entry']) . '" target="_blank">' . $m['admin_entry'] . '</a>';
			empty($m['entry']) || $m['entry'] = '<a href="' . U($m['entry']) . '" target="_blank">' . $m['entry'] . '</a>';
			$m['do'] = '<a class="btn" href="' . U('Module/edit', array('id' => $m['id'], 'name' => $name)) . '"><span style="color:green"><i class="icon-pencil"></i></span> 编辑</a>&nbsp;';

			if ($m['is_setup']) {
				$m['name'] = '<i class="icon-ok" style="color:green"></i> ' . $m['name'];
				if ($m['can_uninstall']) {
					$m['do'] .= '<a class="btn btn-error" onclick="moduleManager.uninstall(\'' . $m['id'] . '\')"><span style="color:red"><i class="icon-cut"></i></span> 卸载</a>';
				}

			} else {
				$m['name'] = '<i class="icon-remove" style="color:red"></i> ' . $m['name'];
				$m['do'] .= '<a class="btn" onclick="moduleManager.install(\'' . $m['id'] . '\')"><span style="color:green"><i class="icon-check"></i></span> 安装</a>';
			}
			if ($m['is_com']) {
				$m['is_com'] = '<strong style="color:orange">商业模块</strong>';
			} else {
				$m['is_com'] = '<strong style="color:green">免费模块</strong>';
			}
		}
		unset($m);

		$listBuilder->data($modules);
		$listBuilder->title('模块管理');

		$listBuilder->button('刷新', array('href' => U('Admin/Module/lists', array('refresh' => 1))));
		$listBuilder->button('快速创建', array('href' => U('Admin/Module/create')));

		$listBuilder->keyId()->keyHtml('alias', '模块名')->keyText('name', '模块英文名')->keyText('summary', '模块介绍')
			->keyText('version', '版本号')->keyText('is_com', '商业模块')
			->keyLink('developer', '开发者', '{$website}')->keyText('entry', '前台入口')->keyText('admin_entry', '后台入口')
			->keyText('do', '操作');
		$listBuilder->display();
	}

	/**
	 * 编辑模块
	 */
	public function edit() {
		if (IS_POST) {
			$aName = I('name', '', 'text');
			$module['id'] = I('id', 0, 'intval');
			$module['name'] = empty($aName) ? $this->error(L('模块英文名不能为空')) : $aName;
			$aAlias = I('alias', '', 'text');
			$module['alias'] = empty($aAlias) ? $this->error(L('模块中文名不能为空')) : $aAlias;
			$aIcon = I('icon', '', 'text');
			$module['icon'] = empty($aIcon) ? $this->error(L('图标不能为空')) : $aIcon;
			$aSummary = I('summary', '', 'text');
			$module['summary'] = empty($aSummary) ? $this->error(L('简述不能为空')) : $aSummary;
			$module['title'] = I('name', '', '');

			if ($this->moduleModel->save($module) === false) {
				$this->error(L('模块编辑失败'));
			} else {
				#$this->moduleModel->cleanModuleCache($aName);
				$this->moduleModel->cleanModulesCache();
				$this->success(L('模块修改成功'));
			}
		} else {
			$aName = I('name', '', 'text');
			$module = $this->moduleModel->getModule($aName);
			$builder = new AdminConfigBuilder();
			$builder->title(L('编辑模块:') . $module['alias']);
			$builder->keyId()->keyReadOnly('name', L('模块名称'))->keyText('alias', L('模块中文名'))->keyReadOnly('version', L('版本号'))
				->keyText('icon', L('图标'))
				->keyTextArea('summary', L('模块简述'))
				->keyReadOnly('developer', L('开发者'))
				->keyText('entry', L('前台入口'))
				->keyText('admin_entry', L('后台入口'));

			$builder->data($module);
			$builder->buttonSubmit()->buttonBack()->display();
		}

	}

	public function uninstall() {
		$aId = I('post.id', 0, 'intval');
		$res = $this->moduleModel->uninstall($aId);
		if ($res === true) {
			$this->success('卸载模块成功。', 'refresh');
		} else {
			$this->error('卸载模块失败。' . $res['error_code']);
		}

	}

	public function install() {
		$aId = I('post.id', 0, 'intval');
		$res = $this->moduleModel->install($aId);
		if ($res === true) {
			$this->success('安装模块成功。', 'refresh');
		} else {
			$this->error('安装模块失败。' . $res['error_code']);
		}

	}

	/**
	 * 创建新模块
	 * @return void
	 * @author swh <swh@admpub.com>
	 */
	public function create() {
		$module = array(
			'name' => 'Example',
			'alias' => '示例模块',
			'version' => '1.0.0',
			'icon' => 'code',
			'summary' => '示例模块',
			'developer' => 'admpub.com',
			'website' => 'http://www.admpub.com',
			'entry' => 'example/index/index',
			'admin_entry' => 'admin/example/index',
			'show_nav' => true,
			'is_com' => false,
		);
		if (IS_POST) {
			foreach ($module as $key => &$value) {
				$value = I('post.' . $key, '', 'text');
			}
			if (!preg_match('/^[a-z][\\w]*$/i', $module['name'])) {
				$this->error('模块英文名必须以字母开头，并且不能包含除了字母、数字、下划线以外的字符');
			}
			$module['show_nav'] = $module['show_nav'] ? 1 : 0;
			$module['is_com'] = $module['is_com'] ? 1 : 0;
			$name = ucfirst($module['name']);
			$modulePath = APP_PATH . $name . '/';
			if (is_dir($modulePath)) {
				$this->error('已经存在英文名为“' . $module['name'] . '”的模块。');
			}
			$dirs = array(
				'Conf',
				'Controller',
				'Info',
				'Model',
				'Static/js',
				'Static/css',
				'View/default/Index',
			);
			foreach ($dirs as $value) {
				if (!is_dir($modulePath . $value)) {
					mkdir($modulePath . $value, TRUE, 0777);
				}
			}

			file_put_contents($modulePath . 'Conf/config.php', '<?php ' . PHP_EOL . "return array(
    // 预先加载的标签库
    'TAGLIB_PRE_LOAD' => 'OT\\TagLib\\Article,OT\\TagLib\\Think',

    /* 主题设置 */
    'DEFAULT_THEME' => 'default', // 默认模板主题名称

    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__ADDONS__' => __ROOT__ . '/Public/' . MODULE_NAME . '/Addons',
        '__IMG__' => __ROOT__ . '/Application/'.MODULE_NAME   . '/Static/images',
        '__CSS__' => __ROOT__ . '/Application/'.MODULE_NAME .'/Static/css',
        '__JS__' => __ROOT__ . '/Application/'.MODULE_NAME. '/Static/js',
        '__ZUI__' => __ROOT__ . '/Public/zui'
    ),

    'NEED_VERIFY'=>true,//此处控制默认是否需要审核，该配置项为了便于部署起见，暂时通过在此修改来设定。
);");
			file_put_contents($modulePath . 'Controller/IndexController.class.php', '<?php
/**
 * 前台首页控制器
 * @author ' . $module['developer'] . '
 * generated by ' . date('Y-m-d H:i:s') . '
 */
namespace ' . $name . '\Controller;
use Common\Controller\Base;

class IndexController extends Base {
	protected function _initialize() {
		parent::_initialize();
	}

	/**
	 * [index description]
	 * @return void
	 * @author ' . $module['developer'] . '
	 */
	public function index(){

		//your code at here.

		$this->display();
	}
}
');
			file_put_contents($modulePath . 'Controller/' . $name . 'Controller.class.php', '<?php
/**
 * 后台管理控制器
 * @author ' . $module['developer'] . '
 * generated by ' . date('Y-m-d H:i:s') . '
 */
namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;

class ' . $name . 'Controller extends AdminController {
	protected function _initialize() {
		parent::_initialize();
	}

	/**
	 * 后台' . $module['alias'] . '首页
	 * @param integer $page 页码
	 * @param integer $rows 每页行数
	 * @return void
	 * @author ' . $module['developer'] . '
	 */
	public function index($page = 1, $rows = 10){
		$list = $map = array();
		$totalCount = 0;

		//因为此类继承于AdminController，所以用D函数时要使用“模块英文名/模型英文名”的格式调用该模块的模型实例，
		//否则它将会试图调用Admin模块下的模型实例，这点要特别注意。
		//$model = D(\'' . $name . '/' . $name . '\');
		//$list = $model->where($map)->page($page, $rows)->select();
		//$totalCount = $model->where($map)->count();

		//显示页面
		$builder = new AdminListBuilder();
		$attr[\'class\'] = \'btn ajax-post\';
		$attr[\'target-form\'] = \'ids\';

		$builder->title(\'' . $module['alias'] . '管理\')
			->setStatusUrl(U(\'setStatus\'))->buttonDisable(\'\', \'审核不通过\')->buttonDelete()
			->keyId()->keyLink(\'title\', \'标题\', \'' . $name . '/Index/detail?id=###\')
			->keyUid()->keyCreateTime()->keyStatus()
			->data($list)
			->pagination($totalCount, $rows)
			->display();
	}

	/**
	 * ' . $module['alias'] . '设置
	 * @return void
	 * @author ' . $module['developer'] . '
	 */
	public function config() {
		$builder = new AdminConfigBuilder();
		$data = $builder->handleConfig();

		$builder->title(\'' . $module['alias'] . '基本设置\')
			->keyBool(\'NEED_VERIFY\', \'投稿是否需要审核\', \'默认无需审核\')
			->buttonSubmit(\'\', \'保存\')->data($data);
		$builder->display();
	}

	/**
	 * 添加' . $module['alias'] . '
	 * @return void
	 * @author ' . $module['developer'] . '
	 */
	public function add($id = 0) {
		$id = intval($id);
		if (IS_POST) {
			if (false) {
				$this->error(\'添加失败\');
			}
			$this->success(\'添加成功\');
		} else {
			$builder = new AdminConfigBuilder();
			$opt = array();
			$data = array();
			$builder->title(\'新增' . $module['alias'] . '\')->keyId()->keyText(\'title\', \'标题\')
				->keySelect(\'pid\', \'父分类\', \'选择父级分类\', array(\'0\' => \'顶级分类\') + $opt)
				->keyStatus()->keyCreateTime()->keyUpdateTime()
				->data($data)
				->buttonSubmit(U(\'' . $name . '/add\'))->buttonBack()->display();
		}
	}


	/**
	 * 删除' . $module['alias'] . '
	 * @return void
	 * @author ' . $module['developer'] . '
	 */
	public function del($id = 0) {
		$id = intval($id);
		if (false) {
			$this->error(\'删除失败\');
		}
		$this->success(\'删除成功\');
	}
}
');

			file_put_contents($modulePath . 'Model/' . $name . 'Model.class.php', '<?php
/**
 * ' . $module['alias'] . '模型
 * @author ' . $module['developer'] . '
 * generated by ' . date('Y-m-d H:i:s') . '
 */
namespace ' . $name . '\Model;
use Common\Model\Base;

class ' . $name . 'Model extends Base {

	//设置自动验证
    protected $_validate = array(
        //array(\'url\',\'require\',\'url必须填写\'),
    );

	//设置指定填值
    protected $_auto = array(
        //array(\'create_time\', NOW_TIME, self::MODEL_INSERT),
    );

	protected function _initialize() {
		parent::_initialize();
	}
}
');
			$module['can_uninstall'] = 1;
			$dumped = var_export($module, TRUE);
			file_put_contents($modulePath . 'Info/info.php', '<?php ' . PHP_EOL . 'return ' . $dumped . ';');
			file_put_contents($modulePath . 'Info/install.sql', '-- 安装模块sql' . PHP_EOL . PHP_EOL . PHP_EOL);
			file_put_contents($modulePath . 'Info/uninstall.sql', '-- 卸载模块sql' . PHP_EOL . PHP_EOL . PHP_EOL);
			file_put_contents($modulePath . 'View/default/Index/index.html', '<extend name="Base/common"/>
<block name="style">
    <link href="__CSS__/' . $module['name'] . '.css" rel="stylesheet" type="text/css"/>
</block>
<block name="body">
是的，你没有看错，本模块就是你创建的！:)
</block>
');
			file_put_contents($modulePath . 'Static/css/' . $module['name'] . '.css', '');
			$this->moduleModel->cleanModulesCache();
			$this->success('创建成功', U('lists'));
			return;
		}
		$builder = new AdminConfigBuilder();

		$builder->title('创建模块');
		$builder->keyText('name', L('模块英文名'), L('必须以字母开头，且整个名称只能由字母、数字、下划线构成'))
			->keyText('alias', L('模块中文名'))
			->keyText('version', L('版本号'))
			->keyText('icon', L('图标'))
			->keyTextArea('summary', L('模块简述'))
			->keyText('developer', L('开发者'))
			->keyText('website', L('官方网站'))
			->keyText('entry', L('前台入口'), L('支持U函数的网址'))
			->keyText('admin_entry', L('后台入口'), L('支持U函数的网址'))
			->keyBool('show_nav', L('显示在导航菜单中'))
			->keyBool('is_com', L('是否商业模块'));
		$module = array(
			'name' => 'Example',
			'alias' => '示例模块',
			'version' => '1.0.0',
			'icon' => 'code',
			'summary' => '示例模块',
			'developer' => 'admpub.com',
			'website' => 'http://www.admpub.com',
			'entry' => 'example/index/index',
			'admin_entry' => 'admin/example/index',
			'show_nav' => true,
			'is_com' => false,
		);
		$builder->data($module);
		$builder->buttonSubmit()->buttonBack()->display();
	}

}