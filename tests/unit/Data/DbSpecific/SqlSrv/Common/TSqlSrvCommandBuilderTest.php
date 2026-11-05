<?php

namespace Prado\Test\Unit\Data\DbSpecific\SqlSrv\Common;

use Prado\Data\Common\SqlSrv\TSqlSrvCommandBuilder;
use Prado\Data\Common\SqlSrv\TSqlSrvTableColumn;
use Prado\Data\Common\SqlSrv\TSqlSrvTableInfo;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * Command that records bound values without preparing the T-SQL on SQLite.
 */
class SqlSrvRecordingCommand extends TDbCommand
{
	/** @var array<string, array{0: mixed, 1: ?int}> parameter name => [value, data type]. */
	public array $bound = [];

	public function bindValue($name, $value, $dataType = null)
	{
		$this->bound[$name] = [$value, $dataType];
	}
}

/**
 * SQLite connection for SQL Server command builder tests.
 *
 * MERGE commands are {@see SqlSrvRecordingCommand}s and "SELECT @@Identity"
 * returns a fixed identity value.
 */
class SqlSrvIdentityConnection extends TDbConnection
{
	public function __construct()
	{
		parent::__construct('sqlite::memory:');
	}

	public function createCommand($sql)
	{
		if (str_starts_with($sql, 'MERGE ')) {
			return new SqlSrvRecordingCommand($this, $sql);
		}
		return parent::createCommand($sql === 'SELECT @@Identity' ? "SELECT '42'" : $sql);
	}
}

/**
 * Server-less tests for TSqlSrvCommandBuilder.
 *
 * MERGE commands are created on an in-memory SQLite connection that records
 * bound values, so the T-SQL text and bindings are asserted without being executed.
 */
class TSqlSrvCommandBuilderTest extends \PHPUnit\Framework\TestCase
{
	private SqlSrvIdentityConnection $_conn;

	protected function setUp(): void
	{
		$this->_conn = new SqlSrvIdentityConnection();
		$this->_conn->setActive(true);
	}

	protected function tearDown(): void
	{
		if ($this->_conn->getCurrentTransaction() !== null) {
			$this->_conn->getCurrentTransaction()->rollback();
		}
		$this->_conn->setActive(false);
	}

	/**
	 * @param bool $identity true to make "id" an identity column.
	 * @return TSqlSrvTableInfo [prado_unitest].[dbo].[scores] (id PK, name, score).
	 */
	private function createTableInfo(bool $identity = true): TSqlSrvTableInfo
	{
		$info = new TSqlSrvTableInfo(['CatalogName' => 'prado_unitest', 'SchemaName' => 'dbo', 'TableName' => 'scores'], ['id'], []);
		$columns = $info->getColumns();
		$columns['id'] = new TSqlSrvTableColumn(['ColumnName' => '[id]', 'ColumnId' => 'id', 'ColumnIndex' => 0, 'DbType' => 'int', 'IsPrimaryKey' => true, 'AutoIncrement' => $identity]);
		$columns['name'] = new TSqlSrvTableColumn(['ColumnName' => '[name]', 'ColumnId' => 'name', 'ColumnIndex' => 1, 'DbType' => 'nvarchar']);
		$columns['score'] = new TSqlSrvTableColumn(['ColumnName' => '[score]', 'ColumnId' => 'score', 'ColumnIndex' => 2, 'DbType' => 'int']);
		return $info;
	}

	private function createBuilder(bool $identity = true): TSqlSrvCommandBuilder
	{
		return $this->createTableInfo($identity)->createCommandBuilder($this->_conn);
	}

	// -----------------------------------------------------------------------
	// insertOrIgnore / upsert MERGE text
	// -----------------------------------------------------------------------

	public function test_insert_or_ignore_requires_transaction(): void
	{
		$this->expectException(TDbException::class);
		$this->createBuilder()->createInsertOrIgnoreCommand(['id' => 1, 'name' => 'alice']);
	}

	public function test_upsert_requires_transaction(): void
	{
		$this->expectException(TDbException::class);
		$this->createBuilder()->createUpsertCommand(['id' => 1, 'name' => 'alice']);
	}

