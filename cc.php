<?php
define('TOKEN','');//设置你的安全口令，不设置的话为不启用token验证

$token=empty($_REQUEST['token'])?'':$_REQUEST['token'];

if($token!=TOKEN) exit('Bad token');

include __DIR__.'/Application/Common/Common/cache.php';
if (function_exists('memcache_init')) {
	$mem = memcache_init();
	$mem->flush();
}
header('Content-Type:text/html;charset=utf-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>清理缓存</title><style>div{border:2px solid green; background:#f1f1f1;padding:20px;margin:20px;width:800px;font-weight:bold;color:green;text-align:center;margin:50px auto}</style></head><body>';
//清理缓存
clean_all_cache();
echo '<div>Runtime 缓存清理完毕。 </div> <br /><br />';
echo '</body></html>';
?>