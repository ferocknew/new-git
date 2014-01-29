<?php
// 取得当前所在的根目录
define('ROOT_PATH', str_replace('app/init.php', '', str_replace('\\', '/', __FILE__)));
define('APP_PATH', ROOT_PATH . 'app/');

// 服务器环境配置
ini_set('memory_limit', '512M');
require APP_PATH . 'config.php';

/**
 * @author  jonah.fu
 * @date    2012-03-28
 */
include APP_PATH . 'base/autoload.class.php';
base_autoloader::init();
// 挂载常量文件
include APP_PATH . 'inc_constant.php';
$url = defined('ADMIN_DIR') ? str_replace(ADMIN_DIR . "/", "", static_function::curURL()) : static_function::curURL();
define('ROOT_URL', $url);

// 处理'引号
if (!get_magic_quotes_gpc()) {
	$_GET = static_function::strip_array($_GET);
	$_POST = static_function::strip_array($_POST);
	$_COOKIE = static_function::strip_array($_COOKIE);
}

$templates = APP_PATH . SMARTY_TEMPLATE_DIR;
$compiled = APP_PATH . SMARTY_COMPILED_DIR;
//用于 SEO 优化
if (!is_dir($compiled))
	static_function::mkdirs($compiled);

include APP_PATH . 'inc_smarty.php';
base_staticobj::smarty() -> template_dir = $templates;
base_staticobj::smarty() -> compile_dir = $compiled;
base_staticobj::smarty() -> assign('JS_VERSION', CDN_JS_VERSION);
base_staticobj::smarty() -> assign('CSS_VERSION', CDN_CSS_VERSION);
base_staticobj::smarty() -> assign('CDN_URL', CDN_URL);
// base_staticobj::smarty() -> assign('root_url', ROOT_URL);

if (SESSION_OPEN)
	base_session::start();

if (DEBUG_MODE) {
	error_reporting(E_ALL ^ E_NOTICE);
} else {
	error_reporting(0);
}
?>