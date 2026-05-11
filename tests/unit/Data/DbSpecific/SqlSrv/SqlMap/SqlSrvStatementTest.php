<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/StatementTest.php');

class SqlSrvStatementTest extends StatementTest
{
	protected static string $configClass = 'SqlSrvBaseTestConfig';
}
