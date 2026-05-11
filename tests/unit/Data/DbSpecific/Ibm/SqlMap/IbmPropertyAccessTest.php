<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/PropertyAccessTest.php');

class IbmPropertyAccessTest extends PropertyAccessTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
