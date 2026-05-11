<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/queryForListLimitTest.php');

class SqlSrvQueryForListLimitTest extends queryForListLimitTest
{
	protected static string $configClass = 'SqlSrvBaseTestConfig';
}
