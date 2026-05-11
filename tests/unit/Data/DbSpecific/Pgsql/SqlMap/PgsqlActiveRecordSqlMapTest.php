<?php

require_once(__DIR__ . '/../../../SqlMap/common.php');
require_once(__DIR__ . '/../../../SqlMap/ActiveRecordSqlMapTestCase.php');

class PgsqlActiveRecordSqlMapTest extends ActiveRecordSqlMapTestCase
{
	protected static string $configClass = 'PgsqlBaseTestConfig';
}
