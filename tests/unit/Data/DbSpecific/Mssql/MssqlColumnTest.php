<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Mssql\TMssqlMetaData;
use Prado\Data\Common\TDbTableColumn;
use Prado\Data\DataGateway\TTableGateway;

class MssqlColumnTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static $msConn = null;
	protected static $msMetaData = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMssqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getTestTables(): array
	{
		return ['table1'];
	}

	protected function setUp(): void
	{
		if (static::$msConn === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				static::$msConn = $conn;
				static::$msMetaData = new TMssqlMetaData($conn);
			}
		}
	}

	public function get_conn(): TDbConnection
	{
		return static::$msConn;
	}

	public function meta_data(): TMssqlMetaData
	{
		return static::$msMetaData;
	}

	public function test_columns()
	{
		$table = $this->meta_data()->getTableInfo('table1');

		// Schema: see tests/initdb_mssql.sql
		$this->assertCount(13, $table->getColumns());
		$this->assertEquals('dbo', $table->getSchemaName());
		$this->assertEquals('prado_unitest', $table->getCatalogName());
		$this->assertEquals('table1', $table->getTableName());
		$this->assertEquals('[prado_unitest].[dbo].[table1]', $table->getTableFullName());
		$this->assertEquals(['id'], $table->getPrimaryKeys());

		$columns = [];

		$columns['id'] = [
			'ColumnName'       => '[id]',
			'ColumnIndex'      => 0,
			'DbType'           => 'int',
			'AllowNull'        => false,
			'IsPrimaryKey'     => true,
			'IsForeignKey'     => false,
			'AutoIncrement'    => true,
			// INFORMATION_SCHEMA reports NUMERIC_PRECISION=10, NUMERIC_SCALE=0 for INT
			'NumericPrecision' => 10,
			'NumericScale'     => 0,
		];

		$columns['name'] = [
			'ColumnName'    => '[name]',
			'ColumnIndex'   => 1,
			'DbType'        => 'nvarchar',
			'AllowNull'     => false,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'ColumnSize'    => 45,
		];

		$columns['field1_tiny'] = [
			'ColumnName'       => '[field1_tiny]',
			'ColumnIndex'      => 2,
			'DbType'           => 'tinyint',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			// MSSQL wraps DEFAULT values in double parentheses: ((0))
			'DefaultValue'     => '((0))',
			'NumericPrecision' => 3,
			'NumericScale'     => 0,
		];

		$columns['field2_text'] = [
			'ColumnName'    => '[field2_text]',
			'ColumnIndex'   => 3,
			'DbType'        => 'nvarchar',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			// NVARCHAR(MAX) reports CHARACTER_MAXIMUM_LENGTH = -1
			'ColumnSize'    => -1,
		];

		$columns['field3_date'] = [
			'ColumnName'    => '[field3_date]',
			'ColumnIndex'   => 4,
			'DbType'        => 'date',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
		];

		$columns['field4_float'] = [
			'ColumnName'       => '[field4_float]',
			'ColumnIndex'      => 5,
			'DbType'           => 'float',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => '((10))',
			// FLOAT without size → NUMERIC_PRECISION=53
			'NumericPrecision' => 53,
		];

		$columns['field5_dec'] = [
			'ColumnName'       => '[field5_dec]',
			'ColumnIndex'      => 6,
			'DbType'           => 'decimal',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => '((0))',
			'NumericPrecision' => 10,
			'NumericScale'     => 4,
		];

		$columns['field6_int'] = [
			'ColumnName'       => '[field6_int]',
			'ColumnIndex'      => 7,
			'DbType'           => 'int',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => '((0))',
			'NumericPrecision' => 10,
			'NumericScale'     => 0,
		];

		$columns['field7_dt'] = [
			'ColumnName'    => '[field7_dt]',
			'ColumnIndex'   => 8,
			'DbType'        => 'datetime',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
		];

		$columns['field8_big'] = [
			'ColumnName'       => '[field8_big]',
			'ColumnIndex'      => 9,
			'DbType'           => 'bigint',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => '((0))',
			'NumericPrecision' => 19,
			'NumericScale'     => 0,
		];

		$columns['field9_char'] = [
			'ColumnName'    => '[field9_char]',
			'ColumnIndex'   => 10,
			'DbType'        => 'char',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'ColumnSize'    => 10,
		];

		$columns['field10_bit'] = [
			'ColumnName'    => '[field10_bit]',
			'ColumnIndex'   => 11,
			'DbType'        => 'bit',
			'AllowNull'     => false,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => '((0))',
		];

		$columns['field11_num'] = [
			'ColumnName'       => '[field11_num]',
			'ColumnIndex'      => 12,
			'DbType'           => 'numeric',
			'AllowNull'        => false,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => '((0))',
			'NumericPrecision' => 8,
			'NumericScale'     => 2,
		];

		$this->assertColumn($columns, $table);
	}

	public function test_find_table_names()
	{
		$names = $this->meta_data()->findTableNames('dbo');

		$this->assertContains('table1', $names);
		$this->assertContains('address', $names);
	}

	public function test_command_builder_insert()
	{
		$meta = $this->meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$data = ['name' => 'test_row', 'field1_tiny' => 1, 'field6_int' => 42];
		$insert = $builder->createInsertCommand($data);
		$this->assertStringContainsString('INSERT INTO', $insert->Text);
		$this->assertStringContainsString('[prado_unitest].[dbo].[table1]', $insert->Text);
		$this->assertStringContainsString('[name]', $insert->Text);
	}

	public function test_command_builder_update()
	{
		$meta = $this->meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$update = $builder->createUpdateCommand(['name' => 'updated'], 'id=1');
		$this->assertStringContainsString('UPDATE', $update->Text);
		$this->assertStringContainsString('[prado_unitest].[dbo].[table1]', $update->Text);
		$this->assertStringContainsString('[name]', $update->Text);
		$this->assertStringContainsString('WHERE id=1', $update->Text);
	}

	public function test_command_builder_delete()
	{
		$meta = $this->meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$delete = $builder->createDeleteCommand('id=1');
		$this->assertStringContainsString('DELETE FROM', $delete->Text);
		$this->assertStringContainsString('[prado_unitest].[dbo].[table1]', $delete->Text);
		$this->assertStringContainsString('WHERE id=1', $delete->Text);
	}

	public function test_insert()
	{
		$table = new TTableGateway('table1', $this->get_conn());
		$result = $table->insert(['name' => 'cool']);

		$this->assertTrue(is_int($result));
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
