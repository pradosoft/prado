<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/CacheTest.php');

class PgsqlCacheTest extends CacheTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