	public function test_insert_or_ignore_merge_text(): void
	{
		$this->_conn->beginTransaction();
		$command = $this->createBuilder()->createInsertOrIgnoreCommand(['id' => 1, 'name' => 'alice']);

		$this->assertSame(
			'MERGE INTO [prado_unitest].[dbo].[scores] AS t USING (SELECT :id AS id, :name AS name) AS s ON (t.[id] = s.id)'
			. ' WHEN NOT MATCHED THEN INSERT ([id], [name]) VALUES (s.id, s.name);',
			$command->getText()
		);
		$this->assertSame([':id' => [1, \PDO::PARAM_STR], ':name' => ['alice', \PDO::PARAM_STR]], $command->bound);
	}

	public function test_upsert_merge_text_updates_non_key_columns(): void
	{
		$this->_conn->beginTransaction();
		$command = $this->createBuilder()->createUpsertCommand(['id' => 1, 'name' => 'alice', 'score' => 7]);

		$this->assertSame(
			'MERGE INTO [prado_unitest].[dbo].[scores] AS t USING (SELECT :id AS id, :name AS name, :score AS score) AS s ON (t.[id] = s.id)'
			. ' WHEN MATCHED THEN UPDATE SET t.[name] = s.name, t.[score] = s.score'
			. ' WHEN NOT MATCHED THEN INSERT ([id], [name], [score]) VALUES (s.id, s.name, s.score);',
			$command->getText()
		);
	}

	public function test_upsert_merge_text_explicit_conflict_and_mixed_update(): void
	{
		$this->_conn->beginTransaction();
		$command = $this->createBuilder()->createUpsertCommand(['name' => 'alice', 'score' => 7], ['score', 'score' => 99], ['name']);

		$this->assertSame(
			'MERGE INTO [prado_unitest].[dbo].[scores] AS t USING (SELECT :name AS name, :score AS score) AS s ON (t.[name] = s.name)'
			. ' WHEN MATCHED THEN UPDATE SET t.[score] = :_upsert_score'
			. ' WHEN NOT MATCHED THEN INSERT ([name], [score]) VALUES (s.name, s.score);',
			$command->getText()
		);
		$this->assertSame([':name' => ['alice', \PDO::PARAM_STR], ':score' => [7, \PDO::PARAM_STR], ':_upsert_score' => [99, null]], $command->bound);
	}

	public function test_post_process_merge_appends_semicolon(): void
	{
		$this->assertSame('MERGE INTO x;', PradoUnit::invoke(new TSqlSrvCommandBuilder(), 'postProcessMerge', 'MERGE INTO x'));
	}

	// -----------------------------------------------------------------------
	// getLastInsertID
	// -----------------------------------------------------------------------

	public function test_last_insert_id_with_identity_column(): void
	{
		$this->assertSame(42, $this->createBuilder(true)->getLastInsertID());
	}

	public function test_last_insert_id_without_identity_column(): void
	{
		$this->assertNull($this->createBuilder(false)->getLastInsertID());
	}

	// -----------------------------------------------------------------------
	// applyLimitOffset
	// -----------------------------------------------------------------------

	public function test_apply_limit_offset_limit_only(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame('SELECT DISTINCT TOP 5 a FROM t', $builder->applyLimitOffset('SELECT DISTINCT a FROM t', 5));
		$this->assertSame('(SELECT TOP 5 a FROM t)', $builder->applyLimitOffset('(SELECT a FROM t)', 5, 0));
		$this->assertSame('SELECT TOP (10) a FROM t', $builder->applyLimitOffset('SELECT TOP (10) a FROM t', 5));
	}

	public function test_apply_limit_offset_offset_only(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame('SELECT a FROM t ORDER BY (SELECT NULL) OFFSET 4 ROWS', $builder->applyLimitOffset('SELECT a FROM t  ', -1, 4));
		$this->assertSame('SELECT a FROM t order by a OFFSET 4 ROWS', $builder->applyLimitOffset('SELECT a FROM t order by a', 0, 4));
	}

	public function test_apply_limit_offset_no_limit_no_offset(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame('SELECT a FROM t  ', $builder->applyLimitOffset('SELECT a FROM t  ', null, null));
		$this->assertSame('SELECT a FROM t', $builder->applyLimitOffset('SELECT a FROM t', 0, 0));
		$this->assertSame('SELECT a FROM t', $builder->applyLimitOffset('SELECT a FROM t'));
	}

