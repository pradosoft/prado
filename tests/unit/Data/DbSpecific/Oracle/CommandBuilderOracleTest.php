<?php

use Prado\Data\Common\Oracle\TOracleCommandBuilder;
use Prado\Data\Common\Oracle\TOracleTableInfo;

class CommandBuilderOracleTest extends PHPUnit\Framework\TestCase
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
}
