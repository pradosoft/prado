<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/PropertyAccessTest.php');

class OraclePropertyAccessTest extends PropertyAccessTest
{
	protected static string $configClass = 'OracleBaseTestConfig';
}
