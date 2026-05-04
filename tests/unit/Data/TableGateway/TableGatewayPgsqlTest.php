<?php

require_once(__DIR__ . '/BaseGateway.php');

class TableGatewayPgsqlTest extends BaseGateway
{
	use PradoUnitDataConnectionTrait;
	
	protected static $pgTableGateway = null;
	
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupPgsqlConnection';
	}
	
	protected function getTestTables(): array
	{
		return ['address'];
	}
	
	protected function setUp(): void
	{
		if (static::$pgTableGateway === null) {
			$conn = $this->setupConnection('prado_unitest');
			if ($conn instanceof TDbConnection) {
				static::$pgTableGateway = new TTableGateway('address', $conn);;
			}
		}
	}
	
	
	//	------- Tests

	public function test_update()
	{
		$this->delete_all();
		$this->add_record1();
		$address = ['username' => 'tester 1', 'field5_text' => null];
		$result = $this->getGateway()->update($address, 'username = ?', 'Username');

		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling');
		/*
				$this->assertTrue($result);

				$test = $this->getGateway()->find('username = ?', 'tester 1');
				unset($test['id']);
				$expect = $this->get_record1();
				$expect['username'] = 'tester 1';
				$expect['field5_text'] = null;
				unset($expect['field7_timestamp']); unset($test['field7_timestamp']);
				$this->assertEquals($expect, $test);

				$this->assertTrue($this->getGateway()->deleteAll('username = ?', 'tester 1'));
		*/
	}

	public function test_update_named()
	{
		$this->delete_all();
		$this->add_record1();
		$address = ['username' => 'tester 1', 'field5_text' => null];
		$result = $this->getGateway()->update($address, 'username = :name', [':name' => 'Username']);

		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling');
		/*
				$this->assertTrue($result);

				$test = $this->getGateway()->find('username = :name', array(':name'=>'tester 1'));
				unset($test['id']);
				$expect = $this->get_record1();
				$expect['username'] = 'tester 1';
				$expect['field5_text'] = null;
				unset($expect['field7_timestamp']); unset($test['field7_timestamp']);
				$this->assertEquals($expect, $test);

				$this->assertTrue($this->getGateway()->deleteAll('username = :name', array(':name'=>'tester 1')));
		*/
	}

	public function test_find_all()
	{
		$this->delete_all();
		$this->add_record1();
		$this->add_record2();

		$results = $this->getGateway()->findAll('true')->readAll();
		$this->assertEquals(count($results), 2);

		$result = $this->getGateway()->findAllBySql('SELECT username FROM address WHERE phone = ?', '45233')->read();
		$this->assertEquals($result['username'], 'record2');
	}
}
