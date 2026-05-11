<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/SelectKeyTest.php');

class OracleSelectKeyTest extends SelectKeyTest
{
	protected static string $configClass = 'OracleBaseTestConfig';
}
