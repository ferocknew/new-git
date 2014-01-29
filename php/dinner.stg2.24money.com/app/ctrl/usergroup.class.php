<?php
class ctrl_usergroup extends base_ctrl {
	/**
	 * 获得用户组列表
	 */
	public function get_list() {
		$dataArr = model_usergroup::get_list();
		$dataArr = static_function::arr_add_top($dataArr, 'user_group_title');
		static_function::output_json($dataArr);
	}

}
