<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/PropertyAccessTest.php');

class FirebirdPropertyAccessTest extends PropertyAccessTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
