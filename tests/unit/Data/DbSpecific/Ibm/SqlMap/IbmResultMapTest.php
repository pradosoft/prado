<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ResultMapTest.php');

class IbmResultMapTest extends ResultMapTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
