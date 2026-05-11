<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/DelegateTest.php');

class SqlSrvDelegateTest extends DelegateTest
{
	protected static string $configClass = 'SqlSrvBaseTestConfig';
}
