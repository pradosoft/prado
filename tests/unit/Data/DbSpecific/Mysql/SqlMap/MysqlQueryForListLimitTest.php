<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/queryForListLimitTest.php');

class MysqlQueryForListLimitTest extends queryForListLimitTest
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
