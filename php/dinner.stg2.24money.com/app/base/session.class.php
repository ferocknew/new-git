<?php
class base_session {
	private $conn;
	private static $redis = NULL, $sessionID = NULL, $nowTime = NULL, $sessionData = NULL, $userIP = NULL, $sessionDataTemp = NULL;
	private static $sessionType = NULL, $memcache = NULL;
	var $db = NULL;
	var $session_table = '';

	var $max_life_time = SESSION_TIMEOUT;
	// SESSION 过期时间

	var $session_name = '';
	var $session_id = '';

	var $session_expiry = '';
	var $session_md5 = '';

	var $session_cookie_path = COOKIE_PATH;
	var $session_cookie_domain = COOKIE_DOMAIN;
	var $session_cookie_secure = FALSE;

	var $_ip = '';
	var $_time = NULL;

	public static $_instance = NULL;
	private $session_data = NULL;

	function __construct(&$db, $session_table, $session_data_table, $session_name = 'ECS_ID', $session_id = '') {
		echo 123;
		$this -> cls_session($db, $session_table, $session_data_table, $session_name, $session_id);
	}

	function cls_session(&$db, $session_table, $session_data_table, $session_name = 'ECS_ID', $session_id = '') {
		$GLOBALS['_SESSION'] = array();

		if (!empty($GLOBALS['cookie_path'])) {
			$this -> session_cookie_path = $GLOBALS['cookie_path'];
		} else {
			$this -> session_cookie_path = '/';
		}

		if (!empty($GLOBALS['cookie_domain'])) {
			$this -> session_cookie_domain = $GLOBALS['cookie_domain'];
		} else {
			$this -> session_cookie_domain = '';
		}

		if (!empty($GLOBALS['cookie_secure'])) {
			$this -> session_cookie_secure = $GLOBALS['cookie_secure'];
		} else {
			$this -> session_cookie_secure = false;
		}

		$this -> session_name = $session_name;
		$this -> session_table = $session_table;
		$this -> session_data_table = $session_data_table;

		$this -> db = &$db;
		$this -> _ip = static_base::real_ip();
		
		if ($session_id == '' && !empty($_COOKIE[$this -> session_name])) {
			$this -> session_id = $_COOKIE[$this -> session_name];
		} else {
			$this -> session_id = $session_id;
		}
		if ($this -> session_id) {
			$tmp_session_id = substr($this -> session_id, 0, 32);
			if ($this -> gen_session_key($tmp_session_id) == substr($this -> session_id, 32)) {
				$this -> session_id = $tmp_session_id;
			} else {
				$this -> session_id = '';
			}
		}

		$this -> _time = date('Y-m-d H:i:s');
		if ($this -> session_id) {
			$this -> load_session();
		} else {
			$this -> gen_session_id();
			setcookie($this -> session_name, $this -> session_id . $this -> gen_session_key($this -> session_id), time() + COOKIE_TIMEOUT, $this -> session_cookie_path, $this -> session_cookie_domain, $this -> session_cookie_secure);
		}

		register_shutdown_function(array(
			&$this,
			'close_session'
		));
	}

	function gen_session_id() {
		$this -> session_id = function_exists('com_create_guid') ? md5(com_create_guid()) : md5($this -> _ip . uniqid(mt_rand(), true));

		// return $this -> insert_session();
		return $this -> load_session();
	}

	function gen_session_key($session_id) {
		static $ip = '';

		if ($ip == '') {
			$ip = substr($this -> _ip, 0, strrpos($this -> _ip, '.'));
		}

		return sprintf('%08x', crc32(ROOT_PATH . $ip . $session_id));
	}

	function insert_session() {
		//    	$sesstemp = array(
		//	            'sesskey'       => ,
		//	            'expiry'        => $this->_time,
		//    			'ip'            => $this->_ip,
		//	            'session_data'  => 'a:0:{}'
		//	        );
		//return $this->db->autoExecute($this->session_table,$sesstemp);
		$sql = 'INSERT INTO ' . $this -> session_table . ' (sesskey, expiry, ip, session_data) VALUES (:sesskey,:expiry,:ip,:session_data)';
		$sqlData = array(
			'sesskey' => $this -> session_id,
			'expiry' => $this -> _time,
			'ip' => $this -> _ip,
			'session_data' => ''
		);

		$this -> db -> prepare($sql) -> execute($sqlData);
		return true;
	}

