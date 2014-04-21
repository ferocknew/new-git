<?php
require_once ('pclzip.lib.php');
$archive = new PclZip("archive.zip");
$v_list = $archive -> create('./');
