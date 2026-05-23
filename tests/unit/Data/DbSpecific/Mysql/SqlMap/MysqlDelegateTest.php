<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/DelegateTest.php');

class MysqlDelegateTest extends DelegateTest
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
