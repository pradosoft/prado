<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/SelectKeyTest.php');

class IbmSelectKeyTest extends SelectKeyTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
