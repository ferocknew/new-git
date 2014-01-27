<?php
/**
 * ctrl 基类
 */
class base_ctrl {
	public static $smarty;
	public function __construct() {
		self::$smarty = base_staticobj::smarty();
	}

	public static function index_main() {
		$at = empty($_GET['at']) ? 'index' : trim($_GET['at']);
		$st = empty($_GET['st']) ? 'main' : trim($_GET['st']);

		$tmpObjName = 'ctrl_' . $at;
		$tmpObj = new $tmpObjName;
		$tmpObj -> $st();
		$tmpObj = NULL;
	}

}
