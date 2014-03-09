<?php
/**
 * model 基类
 */
class base_model {
	public static $db;
	public function __construct() {
		if (self::$db == NULL)
			self::$db = $this -> get_db();
	}

	protected function get_db() {
		$tmpObj = base_pdomysql::connect();
		$return = $tmpObj -> mConn;
		return $return;
	}

	public function make_sn($num, $numLength = 1) {
		return substr(((pow(10, $numLength) + $num) . ''), 1);
	}

}
