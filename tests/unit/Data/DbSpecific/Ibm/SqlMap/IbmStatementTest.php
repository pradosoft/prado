<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/StatementTest.php');

class IbmStatementTest extends StatementTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
