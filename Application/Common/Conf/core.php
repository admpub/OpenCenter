<?php
$mode = include MODE_PATH . APP_MODE . '.php';
$mode['core'] = array_merge($mode['core'], array(
	COMMON_PATH . 'Common/pagination.php',
	COMMON_PATH . 'Common/query_user.php',
	COMMON_PATH . 'Common/thumb.php',
	COMMON_PATH . 'Common/api.php',
	COMMON_PATH . 'Common/time.php',
	COMMON_PATH . 'Common/match.php',
	COMMON_PATH . 'Common/seo.php',
	COMMON_PATH . 'Common/type.php',
	COMMON_PATH . 'Common/cache.php',
	COMMON_PATH . 'Common/vendors.php',
	COMMON_PATH . 'Common/parse.php',
	COMMON_PATH . 'Common/user.php',
	COMMON_PATH . 'Common/limit.php',
	COMMON_PATH . 'Common/role.php',
	COMMON_PATH . 'Common/extend.php',

	COMMON_PATH . 'Controller/Base' . EXT,
	COMMON_PATH . 'Model/Base' . EXT,
	COMMON_PATH . 'Behavior/InitHookBehavior' . EXT,

	CORE_PATH . 'Cache' . EXT,
	CORE_PATH . 'Db' . EXT,
	CORE_PATH . 'Db/Driver' . EXT,
	CORE_PATH . 'Log' . EXT,
	BEHAVIOR_PATH . 'ReadHtmlCacheBehavior' . EXT,
	BEHAVIOR_PATH . 'WriteHtmlCacheBehavior' . EXT,
	BEHAVIOR_PATH . 'ShowPageTraceBehavior' . EXT,
	CORE_PATH . 'Template' . EXT,
	LIB_PATH . 'OT/TagLib/Article' . EXT,
	LIB_PATH . 'OT/TagLib/Think' . EXT,
	LIB_PATH . 'Think/Template/TagLib/Cx' . EXT,

	##########以下文件不可添加至此，会冲突#########
	#CORE_PATH . 'Storage' . EXT,
	#CORE_PATH . 'Model' . EXT,
	#CORE_PATH . 'Behavior' . EXT,
	#LIB_PATH . 'Think/Template/TagLib' . EXT,
));
return $mode;