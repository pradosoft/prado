<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/InheritanceTest.php');

class PgsqlInheritanceTest extends InheritanceTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
