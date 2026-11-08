<?php

namespace Prado\Test\Unit\Data\DbCommon;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbTableInfo;
use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * Command that records bound values without preparing its SQL, so the text of
 * statements the connected driver cannot parse (MERGE on SQLite) can be checked.
 */
class TDbCommandBuilderTestRecordingCommand extends TDbCommand
{
	public array $bound = [];

	public function bindValue($name, $value, $dataType = null)
	{
		$this->bound[$name] = [$value, $dataType];
	}
}

/**
 * Builder whose commands record bindings instead of preparing SQL.
 */
class TDbCommandBuilderTestRecordingBuilder extends TDbCommandBuilder
{
	public ?string $mergeSuffix = null;

	public function createCommand($sql)
	{
		return new TDbCommandBuilderTestRecordingCommand($this->getDbConnection(), $sql);
	}

	protected function postProcessMerge(string $sql): ?string
	{
		return $this->mergeSuffix === null ? parent::postProcessMerge($sql) : $sql . $this->mergeSuffix;
	}
}

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
class TDbCommandBuilderTest extends \PHPUnit\Framework\TestCase
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
		self::$conn->createCommand('CREATE TABLE nopk (a INTEGER, b TEXT)')->execute();
		self::$conn->createCommand('CREATE TABLE pairs (a INTEGER, b TEXT, val TEXT, PRIMARY KEY (a, b))')->execute();
		self::$conn->createCommand("INSERT INTO pairs VALUES (1, 'x', 'keep'), (1, 'y', 'keep'), (2, 'x', 'drop'), (2, 'y', 'keep')")->execute();
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
		$this->assertEquals(\PDO::PARAM_BOOL, TDbCommandBuilder::getPdoType(true));
		$this->assertEquals(\PDO::PARAM_BOOL, TDbCommandBuilder::getPdoType(false));
	}

	public function test_get_pdo_type_integer()
	{
		$this->assertEquals(\PDO::PARAM_INT, TDbCommandBuilder::getPdoType(42));
	}

	public function test_get_pdo_type_string()
	{
		$this->assertEquals(\PDO::PARAM_STR, TDbCommandBuilder::getPdoType('hello'));
	}

	public function test_get_pdo_type_null()
	{
		$this->assertEquals(\PDO::PARAM_NULL, TDbCommandBuilder::getPdoType(null));
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

	public function test_get_search_expression_no_fields_returns_empty()
	{
		$this->assertSame('', $this->builder()->getSearchExpression([], 'foo'));
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

	public function test_get_search_expression_escapes_like_wildcards()
	{
		$result = $this->builder()->getSearchExpression(['name'], '50% a_b c\\d');
		$this->assertStringContainsString("'%50\\%%' ESCAPE '\\'", $result);
		$this->assertStringContainsString("'%a\\_b%' ESCAPE '\\'", $result);
		$this->assertStringContainsString("'%c\\\\d%' ESCAPE '\\'", $result);
	}

	public function test_get_search_expression_matches_wildcards_literally()
	{
		self::$conn->createCommand("INSERT INTO items VALUES (3, 'rate 50%', 1.0, NULL)")->execute();
		self::$conn->createCommand("INSERT INTO items VALUES (4, 'rate 500', 1.0, NULL)")->execute();
		self::$conn->createCommand("INSERT INTO items VALUES (5, 'a_b', 1.0, NULL)")->execute();
		self::$conn->createCommand("INSERT INTO items VALUES (6, 'axb', 1.0, NULL)")->execute();
		try {
			$builder = $this->builder();
			$names = static function ($rows) {
				return array_column($rows, 'name');
			};
			$rows = $builder->createFindCommand($builder->getSearchExpression(['name'], '50%'))->query()->readAll();
			$this->assertSame(['rate 50%'], $names($rows));
			$rows = $builder->createFindCommand($builder->getSearchExpression(['name'], 'a_b'))->query()->readAll();
			$this->assertSame(['a_b'], $names($rows));
			$rows = $builder->createFindCommand($builder->getSearchExpression(['name'], 'rate'))->query()->readAll();
			$this->assertSame(['rate 50%', 'rate 500'], $names($rows));
		} finally {
			self::$conn->createCommand('DELETE FROM items WHERE id > 2')->execute();
		}
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

	public function test_get_select_field_list_null_value_selects_the_column()
	{
		$result = $this->builder()->getSelectFieldList(['price' => null, 'alias' => null, null]);
		$this->assertSame(['"price"', 'NULL AS alias', 'NULL'], $result);
	}

	public function test_get_select_field_list_falsy_values_are_literals()
	{
		$result = $this->builder()->getSelectFieldList(['price' => 0, 'note' => '']);
		$this->assertSame(['0 AS "price"', "'' AS \"note\""], $result);
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
	// assertActiveTransaction
	// -----------------------------------------------------------------------

	public function test_assertActiveTransaction_passes_when_transaction_is_active(): void
	{
		$tx = self::$conn->beginTransaction();
		try {
			$method = new \ReflectionMethod(TDbCommandBuilder::class, 'assertActiveTransaction');
			$method->invoke($this->builder()); // must not throw
			$this->assertTrue(true);
		} finally {
			$tx->rollback();
		}
	}

	public function test_assertActiveTransaction_throws_without_active_transaction(): void
	{
		// Ensure no transaction is active (rollback any stray one).
		if (self::$conn->getCurrentTransaction() !== null) {
			self::$conn->getCurrentTransaction()->rollback();
		}
		$method = new \ReflectionMethod(TDbCommandBuilder::class, 'assertActiveTransaction');

		$this->expectException(\Prado\Exceptions\TDbException::class);
		$method->invoke($this->builder());
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

	// -----------------------------------------------------------------------
	// getSelectFieldList — key and value forms
	// -----------------------------------------------------------------------

	public function test_get_select_field_list_null_key_selects_null()
	{
		$this->assertSame(['NULL'], $this->builder()->getSelectFieldList(['null' => 'ignored']));
	}

	public function test_get_select_field_list_function_key_is_verbatim()
	{
		$this->assertSame(['MAX(price)'], $this->builder()->getSelectFieldList(['MAX(price)' => 'ignored']));
	}

	public function test_get_select_field_list_as_key_is_verbatim()
	{
		$this->assertSame(['price AS cost'], $this->builder()->getSelectFieldList(['price AS cost' => 'ignored']));
	}

	public function test_get_select_field_list_null_string_value_for_alias_and_position()
	{
		$result = $this->builder()->getSelectFieldList(['alias' => 'NULL', 'NULL']);
		$this->assertSame(['NULL AS alias', 'NULL'], $result);
	}

	public function test_get_select_field_list_function_value_for_column_key_aliases_the_column()
	{
		$result = $this->builder()->getSelectFieldList(['price' => 'MAX(price)', 'top' => 'MIN(id)', 'COUNT(*)']);
		$this->assertSame(['MAX(price) AS "price"', 'MIN(id) AS top', 'COUNT(*)'], $result);
	}

	public function test_get_select_field_list_positional_literals_are_quoted_or_numeric()
	{
		$result = $this->builder()->getSelectFieldList(['literal', 7]);
		$this->assertSame(["'literal'", 7], $result);
	}

	public function test_get_select_field_list_literal_with_alias()
	{
		$result = $this->builder()->getSelectFieldList(['label' => 'literal', 'num' => 3]);
		$this->assertSame(["'literal' AS label", '3 AS num'], $result);
	}

	public function test_get_select_field_list_fractional_literal_with_alias()
	{
		$result = $this->builder()->getSelectFieldList(['ratio' => 2.5]);
		$this->assertSame(['2.5 AS ratio'], $result);
	}

	// -----------------------------------------------------------------------
	// resolveUpdateData
	// -----------------------------------------------------------------------

	public function test_resolve_update_data_null_takes_non_conflict_columns()
	{
		$data = ['id' => 1, 'name' => 'a', 'price' => 2.0];
		$result = PradoUnit::invoke($this->builder(), 'resolveUpdateData', $data, null, ['id']);
		$this->assertSame(['name' => 'a', 'price' => 2.0], $result);
	}

	public function test_resolve_update_data_mixes_column_names_and_explicit_values()
	{
		$data = ['id' => 1, 'name' => 'a', 'price' => 2.0];
		$result = PradoUnit::invoke($this->builder(), 'resolveUpdateData', $data, ['name', 'missing', 'price' => 9.5], ['id']);
		$this->assertSame(['name' => 'a', 'price' => 9.5], $result);
	}

	public function test_resolve_update_data_empty_array_updates_nothing()
	{
		$result = PradoUnit::invoke($this->builder(), 'resolveUpdateData', ['id' => 1, 'name' => 'a'], [], ['id']);
		$this->assertSame([], $result);
	}

	// -----------------------------------------------------------------------
	// buildMergeStatement
	// -----------------------------------------------------------------------

	private function recordingBuilder(): TDbCommandBuilderTestRecordingBuilder
	{
		return new TDbCommandBuilderTestRecordingBuilder(self::$conn, self::$meta->getTableInfo('items'));
	}

	public function test_build_merge_statement_null_update_data_updates_non_conflict_columns()
	{
		$cmd = PradoUnit::invoke($this->recordingBuilder(), 'buildMergeStatement', ['id' => 1, 'name' => 'n'], null, ['id'], 'FROM DUAL', true);
		$this->assertSame(
			'MERGE INTO "items" AS t USING (SELECT :id AS id, :name AS name FROM DUAL) AS s ON (t."id" = s.id)'
			. ' WHEN MATCHED THEN UPDATE SET t."name" = s.name'
			. ' WHEN NOT MATCHED THEN INSERT ("id", "name") VALUES (s.id, s.name)',
			$cmd->getText()
		);
		$this->assertSame([':id', ':name'], array_keys($cmd->bound));
		$this->assertSame('n', $cmd->bound[':name'][0]);
	}

	public function test_build_merge_statement_empty_update_data_omits_matched_branch()
	{
		$cmd = PradoUnit::invoke($this->recordingBuilder(), 'buildMergeStatement', ['id' => 1, 'name' => 'n'], [], ['id'], '', false);
		$this->assertSame(
			'MERGE INTO "items" t USING (SELECT :id AS id, :name AS name) s ON (t."id" = s.id)'
			. ' WHEN NOT MATCHED THEN INSERT ("id", "name") VALUES (s.id, s.name)',
			$cmd->getText()
		);
	}

	public function test_build_merge_statement_explicit_value_overrides_source_alias_for_same_column()
	{
		$data = ['id' => 1, 'name' => 'n', 'price' => 2.0];
		$cmd = PradoUnit::invoke($this->recordingBuilder(), 'buildMergeStatement', $data, ['name', 'price', 'name' => 'fixed'], ['id'], '', true);
		$this->assertSame(
			'MERGE INTO "items" AS t USING (SELECT :id AS id, :name AS name, :price AS price) AS s ON (t."id" = s.id)'
			. ' WHEN MATCHED THEN UPDATE SET t."price" = s.price, t."name" = :_upsert_name'
			. ' WHEN NOT MATCHED THEN INSERT ("id", "name", "price") VALUES (s.id, s.name, s.price)',
			$cmd->getText()
		);
		$this->assertSame(['fixed', null], $cmd->bound[':_upsert_name']);
		$this->assertSame([':id', ':name', ':price', ':_upsert_name'], array_keys($cmd->bound));
	}

	// -----------------------------------------------------------------------
	// Binding
	// -----------------------------------------------------------------------

	public function test_bind_column_values_binds_null_for_nullable_column()
	{
		$cmd = $this->recordingBuilder()->createInsertCommand(['name' => 'x', 'note' => null]);
		$this->assertSame([null, self::$conn::PARAM_NULL], $cmd->bound[':note']);
		$this->assertSame('x', $cmd->bound[':name'][0]);
	}

	public function test_insert_with_null_for_nullable_column_stores_null()
	{
		$tx = self::$conn->beginTransaction();
		try {
			$this->builder()->createInsertCommand(['id' => 3, 'name' => 'baz', 'note' => null])->execute();
			$row = self::$conn->createCommand('SELECT name, note FROM items WHERE id = 3')->queryRow();
			$this->assertSame(['name' => 'baz', 'note' => null], $row);
		} finally {
			$tx->rollback();
		}
	}

	public function test_bind_array_values_positional_float_binds_without_type()
	{
		$cmd = $this->builder()->createFindCommand('price > ?', [5.0], ['id' => 'asc'], -1, -1, 'name');
		$this->assertSame(['foo'], $cmd->queryColumn());
	}

	// -----------------------------------------------------------------------
	// getIndexCondition / createIndexCriteria
	// -----------------------------------------------------------------------

	private function pairsBuilder(): TDbCommandBuilder
	{
		return new TDbCommandBuilder(self::$conn, self::$meta->getTableInfo('pairs'));
	}

	/** @return array the "a:b" keys of the pairs rows matching the condition, in key order. */
	private function pairsWhere(string $condition): array
	{
		$rows = self::$conn->createCommand("SELECT a, b FROM pairs WHERE {$condition} ORDER BY a, b")->queryAll();
		return array_map(fn ($row) => $row['a'] . ':' . $row['b'], $rows);
	}

	public function test_index_condition_without_values_is_portable_false()
	{
		$this->assertSame('1=0', $this->pairsBuilder()->getIndexCondition(['a', 'b'], []));
		$this->assertSame('1=0', $this->builder()->createIndexCriteria(['id'], [])->getCondition());
		$this->assertSame([], $this->pairsWhere('1=0'));
	}

	public function test_index_condition_of_one_field_is_an_in_list()
	{
		$builder = $this->builder();
		$this->assertSame("\"items\".\"id\" IN ('1', '2')", $builder->getIndexCondition(['id'], [1, 2]));
		$this->assertSame("\"items\".\"id\" IN ('1', '2')", $builder->getIndexCondition(['id'], [[1], ['id' => 2]]));
	}

	public function test_index_condition_of_several_fields_joins_equalities()
	{
		$builder = $this->pairsBuilder();
		$this->assertSame(
			"(\"pairs\".\"a\" = '1' AND \"pairs\".\"b\" = 'x')",
			$builder->getIndexCondition(['a', 'b'], [[1, 'x']])
		);
		$this->assertSame(
			"((\"pairs\".\"a\" = '1' AND \"pairs\".\"b\" = 'x') OR (\"pairs\".\"a\" = '2' AND \"pairs\".\"b\" = 'y'))",
			$builder->createIndexCriteria(['a', 'b'], [[1, 'x'], ['a' => 2, 'b' => 'y']])->getCondition()
		);
	}

	public function test_index_condition_of_several_rows_keeps_precedence_after_and()
	{
		$condition = $this->pairsBuilder()->getIndexCondition(['a', 'b'], [[1, 'x'], [2, 'x'], [2, 'y']]);

		$this->assertSame(['1:x', '2:x', '2:y'], $this->pairsWhere($condition));
		$this->assertSame(['1:x', '2:y'], $this->pairsWhere("val = 'keep' AND " . $condition));
	}

	public function test_index_condition_of_another_table()
	{
		$condition = $this->builder()->getIndexCondition(['a', 'b'], [[2, 'y']], self::$meta->getTableInfo('pairs'));

		$this->assertSame("(\"pairs\".\"a\" = '2' AND \"pairs\".\"b\" = 'y')", $condition);
		$this->assertSame(['2:y'], $this->pairsWhere($condition));
	}

	// -----------------------------------------------------------------------
	// createPrimaryKeyCriteria / tableExists
	// -----------------------------------------------------------------------

	public function test_create_primary_key_criteria_throws_without_primary_key()
	{
		$builder = new TDbCommandBuilder(self::$conn, self::$meta->getTableInfo('nopk'));
		$this->expectException(TDbException::class);
		$builder->createPrimaryKeyCriteria(1);
	}

	public function test_table_exists_is_false_for_missing_table()
	{
		$builder = new TDbCommandBuilder(self::$conn, new TDbTableInfo(['TableName' => 'no_such_table']));
		$this->assertFalse($builder->tableExists());
		$this->assertTrue($this->builder()->tableExists());
	}

	public function test_build_merge_statement_applies_the_driver_post_processing()
	{
		$builder = $this->recordingBuilder();
		$builder->mergeSuffix = ';';
		$cmd = PradoUnit::invoke($builder, 'buildMergeStatement', ['id' => 1], [], ['id'], '', true);
		$this->assertSame(
			'MERGE INTO "items" AS t USING (SELECT :id AS id) AS s ON (t."id" = s.id) WHEN NOT MATCHED THEN INSERT ("id") VALUES (s.id);',
			$cmd->getText()
		);
	}
}