	function load_session() {
		$sql = "SELECT userid, adminid, user_name, user_rank, discount, email,session_data, expiry, ip, is_table_data FROM {$this -> session_table}  WHERE sesskey = ?";
		$rs = $this -> db -> prepare($sql);
		$rs -> execute(array($this -> session_id));
		$session = $rs -> fetch();
		$session = is_array($session) ? $session : array();

		if (empty($session)) {
			$this -> insert_session();

			$this -> session_expiry = 0;
			$this -> session_md5 = '40cd750bba9870f18aada2478b24840a';
			$GLOBALS['_SESSION'] = array();
		} else {
			//            if (!empty($session['session_data']) && $this->_time - $session['expiry'] <= $this->max_life_time)
			//            {
			//            	if($session['session_data'] != '0')
			//            	{
			//	                $this->session_expiry = $session['expiry'];
			//	                $this->session_md5    = md5(stripcslashes($session['session_data']));
			//	                $GLOBALS['_SESSION']  = unserialize(stripcslashes($session['session_data']));
			//	                $GLOBALS['_SESSION']['user_id'] = $session['userid'];
			//	                $GLOBALS['_SESSION']['admin_id'] = $session['adminid'];
			//	                $GLOBALS['_SESSION']['user_name'] = $session['user_name'];
			//	                $GLOBALS['_SESSION']['user_rank'] = $session['user_rank'];
			//	                $GLOBALS['_SESSION']['discount'] = $session['discount'];
			//	                $GLOBALS['_SESSION']['email'] = $session['email'];
			//            	}
			//            }
			//            else
			//            {
			if ($session['is_table_data']) {
				$sql = "SELECT session_data, expiry FROM {$this -> session_data_table} WHERE sesskey = ?";
				$rs = $this -> db -> prepare($sql);
				$rs -> execute(array($this -> session_id));
				$session_data = $rs -> fetch();
				if (!$session_data)
					$session_data = array();
			} else {
				$session_data = array();
				$session_data["session_data"] = $session["session_data"];
				$session_data['expiry'] = $session['expiry'];
			}

			if (!empty($session_data['session_data']) && strtotime($this -> _time) - strtotime($session_data['expiry']) <= (time() - $this -> max_life_time)) {
				$this -> session_expiry = $session_data['expiry'];
				$this -> session_md5 = md5($session_data['session_data']);
				$GLOBALS['_SESSION'] = json_decode($session_data['session_data'], 1);
				$GLOBALS['_SESSION']['user_id'] = $session['userid'];
				$GLOBALS['_SESSION']['admin_id'] = $session['adminid'];
				$GLOBALS['_SESSION']['user_name'] = $session['user_name'];
				$GLOBALS['_SESSION']['user_rank'] = $session['user_rank'];
				$GLOBALS['_SESSION']['discount'] = $session['discount'];
				$GLOBALS['_SESSION']['email'] = $session['email'];
			} else {
				$this -> session_expiry = 0;
				$this -> session_md5 = '40cd750bba9870f18aada2478b24840a';
				$GLOBALS['_SESSION'] = array();
			}

		}

		$this -> session_data = md5(json_encode($GLOBALS['_SESSION']));
	}

