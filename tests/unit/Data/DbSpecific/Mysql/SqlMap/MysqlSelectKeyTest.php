<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/SelectKeyTest.php');

class MysqlSelectKeyTest extends SelectKeyTest
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
