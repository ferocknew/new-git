<?php
/**
 * lib_base 系统方法类
 *
 * @access  public
 * @return  object
 * @package default
 * @author  Jonah.Fu
 * @date    2012-03-20
 */
class static_base {
    private static $smarty = null, $redis = null, $cdn_loop_count = 1;

    function __construct() {
        // echo "default";
    }

    /**
     * 递归方式的对变量中的特殊字符进行转义
     *
     * @access  public
     * @param   mix     $value
     *
     * @return  mix
     */
    public static function addslashes_deep($value) {
        if (empty($value)) {
            return $value;
        } else {
            return is_array($value) ? array_map('self::addslashes_deep', $value) : addslashes($value);
        }
    }

    /**
     * 自定义 header 函数，用于过滤可能出现的安全隐患
     *
     * @param   string  string  内容
     *
     * @return  void
     **/
    public static function ecs_header($string, $replace = true, $http_response_code = 0) {
        if (strpos($string, '../upgrade/index.php') === 0) {
            echo '<script type="text/javascript">window.location.href="' . $string . '";</script>';
        }
        $string = str_replace(array(
            "\r",
            "\n"
        ), array(
            '',
            ''
        ), $string);

        if (preg_match('/^\s*location:/is', $string)) {
            @header($string . "\n", $replace);

            exit();
        }

        if (empty($http_response_code) || PHP_VERSION < '4.3') {
            @header($string, $replace);
        } else {
            @header($string, $replace, $http_response_code);
        }
    }

    public static function ecs_iconv($source_lang, $target_lang, $source_string = '') {
        static $chs = NULL;

        /* 如果字符串为空或者字符串不需要转换，直接返回 */
        if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0) {
            return $source_string;
        }

        if ($chs === NULL) {
            require_once (ROOT_PATH . 'includes/cls_iconv.php');
            $chs = new Chinese(ROOT_PATH);
        }

