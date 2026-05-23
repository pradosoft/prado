<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/GroupByTest.php');

class OracleGroupByTest extends GroupByTest
{
	protected static string $configClass = 'OracleBaseTestConfig';
}
