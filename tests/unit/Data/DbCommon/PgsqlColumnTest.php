<?php

use Prado\Data\Common\Pgsql\TPgsqlMetaData;

class PgsqlColumnTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!extension_loaded('pdo_pgsql')) {
			$this->markTestSkipped(
				'The pdo_pgsql extension is not available.'
			);
		}
	}

	public function create_meta_data()
	{
		$cred = getenv('SCRUTINIZER') ? 'scrutinizer' : 'prado_unitest';
		$conn = new TDbConnection('pgsql:host=localhost;dbname=prado_unitest', $cred, $cred);
		return new TPgsqlMetaData($conn);
	}

	public function test_text_column_def()
	{
		$table = $this->create_meta_data()->getTableInfo('public.address');
		$this->assertEquals(count($table->getColumns()), 14);

		$columns['id'] = [
			'ColumnName' => '"id"',
			'ColumnSize' => null,
			'ColumnIndex' => 0,
			'DbType' => 'integer',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => true,
			'IsForeignKey' => false,
			'SequenceName' => 'public.address_id_seq',
		];

		$columns['username'] = [
			'ColumnName' => '"username"',
			'ColumnSize' => 128,
			'ColumnIndex' => 1,
			'DbType' => 'character varying',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
		];

		$columns['phone'] = [
			'ColumnName' => '"phone"',
			'ColumnSize' => 40,
			'ColumnIndex' => 2,
			'DbType' => 'character',
			'AllowNull' => false,
			'DefaultValue' => "'hello'::bpchar",
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
		];

		$columns['field1_boolean'] = [
			'ColumnName' => '"field1_boolean"',
			'ColumnSize' => null,
			'ColumnIndex' => 3,
			'DbType' => 'boolean',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
		];

		$columns['field4_integer'] = [
			'ColumnName' => '"field4_integer"',
			'ColumnSize' => null,
			'ColumnIndex' => 6,
			'DbType' => 'integer',
			'AllowNull' => false,
			'DefaultValue' => "1",
			'NumericPrecision' => null,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => true,
			'SequenceName' => null,
		];

		$columns['field7_timestamp'] = [
			'ColumnName' => '"field7_timestamp"',
			'ColumnSize' => 2,
			'ColumnIndex' => 9,
			'DbType' => 'timestamp without time zone',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => 6,
			'NumericScale' => null,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
		];

		$columns['field9_numeric'] = [
			'ColumnName' => '"field9_numeric"',
			'ColumnSize' => 393220,
			'ColumnIndex' => 11,
			'DbType' => 'numeric',
			'AllowNull' => false,
			'DefaultValue' => TDbTableColumn::UNDEFINED_VALUE,
			'NumericPrecision' => 6,
			'NumericScale' => 4,
			'IsPrimaryKey' => false,
			'IsForeignKey' => false,
			'SequenceName' => null,
		];
		$this->assertColumn($columns, $table);

		$this->assertEquals('public', $table->getSchemaName());
		$this->assertEquals('address', $table->getTableName());
		$this->assertEquals(['id'], $table->getPrimaryKeys());
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
