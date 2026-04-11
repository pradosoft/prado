<?php

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\TDbConnection;

/**
 * Unit tests for TDbCommandBuilder — the base SQL-builder class.
 *
 * Uses an in-memory SQLite database to obtain real TDbTableInfo objects, but
 * instantiates TDbCommandBuilder directly (not TSqliteCommandBuilder) so that
 * every test exercises the *base-class* behaviour — in particular the base
 * applyLimitOffset which uses `>= 0` thresholds (different from SQLite's
 * `> 0` override).
 *
 * Tests that require a live connection are guarded with pdo_sqlite checks.
 */
class TDbCommandBuilderTest extends PHPUnit\Framework\TestCase
{
	private static TDbConnection $conn;
	private static TSqliteMetaData $meta;

	public static function setUpBeforeClass(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			return;
		}
		self::$conn = new TDbConnection('sqlite::memory:');
		self::$conn->Active = true;
		self::$conn->createCommand('
			CREATE TABLE items (
				id    INTEGER     NOT NULL PRIMARY KEY,
				name  VARCHAR(100) NOT NULL,
				price REAL         NOT NULL DEFAULT 0.0,
				note  TEXT
			)
		')->execute();
		self::$conn->createCommand("INSERT INTO items VALUES (1, 'foo', 9.99, NULL)")->execute();
		self::$conn->createCommand("INSERT INTO items VALUES (2, 'bar', 4.50, 'a note')")->execute();
		self::$meta = new TSqliteMetaData(self::$conn);
	}

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite not available.');
		}
	}

	/** Returns a fresh base TDbCommandBuilder (not TSqliteCommandBuilder). */
	private function builder(): TDbCommandBuilder
	{
		$tableInfo = self::$meta->getTableInfo('items');
		return new TDbCommandBuilder(self::$conn, $tableInfo);
	}

	// -----------------------------------------------------------------------
	// getPdoType (static helper)
	// -----------------------------------------------------------------------

	public function test_get_pdo_type_boolean()
	{
		$this->assertEquals(PDO::PARAM_BOOL, TDbCommandBuilder::getPdoType(true));
		$this->assertEquals(PDO::PARAM_BOOL, TDbCommandBuilder::getPdoType(false));
	}

	public function test_get_pdo_type_integer()
	{
		$this->assertEquals(PDO::PARAM_INT, TDbCommandBuilder::getPdoType(42));
	}

	public function test_get_pdo_type_string()
	{
		$this->assertEquals(PDO::PARAM_STR, TDbCommandBuilder::getPdoType('hello'));
	}

	public function test_get_pdo_type_null()
	{
		$this->assertEquals(PDO::PARAM_NULL, TDbCommandBuilder::getPdoType(null));
	}

	public function test_get_pdo_type_float_returns_null()
	{
		// float has no PDO constant mapping → null
		$this->assertNull(TDbCommandBuilder::getPdoType(1.5));
	}

	// -----------------------------------------------------------------------
	// applyLimitOffset — base implementation (>= 0 threshold)
	// -----------------------------------------------------------------------

	public function test_apply_limit_only()
	{
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql . ' LIMIT 5', $this->builder()->applyLimitOffset($sql, 5));
	}

	public function test_apply_offset_only()
	{
		// Base class: limit=-1 → no LIMIT clause; offset=10 >= 0 → OFFSET appended
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql . ' OFFSET 10', $this->builder()->applyLimitOffset($sql, -1, 10));
	}

	public function test_apply_limit_and_offset()
	{
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql . ' LIMIT 5 OFFSET 10', $this->builder()->applyLimitOffset($sql, 5, 10));
	}

	public function test_apply_limit_zero()
	{
		// Base: 0 >= 0 → LIMIT 0 emitted (differs from SQLite subclass which requires > 0)
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql . ' LIMIT 0', $this->builder()->applyLimitOffset($sql, 0));
	}

	public function test_apply_offset_zero()
	{
		// 0 >= 0 → OFFSET 0 emitted
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql . ' LIMIT 5 OFFSET 0', $this->builder()->applyLimitOffset($sql, 5, 0));
	}

	public function test_apply_no_limit_no_offset()
	{
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql, $this->builder()->applyLimitOffset($sql, -1, -1));
	}

	public function test_apply_null_treated_as_negative_one()
	{
		// null coerced to -1 → no clause appended
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql, $this->builder()->applyLimitOffset($sql, null, null));
	}

	// -----------------------------------------------------------------------
	// applyOrdering
	// -----------------------------------------------------------------------

	public function test_apply_ordering_single_asc()
	{
		$sql    = 'SELECT * FROM items';
		$result = $this->builder()->applyOrdering($sql, ['name' => 'asc']);
		$this->assertStringContainsString('ORDER BY', $result);
		$this->assertStringContainsString('"name"', $result);
		$this->assertStringContainsString('ASC', $result);
	}

	public function test_apply_ordering_single_desc()
	{
		$sql    = 'SELECT * FROM items';
		$result = $this->builder()->applyOrdering($sql, ['price' => 'desc']);
		$this->assertStringContainsString('ORDER BY', $result);
		$this->assertStringContainsString('"price"', $result);
		$this->assertStringContainsString('DESC', $result);
	}

	public function test_apply_ordering_multiple_columns()
	{
		$sql    = 'SELECT * FROM items';
		$result = $this->builder()->applyOrdering($sql, ['name' => 'asc', 'price' => 'desc']);
		$this->assertStringContainsString('"name" ASC', $result);
		$this->assertStringContainsString('"price" DESC', $result);
	}

	public function test_apply_ordering_function_expression_not_quoted()
	{
		// Keys containing '(' and ')' are used verbatim (not looked up as column IDs)
		$sql    = 'SELECT * FROM items';
		$result = $this->builder()->applyOrdering($sql, ['RANDOM()' => 'asc']);
		$this->assertStringContainsString('ORDER BY RANDOM()', $result);
	}

	public function test_apply_ordering_unknown_direction_treated_as_asc()
	{
		$sql    = 'SELECT * FROM items';
		$result = $this->builder()->applyOrdering($sql, ['name' => 'sideways']);
		$this->assertStringContainsString('ASC', $result);
		$this->assertStringNotContainsString('DESC', $result);
	}

	public function test_apply_ordering_empty_array_no_change()
	{
		$sql = 'SELECT * FROM items';
		$this->assertEquals($sql, $this->builder()->applyOrdering($sql, []));
	}

	// -----------------------------------------------------------------------
	// getSearchExpression
	// -----------------------------------------------------------------------

	public function test_get_search_expression_empty_keyword_returns_empty()
	{
		$builder = $this->builder();
		$this->assertEquals('', $builder->getSearchExpression(['name'], ''));
		$this->assertEquals('', $builder->getSearchExpression(['name'], '   '));
	}

	public function test_get_search_expression_single_field_single_keyword()
	{
		$result = $this->builder()->getSearchExpression(['name'], 'foo');
		$this->assertStringContainsString('"name"', $result);
		$this->assertStringContainsString('LIKE', $result);
		$this->assertStringContainsString('%foo%', $result);
	}

	public function test_get_search_expression_multiple_fields_joined_with_or()
	{
		$result = $this->builder()->getSearchExpression(['name', 'note'], 'foo');
		$this->assertStringContainsString('"name"', $result);
		$this->assertStringContainsString('"note"', $result);
		$this->assertStringContainsString(' OR ', $result);
	}

	public function test_get_search_expression_multiple_keywords_joined_with_and()
	{
		$result = $this->builder()->getSearchExpression(['name'], 'foo bar');
		$this->assertStringContainsString('%foo%', $result);
		$this->assertStringContainsString('%bar%', $result);
		$this->assertStringContainsString(' AND ', $result);
	}

	// -----------------------------------------------------------------------
	// getSelectFieldList
	// -----------------------------------------------------------------------

	public function test_get_select_field_list_string_star()
	{
		$this->assertEquals(['*'], $this->builder()->getSelectFieldList('*'));
	}

	public function test_get_select_field_list_comma_separated_string()
	{
		$result = $this->builder()->getSelectFieldList('id, name');
		$this->assertEquals(['id', 'name'], $result);
	}

	public function test_get_select_field_list_null_returns_all_quoted_columns()
	{
		$result = $this->builder()->getSelectFieldList(null);
		$this->assertContains('"id"', $result);
		$this->assertContains('"name"', $result);
		$this->assertContains('"price"', $result);
		$this->assertContains('"note"', $result);
	}

	public function test_get_select_field_list_array_of_column_ids()
	{
		$result = $this->builder()->getSelectFieldList(['name', 'price']);
		$this->assertContains('"name"', $result);
		$this->assertContains('"price"', $result);
	}

	public function test_get_select_field_list_array_with_wildcard_expands_all()
	{
		$result = $this->builder()->getSelectFieldList(['*']);
		$this->assertContains('"id"', $result);
		$this->assertContains('"name"', $result);
	}

	public function test_get_select_field_list_null_value_for_existing_column()
	{
		// 'id' => 'NULL' → 'NULL AS "id"'
		$result = $this->builder()->getSelectFieldList(['id' => 'NULL']);
		$this->assertContains('NULL AS "id"', $result);
	}

	public function test_get_select_field_list_alias()
	{
		// 'myalias' => 'name' where 'name' is a known column → '"name" AS myalias'
		$result = $this->builder()->getSelectFieldList(['myalias' => 'name']);
		$this->assertContains('"name" AS myalias', $result);
	}

	public function test_get_select_field_list_verbatim_as_expression()
	{
		// A pre-formed "col AS alias" string in the value should be kept verbatim.
		$result = $this->builder()->getSelectFieldList([0 => 'price AS cost']);
		$this->assertContains('price AS cost', $result);
	}

	public function test_get_select_field_list_function_expression_as_value()
	{
		// 'cnt' => 'COUNT(*)' → 'COUNT(*) AS cnt' (function detected by parens in value)
		$result = $this->builder()->getSelectFieldList(['cnt' => 'COUNT(*)']);
		$this->assertContains('COUNT(*) AS cnt', $result);
	}

	// -----------------------------------------------------------------------
	// createInsertCommand
	// -----------------------------------------------------------------------

	public function test_create_insert_command_text()
	{
		$cmd = $this->builder()->createInsertCommand(['name' => 'test', 'price' => 1.0]);
		$this->assertStringContainsString('INSERT INTO', $cmd->Text);
		$this->assertStringContainsString('"name"', $cmd->Text);
		$this->assertStringContainsString('"price"', $cmd->Text);
		$this->assertStringContainsString(':name', $cmd->Text);
		$this->assertStringContainsString(':price', $cmd->Text);
	}

	// -----------------------------------------------------------------------
	// createUpdateCommand
	// -----------------------------------------------------------------------

	public function test_create_update_command_text()
	{
		$cmd = $this->builder()->createUpdateCommand(['name' => 'updated'], 'id=1');
		$this->assertStringContainsString('UPDATE', $cmd->Text);
		$this->assertStringContainsString('"name"', $cmd->Text);
		$this->assertStringContainsString('WHERE id=1', $cmd->Text);
	}

	public function test_create_update_command_no_where()
	{
		$cmd = $this->builder()->createUpdateCommand(['name' => 'x'], '');
		$this->assertStringNotContainsString('WHERE', $cmd->Text);
	}

	// -----------------------------------------------------------------------
	// createDeleteCommand
	// -----------------------------------------------------------------------

	public function test_create_delete_command_with_where()
	{
		$cmd = $this->builder()->createDeleteCommand('id=99');
		$this->assertStringContainsString('DELETE FROM', $cmd->Text);
		$this->assertStringContainsString('WHERE id=99', $cmd->Text);
	}

	public function test_create_delete_command_no_where()
	{
		$cmd = $this->builder()->createDeleteCommand('');
		$this->assertStringContainsString('DELETE FROM', $cmd->Text);
		$this->assertStringNotContainsString('WHERE', $cmd->Text);
	}

	// -----------------------------------------------------------------------
	// createFindCommand / createCountCommand
	// -----------------------------------------------------------------------

	public function test_create_find_command_default_selects_all_with_where_1_eq_1()
	{
		$cmd = $this->builder()->createFindCommand();
		$this->assertStringContainsString('SELECT', $cmd->Text);
		$this->assertStringContainsString('FROM', $cmd->Text);
		$this->assertStringContainsString('WHERE 1=1', $cmd->Text);
	}

	public function test_create_find_command_with_custom_where()
	{
		$cmd = $this->builder()->createFindCommand('id=:id', [':id' => 1]);
		$this->assertStringContainsString('WHERE id=:id', $cmd->Text);
	}

	public function test_create_find_command_no_where_clause_when_empty()
	{
		$cmd = $this->builder()->createFindCommand('');
		$this->assertStringNotContainsString('WHERE', $cmd->Text);
	}

	public function test_create_count_command_uses_count_star()
	{
		$cmd = $this->builder()->createCountCommand();
		$this->assertStringContainsString('COUNT(*)', $cmd->Text);
	}

	// -----------------------------------------------------------------------
	// applyCriterias
	// -----------------------------------------------------------------------

	public function test_apply_criterias_ordering_and_limit()
	{
		$cmd = $this->builder()->applyCriterias(
			'SELECT * FROM items',
			[],
			['name' => 'asc'],
			5,
			0
		);
		$this->assertStringContainsString('ORDER BY', $cmd->Text);
		$this->assertStringContainsString('LIMIT 5', $cmd->Text);
	}

	public function test_apply_criterias_no_ordering_no_limit()
	{
		$sql = 'SELECT * FROM items';
		$cmd = $this->builder()->applyCriterias($sql, [], [], -1, -1);
		// No ORDER BY, no LIMIT, no OFFSET added
		$this->assertStringNotContainsString('ORDER BY', $cmd->Text);
		$this->assertStringNotContainsString('LIMIT', $cmd->Text);
	}
}
