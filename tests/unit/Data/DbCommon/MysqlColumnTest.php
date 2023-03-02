<?php

use Prado\Data\Common\Mysql\TMysqlMetaData;

class MysqlColumnTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!extension_loaded('pdo_mysql')) {
			$this->markTestSkipped(
				'The pdo_mysql extension is not available.'
			);
		}
	}

	public function create_meta_data()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
		$conn->Active = true;
		return new TMysqlMetaData($conn);
	}

	public function test_columns()
	{
		$table = $this->create_meta_data()->getTableInfo('table1');
		$this->assertEquals(count($table->getColumns()), 14);

		$columns = [];

		$columns['id'] = [
			'ColumnName' => '`id`',
//			'ColumnSize' => 10,
			'ColumnIndex' => 0,
			'DbType' => 'int unsigned',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => true,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => true,
		];

		$columns['name'] = [
			'ColumnName' => '`name`',
			'ColumnSize' => 45,
			'ColumnIndex' => 1,
			'DbType' => 'varchar',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => true,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field1'] = [
			'ColumnName' => '`field1`',
//			'ColumnSize' => 4,
			'ColumnIndex' => 2,
			'DbType' => 'tinyint',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field2_text'] = [
			'ColumnName' => '`field2_text`',
			'ColumnSize' => null,
			'ColumnIndex' => 3,
			'DbType' => 'text',
			'AllowNull' => true,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field3_date'] = [
			'ColumnName' => '`field3_date`',
			'ColumnSize' => null,
			'ColumnIndex' => 4,
			'DbType' => 'date',
			'AllowNull' => true,
			'DefaultValue' => '2007-02-25',
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field4_float'] = [
			'ColumnName' => '`field4_float`',
			'ColumnSize' => null,
			'ColumnIndex' => 5,
			'DbType' => 'float',
			'AllowNull' => false,
			'DefaultValue' => 10,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field5_float'] = [
			'ColumnName' => '`field5_float`',
			'ColumnSize' => null,
			'ColumnIndex' => 6,
			'DbType' => 'float',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => 5,
			'NumericScale' => 4,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field6_double'] = [
			'ColumnName' => '`field6_double`',
			'ColumnSize' => null,
			'ColumnIndex' => 7,
			'DbType' => 'double',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field7_datetime'] = [
			'ColumnName' => '`field7_datetime`',
			'ColumnSize' => null,
			'ColumnIndex' => 8,
			'DbType' => 'datetime',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field8_timestamp'] = [
			'ColumnName' => '`field8_timestamp`',
			'ColumnSize' => null,
			'ColumnIndex' => 9,
			'DbType' => 'timestamp',
			'AllowNull' => true,
			'DefaultValue' => 'CURRENT_TIMESTAMP',
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field9_time'] = [
			'ColumnName' => '`field9_time`',
			'ColumnSize' => null,
			'ColumnIndex' => 10,
			'DbType' => 'time',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field10_year'] = [
			'ColumnName' => '`field10_year`',
//			'ColumnSize' => 4,
			'ColumnIndex' => 11,
			'DbType' => 'year',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
		];

		$columns['field11_enum'] = [
			'ColumnName' => '`field11_enum`',
			'ColumnSize' => null,
			'ColumnIndex' => 12,
			'DbType' => 'enum',
			'AllowNull' => false,
			'DefaultValue' => 'one',
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
			'DbTypeValues' => ['one', 'two', 'three'],
		];

		$columns['field12_set'] = [
			'ColumnName' => '`field12_set`',
			'ColumnSize' => null,
			'ColumnIndex' => 13,
			'DbType' => 'set',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
			'AutoIncrement' => false,
			'DbTypeValues' => ['blue', 'red', 'green'],
		];

		$this->assertColumn($columns, $table);

		$this->assertNull($table->getSchemaName());
		$this->assertEquals('table1', $table->getTableName());
		$this->assertEquals(['id', 'name'], $table->getPrimaryKeys());
	}

	public function assertColumn($columns, $table)
	{
		foreach ($columns as $id => $asserts) {
			$column = $table->Columns[$id];
			foreach ($asserts as $property => $assert) {
				$ofAssert = var_export($assert, true);
				$value = $column->{$property};
				$ofValue = var_export($value, true);

				// workaround: mariadb and mysql diverged on this
				if ($assert === 'CURRENT_TIMESTAMP' && $value == 'current_timestamp()') {
					$value = $assert;
				}

				$this->assertEquals(
					$value,
					$assert,
					"Column [{$id}] {$property} value {$ofValue} did not match {$ofAssert}"
				);
			}
		}
	}
}
