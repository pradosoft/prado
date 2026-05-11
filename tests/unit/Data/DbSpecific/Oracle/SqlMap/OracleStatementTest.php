<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/StatementTest.php');

class OracleStatementTest extends StatementTest
{
	protected static string $configClass = 'OracleBaseTestConfig';
}
