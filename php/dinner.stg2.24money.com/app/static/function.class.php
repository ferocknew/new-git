<?php
/**
 * 静态 function
 */
class static_function {
	private static $numLength = 14;
	/**
	 * 远程获取文件
	 */
	public static function curl_file_get_contents($durl) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, trim($durl));
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		// curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
		curl_setopt($ch, CURLOPT_REFERER, _REFERER_);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	/**
	 * @param $url
	 * @param $para
	 * @param string $method
	 * @return mixed
	 */
	public static function http_curl($url, $para, $method = "POST") {
		$data_string = http_build_query($para);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if ($method == "POST") {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		} else {
			//get
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		return $result;
	}

	public static function https_curl($url, $para, $cert = null) {
		if (strpos($url, 'PATr0003') === false) {
			self::write_log($url . "\t" . $para, 'rlms_interface');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);

		//测试数据
		curl_setopt($ch, CURLOPT_POSTFIELDS, $para);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($cert == null) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); ;
			curl_setopt($ch, CURLOPT_CAINFO, $cert);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Content-Length: ' . strlen($para)));
		$result = curl_exec($ch);
		curl_close($ch);
		if (!empty($result)) {
			return json_decode($result, true);
		} else {
			return false;
		}
	}

	/**
	 * 获取page URL
	 */
	public static function curPageURL() {
		if (SERVER_GET_IP)
			$_SERVER["SERVER_NAME"] = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["SERVER_NAME"];
		$pageURL = 'http';

		if (isset($_SERVER["HTTPS"])) {
			if ($_SERVER["HTTPS"] == "on")
				$pageURL .= "s";
		}
		$pageURL .= "://";

		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	/**
	 * 获取 URL
	 */
	public static function curURL() {
		$pageURLArr = explode("/", self::curPageURL());
		unset($pageURLArr[count($pageURLArr) - 1]);
		return implode("/", $pageURLArr) . "/";
	}

	public static function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * 检查文件夹
	 */
	public static function mkdirs($path, $mode = 0777) {
		$dirs = explode('/', $path);
		$pos = strrpos($path, ".");
		if ($pos === false) {
			// note: three equal signs
			// not found, means path ends in a dir not file
			$subamount = 0;
		} else {
			$subamount = 1;
		}

		for ($c = 0; $c < count($dirs) - $subamount; $c++) {
			$thispath = "";
			for ($cc = 0; $cc <= $c; $cc++) {
				$thispath .= $dirs[$cc] . '/';
			}
			// echo $thispath . "<br />";
			if (!file_exists($thispath)) {
				//print "$thispath<br>";
				mkdir($thispath, $mode);
			}
		}
	}

	/**
	 * 写日志到文件
	 */
	public static function log_to_file($app_name, $msg) {
		$time = date('Y-d-m H:i:s');
		$msg = "[$time]:$msg\r";
		$filePATH = LOG_PATH . "/" . $app_name;
		if (!file_exists($filePATH))
			static_base::make_dir($filePATH);

		$filePATH = $filePATH . "/" . date('Ydm', time()) . ".log";
		if (!file_exists($filePATH))
			file_put_contents($filePATH, '');

		try {
			$handle = fopen($filePATH, "a");
			if (!$handle) {
				error_log("can\'t open file {$filePATH}\r");
				exit ;
			}

			$return = fwrite($handle, $msg);
			if ($return === FALSE) {
				error_log("can\'t write file {$filePATH}\r");
				exit ;
			}
			fclose($handle);
		} catch(Exception $e) {
			error_log("can\'t open file {$filePATH}\r");
		}

	}

	/**
	 * 写日志到数据库
	 */
	public static function log_to_db($logName, $logStr = '', $logErrCode = '0000') {
		$logName .= '_log';
		$m = framework_base_mongodb::connect();
		$insertArr = array("log_time" => date('Y-m-d H:i:s'), 'log_str' => $logStr, 'log_err_code' => $logErrCode);
		$m -> insert($logName, $insertArr);
	}

	/**
	 * @param $array
	 * @param int $num
	 * @param bool $jsonHeader
	 * 输出标准json
	 *
	 * @access	public
	 *
	 * @author	jonah.fu
	 * @date	2012-04-19
	 *
	 * @return   string
	 *
	 */
	public static function output_json($array, $num = true, $jsonHeader = true) {
		header("Expires: Mon, 26 Jul 1970 01:00:00 GMT");
		$jsonHeader && header('Content-type: application/json;charset=utf-8');
		header("Pramga: no-cache");
		header("Cache-Control: no-cache");
		if ($num) {
			exit(json_encode((array)($array), JSON_NUMERIC_CHECK));
		} else {
			exit(json_encode((array)($array)));
		}
	}

	/**
	 * 魔术引号
	 */
	public static function strip_array($var) {
		return is_array($var) ? array_map("self::strip_array", $var) : addslashes(htmlspecialchars($var));
	}

	/**
	 * 圆周计算
	 *
	 */
	public static function rad($d) {
		return bcmul($d, (bcdiv('3.1415926535898', '180', self::$numLength)), self::$numLength);
	}

	/**
	 * 经纬度之间获取距离
	 */
	public static function GetDistance($lat1, $lng1, $lat2, $lng2) {
		$EARTH_RADIUS = 6378.137;
		$radLat1 = self::rad($lat1);
		//echo $radLat1;
		$radLat2 = self::rad($lat2);
		$a = bcsub($radLat1, $radLat2, self::$numLength);
		$b = bcsub(self::rad($lng1), self::rad($lng2), self::$numLength);
		$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
		$s = bcmul($s, $EARTH_RADIUS, self::$numLength);
		$s = round($s * 10000) / 10000;
		return $s;
	}

	public static function debug($var) {
		if ($_COOKIE['debug'] == 'jkdc') {
			print_r('<pre style="text-align: left">');
			print_r((array)$var);
			print_r('</pre>');
		}
	}

	public static function write_log($logs, $file_name = '') {
		$logs = date('Y-m-d H:i:s') . "\t" . $logs . "\r\n";
		if (empty($file_name)) {
			$file = SYS_PATH . '/../logs/' . date("Y/m/d") . '/sites.txt';
		} else {
			$file = SYS_PATH . '/../logs/' . date("Y/m/d") . '/' . $file_name . '.txt';
		}
		if (!file_exists(dirname($file))) {
			mkdir(dirname($file), 0775, true);
		}
		error_log($logs, 3, $file);
	}

	public static function history_back($msg = '') {
		if ($msg) {
			$msg = "alert('" . $msg . "');";
		}
		echo "<script>" . $msg . "history.back(-1);</script>";
		die ;
	}

	public static function rand_money() {
		return mt_rand(1, 9) / 100;
	}

	//封装rlms请求数据成json格式
	public static function rlms_json($para) {
		$data_string = "{";
		foreach ($para as $key => $val) {
			$data_string .= $key . ":'" . $val . "',";
		}
		$data_string = substr($data_string, 0, -1);
		$data_string .= "}";
		return $data_string;
	}

	//短信通知开发
	public static function notice_developer($msg, $mobile = '') {
		$mobile = $mobile ? $mobile : DEVELOPER_MOBILE;
		framework_static_message::send($mobile, $msg);
	}

	/**
	 *
	 */
	public static function set_cookie($key, $value, $time = '24*60*60') {
		setCookie($key, $value, $time, '/');
	}

	/**
	 * 获取 CSV
	 */
	public static function getCSVdata($filename) {
		$row = 1;
		//第一行开始
		if (($handle = fopen($filename, "r")) !== false) {
			while (($dataSrc = fgetcsv($handle)) !== false) {
				$num = count($dataSrc);
				for ($c = 0; $c < $num; $c++)//列 column
				{
					if ($row === 1)//第一行作为字段
					{
						$dataName[] = $dataSrc[$c];
						//字段名称
					} else {
						foreach ($dataName as $k => $v) {
							if ($k == $c)//对应的字段
							{
								$data[$v] = $dataSrc[$c];
							}
						}
					}
				}
				if (!empty($data)) {
					$dataRtn[] = $data;
					unset($data);
				}
				$row++;
			}
			fclose($handle);
			return $dataRtn;
		}
	}

	/**
	 * 字符串/二维数组/多维数组编码转换
	 * @param string $in_charset
	 * @param string $out_charset
	 * @param mixed $data
	 **/
	public static function array_iconv($data, $in_charset = 'GBK', $out_charset = 'UTF-8') {
		if (!is_array($data)) {
			$output = iconv($in_charset, $out_charset, $data);
		} elseif (count($data) === count($data, 1)) {//判断是否是二维数组
			foreach ($data as $key => $value) {
				$output[$key] = iconv($in_charset, $out_charset, $value);
			}
		} else {
			eval('$output = ' . iconv($in_charset, $out_charset, var_export($data, TRUE)) . ';');
		}
		return $output;
	}

	/**
	 * 给没有http的网址添加
	 */
	public static function addLink($link) {
		if ($link) {
			if (strpos($link, 'http://') === false && strpos($link, 'https://') === false) {
				$link = 'http://' . $link;
			} else {
				$link = trim($link);
			}
		}
		return $link;
	}

	/**
	 * 二维数组/根据某个键值来排序
	 * @param array
	 * @param key
	 * @param sort
	 **/
	public static function array_sort($array, $key, $sort = 'asc') {
		if (!is_array($array)) {
			return false;
		}
		$arr = array();
		foreach ($array as $k => $v) {
			$arr[$k] = $v[$key];
		}
		if ($sort == 'asc') {
			asort($arr);
		} else {
			arsort($arr);
		}
		$new_array = array();
		foreach ($arr as $k => $v) {
			$new_array[] = $array[$k];
		}
		return $new_array;
	}

	/**
	 * 对象数组转换成普通数组
	 * @param array
	 * return array
	 **/
	public static function objectToArray($d) {
		if (is_object($d)) {
			$d = get_object_vars($d);
		}
		if (is_array($d)) {
			return array_map(__FUNCTION__, $d);
		} else {
			return $d;
		}
	}

	/**
	 * 数组转换成对象数组
	 * @param array
	 * return object
	 **/
	public static function arrayToObject($d) {
		if (is_array($d)) {
			return (object) array_map(__FUNCTION__, $d);
		} else {
			return $d;
		}
	}

	/**
	 * 随机输出验证码
	 */
	public static function captchar() {
		Header("Content-type: image/gif");
		/*
		 * 初始化
		 */
		$border = 0;
		//是否要边框 1要:0不要
		$how = 4;
		//验证码位数
		$w = $how * 15;
		//图片宽度
		$h = 20;
		//图片高度
		$fontsize = 5;
		//字体大小
		$alpha = "abcdefghijkmnopqrstuvwxyz";
		//验证码内容1:字母
		$number = "023456789";
		//验证码内容2:数字
		$randcode = "";
		//验证码字符串初始化
		srand((double)microtime() * 1000000);
		//初始化随机数种子

		$im = ImageCreate($w, $h);
		//创建验证图片

		/*
		 * 绘制基本框架
		 */
		$bgcolor = ImageColorAllocate($im, 255, 255, 255);
		//设置背景颜色
		ImageFill($im, 0, 0, $bgcolor);
		//填充背景色
		if ($border) {
			$black = ImageColorAllocate($im, 0, 0, 0);
			//设置边框颜色
			ImageRectangle($im, 0, 0, $w - 1, $h - 1, $black);
			//绘制边框
		}

		/*
		 * 逐位产生随机字符
		 */
		for ($i = 0; $i < $how; $i++) {
			$alpha_or_number = mt_rand(0, 1);
			//字母还是数字
			$str = $alpha_or_number ? $alpha : $number;
			$which = mt_rand(0, strlen($str) - 1);
			//取哪个字符
			$code = substr($str, $which, 1);
			//取字符
			$j = !$i ? 4 : $j + 15;
			//绘字符位置
			$color3 = ImageColorAllocate($im, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
			//字符随即颜色
			ImageChar($im, $fontsize, $j, 3, $code, $color3);
			//绘字符
			$randcode .= $code;
			//逐位加入验证码字符串
		}

		/*
		 * 添加干扰
		 */
		for ($i = 0; $i < 5; $i++)//绘背景干扰线
		{
			$color1 = ImageColorAllocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
			//干扰线颜色
			ImageArc($im, mt_rand(-5, $w), mt_rand(-5, $h), mt_rand(20, 300), mt_rand(20, 200), 55, 44, $color1);
			//干扰线
		}
		for ($i = 0; $i < $how * 40; $i++)//绘背景干扰点
		{
			$color2 = ImageColorAllocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
			//干扰点颜色
			ImageSetPixel($im, mt_rand(0, $w), mt_rand(0, $h), $color2);
			//干扰点
		}

		//把验证码字符串写入session
		session_start();
		$_SESSION['randcode'] = $randcode;
		$_SESSION['verify_time'] = time();
		/*绘图结束*/
		Imagegif($im);
		ImageDestroy($im);
		/*绘图结束*/
	}

	/*
	 * 生成一个验证码
	 * @param $name SESSION名
	 * @param $how 位数
	 */
	function generate_captchar($how = 6) {
		$alpha = "abcdefghijkmnopqrstuvwxyz";
		//验证码内容1:字母
		$number = "023456789";
		//验证码内容2:数字
		$randcode = "";
		//验证码字符串初始化
		srand((double)microtime() * 1000000);
		//初始化随机数种子

		for ($i = 0; $i < $how; $i++) {
			$alpha_or_number = mt_rand(0, 1);
			//字母还是数字
			$str = $alpha_or_number ? $alpha : $number;
			$which = mt_rand(0, strlen($str) - 1);
			//取哪个字符
			$code = substr($str, $which, 1);
			//取字
			$randcode .= $code;
			//逐位加入验证码字符串
		}

		return $randcode;
	}

	/**
	 * 获得客户端ip
	 */
	public static function getIp() {
		if (isset($_SERVER["HTTP_RLNCLIENTIPADDR"])) {
			$clientIp = $_SERVER["HTTP_RLNCLIENTIPADDR"];
		} else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$clientIp = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$clientIp = $_SERVER['REMOTE_ADDR'];
		} else {
			$clientIp = 'u';
		}
		return $clientIp;
	}

	public static function sqlEscape($var, $strip = true) {
		if (is_numeric($var)) {
			return $var;
		} else {
			return addslashes($strip ? stripslashes($var) : $var);
		}
	}

	public static function escapeStr($string) {
		$string = str_replace(array("\0", "%00", "\r", 'exec'), '', $string);
		//modified@2010-7-5
		$string = preg_replace(array('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '/&(?!(#[0-9]+|[a-z]+);)/is'), array('', '&amp;'), $string);
		$string = str_replace(array("%3C", '<'), '&lt;', $string);
		$string = str_replace(array("%3E", '>'), '&gt;', $string);
		$string = str_replace(array('"', "'", "\t", '  '), array('&quot;', '&#39;', '    ', '&nbsp;&nbsp;'), $string);
		return self::sqlEscape($string);
	}

}
