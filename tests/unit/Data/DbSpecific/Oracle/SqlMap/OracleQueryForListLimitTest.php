<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/queryForListLimitTest.php');

class OracleQueryForListLimitTest extends queryForListLimitTest
{
	protected static string $configClass = 'OracleBaseTestConfig';
}
