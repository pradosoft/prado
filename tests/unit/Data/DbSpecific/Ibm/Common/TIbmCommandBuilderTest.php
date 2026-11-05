<?php

namespace Prado\Test\Unit\Data\DbSpecific\Ibm\Common;

use Prado\Data\Common\Ibm\TIbmCommandBuilder;
use Prado\Data\Common\Ibm\TIbmTableColumn;
use Prado\Data\Common\Ibm\TIbmTableInfo;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * Command that records bound values without preparing the DB2 SQL on SQLite.
 */
class IbmRecordingCommand extends TDbCommand
{
	/** @var array<string, array{0: mixed, 1: ?int}> parameter name => [value, data type]. */
	public array $bound = [];

	public function bindValue($name, $value, $dataType = null)
	{
		$this->bound[$name] = [$value, $dataType];
	}
}

/**
 * SQLite connection for DB2 command builder tests.
 *
 * MERGE commands are {@see IbmRecordingCommand}s and the IDENTITY_VAL_LOCAL()
 * query returns {@see $identity}, or no row when it is null.
 */
class IbmIdentityConnection extends TDbConnection
{
	public ?string $identity = '17';

	public function __construct()
	{
		parent::__construct('sqlite::memory:');
	}

	public function createCommand($sql)
	{
		if (str_starts_with($sql, 'MERGE ')) {
			return new IbmRecordingCommand($this, $sql);
		}
		if ($sql === 'SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM.SYSDUMMY1') {
			$sql = $this->identity === null ? 'SELECT 1 WHERE 0' : "SELECT '{$this->identity}'";
		}
		return parent::createCommand($sql);
	}
}

/**
 * Server-less tests for TIbmCommandBuilder, TIbmTableInfo, and TIbmTableColumn.
 *
 * MERGE commands are created on an in-memory SQLite connection that records
 * bound values, so the DB2 SQL text and bindings are asserted without being executed.
 */
class TIbmCommandBuilderTest extends \PHPUnit\Framework\TestCase
{
	private IbmIdentityConnection $_conn;

