<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/StatementTest.php');

class PgsqlStatementTest extends StatementTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
