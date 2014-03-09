<?php
class ctrl_admin extends base_ctrl {
	public function checkout() {
		$_SESSION = array();
		static_function::history_back('退出成功！', './index.php');
	}

	/**
	 * 保存点餐记录
	 */
	public function getfood_action() {
		$cookID = empty($_POST['cook_id']) ? 0 : trim($_POST['cook_id']) * 1;
		if ($cookID == 0)
			static_function::history_back('餐点参数错误！');

		$userID = $_SESSION['user_id'];
		$modelData = array(
			'user_id' => $userID * 1,
			'cook_id' => $cookID
		);

		$return = model_order::save_order_info($modelData);
	}

	/**
	 * 主方法
	 */
	public function main() {
		self::$smarty -> display('admin/main.html');
	}

	public function menu() {
		self::$smarty -> display('admin/menu.html');
	}

	public function rightindex() {
		self::$smarty -> assign('html_title', '我要点餐');
		self::$smarty -> assign('top_1_title', '我要点餐');
		self::$smarty -> display('admin/rightindex.html');
	}

	public function userlist() {
		self::$smarty -> assign('html_title', '用户列表');
		self::$smarty -> assign('top_1_title', '用户列表设置');
		self::$smarty -> display('admin/userlist.html');
	}

}
