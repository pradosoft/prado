<?php

require_once(__DIR__ . '/../../PradoUnit.php');
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

class BaseGateway extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static $conn = null;
	protected $gateway1;
	protected $gateway2;

	/**
	 * @return TTableGateway
	 */
	public function getGateway()
	{
		if ($this->gateway1 === null) {
			$this->gateway1 = new TTableGateway('address', static::$conn);
		}
		return $this->gateway1;
	}

	/**
	 * @todo 
	 * @return TTableGateway
	 */
	public function getGateway2()
	{
		if ($this->gateway2 === null) {
			$this->gateway2 = new TTableGateway('department_sections', static::$conn);
		}
		return $this->gateway2;
	}
	
	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}
	
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMysqlConnection';
	}
	
	protected function getIsForActiveRecord(): bool
	{
		return false;
	}
	
	protected function getTestTables(): array
	{
		return ['address', 'department_sections'];
	}
	
	protected function setUp(): void
	{
		if (!static::$conn) {
			static::$conn = $this->setupConnection();
			$this->gateway1 = new TTableGateway('address', static::$conn);
		}
	}

	protected function tearDown(): void
	{
		$this->delete_all();
	}

	public function add_record1()
	{
		$result = $this->getGateway()->insert($this->get_record1());
		$this->assertTrue((int) $result > 0);
	}
	public function add_record2()
	{
		$result = $this->getGateway()->insert($this->get_record2());
		$this->assertTrue((int) $result > 0);
	}
	public function get_record1()
	{
		return [
			'username' => 'Username',
			'phone' => 121987,
			'field1_boolean' => true,
			'field2_date' => '2007-12-25',
			'field3_double' => 121.1,
			'field4_integer' => 3,
			'field5_text' => 'asdasd',
			'field6_time' => '12:40:00',
			'field7_timestamp' => '2007-12-25 12:40:00',
			'field8_money' => '121.12',
			'field9_numeric' => 98.2232,
			'int_fk1' => 1,
			'int_fk2' => 1,
		];
	}


	public function get_record2()
	{
		return [
			'username' => 'record2',
			'phone' => 45233,
			'field1_boolean' => false,
			'field2_date' => '2004-10-05',
			'field3_double' => 1221.1,
			'field4_integer' => 2,
			'field5_text' => 'hello world',
			'field6_time' => '22:40:00',
			'field7_timestamp' => '2004-10-05 22:40:00',
			'field8_money' => '1121.12',
			'field9_numeric' => 8.2213,
			'int_fk1' => 1,
			'int_fk2' => 1,
		];
	}
	public function delete_all()
	{
		$this->getGateway()->deleteAll('1=1');
	}
}
