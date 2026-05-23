<?php

require_once(__DIR__ . '/BaseGateway.php');

/**
 * MysqlTableGatewayFindByPkTest — TTableGateway::findByPk() and findAllByPks() for MySQL.
 *
 * Migrated from tests/unit/Data/TableGateway/TestFindByPk.php (renamed so that
 * phpunit's suffix="Test.php" scanner discovers this file).
 */
class MysqlTableGatewayFindByPkTest extends TTableGatewayTestBase
{
	public function test_one_key()
	{
		$this->add_record1();
		// address PK is `username` (VARCHAR) — no AUTO_INCREMENT sequence,
		// so getLastInsertId() returns null.  Use the known PK value directly.
		$id = $this->get_record1()['username'];
		$result = $this->getGateway()->findByPk($id);

		$record1 = $this->get_record1();

		// Normalise types: MySQL PDO returns all scalars as strings; NUMERIC without
		// explicit scale stores only the integer part.  Cast both sides uniformly so
		// assertEquals does a meaningful value comparison.
		unset($result['field7_timestamp']);
		unset($record1['field7_timestamp']);
		$result['phone']          = (int)   $result['phone'];
		$result['field1_boolean'] = (bool)  $result['field1_boolean'];
		$result['field3_double']  = (float) $result['field3_double'];
		$result['field4_integer'] = (int)   $result['field4_integer'];
		$result['field8_money']   = (float) $result['field8_money'];
		$record1['field8_money']  = (float) $record1['field8_money'];
		// NUMERIC without scale truncates to integer on MySQL
		$result['field9_numeric']  = (int) $result['field9_numeric'];
		$record1['field9_numeric'] = (int) $record1['field9_numeric'];
		$result['int_fk1'] = (int) $result['int_fk1'];
		$result['int_fk2'] = (int) $result['int_fk2'];

		$this->assertEquals($record1, $result);
	}

	public function test_composite_key()
	{
		$gateway = $this->getGateway2();

		$result = $gateway->findByPk(1, 1);
		// Seeded row: (department_id=1, section_id=1, order=1) — see tests/initdb_mysql.sql
		$expect = ["department_id" => 1, "section_id" => 1, "order" => 1];
		$this->assertEquals($expect, $result);
	}

	public function test_find_all_keys()
	{
		$gateway = $this->getGateway2();

		// Use two rows that both exist in the seed: (1,1,1) and (1,2,2)
		$result = $gateway->findAllByPks([1, 1], [1, 2])->readAll();

		$expect = [
			["department_id" => 1, "section_id" => 1, "order" => 1],
			["department_id" => 1, "section_id" => 2, "order" => 2]];

		$this->assertEquals($expect, $result);
	}
}
