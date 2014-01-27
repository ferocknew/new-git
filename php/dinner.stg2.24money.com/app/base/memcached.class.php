<?php
/**
 * Memcache的封闭类
 *
 * 在config文件中 添加
    $cacheServer = array(
        array('host'=>'172.16.10.24','port'=>11211)
    );
    demo:
    $memcached = new base_memcached();
    $memcached -> save_cache('bu','aaa111');
    var_dump($memcached ->get_cache('bu'));
    exit;
 * @access  public
 * @return  object
 * @package default
 * @author  Jonah.Fu
 * @date    2012-03-20
 */
class base_memcached extends Memcache {
    private $groupName = MEM_GROUP_ROOT;
    private $groupTempName = "temp:";
    public $groupPath = MEM_GROUP_ROOT;
    public function __construct() {
        $servers = $GLOBALS['cacheServer'];

        foreach ($servers as $item) {
            // $this -> addServer($item['host'], $item['port'], false);
            $this -> addServer($item['host'], $item['port'], 1);
        }

        // 销毁对象
        register_shutdown_function(array(
            &$this,
            'close'
        ));
    }

    /**
     * 设置保存路径
     * @author    jonah.fu
     */
    public function set_group($groupName = "") {
        if (empty($groupName))
            return FALSE;
        $tempPath = implode(":", explode("/", $groupName));
        $tempPath .= ":";
        $this -> groupName = $tempPath;
        $this -> groupPath = $groupName;

        return TRUE;
    }

    /**
     * 常规保存数据方式
     */
    public function save_cache($name, $arr, $groupname = "", $timeout = MEM_DEFAULT_TIMEOUT) {
        if (empty($groupname))
            $groupname = MEM_GROUP_ROOT . ":" . $this -> groupTempName;
        if (is_array($arr))
            $arr = json_encode($arr, JSON_NUMERIC_CHECK);
        $name = $groupname . $name;

        return $this -> set($name, $arr, 0, $timeout);
    }

    /**
     * 常规获取数据方式
     */
    public function get_cache($name, $groupname = "") {
        if (empty($groupname))
            $groupname = MEM_GROUP_ROOT . ":" . $this -> groupTempName;
        $name = $groupname . $name;
        $return = "";
        $temp = $this -> get($name);
        $return = json_decode($temp, 1);

        return empty($return) ? $temp : $return;

    }

}

// END
?>