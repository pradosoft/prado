<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/TestQueryForMap.php');

class MysqlTestQueryForMapTest extends TestQueryForMap
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
