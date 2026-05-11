<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/DelegateTest.php');

class FirebirdDelegateTest extends DelegateTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