	function update_session() {
		$session_md5 = md5(json_encode($GLOBALS['_SESSION']));
		// if ($session_md5 == $this -> session_data)
		// return TRUE;

		$adminid = !empty($GLOBALS['_SESSION']['admin_id']) ? intval($GLOBALS['_SESSION']['admin_id']) : 0;
		$userid = !empty($GLOBALS['_SESSION']['user_id']) ? intval($GLOBALS['_SESSION']['user_id']) : 0;
		$user_name = !empty($GLOBALS['_SESSION']['user_name']) ? trim($GLOBALS['_SESSION']['user_name']) : 0;
		$user_rank = !empty($GLOBALS['_SESSION']['user_rank']) ? intval($GLOBALS['_SESSION']['user_rank']) : 0;
		$discount = !empty($GLOBALS['_SESSION']['discount']) ? round($GLOBALS['_SESSION']['discount'], 2) : 0;
		$email = !empty($GLOBALS['_SESSION']['email']) ? trim($GLOBALS['_SESSION']['email']) : 0;
		// unset($GLOBALS['_SESSION']['admin_id']);
		// unset($GLOBALS['_SESSION']['user_id']);
		// unset($GLOBALS['_SESSION']['user_name']);
		// unset($GLOBALS['_SESSION']['user_rank']);
		// unset($GLOBALS['_SESSION']['discount']);
		// unset($GLOBALS['_SESSION']['email']);

		$data = json_encode($GLOBALS['_SESSION']);
		$this -> _time = date('Y-m-d H:i:s');
		if (isset($data{SESSION_DATA_LENGTH})) {
			/*
			 */
			//        	$sesstemp = array(
			//	            'sesskey'       => $this->session_id,
			//	            'expiry'        => $this->_time,
			//	            'session_data'  => $data
			//	        );

			$sql = "SELECT SESSKEY FROM {$this -> session_data_table} . ' WHERE SESSKEY=?";
			$rs = $this -> db -> prepare($sql);
			$rs -> execute(array($this -> session_id));
			$sesskeytemp = $rs -> fetchcolumn();
			if ($sesskeytemp || static_base::str_len($sesskeytemp) > 0) {
				$sql = 'UPDATE ' . $this -> session_data_table . ' SET expiry=:expiry, session_data=:session_data WHERE sesskey=:sesskey';
				$this -> db -> Binds = array();
				$this -> db -> bind('sesskey', $this -> session_id);
				$this -> db -> bind('expiry', $this -> _time);
				$this -> db -> bind('session_data', $data);
				$this -> db -> query($sql);
				//$this->db->autoExecute($sesstemp,'UPDATE',"sesskey='".$this->session_id."'");
			} else {
				$sql = 'INSERT INTO ' . $this -> session_data_table . ' (sesskey,expiry,session_data) VALUES (:sesskey,:expiry,:session_data)';
				$this -> db -> Binds = array();
				$this -> db -> bind('sesskey', $this -> session_id);
				$this -> db -> bind('expiry', $this -> _time);
				$this -> db -> bind('session_data', $data);
				$this -> db -> query($sql);
				//$this->db->autoExecute($this->session_data_table,$sesstemp);
			}
			$data = '0';
		}

		$sqlData = array(
			'ip' => $this -> _ip,
			'userid' => $userid,
			'adminid' => $adminid,
			'user_name' => $user_name,
			'user_rank' => $user_rank,
			'discount' => $discount,
			'email' => $email,
			'expiry' => $this -> _time,
			'session_data' => $data
		);

		$sql = "UPDATE {$this -> session_table} SET " . static_base::str4prepare($sqlData) . " where  sesskey=:sesskey";
		$sqlData['sesskey'] = $this -> session_id;
		$this -> db -> prepare($sql) -> execute($sqlData);
		//return $this->db->query('UPDATE ' . $this->session_table . " SET expiry = '" . $this->_time . "', ip = '" . $this->_ip . "', userid = '" . $userid . "', adminid = '" . $adminid . "', user_name='" . $user_name . "', user_rank='" . $user_rank . "', discount='" . $discount . "', email='" . $email . "', session_data = '$data' WHERE sesskey = '" . $this->session_id . "'");
		return true;
	}

	function close_session() {
		$this -> update_session();

		/* 随机对 sessions_data 的库进行删除操作 */
		if (mt_rand(0, 2) == 2) {
			$sql = "
			DELETE FROM {$this -> session_data_table} WHERE expiry < ?";
			$this -> db -> prepare($sql) -> execute(array(date('Y-m-d H:i:s', (strtotime($this -> _time) - $this -> max_life_time))));
		}

		if ((time() % 2) == 0) {
			$sql = '
			DELETE FROM ' . $this -> session_table . ' WHERE expiry < ?';
			return $this -> db -> prepare($sql) -> execute(array(date('Y-m-d H:i:s', (strtotime($this -> _time) - $this -> max_life_time))));
		}

		return true;
	}

