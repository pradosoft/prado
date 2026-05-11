<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/CacheTest.php');

class MysqlCacheTest extends CacheTest
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
