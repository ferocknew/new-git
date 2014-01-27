<?php
/**
 * cmsshop 基类
 */
class base_cmshop {
	private static $smarty, $dbName, $preFix;

	/**
	 * 模板静态方法
	 * @author	jonah.fu
	 * @date	2012-09-04
	 */
	public static function smarty() {
		if (self::$smarty == NULL)
			self::$smarty = new base_template();

		return self::$smarty;
	}

	public static function table($str) {
		if (self::$dbName == NULL)
			self::$dbName = MYSQL_MASTER_DBNAME;
		if (self::$preFix == NULL)
			self::$preFix = TABLE_PREFIX;
		return '`' . self::$dbName . '`.`' . self::$preFix . $str . '`';
	}

}
?>