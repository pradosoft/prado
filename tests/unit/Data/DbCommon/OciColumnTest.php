<?php

use Prado\Data\Common\Oracle\TOracleMetaData;

class OciColumnTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!extension_loaded('pdo_oci')) {
			$this->markTestSkipped('The pdo_oci extension is not available.');
		}
	}

	public function create_meta_data()
	{
		// Service name is the Oracle pluggable database (PDB) identifier.
		// gvenzl/oracle-free (CI / Docker):  FREEPDB1
		// Oracle XE local install:            XEPDB1
		// Override via ORACLE_SERVICE_NAME env var for other installations.
		$serviceName = getenv('ORACLE_SERVICE_NAME') ?: 'FREEPDB1';
		$conn = new TDbConnection(
			'oci:dbname=//localhost:1521/' . $serviceName,
			'prado_unitest',
			'prado_unitest'
		);
		$meta = new TOracleMetaData($conn);
		$meta->setDefaultSchema('PRADO_UNITEST');
		return $meta;
	}

	public function test_columns()
	{
		$meta = $this->create_meta_data();
		try {
			$table = $meta->getTableInfo('table1');
		} catch (\Exception $e) {
			$this->fail('Cannot connect to Oracle: ' . $e->getMessage());
		}

		// Schema: see tests/initdb_oracle.sql
		// TOracleMetaData uses lowercase column IDs; ColumnName is NOT quoted (by design).
		$this->assertCount(11, $table->getColumns());
		$this->assertEquals('PRADO_UNITEST', $table->getSchemaName());
		$this->assertEquals('table1', $table->getTableName());
		$this->assertEquals(['id'], $table->getPrimaryKeys());
		$this->assertEquals('PRADO_UNITEST.table1', $table->getTableFullName());

		$columns = [];

		$columns['id'] = [
			'ColumnName'       => 'id',
			'ColumnIndex'      => 0,
			// NUMBER(10) → SQL type = 'NUMBER(10,0)'; parens stripped → DbType='NUMBER'
			'DbType'           => 'NUMBER',
			'AllowNull'        => false,
			'IsPrimaryKey'     => true,
			'IsForeignKey'     => false,
			'NumericPrecision' => 10,
			'NumericScale'     => 0,
		];

		$columns['name'] = [
			'ColumnName'   => 'name',
			'ColumnIndex'  => 1,
			'DbType'       => 'VARCHAR2',
			'AllowNull'    => false,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			// ColumnSize = DATA_LENGTH = 45
			'ColumnSize'   => 45,
		];

		$columns['field1_number'] = [
			'ColumnName'       => 'field1_number',
			'ColumnIndex'      => 2,
			'DbType'           => 'NUMBER',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'NumericPrecision' => 5,
			'NumericScale'     => 0,
		];

		$columns['field2_varchar2'] = [
			'ColumnName'   => 'field2_varchar2',
			'ColumnIndex'  => 3,
			'DbType'       => 'VARCHAR2',
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'ColumnSize'   => 255,
		];

		$columns['field3_date'] = [
			'ColumnName'   => 'field3_date',
			'ColumnIndex'  => 4,
			'DbType'       => 'DATE',
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
		];

		$columns['field5_number_ps'] = [
			'ColumnName'       => 'field5_number_ps',
			'ColumnIndex'      => 6,
			'DbType'           => 'NUMBER',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'NumericPrecision' => 10,
			'NumericScale'     => 4,
		];

		$columns['field6_char'] = [
			'ColumnName'   => 'field6_char',
			'ColumnIndex'  => 7,
			'DbType'       => 'CHAR',
			'AllowNull'    => true,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'ColumnSize'   => 10,
		];

		$columns['field9_number2'] = [
			'ColumnName'       => 'field9_number2',
			'ColumnIndex'      => 10,
			'DbType'           => 'NUMBER',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'NumericPrecision' => 8,
			'NumericScale'     => 2,
		];

		$this->assertColumn($columns, $table);
	}

	public function test_find_table_names()
	{
		$meta = $this->create_meta_data();
		try {
			$names = $meta->findTableNames('PRADO_UNITEST');
		} catch (\Exception $e) {
			$this->fail('Cannot connect to Oracle: ' . $e->getMessage());
		}

		// findTableNames returns uppercase table names for Oracle
		$this->assertContains('TABLE1', $names);
		$this->assertContains('ADDRESS', $names);
	}

	public function test_command_builder_insert()
	{
		$meta = $this->create_meta_data();
		try {
			$builder = $meta->createCommandBuilder('table1');
		} catch (\Exception $e) {
			$this->fail('Cannot connect to Oracle: ' . $e->getMessage());
		}

		$data = ['name' => 'test', 'field1_number' => 1, 'field4_float' => 1.0,
			'field5_number_ps' => 1.0, 'field8_timestamp' => '2024-01-01 00:00:00',
			'field9_number2' => 1.0];
		$insert = $builder->createInsertCommand($data);
		$this->assertStringContainsString('INSERT INTO', $insert->Text);
	}

	public function test_select_limit_and_offset()
	{
		$meta = $this->create_meta_data();
		try {
			$builder = $meta->createCommandBuilder('table1');
		} catch (\Exception $e) {
			$this->fail('Cannot connect to Oracle: ' . $e->getMessage());
		}

		$query = 'SELECT id FROM table1';

		$result = $builder->applyLimitOffset($query, 5, 10);
		$this->assertStringContainsString('ROW_NUMBER() OVER', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN >= 10', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN < 15', $result);
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
