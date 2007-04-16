<?php
Prado::using('System.Data.*');
Prado::using('System.Data.DataGateway.TTableGateway');

class BaseGatewayTest extends UnitTestCase
{
	protected $gateway1;
	protected $gateway2;

	/**
	 * @return TTableGateway
	 */
	function getGateway()
	{
		if($this->gateway1===null)
		{
			$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
			$this->gateway1 = new TTableGateway('address', $conn);
		}
		return $this->gateway1;
	}

	/**
	 * @return TTableGateway
	 */
	function getGateway2()
	{
		if($this->gateway2===null)
		{
			$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
			$this->gateway2 = new TTableGateway('department_sections', $conn);
		}
		return $this->gateway2;
	}

	function setup()
	{
		$this->delete_all();
	}

	function add_record1()
	{
		$result = $this->getGateway()->insert($this->get_record1());
		$this->assertTrue(intval($result) > 0);
	}
	function add_record2()
	{
		$result = $this->getGateway()->insert($this->get_record2());
		$this->assertTrue(intval($result) > 0);
	}
	function get_record1()
	{
		return array(
			'username' => 'Username',
			'phone' => 121987,
			'field1_boolean' => true,
			'field2_date' => '2007-12-25',
			'field3_double' => 121.1,
			'field4_integer' => 3,
			'field5_text' => 'asdasd',
			'field6_time' => '12:40:00',
			'field7_timestamp' => 'NOW',
			'field8_money' => '$121.12',
			'field9_numeric' => 98.2232,
			'int_fk1'=>1,
			'int_fk2'=>1,
		);
	}


	function get_record2()
	{
		return array(
			'username' => 'record2',
			'phone' => 45233,
			'field1_boolean' => false,
			'field2_date' => '2004-10-05',
			'field3_double' => 1221.1,
			'field4_integer' => 2,
			'field5_text' => 'hello world',
			'field6_time' => '22:40:00',
			'field7_timestamp' => 'NOW',
			'field8_money' => '$1121.12',
			'field9_numeric' => 8.2213,
			'int_fk1'=>1,
			'int_fk2'=>1,
		);
	}
	function delete_all()
	{
		$this->getGateway()->deleteAll('1=1');
	}
}
?>