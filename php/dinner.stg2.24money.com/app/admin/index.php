<?php
/*!
 * CMShop
 * https://code.google.com/p/cmshop-php/
 *
 * Copyright 2012
 * @author	Jonah.Fu (JianZhe)
 * @author	Wind.Wang
 * @author	doocal
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * https://code.google.com/p/cmshop-php/
 *
 * Date: Wed Aug 22 16:14:11 2012 +0800
 */
define('IN_CMSHOP', TRUE);
require (dirname(__FILE__) . '/inc.php');
base_cmshop::smarty() -> assign('shop_url', "..");
base_cmshop::smarty() -> display('index.html');
?>