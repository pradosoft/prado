<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ParameterMapTest.php');

class SqlSrvParameterMapTest extends ParameterMapTest
{
	protected static string $configClass = 'SqlSrvBaseTestConfig';
}
