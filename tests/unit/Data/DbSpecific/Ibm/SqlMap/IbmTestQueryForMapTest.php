<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/TestQueryForMap.php');

class IbmTestQueryForMapTest extends TestQueryForMap
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
