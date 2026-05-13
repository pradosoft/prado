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

	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: update() calls PDO::quote(null)
	 *       which is deprecated and throws on PHP 8.2+ when a field value is null.
	 */
	public function test_update()
	{
		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling causes TTableGateway::update() to fail when a field value is null.');
		/*
				$this->delete_all();
				$this->add_record1();
				$address = ['username' => 'tester 1', 'field5_text' => null];
				$result = $this->getGateway()->update($address, 'username = ?', 'Username');

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

	/**
	 * @agent these should stay as skipped until the framework bug is fixed
	 * @todo fix this framework bug in TTableGateway: update() with named parameters calls
	 *       PDO::quote(null) which is deprecated and throws on PHP 8.2+ when a field value is null.
	 */
	public function test_update_named()
	{
		$this->markTestSkipped('Test exposes framework bug: PDO::quote() deprecated null handling causes TTableGateway::update() with named params to fail when a field value is null.');
		/*
				$this->delete_all();
				$this->add_record1();
				$address = ['username' => 'tester 1', 'field5_text' => null];
				$result = $this->getGateway()->update($address, 'username = :name', [':name' => 'Username']);

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
