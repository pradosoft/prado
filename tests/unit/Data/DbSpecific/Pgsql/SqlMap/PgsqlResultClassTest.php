<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ResultClassTest.php');

class PgsqlResultClassTest extends ResultClassTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
