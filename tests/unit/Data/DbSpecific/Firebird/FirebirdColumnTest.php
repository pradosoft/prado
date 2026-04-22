<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Firebird\TFirebirdMetaData;
use Prado\Data\Common\TDbTableColumn;

class FirebirdColumnTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static $fbMetaData = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getTestTables(): array
	{
		return ['table1'];
	}

	protected function setUp(): void
	{
		if (static::$fbMetaData === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				static::$fbMetaData = new TFirebirdMetaData($conn);
			}
		}
	}

	public function test_columns()
	{
		$table = static::$fbMetaData->getTableInfo('table1');

		// Schema: see tests/initdb_firebird.sql
		// Firebird stores unquoted names as uppercase; TFirebirdMetaData lowercases them.
		$this->assertCount(13, $table->getColumns());
		$this->assertNull($table->getSchemaName()); // Firebird has no schema
		$this->assertEquals('TABLE1', $table->getTableName());
		$this->assertEquals(['id'], $table->getPrimaryKeys());
		$this->assertEquals('"TABLE1"', $table->getTableFullName());

		$columns = [];

		$columns['id'] = [
			'ColumnName'    => '"ID"',
			'ColumnIndex'   => 0,
			// IDENTITY columns in Firebird 3 are stored as INTEGER (type code 8)
			'DbType'        => 'INTEGER',
			'AllowNull'     => false,
			'IsPrimaryKey'  => true,
			'IsForeignKey'  => false,
			'AutoIncrement' => true,
		];

		$columns['name'] = [
			'ColumnName'   => '"NAME"',
			'ColumnIndex'  => 1,
			'DbType'       => 'VARCHAR',
			'ColumnSize'   => 45,
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field1_smallint'] = [
			'ColumnName'   => '"FIELD1_SMALLINT"',
			'ColumnIndex'  => 2,
			'DbType'       => 'SMALLINT',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field2_varchar'] = [
			'ColumnName'   => '"FIELD2_VARCHAR"',
			'ColumnIndex'  => 3,
			'DbType'       => 'VARCHAR',
			'ColumnSize'   => 255,
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field3_date'] = [
			'ColumnName'   => '"FIELD3_DATE"',
			'ColumnIndex'  => 4,
			'DbType'       => 'DATE',
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field5_double'] = [
			'ColumnName'   => '"FIELD5_DOUBLE"',
			'ColumnIndex'  => 6,
			// DOUBLE PRECISION is isPrecisionType, so ColumnSize is not set
			'DbType'       => 'DOUBLE PRECISION',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field6_timestamp'] = [
			'ColumnName'   => '"FIELD6_TIMESTAMP"',
			'ColumnIndex'  => 7,
			'DbType'       => 'TIMESTAMP',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field7_time'] = [
			'ColumnName'   => '"FIELD7_TIME"',
			'ColumnIndex'  => 8,
			'DbType'       => 'TIME',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field8_bigint'] = [
			'ColumnName'   => '"FIELD8_BIGINT"',
			'ColumnIndex'  => 9,
			'DbType'       => 'BIGINT',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field10_boolean'] = [
			'ColumnName'   => '"FIELD10_BOOLEAN"',
			'ColumnIndex'  => 11,
			'DbType'       => 'BOOLEAN',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field11_blob'] = [
			'ColumnName'   => '"FIELD11_BLOB"',
			'ColumnIndex'  => 12,
			// BLOB SUB_TYPE TEXT (sub_type=1) maps to 'TEXT'
			'DbType'       => 'TEXT',
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$this->assertColumn($columns, $table);
	}

	public function test_find_table_names()
	{
		$names = static::$fbMetaData->findTableNames();

		$this->assertContains('table1', $names);
		$this->assertContains('address', $names);
	}

	public function test_command_builder_insert()
	{
		$builder = static::$fbMetaData->createCommandBuilder('table1');

		$data = ['name' => 'test_insert', 'field4_float' => 1.5, 'field5_double' => 2.5,
			'field6_timestamp' => '2024-01-01 00:00:00', 'field7_time' => '00:00:00',
			'field8_bigint' => 100, 'field10_boolean' => false];
		$insert = $builder->createInsertCommand($data);
		$this->assertStringContainsString('INSERT INTO', $insert->Text);
		$this->assertStringContainsString('"TABLE1"', $insert->Text);
	}

	public function test_select_limit()
	{
		$builder = static::$fbMetaData->createCommandBuilder('table1');
		$fullName = static::$fbMetaData->getTableInfo('table1')->getTableFullName();

		$query = 'SELECT * FROM ' . $fullName;

		$limit = $builder->applyLimitOffset($query, 1);
		$this->assertEquals('SELECT FIRST 1 * FROM ' . $fullName, $limit);

		$limit = $builder->applyLimitOffset($query, -1, 5);
		$this->assertEquals('SELECT SKIP 5 * FROM ' . $fullName, $limit);

		$limit = $builder->applyLimitOffset($query, 10, 5);
		$this->assertEquals('SELECT FIRST 10 SKIP 5 * FROM ' . $fullName, $limit);
	}

	public function assertColumn($columns, $table)
	{
		foreach ($columns as $id => $asserts) {
			$column = $table->Columns[$id];
			foreach ($asserts as $property => $assert) {
				$ofAssert = var_export($assert, true);
				$value = $column->{$property};
				$ofValue = var_export($value, true);
				$this->assertEquals(
					$value,
					$assert,
					"Column [{$id}] {$property} value {$ofValue} did not match {$ofAssert}"
				);
			}
		}
	}
}
