<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/queryForListLimitTest.php');

class IbmQueryForListLimitTest extends queryForListLimitTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
