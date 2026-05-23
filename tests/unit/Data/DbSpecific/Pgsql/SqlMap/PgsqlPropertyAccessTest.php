<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/PropertyAccessTest.php');

class PgsqlPropertyAccessTest extends PropertyAccessTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
