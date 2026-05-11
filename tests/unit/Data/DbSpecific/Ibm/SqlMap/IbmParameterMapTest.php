<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ParameterMapTest.php');

class IbmParameterMapTest extends ParameterMapTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
