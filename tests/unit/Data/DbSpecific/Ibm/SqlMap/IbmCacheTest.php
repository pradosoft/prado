<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/CacheTest.php');

class IbmCacheTest extends CacheTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
