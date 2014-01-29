<?php
class ctrl_admin extends base_ctrl {
	/**
	 * 主方法
	 */
	public function main() {
		self::$smarty -> display('admin/main.html');
	}

	public function rightindex() {
		self::$smarty -> assign('html_title', '我要点餐');
		self::$smarty -> assign('top_1_title', '我要点餐');
		self::$smarty -> display('admin/rightindex.html');
	}

	public function menu() {
		self::$smarty -> display('admin/menu.html');
	}

	/**
	 * 保存点餐记录
	 */
	public function getfood_action() {
		print_r($_POST);
		print_r($_SESSION);
	}

	public function userlist() {
		self::$smarty -> assign('html_title', '用户列表');
		self::$smarty -> assign('top_1_title', '用户列表设置');
		self::$smarty -> display('admin/userlist.html');
	}

}
