<?php
// MYSQL SET
define('MYSQL_MASTER_HOST', 'localhost');
define('MYSQL_MASTER_PORT', '3306');
define('MYSQL_MASTER_DBNAME', 'cmshop');
define('MYSQL_MASTER_USERNAME', 'root');
define('MYSQL_MASTER_PASSWORD', 'root123');
define('MYSQL_MASTER_CHARSET', 'UTF8');

define('MEMCACHE_SERVER', '192.168.37.130');
define('MEMCACHE_PORT', 11211);
define('MEMCACHE_ROOT', '192.168.37.130:');


// TABLE TOP
define('TABLE_PREFIX', 'cs_');
define('BASE_CHARSET', 'utf-8');

define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '.');

define('SESSION_OPEN', 1);
define('SESSION_NAME', 'sid');
define('SESSION_MEM_TABLE_NAME', 'session:');
define('SESSION_TIMEOUT', 1440);
define('SESSION_STORE_TYPE', 'memcache');

define('SMARTY_TEMPLATE_DIR', 'templates/');
define('SMARTY_COMPILED_DIR', 'temp/compiled/');
?>