<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\TDbTableColumn;

class IbmColumnTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static $ibmMetaData = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
	}

	protected function getDatabaseName(): ?string
	{
		// Database name resolved inside setupIbmConnection via DB2_DATABASE env var.
		return null;
	}

	protected function getTestTables(): array
	{
		return ['table1'];
	}

	protected function setUp(): void
	{
		if (static::$ibmMetaData === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				static::$ibmMetaData = new TIbmMetaData($conn);
			}
		}
	}

	public function create_meta_data(): TIbmMetaData
	{
		return static::$ibmMetaData;
	}

	public function test_columns()
	{
		$meta = $this->create_meta_data();
		$table = $meta->getTableInfo('table1');

		// Schema: see tests/initdb_ibm.sql
		$this->assertCount(14, $table->getColumns());
		// TIbmMetaData always resolves the schema to the DB2 current schema (e.g. the DB2 user name)
		$this->assertNotNull($table->getSchemaName());
		$this->assertEquals('TABLE1', $table->getTableName());
		$this->assertEquals(['id'], $table->getPrimaryKeys());

		$columns = [];

		$columns['id'] = [
			'ColumnName'   => '"ID"',
			'ColumnIndex'  => 0,
			'DbType'       => 'integer',
			'AllowNull'    => false,
			'IsPrimaryKey' => true,
			'IsForeignKey' => false,
			'AutoIncrement' => true,
		];

		$columns['name'] = [
			'ColumnName'   => '"NAME"',
			'ColumnIndex'  => 1,
			'DbType'       => 'varchar',
			'ColumnSize'   => 45,
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'AutoIncrement' => false,
		];

		$columns['field1_smallint'] = [
			'ColumnName'   => '"FIELD1_SMALLINT"',
			'ColumnIndex'  => 2,
			'DbType'       => 'smallint',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'AutoIncrement' => false,
		];

		$columns['field2_varchar'] = [
			'ColumnName'   => '"FIELD2_VARCHAR"',
			'ColumnIndex'  => 3,
			'DbType'       => 'varchar',
			'ColumnSize'   => 4000,
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field3_date'] = [
			'ColumnName'   => '"FIELD3_DATE"',
			'ColumnIndex'  => 4,
			'DbType'       => 'date',
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field5_decimal'] = [
			'ColumnName'       => '"FIELD5_DECIMAL"',
			'ColumnIndex'      => 6,
			'DbType'           => 'decimal',
			'AllowNull'        => false,
			'NumericPrecision' => 10,
			'NumericScale'     => 4,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
		];

		$columns['field9_bigint'] = [
			'ColumnName'   => '"FIELD9_BIGINT"',
			'ColumnIndex'  => 10,
			'DbType'       => 'bigint',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field10_char'] = [
			'ColumnName'   => '"FIELD10_CHAR"',
			'ColumnIndex'  => 11,
			// DB2 SYSCAT.COLUMNS reports CHAR as TYPENAME='CHARACTER'
			'DbType'       => 'character',
			'ColumnSize'   => 10,
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field11_boolean'] = [
			'ColumnName'   => '"FIELD11_BOOLEAN"',
			'ColumnIndex'  => 12,
			'DbType'       => 'boolean',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field12_numeric'] = [
			'ColumnName'       => '"FIELD12_NUMERIC"',
			'ColumnIndex'      => 13,
			// DB2: NUMERIC is a synonym for DECIMAL; SYSCAT reports TYPENAME='DECIMAL'
			'DbType'           => 'decimal',
			'AllowNull'        => false,
			'NumericPrecision' => 8,
			'NumericScale'     => 2,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
		];

		$this->assertColumn($columns, $table);
	}

	public function test_schema_name()
	{
		$meta = $this->create_meta_data();
		$schema = $meta->getDefaultSchema();
		$table = $meta->getTableInfo($schema . '.table1');

		$this->assertEquals(strtoupper($schema), $table->getSchemaName());
		$this->assertEquals('TABLE1', $table->getTableName());
		$this->assertEquals('"' . strtoupper($schema) . '"."TABLE1"', $table->getTableFullName());
	}

	public function test_find_table_names()
	{
		$meta = $this->create_meta_data();
		$names = $meta->findTableNames();

		$this->assertContains('table1', $names);
		$this->assertContains('address', $names);
	}

	public function test_command_builder_insert()
	{
		$meta = $this->create_meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$data = ['name' => 'test_insert', 'field1_smallint' => 1, 'field9_bigint' => 100];
		$insert = $builder->createInsertCommand($data);
		$this->assertStringContainsString('INSERT INTO', $insert->Text);
		$this->assertStringContainsString('"TABLE1"', $insert->Text);
	}

	public function test_select_limit()
	{
		$meta = $this->create_meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$query = 'SELECT * FROM ' . $meta->getTableInfo('table1')->getTableFullName();

		$limit = $builder->applyLimitOffset($query, 1);
		$this->assertEquals($query . ' FETCH FIRST 1 ROWS ONLY', $limit);

		$limit = $builder->applyLimitOffset($query, 5, 0);
		$this->assertEquals($query . ' FETCH FIRST 5 ROWS ONLY', $limit);

		$limit = $builder->applyLimitOffset($query, 5, 10);
		$this->assertStringContainsString('ROW_NUMBER() OVER()', $limit);
		$this->assertStringContainsString('BETWEEN 11 AND 15', $limit);
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
