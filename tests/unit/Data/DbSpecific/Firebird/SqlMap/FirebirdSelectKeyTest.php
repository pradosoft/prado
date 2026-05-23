<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/SelectKeyTest.php');

class FirebirdSelectKeyTest extends SelectKeyTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
