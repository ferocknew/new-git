<?php
class ctrl_restaurant extends base_ctrl {
	/**
	 * 获得用户组列表
	 */
	public function get_list() {
		$dataArr = model_restaurant::get_list();
		$dataArr = static_function::arr_add_top($dataArr, 'restaurant_name');
		static_function::output_json($dataArr);
	}

}
