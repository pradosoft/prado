<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/queryForListLimitTest.php');

class PgsqlQueryForListLimitTest extends queryForListLimitTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
