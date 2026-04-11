<?php

use Prado\Data\Common\IDbHasSchema;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbTableColumn;
use Prado\Data\Common\TDbTableInfo;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

/**
 * Unit tests for TDbTableInfo — the base table-metadata value object.
 *
 * No database connection is required; columns are wired in manually.
 */
class TDbTableInfoTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function makeInfo(array $columns = [], array $primary = [], array $foreign = [], array $tableInfo = ['TableName' => 'test_table']): TDbTableInfo
	{
		$info = new TDbTableInfo($tableInfo, $primary, $foreign);
		foreach ($columns as $id => $colInfo) {
			$info->getColumns()->add($id, new TDbTableColumn($colInfo));
		}
		return $info;
	}

	// -----------------------------------------------------------------------
	// TableName / TableFullName
	// -----------------------------------------------------------------------

	public function test_get_table_name()
	{
		$info = new TDbTableInfo(['TableName' => 'my_table']);
		$this->assertEquals('my_table', $info->getTableName());
	}

	public function test_get_table_full_name_equals_table_name_in_base_class()
	{
		// Base TDbTableInfo: getTableFullName() delegates to getTableName().
		$info = new TDbTableInfo(['TableName' => 'my_table']);
		$this->assertEquals('my_table', $info->getTableFullName());
	}

	public function test_table_name_null_when_not_set()
	{
		$info = new TDbTableInfo([]);
		$this->assertNull($info->getTableName());
	}

	public function test_get_schema_name_returns_null_for_base_class()
	{
		// Base TDbTableInfo does NOT implement IDbHasSchema, so getSchemaName()
		// must always return null regardless of what is in the info array.
		$info = new TDbTableInfo(['SchemaName' => 'public']);
		$this->assertNull($info->getSchemaName());
	}

	public function test_get_schema_name_returns_value_when_interface_implemented()
	{
		// An anonymous subclass that declares IDbHasSchema should return the value.
		$info = new class(['SchemaName' => 'myschema']) extends TDbTableInfo implements IDbHasSchema {};
		$this->assertEquals('myschema', $info->getSchemaName());
	}

	public function test_get_schema_name_returns_null_when_interface_implemented_but_not_set()
	{
		$info = new class([]) extends TDbTableInfo implements IDbHasSchema {};
		$this->assertNull($info->getSchemaName());
	}

	// -----------------------------------------------------------------------
	// IsView
	// -----------------------------------------------------------------------

	public function test_is_view_defaults_to_false()
	{
		$info = new TDbTableInfo([]);
		$this->assertFalse($info->getIsView());
	}

	public function test_is_view_true_when_set()
	{
		$info = new TDbTableInfo(['IsView' => true]);
		$this->assertTrue($info->getIsView());
	}

	// -----------------------------------------------------------------------
	// Primary / foreign keys
	// -----------------------------------------------------------------------

	public function test_primary_keys_returned_as_given()
	{
		$info = new TDbTableInfo([], ['id', 'code']);
		$this->assertEquals(['id', 'code'], $info->getPrimaryKeys());
	}

	public function test_primary_keys_empty_by_default()
	{
		$info = new TDbTableInfo([]);
		$this->assertEquals([], $info->getPrimaryKeys());
	}

	public function test_foreign_keys_returned_as_given()
	{
		$fk = [['table' => 'other', 'keys' => ['fk_id' => 'id']]];
		$info = new TDbTableInfo([], [], $fk);
		$this->assertEquals($fk, $info->getForeignKeys());
	}

	public function test_foreign_keys_empty_by_default()
	{
		$info = new TDbTableInfo([]);
		$this->assertEquals([], $info->getForeignKeys());
	}

	// -----------------------------------------------------------------------
	// Columns map
	// -----------------------------------------------------------------------

	public function test_columns_empty_on_construction()
	{
		$info = new TDbTableInfo([]);
		$this->assertCount(0, $info->getColumns());
	}

	public function test_get_column_returns_correct_column()
	{
		$tableInfo = $this->makeInfo([
			'id' => ['ColumnName' => '"id"', 'ColumnIndex' => 0],
		]);
		$col = $tableInfo->getColumn('id');
		$this->assertEquals('"id"', $col->getColumnName());
	}

	public function test_get_column_throws_on_unknown_name()
	{
		$tableInfo = $this->makeInfo([]);
		$this->expectException(TDbException::class);
		$tableInfo->getColumn('nonexistent');
	}

	public function test_get_column_names_returns_quoted_names_in_order()
	{
		$tableInfo = $this->makeInfo([
			'id'   => ['ColumnName' => '"id"'],
			'name' => ['ColumnName' => '"name"'],
		]);
		$this->assertEquals(['"id"', '"name"'], $tableInfo->getColumnNames());
	}

	public function test_get_column_names_cached_on_second_call()
	{
		$tableInfo = $this->makeInfo([
			'id' => ['ColumnName' => '"id"'],
		]);
		$first  = $tableInfo->getColumnNames();
		$second = $tableInfo->getColumnNames();
		$this->assertSame($first, $second);
	}

	// -----------------------------------------------------------------------
	// getLowerCaseColumnNames
	// -----------------------------------------------------------------------

	public function test_get_lowercase_column_names()
	{
		$tableInfo = $this->makeInfo([
			'MyCol' => ['ColumnName' => '"MyCol"'],
			'OTHER' => ['ColumnName' => '"OTHER"'],
		]);
		$lc = $tableInfo->getLowerCaseColumnNames();

		$this->assertArrayHasKey('mycol', $lc);
		$this->assertEquals('MyCol', $lc['mycol']);
		$this->assertArrayHasKey('other', $lc);
		$this->assertEquals('OTHER', $lc['other']);
	}

	public function test_get_lowercase_column_names_cached()
	{
		$tableInfo = $this->makeInfo([
			'id' => ['ColumnName' => '"id"'],
		]);
		$first  = $tableInfo->getLowerCaseColumnNames();
		$second = $tableInfo->getLowerCaseColumnNames();
		$this->assertSame($first, $second);
	}

	// -----------------------------------------------------------------------
	// createCommandBuilder
	// -----------------------------------------------------------------------

	public function test_create_command_builder_returns_base_builder()
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite not available.');
		}
		$tableInfo = $this->makeInfo([]);
		$conn      = new TDbConnection('sqlite::memory:');

		$builder = $tableInfo->createCommandBuilder($conn);

		$this->assertInstanceOf(TDbCommandBuilder::class, $builder);
		$this->assertSame($conn, $builder->getDbConnection());
		$this->assertSame($tableInfo, $builder->getTableInfo());
	}
}
