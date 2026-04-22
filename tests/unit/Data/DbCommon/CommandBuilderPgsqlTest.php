<?php

use Prado\Data\Common\Pgsql\TPgsqlMetaData;

require_once(__DIR__ . '/../../PradoUnit.php');

class CommandBuilderPgsqlTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;
	
	protected static $pgMetaData = null;
	
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
		if (static::$pgMetaData === null) {
			$conn = $this->setupConnection('prado_unitest');
			if ($conn instanceof TDbConnection) {
				static::$pgMetaData = new TPgsqlMetaData($conn);;
			}
		}
	}
	
	public function getCommandBuilder($table)
	{
		return static::$pgMetaData->createCommandBuilder($table);
	}
	
	
	//	------- Tests

	public function test_insert_command_using_named_array()
	{
		$builder = $this->getCommandBuilder('address');
		$address = [
			'username' => 'Username',
			'phone' => 121987,
			'field1_boolean' => true,
			'field2_date' => '1213',
			'field3_double' => 121.1,
			'field4_integer' => 345,
			'field6_time' => time(),
			'field7_timestamp' => time(),
			'field8_money' => '121.12',
			'field9_numeric' => 984.22,
			'int_fk1' => 1,
			'int_fk2' => 1,
		];
		$insert = $builder->createInsertCommand($address);
		$sql = 'INSERT INTO public.address("username", "phone", "field1_boolean", "field2_date", "field3_double", "field4_integer", "field6_time", "field7_timestamp", "field8_money", "field9_numeric", "int_fk1", "int_fk2") VALUES (:username, :phone, :field1_boolean, :field2_date, :field3_double, :field4_integer, :field6_time, :field7_timestamp, :field8_money, :field9_numeric, :int_fk1, :int_fk2)';
		$this->assertEquals($sql, $insert->Text);
	}

	public function test_update_command()
	{
		$builder = $this->getCommandBuilder('address');
		$data = [
			'phone' => 9809,
			'int_fk1' => 1212,
		];
		$update = $builder->createUpdateCommand($data, '1');
		$sql = 'UPDATE public.address SET "phone" = :phone, "int_fk1" = :int_fk1 WHERE 1';
		$this->assertEquals($sql, $update->Text);
	}

	public function test_delete_command()
	{
		$builder = $this->getCommandBuilder('address');
		$where = 'phone is NULL';
		$delete = $builder->createDeleteCommand($where);
		$sql = 'DELETE FROM public.address WHERE phone is NULL';
		$this->assertEquals($sql, $delete->Text);
	}

	public function test_select_limit()
	{
		$builder = $this->getCommandBuilder('address');
		$query = 'SELECT * FROM ' . $builder->getTableInfo('address')->getTableFullName();

		$limit = $builder->applyLimitOffset($query, 1);
		$expect = $query . ' LIMIT 1';
		$this->assertEquals($expect, $limit);

		$limit = $builder->applyLimitOffset($query, -1, 10);
		$expect = $query . ' OFFSET 10';
		$this->assertEquals($expect, $limit);

		$limit = $builder->applyLimitOffset($query, 2, 3);
		$expect = $query . ' LIMIT 2 OFFSET 3';
		$this->assertEquals($expect, $limit);
	}
}
