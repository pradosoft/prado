<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/StatementTest.php');

class MysqlStatementTest extends StatementTest
{
	protected static string $configClass = 'MySQLBaseTestConfig';
}
