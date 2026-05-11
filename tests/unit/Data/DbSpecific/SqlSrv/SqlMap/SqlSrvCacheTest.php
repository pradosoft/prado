<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/CacheTest.php');

class SqlSrvCacheTest extends CacheTest
{
	protected static string $configClass = 'SqlSrvBaseTestConfig';
}
