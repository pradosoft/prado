<?php

require_once(__DIR__ . '/BaseGateway.php');

class TableGatewayPgsqlTest extends BaseGateway
{
	protected function setUp(): void
	{
		if (!extension_loaded('pdo_pgsql')) {
			$this->markTestSkipped(
				'The pdo_pgsql extension is not available.'
			);
		}
	}

	public function test_update()
	{
		$this->delete_all();
		$this->add_record1();
		$address = ['username' => 'tester 1', 'field5_text' => null];
		$result = $this->getGateway()->update($address, 'username = ?', 'Username');

		$this->markTestSkipped('Needs fixing');
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

		$this->markTestSkipped('Needs fixing');
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
