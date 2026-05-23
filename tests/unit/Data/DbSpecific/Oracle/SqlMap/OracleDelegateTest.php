<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/DelegateTest.php');

class OracleDelegateTest extends DelegateTest
{
	protected static string $configClass = 'OracleBaseTestConfig';
}
