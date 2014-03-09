<?php
class model_order extends base_model {
	protected static $own;
	/**
	 * 获得用户组列表，返回数组
	 */
	public static function save_order_info($modelData) {
		self::main();
		return self::$own -> save_order_info_action($modelData);
	}

	public static function main() {
		if (self::$own == NULL)
			self::$own = new self;
		return self::$own;
	}

	private function save_order_info_action($modelData) {
		// $orderSN = $this -> get_order_sn();
		if ($modelData['user_id'] != -1) {
			$userInfo = model_user::get_info(array('user_id' => $modelData['user_id']));
			$modelData['user_name'] = $userInfo['user_name'];
			$modelData['user_group_id'] = $userInfo['user_group_id'] * 1;
			$modelData['user_full_name'] = $userInfo['user_full_name'];
			$modelData['user_group_title'] = $userInfo['user_group_title'];
		} else {
			$modelData['user_name'] = SUPER_ADMIN_USER_NAME;
			$modelData['user_group_id'] = 0;
			$modelData['user_full_name'] = SUPER_ADMIN_USER_NAME;
			$modelData['user_group_title'] = '';
		}

		$cookInfo = model_food::get_info($modelData['cook_id']);
		var_dump($cookInfo);
		exit ;
	}

	private function get_order_sn() {
		$snPrefix = ORDER_SN_ROOT . date('ymd');
		$sequenceTableName = 'sequence_list';

		$sql = "
		select sequence_num from sequence_list where sequence_name=?
		";

		$sql2 = "
		update sequence_list s
		set s.sequence_num=s.sequence_num+1
		where s.sequence_name=:sequence_name and s.sequence_num<:sequence_num
		";

		$rs = self::$db -> prepare($sql);
		self::$db -> beginTransaction();
		try {
			$rs -> execute(array(ORDER_SEQUENCE_NAME));
			$sequenceNum = $rs -> fetchColumn();
			$updateSequenceNum = $sequenceNum + 1;
			$rs = self::$db -> prepare($sql2);
			$return = $rs -> execute(array(
				'sequence_name' => ORDER_SEQUENCE_NAME,
				'sequence_num' => $updateSequenceNum
			));

			if ($return == TRUE)
				self::$db -> commit();
			else {
				self::$db -> rollback();
				return $this -> get_order_sn();
			}

			$orderNum = $this -> make_sn($updateSequenceNum, ORDER_SN_LENGTH);
			$orderSN = $snPrefix . $orderNum;
			return $orderSN;
		} catch(Exception $e) {
			self::$db -> rollback();
			exit($e -> getMessage());
		}

	}

}
