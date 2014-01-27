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

$act = isset($_GET['act']) ? $_GET['act'] : '';
if (empty($act))
	exit('get act code err!');

$st = isset($_GET['st']) ? $_GET['st'] : '';
if (empty($st))
	exit('get st code err!');

$tmpObjName = 'act_' . $act;
$tmpObjName::$st();
?>