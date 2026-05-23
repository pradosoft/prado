<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/PropertyAccessTest.php');

class MysqlPropertyAccessTest extends PropertyAccessTest
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