	protected function setUp(): void
	{
		$this->_conn = new IbmIdentityConnection();
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
	 * @return TIbmTableInfo "DB2INST1"."SCORES" (id PK, name, score).
	 */
	private function createTableInfo(bool $identity = true): TIbmTableInfo
	{
		$info = new TIbmTableInfo(['SchemaName' => 'DB2INST1', 'TableName' => 'SCORES'], ['id'], []);
		$columns = $info->getColumns();
		$columns['id'] = new TIbmTableColumn(['ColumnName' => '"ID"', 'ColumnId' => 'id', 'ColumnIndex' => 0, 'DbType' => 'integer', 'IsPrimaryKey' => true, 'AutoIncrement' => $identity]);
		$columns['name'] = new TIbmTableColumn(['ColumnName' => '"NAME"', 'ColumnId' => 'name', 'ColumnIndex' => 1, 'DbType' => 'varchar']);
		$columns['score'] = new TIbmTableColumn(['ColumnName' => '"SCORE"', 'ColumnId' => 'score', 'ColumnIndex' => 2, 'DbType' => 'integer']);
		return $info;
	}

	private function createBuilder(bool $identity = true): TIbmCommandBuilder
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
			'MERGE INTO "DB2INST1"."SCORES" AS t USING (SELECT :id AS id, :name AS name FROM SYSIBM.SYSDUMMY1) AS s ON (t."ID" = s.id)'
			. ' WHEN NOT MATCHED THEN INSERT ("ID", "NAME") VALUES (s.id, s.name)',
			$command->getText()
		);
		$this->assertSame([':id' => [1, \PDO::PARAM_INT], ':name' => ['alice', \PDO::PARAM_STR]], $command->bound);
	}

	public function test_upsert_merge_text_updates_non_key_columns(): void
	{
		$this->_conn->beginTransaction();
		$command = $this->createBuilder()->createUpsertCommand(['id' => 1, 'name' => 'alice', 'score' => 7]);

		$this->assertSame(
			'MERGE INTO "DB2INST1"."SCORES" AS t USING (SELECT :id AS id, :name AS name, :score AS score FROM SYSIBM.SYSDUMMY1) AS s ON (t."ID" = s.id)'
			. ' WHEN MATCHED THEN UPDATE SET t."NAME" = s.name, t."SCORE" = s.score'
			. ' WHEN NOT MATCHED THEN INSERT ("ID", "NAME", "SCORE") VALUES (s.id, s.name, s.score)',
			$command->getText()
		);
	}

	public function test_upsert_merge_text_explicit_conflict_and_values(): void
	{
		$this->_conn->beginTransaction();
		$command = $this->createBuilder()->createUpsertCommand(['name' => 'alice', 'score' => 7], ['score' => 99], ['name']);

		$this->assertSame(
			'MERGE INTO "DB2INST1"."SCORES" AS t USING (SELECT :name AS name, :score AS score FROM SYSIBM.SYSDUMMY1) AS s ON (t."NAME" = s.name)'
			. ' WHEN MATCHED THEN UPDATE SET t."SCORE" = :_upsert_score'
			. ' WHEN NOT MATCHED THEN INSERT ("NAME", "SCORE") VALUES (s.name, s.score)',
			$command->getText()
		);
		$this->assertSame([':name' => ['alice', \PDO::PARAM_STR], ':score' => [7, \PDO::PARAM_INT], ':_upsert_score' => [99, null]], $command->bound);
	}

	// -----------------------------------------------------------------------
	// getLastInsertID
	// -----------------------------------------------------------------------

	public function test_last_insert_id_with_identity_column(): void
	{
		$this->assertSame(17, $this->createBuilder(true)->getLastInsertID());
	}

	public function test_last_insert_id_without_identity_value(): void
	{
		$this->_conn->identity = null;

		$this->assertNull($this->createBuilder(true)->getLastInsertID());
	}

	public function test_last_insert_id_without_identity_column(): void
	{
		$this->assertNull($this->createBuilder(false)->getLastInsertID());
	}

	// -----------------------------------------------------------------------
	// applyLimitOffset / rewriteLimitOffsetSql
	// -----------------------------------------------------------------------

	public function test_apply_limit_offset_null_and_numeric_strings(): void
	{
		$builder = new TIbmCommandBuilder();

		$this->assertSame('SELECT a FROM t', $builder->applyLimitOffset('SELECT a FROM t', null, null));
		$this->assertSame('SELECT a FROM t', $builder->applyLimitOffset('SELECT a FROM t'));
		$this->assertSame('SELECT a FROM t OFFSET 2 ROWS FETCH NEXT 7 ROWS ONLY', $builder->applyLimitOffset('SELECT a FROM t', '7', '2'));
		$this->assertSame('SELECT a FROM t OFFSET 2 ROWS FETCH NEXT 0 ROWS ONLY', $builder->applyLimitOffset('SELECT a FROM t', 0, 2));
		$this->assertSame('SELECT a FROM t FETCH FIRST 0 ROWS ONLY', $builder->applyLimitOffset('SELECT a FROM t', 0, -1));
	}

	public function test_apply_limit_offset_offset_only(): void
	{
		$builder = new TIbmCommandBuilder();
		$upper = PHP_INT_MAX;

		$this->assertSame(
			'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum FROM (SELECT a FROM t) AS prado_inner) AS prado_outer WHERE prado_rownum BETWEEN 5 AND ' . $upper,
			$builder->applyLimitOffset('SELECT a FROM t', -1, 4)
		);
	}

	public function test_apply_limit_offset_zero_offset_only(): void
	{
		$builder = new TIbmCommandBuilder();
		$upper = PHP_INT_MAX;

		$this->assertSame(
			'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum FROM (SELECT a FROM t) AS prado_inner) AS prado_outer WHERE prado_rownum BETWEEN 1 AND ' . $upper,
			$builder->applyLimitOffset('SELECT a FROM t', -1, 0)
		);
	}

	public function test_rewrite_limit_offset_sql(): void
	{
		$this->assertSame(
			'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum FROM (SELECT a FROM t ORDER BY a) AS prado_inner) AS prado_outer WHERE prado_rownum BETWEEN 11 AND 15',
			PradoUnit::invoke(new TIbmCommandBuilder(), 'rewriteLimitOffsetSql', 'SELECT a FROM t ORDER BY a', 5, 10)
		);
	}

	// -----------------------------------------------------------------------
	// TIbmTableInfo / TIbmTableColumn
	// -----------------------------------------------------------------------

	public function test_table_info(): void
	{
		$info = $this->createTableInfo();

		$this->assertSame('"DB2INST1"."SCORES"', $info->getTableFullName());
		$this->assertSame('"SCORES"', (new TIbmTableInfo(['TableName' => 'SCORES']))->getTableFullName());
		$this->assertSame('"SCORES"', (new TIbmTableInfo(['SchemaName' => '', 'TableName' => 'SCORES']))->getTableFullName());

		$builder = $info->createCommandBuilder($this->_conn);
		$this->assertInstanceOf(TIbmCommandBuilder::class, $builder);
		$this->assertSame($info, $builder->getTableInfo());
		$this->assertSame($this->_conn, $builder->getDbConnection());
	}

	public function test_table_column_php_type(): void
	{
		$expected = [
			'INTEGER' => 'integer', 'int' => 'integer', 'bigint' => 'integer', ' smallint ' => 'integer',
			'boolean' => 'boolean',
			'double' => 'float', 'real' => 'float', 'float' => 'float', 'decimal' => 'float', 'numeric' => 'float', 'decfloat' => 'float',
			'varchar' => 'string', 'clob' => 'string', 'timestamp' => 'string', 'blob' => 'string',
		];
		foreach ($expected as $dbType => $phpType) {
			$this->assertSame($phpType, (new TIbmTableColumn(['DbType' => $dbType]))->getPHPType(), $dbType);
		}
	}

	public function test_table_column_auto_increment(): void
	{
		$identity = new TIbmTableColumn(['DbType' => 'integer', 'AutoIncrement' => true]);
		$this->assertTrue($identity->getAutoIncrement());
		$this->assertTrue($identity->hasSequence());

		$plain = new TIbmTableColumn(['DbType' => 'integer']);
		$this->assertFalse($plain->getAutoIncrement());
		$this->assertFalse($plain->hasSequence());
	}
}
