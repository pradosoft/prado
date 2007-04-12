<?php

Prado::using('System.Data.*');
Prado::using('System.Data.Common.Pgsql.TPgsqlMetaData');
class PgsqlColumnTest extends UnitTestCase
{
	function create_meta_data()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		return new TPgsqlMetaData($conn);
	}

	function test_text_column_def()
	{
		$table = $this->create_meta_data()->getTableInfo('public.address');
		$this->assertEqual(count($table->getColumns()), 14);

		$columns['id'] = array(
			'ColumnName'       => '"id"',
			'ColumnSize'       => null,
			'ColumnIndex'      => 0,
			'DbType'           => 'integer',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => true,
			'IsForeignKey'     => false,
			'SequenceName'     => 'public.address_id_seq',
		);

		$columns['username'] = array(
			'ColumnName'       => '"username"',
			'ColumnSize'       => 128,
			'ColumnIndex'      => 1,
			'DbType'           => 'character varying',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'SequenceName'     => null,
		);

		$columns['phone'] = array(
			'ColumnName'       => '"phone"',
			'ColumnSize'       => 40,
			'ColumnIndex'      => 2,
			'DbType'           => 'character',
			'AllowNull'        => false,
			'DefaultValue'     => "'hello'::bpchar",
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'SequenceName'     => null,
		);

		$columns['field1_boolean'] = array(
			'ColumnName'       => '"field1_boolean"',
			'ColumnSize'       => null,
			'ColumnIndex'      => 3,
			'DbType'           => 'boolean',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'SequenceName'     => null,
		);

		$columns['field4_integer'] = array(
			'ColumnName'       => '"field4_integer"',
			'ColumnSize'       => null,
			'ColumnIndex'      => 6,
			'DbType'           => 'integer',
			'AllowNull'        => false,
			'DefaultValue'     => "1",
			'NumericPrecision' => null,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => true,
			'SequenceName'     => null,
		);

		$columns['field7_timestamp'] = array(
			'ColumnName'       => '"field7_timestamp"',
			'ColumnSize'       => 2,
			'ColumnIndex'      => 9,
			'DbType'           => 'timestamp without time zone',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => 6,
			'NumericScale'     => null,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'SequenceName'     => null,
		);

		$columns['field9_numeric'] = array(
			'ColumnName'       => '"field9_numeric"',
			'ColumnSize'       => 393220,
			'ColumnIndex'      => 11,
			'DbType'           => 'numeric',
			'AllowNull'        => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => 6,
			'NumericScale'     => 4,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'SequenceName'     => null,
		);
		$this->assertColumn($columns, $table);

		$this->assertEqual('public', $table->getSchemaName());
		$this->assertEqual('address', $table->getTableName());
		$this->assertEqual(array('id'), $table->getPrimaryKeys());
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