        return $chs -> Convert($source_lang, $target_lang, $source_string);
    }

    // 处理csv格式
    public static function get_csv($text, $topArr = array()) {
        $fileConn = mb_convert_encoding($text, 'UTF-8', 'ascii,GB2312,gbk,UTF-8');
        $fileDemo = explode("\n", $fileConn);
        if (empty($topArr))
            $topArr = explode(",", $fileDemo[0]);
        $fileArr = array();
        foreach ($fileDemo as $item) {
            $temp = array();
            $tempArr = explode(",", $item);
            $i = 0;
            foreach ($topArr as $item1) {
                $temp[$item1] = htmlspecialchars(trim($tempArr[$i]));
                $i++;
            }
            $temp["is_ok"] = 0;

            $fileArr[] = $temp;
        }
        return $fileArr;
    }

    // 获取颜色列表
    public static function get_color_list() {
        $cache = $GLOBALS['cache'];
        $cacheName = MEM_GROUP_ROOT . "_" . __CLASS__ . "_" . __FUNCTION__;
        $cacheValue = $cache -> get($cacheName);
        $cacheValue = "";
        $return = array();
        if (empty($cacheValue)) {

            $colorObj = new model_color();
            $return = $colorObj -> model_get_list();
            unset($colorObj);
            $cache -> set($cacheName, json_encode($return, JSON_NUMERIC_CHECK), 0, MEM_DEFAULT_TIMEOUT);
        } else {
            $return = json_decode($cacheValue, 1);
        }

        return $return;
    }

    // 获取规格列表
    public static function get_size_list() {
        $cache = $GLOBALS['cache'];
        $cacheName = MEM_GROUP_ROOT . "_" . __CLASS__ . "_" . __FUNCTION__;
        $cacheValue = $cache -> get($cacheName);
        $cacheValue = "";
        $return = array();
        if (empty($cacheValue)) {

            $sizeObj = new model_size();
            $return = $sizeObj -> model_get_list();
            unset($sizeObj);
            $cache -> set($cacheName, json_encode($return, JSON_NUMERIC_CHECK), 0, MEM_DEFAULT_TIMEOUT);
        } else {
            $return = json_decode($cacheValue, 1);
        }

        return $return;
    }

    /**
     * 创建SN
     */
    public static function great_sn($root = "", $size = 0, $nextId = 0) {
        if ($root == "")
            return false;
        if ($size == 0)
            return false;
        if ($nextId == 0)
            return false;

        $general_sn = $root . ltrim(pow(10, $size) + $nextId, '1');
        return $general_sn;
    }

    /**
     * 获得系统是否启用了 gzip
     *
     * @access  public
     *
     * @return  boolean
     */
    public static function gzip_enabled() {
        static $enabled_gzip = NULL;

        if ($enabled_gzip === NULL) {
            $enabled_gzip = ($GLOBALS['_CFG']['enable_gzip'] && function_exists('ob_gzhandler'));
        }

        return $enabled_gzip;
    }

    /**
     * 文件或目录权限检查函数
     *
     * @access          public
     * @param           string  $file_path   文件路径
     * @param           bool    $rename_prv  是否在检查修改权限时检查执行rename()函数的权限
     *
     * @return          int     返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
     *                          返回值在二进制计数法中，四位由高到低分别代表
     *                          可执行rename()函数权限、可对文件追加内容权限、可写入文件权限、可读取文件权限。
     */
    public static function file_mode_info($file_path) {
        /* 如果不存在，则不可读、不可写、不可改 */
        if (!file_exists($file_path)) {
            return false;
        }

        $mark = 0;

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            /* 测试文件 */
            $test_file = $file_path . '/cf_test.txt';

            /* 如果是目录 */
            if (is_dir($file_path)) {
                /* 检查目录是否可读 */
                $dir = @opendir($file_path);
                if ($dir === false) {
                    return $mark;
                    //如果目录打开失败，直接返回目录不可修改、不可写、不可读
                }
                if (@readdir($dir) !== false) {
                    $mark^=1;
                    //目录可读 001，目录不可读 000
                }
                @closedir($dir);

                /* 检查目录是否可写 */
                $fp = @fopen($test_file, 'wb');
                if ($fp === false) {
                    return $mark;
                    //如果目录中的文件创建失败，返回不可写。
                }
                if (@fwrite($fp, 'directory access testing.') !== false) {
                    $mark^=2;
                    //目录可写可读011，目录可写不可读 010
                }
                @fclose($fp);

                @unlink($test_file);

                /* 检查目录是否可修改 */
                $fp = @fopen($test_file, 'ab+');
                if ($fp === false) {
                    return $mark;
                }
                if (@fwrite($fp, "modify test.\r\n") !== false) {
                    $mark^=4;
                }
                @fclose($fp);

                /* 检查目录下是否有执行rename()函数的权限 */
                if (@rename($test_file, $test_file) !== false) {
                    $mark^=8;
                }
                @unlink($test_file);
            }
            /* 如果是文件 */
            elseif (is_file($file_path)) {
                /* 以读方式打开 */
                $fp = @fopen($file_path, 'rb');
                if ($fp) {
                    $mark^=1;
                    //可读 001
                }
                @fclose($fp);

                /* 试着修改文件 */
                $fp = @fopen($file_path, 'ab+');
                if ($fp && @fwrite($fp, '') !== false) {
                    $mark^=6;
                    //可修改可写可读 111，不可修改可写可读011...
                }
                @fclose($fp);

                /* 检查目录下是否有执行rename()函数的权限 */
                if (@rename($test_file, $test_file) !== false) {
                    $mark^=8;
                }
            }
        } else {
            if (@is_readable($file_path)) {
                $mark^=1;
            }

            if (@is_writable($file_path)) {
                $mark^=14;
            }
        }

        return $mark;
    }

    /**
     * 插入审核log记录表
     * @param	$insertData	array
     * @param	# FROM_TABLE	被审数据表
     * @param	# AUDIT_TYPE	审核类型
     * @param	# PUBLIC_SN		公共SN字段
     * @param	# RESULT		审核结果 1.通过 0.不通过
     * @param	# AUDIT_DESC	审核备注
     * @param	# ADD_USER_ID	创建人ID
     * @param	# ADD_USER		创建人
     *
     * @return	TRUE / FALSE
     */
    public static function insert_audit_log($insertData) {
        $tempObj = new model_public;
        $return = $tempObj -> model_insert_audit_log($insertData);
        unset($tempObj);

        return $return;
    }

    /**
     * 将JSON传递的参数转码
     *
     * @param string $str
     * @return string
     */
    public static function json_str_iconv($str) {
        if (EC_CHARSET != 'utf-8') {
            if (is_string($str)) {
                return self::ecs_iconv('utf-8', EC_CHARSET, $str);
            } elseif (is_array($str)) {
                foreach ($str as $key => $value) {
                    $str[$key] = self::json_str_iconv($value);
                }
                return $str;
            } elseif (is_object($str)) {
                foreach ($str as $key => $value) {
                    $str -> $key = self::json_str_iconv($value);
                }
                return $str;
            } else {
                return $str;
            }
        }
        return $str;
    }

    /**
     * 检查目标文件夹是否存在，如果不存在则自动创建该目录
     *
     * @access      public
     * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
     *
     * @return      bool
     */
    public static function make_dir($folder) {
        $reval = false;

        if (!file_exists($folder)) {
            /* 如果目录不存在则尝试创建该目录 */
            @umask(0);

            /* 将目录路径拆分成数组 */
            preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

            /* 如果第一个字符为/则当作物理路径处理 */
            $base = ($atmp[0][0] == '/') ? '/' : '';

            /* 遍历包含路径信息的数组 */
            foreach ($atmp[1] AS $val) {
                if ('' != $val) {
                    $base .= $val;

                    if ('..' == $val || '.' == $val) {
                        /* 如果目录为.或者..则直接补/继续下一个循环 */
                        $base .= '/';

                        continue;
                    }
                } else {
                    continue;
                }

                $base .= '/';

                if (!file_exists($base)) {
                    /* 尝试创建目录，如果创建失败则继续循环 */
                    if (@mkdir(rtrim($base, '/'), 0777)) {
                        @chmod($base, 0777);
                        $reval = true;
                    }
                }
            }
        } else {
            /* 路径已经存在。返回该路径是不是一个目录 */
            $reval = is_dir($folder);
        }

        clearstatcache();

        return $reval;
    }

    public static function memcached() {
        $return = new memcache;
        $server = json_decode(MEMCACHE, 1);
        foreach ($server as $v) {
            $return -> addServer($v['host'], $v['port']);

        }
        return $return;
    }

    /**
     * 对 MYSQL LIKE 的内容进行转义
     *
     * @access      public
     * @param       string      string  内容
     * @return      string
     */
    public static function mysql_like_quote($str) {
        return strtr($str, array(
            "\\\\" => "\\\\\\\\",
            '_' => '\_',
            '%' => '\%',
            "\'" => "\\\\\'"
        ));
    }

    /**
     * 获取配置文件内容
     * @author	jonah.fu
     * @date	2012-04-21
     */
    public static function load_config($type = "") {
        if (empty($type))
            return FALSE;
        $cache = $GLOBALS['cache'];
        $uploadConfigFilePath = CLS_PATH . "includes";
        switch ($type) {
            case 'upload' :
                $uploadConfigFilePath .= CONFIG_DIR_PATH . CONFIG_UPLOAD_FILE;
                break;
        }
        $cachePath = MEM_GROUP_ROOT . "/" . __CLASS__ . "/" . __FUNCTION__ . "/upload";
        $return = $cache -> get_cache("config", $cachePath);
        if (!$return) {
            $return = json_decode(file_get_contents($uploadConfigFilePath), 1);
            $cache -> save_cache("config", $return, $cachePath, MEM_DEFAULT_TIMEOUT * 60 * 12);
        }

        return $return;
    }

    /**
     * 处理prepare的字符串
     */
    public static function str4prepare($arr) {
        $return = '';
        $i = 0;
        foreach ($arr as $k => $v) {
            $return .= $k . '=:' . $k;
            if ($i < count($arr) - 1)
                $return .= ",";
            ++$i;
        }
        return $return;
    }

    /**
     * 处理prepare的字符串
     */
    public static function str4insert_prepare($arr) {
        $return = '';
        $i = 0;
        foreach ($arr as $k => $v) {
            $return .= ':' . $k;
            if ($i < count($arr) - 1)
                $return .= ",";
            ++$i;
        }
        return $return;
    }

    /**
     * 获得用户的真实IP地址
     *
     * @access  public
     * @return  string
     */
    public static function real_ip() {
        static $realip = NULL;

        if ($realip !== NULL) {
            return $realip;
        }

        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
                foreach ($arr AS $ip) {
                    $ip = trim($ip);

                    if ($ip != 'unknown') {
                        $realip = $ip;

                        break;
                    }
                }
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $realip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $realip = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

        return $realip;
    }

    /**
     * 读结果缓存文件
     *
     * @params  string  $cache_name
     *
     * @return  array   $data
     */
    public static function read_static_cache($cache_name, $cacheUse = 0) {
        static $result = array();
        $cache = $GLOBALS['cache'];
        $cacheName = MEM_GROUP_ROOT . ":" . __CLASS__ . ":" . __FUNCTION__ . ":" . $cache_name;
        $cacheValue = $cacheUse ? $cache -> get($cacheName) : "";
        if ((DEBUG_MODE & 2) == 2) {
            return false;
        }

        if (!empty($result[$cache_name])) {
            return $result[$cache_name];
        }
        $cache_file_path = ROOT_PATH . '/temp/static_caches/' . $cache_name . '.php';
        if (file_exists($cache_file_path)) {
            if (!empty($cacheValue)) {
                $result[$cache_name] = json_decode($cacheValue, 1);
            } else {
                include_once ($cache_file_path);
                $cacheUse ? $cache -> set($cacheName, json_encode($data, JSON_NUMERIC_CHECK), 0, MEM_DEFAULT_TIMEOUT) : "";
                $result[$cache_name] = $data;
            }

            return $result[$cache_name];
        } else {
            return false;
        }
    }

    public static function redis() {
        if (is_null(self::$redis)) {
            self::$redis = new Redis();
            self::$redis -> connect(REDIS_HOST, REDIS_PORT);
        }
        return self::$redis;
    }

    public static function smarty() {
        if (is_null(self::$smarty)) {
            self::$smarty = new framework_base_template();
            self::$smarty -> template_dir = SYS_PATH . "/" .  TEMPLATE_DIR_TEMPLATE;
            self::$smarty -> compile_dir = SYS_PATH . "/" .  TEMPLATE_DIR_COMPILE;
			$initConf = export_define('smarty.import.php'); //导入并调用 smarty 需初始调用常量
			self::smarty()-> assign('initConf',$initConf);
        }

        return self::$smarty;

    }


    /**
     * 截取UTF-8编码下字符串的函数
     *
     * @param   string      $str        被截取的字符串
     * @param   int         $length     截取的长度
     * @param   bool        $append     是否附加省略号
     *
     * @return  string
     */
    public static function sub_str($str, $length = 0, $append = true) {
        $str = trim($str);
        $strlength = strlen($str);

        if ($length == 0 || $length >= $strlength) {
            return $str;
        } elseif ($length < 0) {
            $length = $strlength + $length;
            if ($length < 0) {
                $length = $strlength;
            }
        }

        if (function_exists('mb_substr')) {
            $newstr = mb_substr($str, 0, $length, EC_CHARSET);
        } elseif (function_exists('iconv_substr')) {
            $newstr = iconv_substr($str, 0, $length, EC_CHARSET);
        } else {
            //$newstr = trim_right(substr($str, 0, $length));
            $newstr = substr($str, 0, $length);
        }

        if ($append && $str != $newstr) {
            $newstr .= '...';
        }

        return $newstr;
    }

    /**
     * 计算字符串的长度（汉字按照两个字符计算）
     *
     * @param   string      $str        字符串
     *
     * @return  int
     */
    public static function str_len($str) {
        $length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));

        if ($length) {
            return strlen($str) - $length + intval($length / 3) * 2;
        } else {
            return strlen($str);
        }
    }

    /**
     * 获取指定长度的随机数
     * @author xiaopeng
     */
    public static function str_rand($length = 4) {
        $randstr = 'QAZWSXEDCRFVTGBYHNUJMIKOLP123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen($randstr));
            $str .= substr($randstr, $rand, 1);
        }
        return $str;
    }

    /**
     * 添加table前綴
     * @author	jonah.fu
     * @date	2012-12-26
     */
    public static function table($tablename) {
        $prefix = @$GLOBALS['prefix'];
        if ($prefix === NULL)
            return $tablename;

        return $prefix . $tablename;
    }

    /**
     * 上传功能对应接口
     * @author	jonah.fu
     * @date	2012-04-21
     * @access	public
     * @param	string	$type	接口提供不同的路径
     * @param	array 	$return 返回 的 msg 定义
     * @return	string	json code
     */
    public static function upload_file($type = "", $return = array()) {
        $uploadConfig = self::load_config("upload");
        // 上传允许的类型
        $accType = $uploadConfig["upload_accType"];
        // k为单位
        $fileMaxSize = $uploadConfig["file_MaxSize"];

        if (!empty($_FILES)) {
            $tempFile = $_FILES['Filedata']['tmp_name'];
            $imgUploadDir = "";
            $fileName = "";

            $fileType = explode(".", $_FILES['Filedata']['name']);
            $fileType = $fileType[count($fileType) - 1];

            $fileSize = $_FILES['Filedata']['size'];

            // 判断文件类型
            if (!array_key_exists($fileType, $accType)) {
                $return["err"] = 1;
                $return["msg"] = "文件类型不允许！";
                exit(base_common::output_json($return));
            }

            if ($fileSize > $fileMaxSize * 1024) {
                $return["err"] = 1;
                $return["msg"] = "文件大小不允许！";
                exit(base_common::output_json($return));
            }

            switch($_GET['type']) {
                case "goods_img" :
                    $imgUploadDir = ".." . IMG_DIR . "/article";
                    $fileName = sha1(com_create_guid()) . "." . $fileType;
                    $dir = date("Y_m_d", strtotime(date("Y-m-d", time())));
                    break;
                case "group_img" :
                    $imgUploadDir = ".." . IMG_DIR . "/group";
                    $fileName = sha1(com_create_guid()) . "." . $fileType;
                    $dir = date("Y_m_d", strtotime(date("Y-m-d", time())));
                    break;
                case "certificate_img" :
                    //证书上传
                    $imgUploadDir = ".." . IMG_DIR . "/certificate";
                    $fileName = $_GET['imgName'] . "." . $fileType;
                    $dir = $_GET['dir'];
                    break;
                case "service_img" :
                    //服务图片上传
                    $imgUploadDir = ".." . IMG_DIR . "/service_assurance";
                    $fileName = sha1(com_create_guid()) . "." . $fileType;
                    $dir = date("Y_m_d", strtotime(date("Y-m-d", time())));
                    break;
                default :
                    $fileName = $_FILES['Filedata']['name'];
                    exit("0");
                    break;
            }
            if (!framework_static_base::make_dir($imgUploadDir)) {
                $return["err"] = 1;
                $return["msg"] = "目录异常！";
                exit(base_common::output_json($return));
            }
            $smallDirName = $imgUploadDir . "/" . $dir;
            if (!framework_static_base::make_dir($smallDirName)) {
                $return["err"] = 1;
                $return["msg"] = "目录异常！";
                exit(base_common::output_json($return));
            }
            $targetPath = $smallDirName . "/";
            $targetFile = str_replace('//', '/', $targetPath) . $fileName;

            if (move_uploaded_file($tempFile, $targetFile)) {
                $return["filePath"] = $targetFile;
                exit(base_common::output_json($return));
            } else {
                $return["err"] = 1;
                $return["msg"] = "文件写入错误！";
                exit(base_common::output_json($return));
            };
        }
    }

    /**
     * 原ecshop上传接口
     */
    public static function upload_article_file($upload, $saveDir = "", $fileName = "") {
        $imgUploadDir = ".." . IMG_DIR . "/article";
        if (!self::make_dir($imgUploadDir)) {
            // 创建目录失败
            return false;
        }

        $smallDirName = $imgUploadDir . "/" . strtotime(date("Y-m-d", time()));
        if (!self::make_dir($smallDirName)) {
            // 创建目录失败
            return false;
        }

        $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = ROOT_PATH . DATA_DIR . "/article/" . $filename;

        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/article/" . $filename;
        } else {
            return false;
        }
    }

    /**
     * 写结果缓存文件
     *
     * @params  string  $cache_name
     * @params  string  $caches
     *
     * @return
     */
    public static function write_static_cache($cache_name, $caches) {
        if ((DEBUG_MODE & 2) == 2) {
            return false;
        }
        $cache_file_path = ROOT_PATH . '/temp/static_caches/' . $cache_name . '.php';
        $content = "<?php\r\n";
        $content .= "\$data = " . var_export($caches, true) . ";\r\n";
        $content .= "?>";
        file_put_contents($cache_file_path, $content, LOCK_EX);
    }
	
	/*
     * 轮循 CDN 路径
     */
    
	public static function cdn_loop() 
	{
		self::$cdn_loop_count++;   
		if (self::$cdn_loop_count > CDN_NUMS) 
		{            
			self::$cdn_loop_count = 1;
		}
		return constant(CDN . self::$cdn_loop_count);
	}
}
