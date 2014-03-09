<?php
class ctrl_food extends base_ctrl {

	public function list_html() {
		self::$smarty -> assign('html_title', '菜单列表');
		self::$smarty -> assign('top_1_title', '菜单列表');
		self::$smarty -> display('admin/foodlist.html');
	}

	public function add_html() {
		self::$smarty -> assign('html_title', '添加菜单');
		self::$smarty -> assign('top_1_title', '菜单设置');
		self::$smarty -> display('admin/cookbook/add.html');
	}

	public function add_food_action() {
		// print_r($_POST);
		$cookName = trim($_POST['cook_name']);
		$cookPrice = trim($_POST['cook_price']) * 1;
		$restaurantID = $_POST['from_restaurant'] * 1;
		$restaurantName = $_POST['from_restaurant_name'];

		$cookID = empty($_POST['cook_id']) ? 0 : trim($_POST['cook_id']) * 1;
		// 编辑用户
		if ($cookID != 0) {
			$this -> edit_food_action();
			return;
		}

		$modelData = array(
			'cook_name' => $cookName,
			'cook_price' => $cookPrice,
			'restaurant_id' => $restaurantID,
			'restaurant_name' => $restaurantName
		);

		$return = model_food::add_food($modelData);

		if ($return == TRUE)
			static_function::history_back('添加成功', 'index.php?at=food&st=list_html');

		exit ;
	}

	public function edit_food_action() {
	}

	public function get_list() {
		$modelData = array(
			'page' => $_POST['page'] * 1,
			'rows' => $_POST['rows'] * 1
		);

		$dataCount = model_food::get_list_count();
		$data = model_food::get_list($modelData);

		$return = array(
			'total' => $dataCount,
			'rows' => $data
		);
		static_function::output_json($return);
	}

	public function get_top_list() {
		$data = model_food::get_top_list();

		$arr = array();
		foreach ($data as $v) {
			$tmp = array();
			$tmp['id'] = $v['id'];
			$tmp['text'] = $v['cook_name'] . '【价格：￥' . $v['cook_price'] . '】';
			$arr[] = $tmp;
		}
		$data = static_function::arr_add_top($arr, 'text');
		static_function::output_json($data);
	}

}
