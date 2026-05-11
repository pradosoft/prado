<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/StatementTest.php');

class FirebirdStatementTest extends StatementTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
