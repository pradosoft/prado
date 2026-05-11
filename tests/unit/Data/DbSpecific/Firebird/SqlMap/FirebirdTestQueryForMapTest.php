<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/TestQueryForMap.php');

class FirebirdTestQueryForMapTest extends TestQueryForMap
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
