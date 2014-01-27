<?php
class ctrl_index extends base_ctrl {
	/**
	 * 主方法
	 */
	public function main() {
		ctrl_user::check_user();
	}

}
