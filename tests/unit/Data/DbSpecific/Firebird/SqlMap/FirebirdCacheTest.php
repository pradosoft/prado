<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/CacheTest.php');

class FirebirdCacheTest extends CacheTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
