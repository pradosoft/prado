<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ResultClassTest.php');

class IbmResultClassTest extends ResultClassTest
{
	protected static string $configClass = 'IbmBaseTestConfig';
}
