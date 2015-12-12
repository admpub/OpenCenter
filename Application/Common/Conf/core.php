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
));
return $mode;