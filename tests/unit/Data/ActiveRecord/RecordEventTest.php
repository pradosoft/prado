<?php

require_once(__DIR__ . '/records/UserRecord.php');

class RecordEventTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	public function testFindByPk()
	{
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);
	}

	public function logger($sender, $param)
	{
		//var_dump($param);
	}
}
