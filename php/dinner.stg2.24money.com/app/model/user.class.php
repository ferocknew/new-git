<?php
class model_user extends base_model {
	protected static $own;
	/**
	 * 获得用户组列表，返回数组
	 */
	public static function add_user($modelData) {
		self::main();
		return self::$own -> add_user_action($modelData);
	}

	public static function main() {
		if (self::$own == NULL)
			self::$own = new self;
		return self::$own;
	}

	private function add_user_action($modelData) {
		$sql = "
		insert into user_list
		(user_name,user_password,user_full_name,user_group_id,raw_add_time) values (:user_name,:user_password,:user_full_name,:user_group_id,now())
		";

		$rs = self::$db -> prepare($sql);
		self::$db -> beginTransaction();
		try {
			$return = $rs -> execute($modelData);
			self::$db -> commit();
		} catch(Exception $e) {
			// echo $e -> getMessage();
			self::$db -> rollback();
			return FALSE;
		}

		return $return;
	}

	public static function edit_user($modelData) {
		self::main();
		return self::$own -> edit_user_action($modelData);
	}

	private function edit_user_action($modelData) {
		$userPassword = '';
		if (!empty($modelData['user_password'])) {
			$userPassword = ',user_password=:user_password';
			$modelData['user_password'] = sha1($modelData['user_password']);
		}
		$sql = "
		update user_list set user_group_id=:user_group_id,user_full_name=:user_full_name $userPassword where id=:user_id
		";

		$rs = self::$db -> prepare($sql);
		// $rs -> bindValue('user_group_id', $modelData['user_group_id'], PDO::PARAM_INT);
		$return = $rs -> execute($modelData);

		return $return;
	}

	public static function get_info($modelData) {
		self::main();
		return self::$own -> get_info_action($modelData);
	}

	private function get_info_action($modelData) {
		$sql = "
		select user_name,user_group_id as user_group,user_full_name from user_list where id=:id
		";

		$rs = self::$db -> prepare($sql);
		$return = $rs -> execute(array('id' => $modelData['user_id']));
		$data = $rs -> fetch();

		return $data;
	}

	public static function get_list($modelData) {
		self::main();
		return self::$own -> get_list_action($modelData);
	}

	private function get_list_action($modelData) {
		$sql = "
		SELECT
			u.id,
			u.user_name,
			u.raw_add_time,
			u.user_full_name,
			g.user_group_title
		FROM
			user_list u
		LEFT JOIN user_group g ON g.id = u.user_group_id
		ORDER BY
			u.raw_add_time DESC
		LIMIT ?,?
		";

		$sqlData = array(
			($modelData['page'] * $modelData['rows']) - $modelData['rows'],
			$modelData['rows']
		);

		$rs = self::$db -> prepare($sql);
		// $rs -> bindValue(1, $sqlData[0], PDO::PARAM_INT);
		// $rs -> bindValue(2, $sqlData[1], PDO::PARAM_INT);
		$rs -> execute($sqlData);
		$return = $rs -> fetchAll();
		return $return;
	}

	public static function get_list_count($modelData = array()) {
		self::main();
		return self::$own -> get_list_count_action($modelData);
	}

	private function get_list_count_action($modelData) {
		$sql = "
		select count(*) from user_list
		";
		$rs = self::$db -> query($sql) -> fetchColumn();
		return $rs * 1;
	}

}
