<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/GroupByTest.php');

class FirebirdGroupByTest extends GroupByTest
{
	protected static string $configClass = 'FirebirdBaseTestConfig';
}
