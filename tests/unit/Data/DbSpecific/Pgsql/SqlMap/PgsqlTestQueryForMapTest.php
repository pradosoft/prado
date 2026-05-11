<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/TestQueryForMap.php');

class PgsqlTestQueryForMapTest extends TestQueryForMap
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
