<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle;

use Prado\Data\Common\Oracle\TOracleCommandBuilder;
use Prado\Data\Common\Oracle\TOracleTableColumn;
use Prado\Data\Common\Oracle\TOracleTableInfo;

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

	public function test_no_limit_no_offset()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT username, age FROM accounts';

		// Oracle early-return condition is limit <= 0 AND offset <= 0
		$result = $builder->applyLimitOffset($sql, -1, -1);
		$this->assertEquals($sql, $result);
	}

	public function test_zero_limit_and_offset_returns_unchanged()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT username FROM accounts';

		$result = $builder->applyLimitOffset($sql, 0, 0);
		$this->assertEquals($sql, $result);
	}

	public function test_limit_and_offset_wraps_with_row_number()
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

	public function test_limit_only_uses_zero_based_offset()
	{
		$builder = $this->make_builder();
		$sql = 'SELECT id FROM accounts';

		// offset defaults to 0 when not provided
		$result = $builder->applyLimitOffset($sql, 5, 0);

		$this->assertStringContainsString('ROW_NUMBER() OVER', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN >= 0', $result);
		$this->assertStringContainsString('nn.pradoNUMLIN < 5', $result);
	}

	public function test_order_by_is_preserved_in_row_number()
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

	public function test_table_full_name_used_in_subquery()
	{
		$builder = $this->make_builder('MYSCHEMA', 'USERS');
		$sql = 'SELECT id FROM users';

		$result = $builder->applyLimitOffset($sql, 3, 0);

		$this->assertStringContainsString('MYSCHEMA.USERS', $result);
	}

	public function test_row_number_default_order_is_rownum()
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
}
