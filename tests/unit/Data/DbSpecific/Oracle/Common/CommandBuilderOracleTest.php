<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle\Common;

use Prado\Data\Common\Oracle\TOracleCommandBuilder;
use Prado\Data\Common\Oracle\TOracleDbCommand;
use Prado\Data\Common\Oracle\TOracleTableColumn;
use Prado\Data\Common\Oracle\TOracleTableInfo;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

class CommandBuilderOracleTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Creates a command builder with a minimal stub table info (no DB connection required).
	 * @param string $schema
	 * @param string $table
	 * @return TOracleCommandBuilder
	 */
	protected function make_builder($schema = 'PRADO_TEST', $table = 'ACCOUNTS')
	{
		$tableInfo = new TOracleTableInfo(['SchemaName' => $schema, 'TableName' => $table]);
		return new TOracleCommandBuilder(null, $tableInfo);
	}

	public function test_oracle_no_limit_no_offset()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT username, age FROM accounts';

		// Oracle early-return condition is limit <= 0 AND offset <= 0
		$result = $builder->applyLimitOffset($sql, -1, -1);
		$this->assertEquals($sql, $result);
	}

	public function test_oracle_zero_limit_and_offset_returns_unchanged()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT username FROM accounts';

		$result = $builder->applyLimitOffset($sql, 0, 0);
		$this->assertEquals($sql, $result);
	}

	public function test_oracle_limit_and_offset_wraps_with_row_number()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT age FROM accounts';

		$result = $builder->applyLimitOffset($sql, 10, 5);

		$this->assertStringContainsString('ROW_NUMBER() OVER', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN >= 5', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN < 15', $result);
		$this->assertStringContainsString('PRADO_TEST.ACCOUNTS', $result);
		$this->assertStringStartsWith(' SELECT age FROM', $result);
	}

	public function test_oracle_limit_only_uses_zero_based_offset()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT id FROM accounts';

		// offset defaults to 0 when not provided
		$result = $builder->applyLimitOffset($sql, 5, 0);

		$this->assertStringContainsString('ROW_NUMBER() OVER', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN >= 0', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN < 5', $result);
	}

	public function test_oracle_limit_with_unset_offset_starts_at_row_zero()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT id FROM accounts';

		// A criteria without an offset passes -1; the window still starts at row 0.
		$result = $builder->applyLimitOffset($sql, 1, -1);

		$this->assertStringContainsString('nn.pradoNUMLIN >= 0', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN < 1', $result);
		$this->assertStringNotContainsString('>= -1', $result);
	}

	public function test_oracle_offset_without_limit_has_no_upper_bound()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT id FROM accounts';

		$result = $builder->applyLimitOffset($sql, -1, 5);

		$this->assertStringContainsString('nn.pradoNUMLIN >= 5', $result);
		$this->assertStringNotContainsString('nn.pradoNUMLIN <', $result);
	}

	public function test_oracle_order_by_is_preserved_in_row_number()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT age FROM accounts ORDER BY age DESC';

		$result = $builder->applyLimitOffset($sql, 10, 0);

		// ORDER BY clause is captured and used inside ROW_NUMBER() OVER.
		// substr($sql, $p + 8) skips 'ORDER BY' (8 chars) and preserves the leading
		// space before the column name, resulting in a double-space before the column.
		$this->assertStringContainsString('ROW_NUMBER() OVER ( ORDER BY', $result);
		$this->assertStringContainsString('age DESC', $result);
	}

	public function test_oracle_table_full_name_used_in_subquery()
	{
		$builder = $this->make_builder('MYSCHEMA', 'USERS');
		$sql = 'SELECT id FROM users';

		$result = $builder->applyLimitOffset($sql, 3, 0);

		$this->assertStringContainsString('MYSCHEMA.USERS', $result);
	}

	public function test_oracle_row_number_default_order_is_rownum()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT age FROM accounts';

		// When no ORDER BY is present, ROW_NUMBER() orders by ROWNUM
		$result = $builder->applyLimitOffset($sql, 5, 0);

		$this->assertStringContainsString('ORDER BY ROWNUM', $result);
	}

	/**
	 * Creates a command builder over a stub table with the given columns, so the
	 * `SELECT *` rewrite has a column list to expand.
	 * @param string[] $columns column ids
	 * @return TOracleCommandBuilder
	 */
	protected function make_builder_with_columns(array $columns = ['department_id', 'name', 'sort_order'])
	{
		$tableInfo = new TOracleTableInfo(['SchemaName' => 'PRADO_TEST', 'TableName' => 'DEPARTMENTS'], [$columns[0]]);
		foreach ($columns as $index => $column) {
			$tableInfo->getColumns()[$column] = new TOracleTableColumn([
				'ColumnName' => strtoupper($column),
				'ColumnId' => $column,
				'ColumnIndex' => $index,
				'DbType' => $index === 1 ? 'varchar2' : 'number',
			]);
		}
		return new TOracleCommandBuilder(null, $tableInfo);
	}

	/**
	 * @param string $sql
	 * @return string the SQL with runs of white space collapsed to one space
	 */
	protected function collapse($sql)
	{
		return trim(preg_replace('/\s+/', ' ', $sql));
	}

	public function test_column_named_sort_order_is_not_an_order_by_clause()
	{
		$builder = $this->make_builder_with_columns();
		$sql = 'SELECT * FROM PRADO_TEST.DEPARTMENTS WHERE department_id < ? AND sort_order > ?';

		$result = $this->collapse($builder->applyLimitOffset($sql, 2, 0));

		$this->assertStringStartsWith('SELECT department_id, name, sort_order FROM (', $result);
		$this->assertStringContainsString('ROW_NUMBER() OVER ( ORDER BY ROWNUM ) -1 as pradoNUMLIN , xyz.* FROM PRADO_TEST.DEPARTMENTS xyz WHERE department_id < ? AND sort_order > ?) nn', $result);
		$this->assertStringContainsString('WHERE nn.pradoNUMLIN >= 0 AND nn.pradoNUMLIN < 2', $result);
		$this->assertStringNotContainsString('sort_ ', $result);
	}

	public function test_order_by_on_sort_order_column_ends_the_where_clause()
	{
		$builder = $this->make_builder_with_columns();
		$sql = 'SELECT * FROM PRADO_TEST.DEPARTMENTS WHERE sort_order > ? ORDER BY sort_order DESC, department_id';

		$result = $this->collapse($builder->applyLimitOffset($sql, 5, 10));

		$this->assertStringContainsString('ROW_NUMBER() OVER ( ORDER BY sort_order DESC, department_id ) -1 as pradoNUMLIN , xyz.* FROM PRADO_TEST.DEPARTMENTS xyz WHERE sort_order > ? ) nn', $result);
		$this->assertStringContainsString('WHERE nn.pradoNUMLIN >= 10 AND nn.pradoNUMLIN < 15', $result);
	}

	public function test_keywords_inside_quoted_strings_are_not_clauses()
	{
		$builder = $this->make_builder_with_columns();
		$sql = "SELECT * FROM PRADO_TEST.DEPARTMENTS WHERE name = 'select from where order by' OR name = 'it''s from'";

		$result = $this->collapse($builder->applyLimitOffset($sql, 3, 0));

		$this->assertStringStartsWith('SELECT department_id, name, sort_order FROM (', $result);
		$this->assertStringContainsString("ORDER BY ROWNUM ) -1 as pradoNUMLIN , xyz.* FROM PRADO_TEST.DEPARTMENTS xyz WHERE name = 'select from where order by' OR name = 'it''s from') nn", $result);
	}

	public function test_columns_named_after_from_and_where_are_not_clauses()
	{
		$builder = $this->make_builder_with_columns(['id', 'from_date', 'where_flag']);
		$sql = 'SELECT from_date FROM PRADO_TEST.DEPARTMENTS WHERE from_date < ? AND where_flag = ?';

		$result = $this->collapse($builder->applyLimitOffset($sql, 4, 0));

		$this->assertStringStartsWith('SELECT from_date FROM (', $result);
		$this->assertStringContainsString('FROM PRADO_TEST.DEPARTMENTS xyz WHERE from_date < ? AND where_flag = ?) nn', $result);
	}

	public function test_lower_case_clauses_are_matched()
	{
		$builder = $this->make_builder_with_columns();
		$sql = 'select * from PRADO_TEST.DEPARTMENTS where department_id > ? order by department_id';

		$result = $this->collapse($builder->applyLimitOffset($sql, 2, 0));

		$this->assertStringStartsWith('SELECT department_id, name, sort_order FROM (', $result);
		$this->assertStringContainsString('OVER ( ORDER BY department_id ) -1 as pradoNUMLIN , xyz.* FROM PRADO_TEST.DEPARTMENTS xyz WHERE department_id > ? ) nn', $result);
	}
	// -----------------------------------------------------------------------
	// Paging without a table: OFFSET / FETCH NEXT
	// -----------------------------------------------------------------------

	public function test_tableless_limit_and_offset_uses_offset_fetch()
	{
		$builder = new TOracleCommandBuilder(null, new TOracleTableInfo());

		$this->assertSame('SELECT * FROM t OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY', $builder->applyLimitOffset("SELECT * FROM t \n", 10, 5));
		$this->assertSame('SELECT * FROM t FETCH NEXT 3 ROWS ONLY', $builder->applyLimitOffset('SELECT * FROM t', 3, -1));
		$this->assertSame('SELECT * FROM t OFFSET 4 ROWS', $builder->applyLimitOffset('SELECT * FROM t', -1, 4));
		$this->assertSame('SELECT * FROM t OFFSET 4 ROWS', $builder->applyLimitOffset('SELECT * FROM t', 0, 4));
	}

	public function test_builder_without_table_info_uses_offset_fetch()
	{
		$builder = new TOracleCommandBuilder();

		$this->assertSame('SELECT 1 FROM DUAL FETCH NEXT 1 ROWS ONLY', $builder->applyLimitOffset('SELECT 1 FROM DUAL', '1', null));
	}

	public function test_select_list_fields_are_aliased_to_the_table()
	{
		$builder = $this->make_builder();

		$result = $this->collapse($builder->applyLimitOffset('SELECT id, Name FROM accounts', 2, 1));

		$this->assertSame('SELECT id, Name FROM ( SELECT ROW_NUMBER() OVER ( ORDER BY ROWNUM ) -1 as pradoNUMLIN , xyz.id, xyz.name FROM PRADO_TEST.ACCOUNTS xyz ) nn WHERE nn.pradoNUMLIN >= 1 AND nn.pradoNUMLIN < 3', $result);
	}

	public function test_select_list_with_table_columns()
	{
		$builder = $this->make_builder();
		$builder->getTableInfo()->getColumns()->add('id', new TOracleTableColumn(['ColumnName' => 'ID', 'ColumnId' => 'id']));

		$result = $this->collapse($builder->applyLimitOffset('SELECT id, score AS s FROM accounts', 2, 0));

		$this->assertSame('SELECT id, score AS s FROM ( SELECT ROW_NUMBER() OVER ( ORDER BY ROWNUM ) -1 as pradoNUMLIN , xyz.id, score as s FROM PRADO_TEST.ACCOUNTS xyz ) nn WHERE nn.pradoNUMLIN >= 0 AND nn.pradoNUMLIN < 2', $result);
	}

	public function test_select_without_from_clause()
	{
		$builder = $this->make_builder();

		$result = $this->collapse($builder->applyLimitOffset('SELECT 1', 2, 0));

		$this->assertSame('SELECT 1 FROM ( SELECT ROW_NUMBER() OVER ( ORDER BY ROWNUM ) -1 as pradoNUMLIN , xyz.* FROM PRADO_TEST.ACCOUNTS xyz ) nn WHERE nn.pradoNUMLIN >= 0 AND nn.pradoNUMLIN < 2', $result);
	}

	public function test_find_clause_returns_offset_and_length()
	{
		$builder = $this->make_builder();

		$this->assertSame([33, 9], PradoUnit::invoke($builder, 'findClause', "SELECT a FROM t WHERE 'order by' ORDER  BY a", 'ORDER\s+BY'));
		$this->assertSame([0, 6], PradoUnit::invoke($builder, 'findClause', 'select x', 'SELECT'));
		$this->assertNull(PradoUnit::invoke($builder, 'findClause', 'SELECT a FROM t', 'WHERE'));
		$this->assertNull(PradoUnit::invoke($builder, 'findClause', "SELECT 'where' FROM \"WHERE\"", 'WHERE'));
	}

	// -----------------------------------------------------------------------
	// Search expressions
	// -----------------------------------------------------------------------

	/**
	 * A builder over an in-memory SQLite connection, for string quoting, with
	 * columns of several Oracle types.
	 * @return TOracleCommandBuilder
	 */
	protected function make_search_builder()
	{
		$conn = new TDbConnection('sqlite::memory:');
		$conn->setActive(true);
		$tableInfo = new TOracleTableInfo(['SchemaName' => 'PRADO_TEST', 'TableName' => 'ACCOUNTS'], ['id']);
		$types = ['id' => 'NUMBER', 'name' => 'VARCHAR2', 'code' => 'CHAR', 'notes' => 'text', 'title' => 'Character Varying', 'flag' => 'character', 'created' => 'DATE'];
		$index = 0;
		foreach ($types as $id => $type) {
			$tableInfo->getColumns()[$id] = new TOracleTableColumn(['ColumnName' => $id, 'ColumnId' => $id, 'ColumnIndex' => $index++, 'DbType' => $type]);
		}
		return new TOracleCommandBuilder($conn, $tableInfo);
	}

	public function test_searchable_column_types()
	{
		$builder = $this->make_search_builder();
		$columns = $builder->getTableInfo()->getColumns();

		$searchable = [];
		foreach ($columns as $id => $column) {
			$searchable[$id] = PradoUnit::invoke($builder, 'isSearchableColumn', $column);
		}

		$this->assertSame(['id' => false, 'name' => true, 'code' => true, 'notes' => true, 'title' => true, 'flag' => true, 'created' => false], $searchable);
	}

	public function test_search_expression_skips_unsearchable_columns()
	{
		$builder = $this->make_search_builder();

		$result = $builder->getSearchExpression(['id', 'name', 'created', 'code'], 'ab 5%');

		$this->assertSame("((name LIKE '%ab%' ESCAPE '\\' AND name LIKE '%5\\%%' ESCAPE '\\') OR (code LIKE '%ab%' ESCAPE '\\' AND code LIKE '%5\\%%' ESCAPE '\\'))", $result);
	}

	public function test_search_expression_with_blank_keywords_is_empty()
	{
		$builder = $this->make_search_builder();

		$this->assertSame('', $builder->getSearchExpression(['name'], '  '));
	}

	// -----------------------------------------------------------------------
	// insertOrIgnore / upsert MERGE statements
	// -----------------------------------------------------------------------

	/**
	 * A builder whose connection is an in-memory SQLite database that creates
	 * {@see TOracleDbCommand} commands. The OCI command defers parameter binding,
	 * so the MERGE text is built without the SQLite driver preparing it.
	 * @param bool $transaction whether to begin a transaction
	 * @return TOracleCommandBuilder
	 */
	protected function make_merge_builder(bool $transaction = true)
	{
		$conn = new CommandBuilderOracleConnection('sqlite::memory:');
		$conn->setActive(true);
		if ($transaction) {
			$conn->beginTransaction();
		}
		$tableInfo = new TOracleTableInfo(['SchemaName' => 'PRADO_TEST', 'TableName' => 'UPSERT_TEST'], ['username']);
		$tableInfo->getColumns()['username'] = new TOracleTableColumn(['ColumnName' => 'username', 'ColumnId' => 'username', 'ColumnIndex' => 0, 'DbType' => 'VARCHAR2', 'IsPrimaryKey' => true]);
		$tableInfo->getColumns()['score'] = new TOracleTableColumn(['ColumnName' => 'score', 'ColumnId' => 'score', 'ColumnIndex' => 1, 'DbType' => 'NUMBER']);
		$tableInfo->getColumns()['email'] = new TOracleTableColumn(['ColumnName' => 'email', 'ColumnId' => 'email', 'ColumnIndex' => 2, 'DbType' => 'VARCHAR2', 'AllowNull' => true]);
		return new TOracleCommandBuilder($conn, $tableInfo);
	}

	public function test_insert_or_ignore_builds_merge_from_dual()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createInsertOrIgnoreCommand(['username' => 'wei', 'score' => 5]);

		$this->assertInstanceOf(TOracleDbCommand::class, $command);
		$this->assertSame(
			'MERGE INTO PRADO_TEST.UPSERT_TEST t USING (SELECT :username AS username, :score AS score FROM DUAL) s ON (t.username = s.username)'
			. ' WHEN NOT MATCHED THEN INSERT (username, score) VALUES (s.username, s.score)',
			$command->getText()
		);
		$this->assertSame([':username' => 'wei', ':score' => 5], PradoUnit::getProp($command, '_ociParams'));
	}

	public function test_upsert_updates_non_conflict_columns_by_default()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createUpsertCommand(['username' => 'wei', 'score' => 5, 'email' => null]);

		$this->assertSame(
			'MERGE INTO PRADO_TEST.UPSERT_TEST t USING (SELECT :username AS username, :score AS score, :email AS email FROM DUAL) s ON (t.username = s.username)'
			. ' WHEN MATCHED THEN UPDATE SET t.score = s.score, t.email = s.email'
			. ' WHEN NOT MATCHED THEN INSERT (username, score, email) VALUES (s.username, s.score, s.email)',
			$command->getText()
		);
		$this->assertSame([':username' => 'wei', ':score' => 5, ':email' => null], PradoUnit::getProp($command, '_ociParams'));
	}

	public function test_upsert_with_explicit_values_and_conflict_columns()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createUpsertCommand(['username' => 'wei', 'score' => 5, 'email' => 'w@x.y'], ['email', 'score' => 99], ['username', 'email']);

		$this->assertSame(
			'MERGE INTO PRADO_TEST.UPSERT_TEST t USING (SELECT :username AS username, :score AS score, :email AS email FROM DUAL) s ON (t.username = s.username AND t.email = s.email)'
			. ' WHEN MATCHED THEN UPDATE SET t.email = s.email, t.score = :_upsert_score'
			. ' WHEN NOT MATCHED THEN INSERT (username, score, email) VALUES (s.username, s.score, s.email)',
			$command->getText()
		);
		$this->assertSame([':username' => 'wei', ':score' => 5, ':email' => 'w@x.y', ':_upsert_score' => 99], PradoUnit::getProp($command, '_ociParams'));
	}

	public function test_upsert_with_empty_update_data_omits_matched_branch()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createUpsertCommand(['username' => 'wei'], []);

		$this->assertSame(
			'MERGE INTO PRADO_TEST.UPSERT_TEST t USING (SELECT :username AS username FROM DUAL) s ON (t.username = s.username)'
			. ' WHEN NOT MATCHED THEN INSERT (username) VALUES (s.username)',
			$command->getText()
		);
	}

	public function test_insert_or_ignore_requires_a_transaction()
	{
		$builder = $this->make_merge_builder(false);

		$this->expectException(TDbException::class);
		$builder->createInsertOrIgnoreCommand(['username' => 'wei']);
	}

	public function test_upsert_requires_a_transaction()
	{
		$builder = $this->make_merge_builder(false);

		$this->expectException(TDbException::class);
		$builder->createUpsertCommand(['username' => 'wei']);
	}
}

/**
 * An SQLite connection that creates Oracle commands, which defer binding until execution.
 */
class CommandBuilderOracleConnection extends TDbConnection
{
	public function createCommand($sql)
	{
		$this->assertActive();
		return new TOracleDbCommand($this, $sql);
	}
}
