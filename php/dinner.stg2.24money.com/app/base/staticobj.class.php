<?php
class base_staticobj {
	public static $smarty, $memcached;
	public static function memcached() {
		if (self::$memcached == NULL) {
			self::$memcached = new memcache;
			self::$memcached -> addServer(MEMCACHE_SERVER, MEMCACHE_PORT);

		}
		return self::$memcached;
	}

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

}
