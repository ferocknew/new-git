<?php
class ctrl_user extends base_ctrl {
	/**
	 * 检查用户
	 */
	public static function check_user() {
		if (empty($_SESSION['user_name'])) {
			self::login_html();
		}
	}

	/**
	 * 登录页面
	 */
	public static function login_html() {
		self::$smarty -> display('admin/login.html');
	}

}