	public function test_apply_limit_offset_numeric_strings(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame('SELECT a FROM t ORDER BY (SELECT NULL) OFFSET 2 ROWS FETCH NEXT 7 ROWS ONLY', $builder->applyLimitOffset('SELECT a FROM t', '7', '2'));
	}

	// -----------------------------------------------------------------------
	// TOP-nesting rewrite helpers
	// -----------------------------------------------------------------------

	public function test_find_ordering(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame([], PradoUnit::invoke($builder, 'findOrdering', 'SELECT a FROM t'));
		$this->assertSame(['age' => 'DESC', 'name' => 'ASC'], PradoUnit::invoke($builder, 'findOrdering', 'SELECT a FROM t ORDER BY age DESC, name'));
		$this->assertSame(['[age]' => 'asc', 'b' => 'desc'], PradoUnit::invoke($builder, 'findOrdering', 'SELECT a FROM t order by [age] asc, b desc'));
		$this->assertSame([1 => 'DESC', 2 => 'ASC'], PradoUnit::invoke($builder, 'findOrdering', 'SELECT a FROM t ORDER BY 1 DESC, 2 ASC'));
	}

	public function test_join_ordering(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame('', PradoUnit::invoke($builder, 'joinOrdering', []));
		$this->assertSame('ORDER BY age DESC, name ASC', PradoUnit::invoke($builder, 'joinOrdering', ['age' => 'DESC', 'name' => 'ASC']));
	}

	public function test_reverse_direction(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame([], PradoUnit::invoke($builder, 'reverseDirection', []));
		$this->assertSame(['age' => 'ASC', 'name' => 'DESC', 'id' => 'ASC'], PradoUnit::invoke($builder, 'reverseDirection', ['age' => 'DESC', 'name' => 'ASC', 'id' => ' desc ']));
	}

	public function test_rewrite_limit_offset_sql(): void
	{
		$builder = new TSqlSrvCommandBuilder();

		$this->assertSame(
			'SELECT * FROM (SELECT TOP 3 * FROM (SELECT TOP 5 a FROM t ORDER BY age DESC, name) as [__inner top table__] ORDER BY age ASC, name DESC) as [__outer top table__] ORDER BY age DESC, name ASC',
			PradoUnit::invoke($builder, 'rewriteLimitOffsetSql', 'SELECT a FROM t ORDER BY age DESC, name', 3, 2)
		);
		$this->assertSame(
			'SELECT * FROM (SELECT TOP 3 * FROM (SELECT TOP 5 a FROM t) as [__inner top table__] ) as [__outer top table__] ',
			PradoUnit::invoke($builder, 'rewriteLimitOffsetSql', 'SELECT a FROM t', 3, 2)
		);
	}

	// -----------------------------------------------------------------------
	// TSqlSrvTableInfo / TSqlSrvTableColumn
	// -----------------------------------------------------------------------

	public function test_table_info(): void
	{
		$info = $this->createTableInfo();

		$this->assertSame('prado_unitest', $info->getCatalogName());
		$this->assertSame('[prado_unitest].[dbo].[scores]', $info->getTableFullName());
		$this->assertNull((new TSqlSrvTableInfo())->getCatalogName());
		$this->assertSame('[].[].[]', (new TSqlSrvTableInfo())->getTableFullName());

		$builder = $info->createCommandBuilder($this->_conn);
		$this->assertInstanceOf(TSqlSrvCommandBuilder::class, $builder);
		$this->assertSame($info, $builder->getTableInfo());
		$this->assertSame($this->_conn, $builder->getDbConnection());
	}

	public function test_table_column(): void
	{
		$identity = new TSqlSrvTableColumn(['DbType' => 'int', 'AutoIncrement' => true]);
		$this->assertSame('string', $identity->getPHPType());
		$this->assertTrue($identity->getAutoIncrement());
		$this->assertTrue($identity->hasSequence());
		$this->assertFalse($identity->getIsExcluded());

		$rowVersion = new TSqlSrvTableColumn(['DbType' => 'TimeStamp']);
		$this->assertSame('string', $rowVersion->getPHPType());
		$this->assertFalse($rowVersion->getAutoIncrement());
		$this->assertFalse($rowVersion->hasSequence());
		$this->assertTrue($rowVersion->getIsExcluded());
	}
}
