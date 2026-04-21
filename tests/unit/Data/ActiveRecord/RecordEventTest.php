<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/UserRecord.php');

class RecordEventTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		try {
			$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
			$conn->setActive(true);
			TActiveRecordManager::getInstance()->setDbConnection($conn);
		} catch(\Exception $e) {
			if (!PradoUnit::skipDatabaseTests()) {
				throw $e;
			}
			$this->markTestSkipped('Env set PRADO_UNITTEST_SKIP_DB=1 - skip for missing database connection.');
		}
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
