<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/SelectKeyTest.php');

class PgsqlSelectKeyTest extends SelectKeyTest
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
