<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ParameterMapTest.php');

class FirebirdParameterMapTest extends ParameterMapTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
