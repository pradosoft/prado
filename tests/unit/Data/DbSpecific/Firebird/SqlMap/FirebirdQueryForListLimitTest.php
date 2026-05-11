<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/queryForListLimitTest.php');

class FirebirdQueryForListLimitTest extends queryForListLimitTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
