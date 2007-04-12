<?php

Prado::using('System.Data.*');
Prado::using('System.Data.Common.Mysql.TMysqlMetaData');

class MysqlColumnTest extends UnitTestCase
{
	function create_meta_data()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=tests', 'test','test');
		return new TMysqlMetaData($conn);
	}

	function test_columns()
	{
		$table = $this->create_meta_data()->getTableInfo('table1');
		$this->assertEqual(count($table->getColumns()), 18);

		$columns['id'] = array(
			'ColumnName'       => '`id`',
			'ColumnSize'       => 10,
			'ColumnIndex'      => 0,
			'DbType'           => 'int unsigned',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => true,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => true,
		);

		$columns['name'] = array(
			'ColumnName'       => '`name`',
			'ColumnSize'       => 45,
			'ColumnIndex'      => 1,
			'DbType'           => 'varchar',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => true,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field1'] = array(
			'ColumnName'       => '`field1`',
			'ColumnSize'       => 4,
			'ColumnIndex'      => 2,
			'DbType'           => 'tinyint',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field2_text'] = array(
			'ColumnName'       => '`field2_text`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 3,
			'DbType'           => 'text',
			'AllowNull'        => true,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field3_date'] = array(
			'ColumnName'       => '`field3_date`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 4,
			'DbType'           => 'date',
			'AllowNull'        => true,
			'DefaultValue'     => '2007-02-25',
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field4_float'] = array(
			'ColumnName'       => '`field4_float`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 5,
			'DbType'           => 'float',
			'AllowNull'        => false,
			'DefaultValue'     => 10,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field5_float'] = array(
			'ColumnName'       => '`field5_float`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 6,
			'DbType'           => 'float',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => 5,
			'NumericScale'     => 4,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field6_double'] = array(
			'ColumnName'       => '`field6_double`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 7,
			'DbType'           => 'double',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field7_datetime'] = array(
			'ColumnName'       => '`field7_datetime`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 8,
			'DbType'           => 'datetime',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field8_timestamp'] = array(
			'ColumnName'       => '`field8_timestamp`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 9,
			'DbType'           => 'timestamp',
			'AllowNull'        => true,
			'DefaultValue'     => 'CURRENT_TIMESTAMP',
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field9_time'] = array(
			'ColumnName'       => '`field9_time`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 10,
			'DbType'           => 'time',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field10_year'] = array(
			'ColumnName'       => '`field10_year`',
			'ColumnSize'       => 4,
			'ColumnIndex'      => 11,
			'DbType'           => 'year',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
		);

		$columns['field11_enum'] = array(
			'ColumnName'       => '`field11_enum`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 12,
			'DbType'           => 'enum',
			'AllowNull'        => false,
			'DefaultValue'     => 'one',
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
			'DbTypeValues'     => array('one', 'two', 'three'),
		);

		$columns['field12_SET'] = array(
			'ColumnName'       => '`field12_SET`',
			'ColumnSize'       => null,
			'ColumnIndex'      => 13,
			'DbType'           => 'set',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'IsUnique'         => false,
			'SequenceName'     => null,
			'AutoIncrement'    => false,
			'DbTypeValues'     => array('blue', 'red', 'green'),
		);

		$this->assertColumn($columns, $table);

		$this->assertNull($table->getSchemaName());
		$this->assertEqual('table1', $table->getTableName());
		$this->assertEqual(array('id', 'name'), $table->getPrimaryKeys());
		$this->assertEqual(array('fk3'), $table->getUniqueKeys());
	}

	function assertColumn($columns, $table)
	{
		foreach($columns as $id=>$asserts)
		{
			$column = $table->Columns[$id];
			foreach($asserts as $property=>$assert)
			{
				$ofAssert= var_export($assert,true);
				$value = $column->{$property};
				$ofValue = var_export($value, true);
				$this->assertEqual($value, $assert,
					"Column [{$id}] {$property} value {$ofValue} did not match {$ofAssert}");
			}
		}
	}
}

?>