	function delete_spec_admin_session($adminid) {
		if (!empty($GLOBALS['_SESSION']['admin_id']) && $adminid) {
			return $this -> db -> query('DELETE FROM ' . $this -> session_table . " WHERE adminid = '$adminid'");
		} else {
			return false;
		}
	}

	function destroy_session() {
		$GLOBALS['_SESSION'] = array();

		setcookie($this -> session_name, $this -> session_id, 1, $this -> session_cookie_path, $this -> session_cookie_domain, $this -> session_cookie_secure);

		/* ECSHOP 自定义执行部分 */
		if (!empty($GLOBALS['ecs'])) {
			$this -> db -> query('DELETE FROM ' . $GLOBALS['ecs'] -> table_oci('cart') . " WHERE session_id = '$this->session_id'");
		}
		/* ECSHOP 自定义执行部分 */

		$this -> db -> query('DELETE FROM ' . $this -> session_data_table . " WHERE sesskey = '" . $this -> session_id . "'");

		return $this -> db -> query('DELETE FROM ' . $this -> session_table . " WHERE sesskey = '" . $this -> session_id . "'");
	}

	function get_session_id() {
		return $this -> session_id;
	}

	function get_users_count() {
		return $this -> db -> getOne('SELECT count(*) FROM ' . $this -> session_table);
	}

	/**
	 * 初始化函數
	 */
	private static function start_mysql(&$db, $session_table, $session_data_table, $session_name = SESSION_ID_NAME, $session_id = '') {
		if (empty(self::$_instance) || !(self::$_instance instanceof self)) {
			self::$_instance = new self($db, $session_table, $session_data_table, $session_name);
		}
	}

	private static function start_memcache() {
		self::$memcache = base_staticobj::memcached();
		self::start_memcache_session();

	}

	private static function start_memcache_session() {
		self::start_redis_session();
	}

	private static function start_redis() {
		self::$redis = framework_static_base::redis();
		self::start_redis_session();

	}

	private static function start_redis_session() {
		$GLOBALS['_SESSION'] = array();
		self::$nowTime = time();
		self::$userIP = static_base::real_ip();
		$arr = $_COOKIE;
		// var_dump($_COOKIE);
		// var_dump($arr[SESSION_NAME]);

		if (is_null(self::$sessionID) && empty($arr[SESSION_NAME])) {
			self::$sessionID = function_exists('com_create_guid') ? md5(self::$userIP . com_create_guid()) : md5(self::$userIP . uniqid(mt_rand(), true));
			self::$sessionID .= self::gen_redis_session_key(self::$sessionID);
			$return = setcookie(SESSION_NAME, self::$sessionID, 0, COOKIE_PATH, COOKIE_DOMAIN, FALSE);
			// var_dump($return);
		} else {
			self::$sessionID = $arr[SESSION_NAME];
		}

		if (!empty(self::$sessionID)) {
			$tmp_session_id = substr(self::$sessionID, 0, 32);
			// var_dump(self::gen_redis_session_key($tmp_session_id));
			// echo "<br />";
			// var_dump(substr(self::$sessionID, 32));			// exit;
			if (self::gen_redis_session_key($tmp_session_id) == substr(self::$sessionID, 32)) {
				self::$sessionID = $tmp_session_id;
			} else {
				self::$sessionID = '';
			}
		}

		if (!empty(self::$sessionID)) {
			self::load_redis_session(self::$sessionType);

			register_shutdown_function(array(
				'base_session',
				'close_redis_session'
			));
		} else {//当session安全校验失败时生成新的session ID
			//exit('Session_Err!');
			//setcookie(SESSION_NAME, '', time()-42000, COOKIE_PATH, COOKIE_DOMAIN, FALSE);
			self::$sessionID = function_exists('com_create_guid') ? md5($userIP . com_create_guid()) : md5($userIP . uniqid(mt_rand(), true));
			self::$sessionID .= self::gen_redis_session_key(self::$sessionID);
			$return = setcookie(SESSION_NAME, self::$sessionID, 0, COOKIE_PATH, COOKIE_DOMAIN, FALSE);
		}
	}

