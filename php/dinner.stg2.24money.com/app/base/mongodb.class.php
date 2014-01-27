<?php
/**
 * Mongodb类
 */
class framework_base_mongodb {
    public $mConn = NULL, $error = '';
    public static $_instance = array();

    private $DbName = '';

    private function __construct($type = 0, $autoClose = 0, $server = '') {
        $username = MONGODB_USERNAME;
        $password = MONGODB_PASSWORD;
        $serverHost = MONGODB_SERVER;
        $dbName = MONGODB_DBNAME;
        $serverDNS = "mongodb://${username}:${password}@${serverHost}/${dbName}";
        try {
            $this -> mConn = new Mongo($serverDNS);
            $this -> select_db(MONGODB_DBNAME);
        } catch (MongoCursorException $e) {
            printf($e -> getMessage());
            exit();
        }

        if ($autoClose) {
            register_shutdown_function(array(
                &$this,
                'close_pdooci'
            ));
        }
    }

    /**
     * 返回的连接状态
     * @author	jonah.fu
     * @date	2012-04-21
     * @param	int			type			是否长联接
     * @param	int			autoClose		是否自动关闭连接
     * @param	int			autoClose		是否自动关闭
     * @param	array		ociDbServer		数据库信息，参考 config.php 文件
     * @param	string		ociConnectStr	数据库连接字符串，参考 config.php 文件
     *
     * @return	object		返回pdo_oci_obj 对象
     */
    public static function connect($type = 0, $autoClose = 0, $server = '') {
        $arrStr = md5($server);
        if (empty(self::$_instance) || !(self::$_instance[$arrStr] instanceof self)) {

            // 如果是长联接则不自动关闭
            if ($type)
                $autoClose = 0;
            self::$_instance[$arrStr] = new self($type, $autoClose, $server);
        }
        return self::$_instance[$arrStr];
    }

    /**
     * 选择DB
     */
    private function select_db($dbName) {
        if (empty($dbName))
            exit('MongoDB DBName Err!');
        $this -> DbName = $dbName;
    }

    /**
     * 插入记录
     *
     * 参数：
     * $table_name:表名
     * $record:记录
     *
     * 返回值：
     * 成功：true
     * 失败：false
     */
    public function insert($table_name, $record) {
        $dbname = $this -> DbName;
        try {
            $this -> mConn -> $dbname -> $table_name -> insert($record, array('safe' => true));
            return true;
        } catch (MongoCursorException $e) {
            $this -> error = $e -> getMessage();
            return false;
        }
    }

    /**
     * 查询表的记录数
     *
     * 参数：
     * $table_name:表名
     *
     * 返回值：表的记录数
     */
    function count($table_name) {
        $dbname = $this -> DbName;
        return $this -> mConn -> $dbname -> $table_name -> count();
    }

}
?>  