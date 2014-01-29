<?php
class ctrl_user extends base_ctrl {
	/**
	 * 添加用户页面
	 */
	public function add_html() {
		$userGroupList = model_usergroup::get_list();
		self::$smarty -> assign('html_title', '添加用户');
		self::$smarty -> assign('top_1_title', '用户设置');
		self::$smarty -> display('admin/user/adduser.html');
	}

	public function add_user_action() {
		$user_name = trim($_POST['user_name']);
		$user_password = trim($_POST['user_password']);
		$userFullName = trim($_POST['user_full_name']);
		$user_group_id = $_POST['user_group'] * 1;

		$userID = empty($_POST['user_id']) ? 0 : trim($_POST['user_id']) * 1;

		// 编辑用户
		if ($userID != 0) {
			$this -> edit_user_action();
			return;
		}

		$modelData = array(
			'user_name' => $user_name,
			'user_password' => sha1($user_password),
			'user_group_id' => $user_group_id,
			'user_full_name' => $userFullName
		);

		$return = model_user::add_user($modelData);
		// var_dump($return);
		if ($return == TRUE)
			static_function::history_back('添加成功', 'index.php?at=admin&st=userlist');
		else {
			static_function::history_back('添加失败');
		}
		exit ;
	}

	/**
	 * 检查用户
	 */
	public static function check_user() {
		print_r($_SESSION);
		if (empty($_SESSION['user_name'])) {
			self::login_html();
		} else {
			header("Location: ./index.php?at=admin");
			exit ;
		}
	}

	/**
	 * 编辑用户html 页面
	 */
	public function edit() {
		$userID = empty($_GET['id']) ? '' : trim($_GET['id']) * 1;

		// $userGroupList = model_usergroup::get_list();
		self::$smarty -> assign('html_title', '编辑用户');
		self::$smarty -> assign('top_1_title', '用户设置');
		self::$smarty -> assign('user_id', $userID);
		self::$smarty -> display('admin/user/adduser.html');

	}

	private function edit_user_action() {
		$modelData = array(
			'user_id' => $_POST['user_id'] * 1,
			'user_group_id' => $_POST['user_group'] * 1,
			'user_full_name' => trim($_POST['user_full_name'])
		);
		if (trim($_POST['user_password']) != '')
			$modelData['user_password'] = trim($_POST['user_password']);

		$return = model_user::edit_user($modelData);
		// var_dump($return);
		if ($return == TRUE)
			static_function::history_back('修改成功', 'index.php?at=admin&st=userlist');
		else {
			static_function::history_back('修改失败');
		}
		exit ;
	}

	public function get_list() {
		// var_dump($_POST);
		$modelData = array(
			'page' => $_POST['page'] * 1,
			'rows' => $_POST['rows'] * 1
		);
		$dataCount = model_user::get_list_count();
		$data = model_user::get_list($modelData);

		$return = array(
			'total' => $dataCount,
			'rows' => $data
		);
		static_function::output_json($return);
	}

	public function info() {
		$userID = empty($_GET['id']) ? 0 : trim($_GET['id']) * 1;

		$modelData = array('user_id' => $userID);
		$data = model_user::get_info($modelData);

		static_function::output_json($data);
	}

	/**
	 * 登录页面
	 */
	public static function login_html() {
		self::$smarty -> display('admin/login.html');
	}

	/**
	 * 登录验证
	 */
	public function login() {
		$user_name = trim($_POST['user_name']);
		$user_password = trim($_POST['user_password']);

		if ($user_name == SUPER_ADMIN_USER_NAME && $user_password == SUPER_ADMIN_PASSWORD) {
			$_SESSION['user_name'] = SUPER_ADMIN_USER_NAME;
			$_SESSION['user_id'] = -1;
			$_SESSION['login_time'] = date('Y-m-d H:i:s');

			header("Location: ./index.php?at=admin");
			exit ;
		}

		static_function::history_back();
		exit ;
	}

}
