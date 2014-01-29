<?php
class model_usergroup extends base_model {
	protected static $own;
	/**
	 * 获得用户组列表，返回数组
	 */
	public static function get_list() {
		self::main();
		return self::$own -> get_list_action();
	}

	public static function main() {
		if (self::$own == NULL)
			self::$own = new model_usergroup;
		return self::$own;
	}

	private function get_list_action() {
		$sql = "
		select id,user_group_title
		from user_group
		order by id desc
		";

		$rs = self::$db -> query($sql) -> fetchAll();
		return $rs;
	}

}