	private static function load_redis_session($sessionType = '') {
		switch($sessionType) {
			case 'redis' :
				$return = self::$redis -> GET(REDIS_ROOT . SESSION_REDIS_TABLE_NAME . self::$sessionID);
				if ($return !== FALSE) {
					$GLOBALS['_SESSION'] = json_decode($return, 1);
				}
				unset($GLOBALS['_SESSION']['__registry']);
				self::$sessionDataTemp = md5(json_encode($GLOBALS['_SESSION']));
				break;
			case "memcache" :
				$cacheName = MEMCACHE_ROOT . SESSION_MEM_TABLE_NAME . self::$sessionID;
				$cacheValue = self::$memcache -> get($cacheName);
				// var_dump($cacheValue);
				// exit;				if ($cacheValue != FALSE)
					$GLOBALS['_SESSION'] = json_decode($cacheValue, 1);
				self::$sessionDataTemp = md5(json_encode($GLOBALS['_SESSION']));
				break;
		}

	}

	private static function update_redis_session() {
		if (!empty($GLOBALS['_SESSION'])) {
			$isChange = 0;
			if (self::$sessionDataTemp != md5(json_encode($GLOBALS['_SESSION'])))
				$isChange = 1;
			if (self::$sessionType == 'redis')
				$GLOBALS['_SESSION']['__registry'] = self::$nowTime;
			switch(self::$sessionType) {
				case "redis" :
					$pipe = self::$redis -> multi(Redis::PIPELINE);
					if ($isChange)
						$pipe -> SETEX(REDIS_ROOT . SESSION_REDIS_TABLE_NAME . self::$sessionID, SESSION_TIMEOUT, json_encode($GLOBALS['_SESSION']));
					else
						$pipe -> EXPIRE(REDIS_ROOT . SESSION_REDIS_TABLE_NAME . self::$sessionID, SESSION_TIMEOUT);
					$pipe -> exec();
					break;
				case "memcache" :
					if (!empty($GLOBALS['_SESSION'])) {
						$return = self::$memcache -> set(MEMCACHE_ROOT . SESSION_MEM_TABLE_NAME . self::$sessionID, json_encode($GLOBALS['_SESSION']), 0, SESSION_TIMEOUT);
					} else {
						$return = self::$memcache -> delete(MEMCACHE_ROOT . SESSION_MEM_TABLE_NAME . self::$sessionID);
					}

					// var_dump($return);
					// exit;
					break;
			}

		}else{
			$return = self::$memcache -> delete(MEMCACHE_ROOT . SESSION_MEM_TABLE_NAME . self::$sessionID);
		}
	}

	private static function gen_redis_session_key($session_id) {
		static $ip = '';

		if ($ip == '') {
			$ip = substr(self::$userIP, 0, strrpos(self::$userIP, '.'));
		}
		
		return sprintf('%08x', crc32(ROOT_PATH . $ip . $session_id));
	}

	public static function close_redis_session() {
		self::update_redis_session();
		return TRUE;
	}

	/**
	 * 初始化函數
	 */
	public static function start() {
		self::$sessionType = SESSION_STORE_TYPE;
		switch (SESSION_STORE_TYPE) {
			case 'redis' :
				self::start_redis();
				break;

			case 'memcache' :
				self::start_memcache();				// $cache = json_decode(MEMCACHE, 1);
				// ini_set('session.name', SESSION_NAME);
				// ini_set('session.save_handler', 'memcache');
				// ini_set('session.save_path', 'tcp://' . $cache[0]['host'] . ':' . $cache[0]['port']);
				// session_start();
				break;

			default :
				// self::start_mysql($db, 'sessions', 'sessions_data');
				break;
		}

	}

}
