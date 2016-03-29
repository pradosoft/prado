<?php
require_once(dirname(__FILE__).'/BaseGateway.php');

/**
 * @package System.Data.TableGateway
 */
class TableGatewayPgsqlTest extends BaseGateway
{
    public function setUp()
    {
        if (!extension_loaded('pgsql')) {
            $this->markTestSkipped(
              'The pgsql extension is not available.'
            );
        }
    }

	function test_update()
	{
		$this->add_record1();
		$address = array('username' => 'tester 1', 'field5_text'=>null);
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
	}

	function test_update_named()
	{
		$this->add_record1();
		$address = array('username' => 'tester 1', 'field5_text'=>null);
		$result = $this->getGateway()->update($address, 'username = :name', array(':name'=>'Username'));
		$this->assertTrue($result);

		$test = $this->getGateway()->find('username = :name', array(':name'=>'tester 1'));
		unset($test['id']);
		$expect = $this->get_record1();
		$expect['username'] = 'tester 1';
		$expect['field5_text'] = null;
		unset($expect['field7_timestamp']); unset($test['field7_timestamp']);
		$this->assertEquals($expect, $test);

		$this->assertTrue($this->getGateway()->deleteAll('username = :name', array(':name'=>'tester 1')));
	}

	function test_find_all()
	{
		$this->add_record1();
		$this->add_record2();

		$results = $this->getGateway()->findAll('true')->readAll();
		$this->assertEquals(count($results), 2);

		$result = $this->getGateway()->findAllBySql('SELECT username FROM address WHERE phone = ?', '45233')->read();
		$this->assertEquals($result['username'], 'record2');
	}

}