<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/SelectKeyTest.php');

class SqlSrvSelectKeyTest extends SelectKeyTest
{
	protected static string $configClass = 'SqlSrvBaseTestConfig';
}
