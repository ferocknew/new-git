<?php
/**
 * autoloader 类
 *
 * @access  public
 * @return  object
 * @package default
 * @author  Jonah.Fu
 * @date    2012-03-28
 */
class base_autoloader {
	public static $loader;

	/**
	 * 构造函数
	 */
	private function __construct() {
		spl_autoload_register(array(
			$this,
			'base_class'
		));
	}

	public static function init() {
		// 静态化自调用
		if (self::$loader == NULL)
			self::$loader = new self();

		return self::$loader;
	}

	/**
	 * 固定路径的class 类文件 以.class.php 结尾
	 * @author	jonah.fu
	 */
	private function base_class($className) {
		$path = array();
		$pathDir = '';
		$path = explode('_', $className);
		$arrCount = count($path) - 1;
		$pathDir = implode("/", array_slice($path, 0, $arrCount));
		set_include_path(ROOT_PATH . "/app/" . $pathDir);
		spl_autoload_extensions('.class.php');
		spl_autoload($path[$arrCount]);
	}

	// 文件出错提示
	private function err_fn($className) {
		exit("class $className files includes err!!");
	}

}
?>