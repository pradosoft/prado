<?php

namespace Prado\Test\Unit\Data\DbSpecific\Firebird\Common;

use Prado\Data\Common\Firebird\TFirebirdCommandBuilder;
use Prado\Data\Common\Firebird\TFirebirdTableColumn;
use Prado\Data\Common\Firebird\TFirebirdTableInfo;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;

/**
 * TFirebirdCommandBuilder: FIRST/SKIP paging, and the identity read-back through
 * `INSERT ... RETURNING`. The statement tests need no server. The RETURNING round
 * trip runs on an SQLite in-memory database, which prepares and answers the same
 * statement, and on the Firebird test database when one is available.
 */
class CommandBuilderFirebirdTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static $sql = [
		'simple'   => 'SELECT username, age FROM accounts',
		'distinct' => 'SELECT DISTINCT username FROM accounts',
		'multiple' => 'SELECT a.username, b.name FROM accounts a, table1 b WHERE a.age = b.id',
		'ordering' => 'SELECT a.username, b.age FROM accounts a ORDER BY a.age DESC',
	];

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getTestTables(): array
	{
		return ['table1'];
	}

	/**
	 * No connection is opened per test; {@see test_insert_returns_identity_on_firebird()}
	 * opens its own.
	 */
	protected function setUp(): void
	{
	}

	/**
	 * A builder over an SQLite in-memory database holding a `TABLE1` like the
	 * Firebird test schema, with a table info stub whose `id` is an identity column.
	 * @param bool $identity whether `id` is marked as an identity column
	 * @return TFirebirdCommandBuilder
	 */
	protected function make_insert_builder(bool $identity = true)
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$conn = new TDbConnection('sqlite::memory:');
		$conn->setActive(true);
		$conn->createCommand('CREATE TABLE "TABLE1" ("ID" INTEGER PRIMARY KEY, "NAME" VARCHAR(45) NOT NULL)')->execute();
		$tableInfo = new TFirebirdTableInfo(['TableName' => 'TABLE1'], ['id']);
		$tableInfo->getColumns()['id'] = new TFirebirdTableColumn([
			'ColumnName' => '"ID"',
			'ColumnId' => 'id',
			'ColumnIndex' => 0,
			'DbType' => 'INTEGER',
			'IsPrimaryKey' => true,
			'AutoIncrement' => $identity,
		]);
		$tableInfo->getColumns()['name'] = new TFirebirdTableColumn([
			'ColumnName' => '"NAME"',
			'ColumnId' => 'name',
			'ColumnIndex' => 1,
			'DbType' => 'VARCHAR',
			'ColumnSize' => 45,
		]);
		return new TFirebirdCommandBuilder($conn, $tableInfo);
	}

	/**
	 * Skips the test on an SQLite library older than 3.35, which has no `RETURNING` clause.
	 * @param TDbConnection $conn SQLite connection
	 */
	protected function requireSqliteReturning(TDbConnection $conn)
	{
		$version = (string) $conn->createCommand('SELECT sqlite_version()')->queryScalar();
		if (version_compare($version, '3.35.0', '<')) {
			$this->markTestSkipped('SQLite ' . $version . ' has no RETURNING clause.');
		}
	}

	public function test_no_limit_no_offset()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], -1, -1);
		$this->assertEquals(self::$sql['simple'], $sql);
	}

	public function test_firebird_limit_only()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, -1);
		$this->assertEquals('SELECT FIRST 5 username, age FROM accounts', $sql);
	}

	public function test_firebird_offset_only()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], -1, 10);
		$this->assertEquals('SELECT SKIP 10 username, age FROM accounts', $sql);
	}

	public function test_firebird_limit_and_offset()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, 10);
		$this->assertEquals('SELECT FIRST 5 SKIP 10 username, age FROM accounts', $sql);
	}

	public function test_firebird_limit_and_offset_with_distinct()
	{
		$builder = new TFirebirdCommandBuilder();

		// FIRST/SKIP must be inserted after DISTINCT, not before it
		$sql = $builder->applyLimitOffset(self::$sql['distinct'], 3, 2);
		$this->assertEquals('SELECT DISTINCT FIRST 3 SKIP 2 username FROM accounts', $sql);
	}

	public function test_firebird_limit_and_offset_with_ordering()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['ordering'], 10, 20);
		$this->assertEquals(
			'SELECT FIRST 10 SKIP 20 a.username, b.age FROM accounts a ORDER BY a.age DESC',
			$sql
		);
	}

	public function test_firebird_multiple_table_query()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['multiple'], 10, 20);
		$this->assertEquals(
			'SELECT FIRST 10 SKIP 20 a.username, b.name FROM accounts a, table1 b WHERE a.age = b.id',
			$sql
		);
	}

	public function test_firebird_zero_offset_emits_skip()
	{
		$builder = new TFirebirdCommandBuilder();

		// offset=0 satisfies offset >= 0, so SKIP 0 is still emitted
		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, 0);
		$this->assertEquals('SELECT FIRST 5 SKIP 0 username, age FROM accounts', $sql);
	}

	public function test_firebird_case_insensitive_select_keyword()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset('select id from table1', 3, 1);
		$this->assertEquals('select FIRST 3 SKIP 1 id from table1', $sql);
	}

	public function test_insert_command_returns_the_identity_column()
	{
		$builder = $this->make_insert_builder();

		$command = $builder->createInsertCommand(['name' => 'wei']);

		$this->assertEquals('INSERT INTO "TABLE1"("NAME") VALUES (:name) RETURNING "ID"', $command->getText());
	}

	public function test_insert_command_without_identity_column_has_no_returning()
	{
		$builder = $this->make_insert_builder(false);

		$command = $builder->createInsertCommand(['id' => 7, 'name' => 'wei']);

		$this->assertEquals('INSERT INTO "TABLE1"("ID", "NAME") VALUES (:id, :name)', $command->getText());
		$this->assertSame(1, $command->execute());
		$this->assertNull($builder->getLastInsertID());
	}

	public function test_last_insert_id_is_null_before_the_insert_runs()
	{
		$builder = $this->make_insert_builder();

		$this->assertNull($builder->getLastInsertID());
		$builder->createInsertCommand(['name' => 'wei']);
		$this->assertNull($builder->getLastInsertID());
	}

	public function test_last_insert_id_is_read_from_the_returning_row()
	{
		$builder = $this->make_insert_builder();
		$this->requireSqliteReturning($builder->getDbConnection());

		$command = $builder->createInsertCommand(['name' => 'first']);
		$command->execute();
		$this->assertSame(1, $builder->getLastInsertID());
		$this->assertSame(1, $builder->getLastInsertID());

		$command = $builder->createInsertCommand(['name' => 'second']);
		$this->assertNull($builder->getLastInsertID());
		$command->execute();
		$this->assertSame(2, $builder->getLastInsertID());
		$this->assertEquals(2, $builder->getDbConnection()->createCommand('SELECT COUNT(*) FROM "TABLE1"')->queryScalar());
	}

	public function test_insert_returns_identity_on_firebird()
	{
		$conn = $this->setUpConnection();
		$gateway = new TTableGateway('table1', $conn);

		$id = $gateway->insert(['name' => 'returning']);
		$this->assertIsInt($id);
		$this->assertSame($id, $gateway->getLastInsertId());
		$row = array_change_key_case($gateway->findByPk($id));
		$this->assertEquals('returning', $row['name']);

		$second = $gateway->insert(['name' => 'returning again']);
		$this->assertIsInt($second);
		$this->assertGreaterThan($id, $second);

		$this->assertSame(2, $gateway->deleteAll('id IN (?, ?)', [$id, $second]));
	}
	// -----------------------------------------------------------------------
	// insertOrIgnore / upsert MERGE statements
	// -----------------------------------------------------------------------

	/**
	 * A builder whose connection is an in-memory SQLite database creating
	 * {@see CommandBuilderFirebirdCommand} commands, which record bound values
	 * without preparing the Firebird MERGE text.
	 * @param bool $transaction whether to begin a transaction
	 * @return TFirebirdCommandBuilder
	 */
	protected function make_merge_builder(bool $transaction = true)
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$conn = new CommandBuilderFirebirdConnection('sqlite::memory:');
		$conn->setActive(true);
		if ($transaction) {
			$conn->beginTransaction();
		}
		$tableInfo = new TFirebirdTableInfo(['TableName' => 'upsert_test'], ['id']);
		$columns = [
			'id' => ['DbType' => 'INTEGER', 'IsPrimaryKey' => true, 'AutoIncrement' => true],
			'name' => ['DbType' => 'VARCHAR', 'ColumnSize' => 45],
			'code' => ['DbType' => ' char ', 'ColumnSize' => 3],
			'price' => ['DbType' => 'DECIMAL', 'NumericPrecision' => 10, 'NumericScale' => 2],
			'qty' => ['DbType' => 'NUMERIC', 'NumericPrecision' => 5, 'NumericScale' => 0],
			'note' => ['DbType' => 'VARCHAR', 'AllowNull' => true],
			'amount' => ['DbType' => 'DECIMAL'],
			'body' => ['DbType' => 'BLOB', 'ColumnSize' => 8],
		];
		$index = 0;
		foreach ($columns as $id => $info) {
			$tableInfo->getColumns()[$id] = new TFirebirdTableColumn(['ColumnName' => '"' . strtoupper($id) . '"', 'ColumnId' => $id, 'ColumnIndex' => $index++] + $info);
		}
		return new TFirebirdCommandBuilder($conn, $tableInfo);
	}

	public function test_cast_types_of_merge_columns()
	{
		$builder = $this->make_merge_builder();

		$casts = [];
		foreach (['id', 'name', 'code', 'price', 'qty', 'note', 'amount', 'body'] as $name) {
			$casts[$name] = PradoUnit::invoke($builder, 'getFirebirdCastType', $name);
		}

		$this->assertSame([
			'id' => 'INTEGER',
			'name' => 'VARCHAR(45)',
			'code' => 'CHAR(3)',
			'price' => 'DECIMAL(10,2)',
			'qty' => 'NUMERIC(5)',
			'note' => 'VARCHAR',
			'amount' => 'DECIMAL',
			'body' => 'BLOB',
		], $casts);
		$this->assertSame('CAST(:price AS DECIMAL(10,2)) AS price', PradoUnit::invoke($builder, 'processMergeColumn', 'price'));
	}

	public function test_cast_type_of_an_unknown_column_throws()
	{
		$builder = $this->make_merge_builder();

		$this->expectException(TDbException::class);
		PradoUnit::invoke($builder, 'getFirebirdCastType', 'unknown');
	}

	public function test_insert_or_ignore_builds_merge_from_rdb_database()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createInsertOrIgnoreCommand(['id' => 3, 'name' => 'wei']);

		$this->assertSame(
			'MERGE INTO "UPSERT_TEST" t USING (SELECT CAST(:id AS INTEGER) AS id, CAST(:name AS VARCHAR(45)) AS name FROM RDB$DATABASE) s ON (t."ID" = s.id)'
			. ' WHEN NOT MATCHED THEN INSERT ("ID", "NAME") VALUES (s.id, s.name)',
			$command->getText()
		);
		$this->assertSame([':id' => 3, ':name' => 'wei'], $command->getBoundValues());
	}

	public function test_upsert_updates_non_conflict_columns_by_default()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createUpsertCommand(['id' => 3, 'price' => '1.50', 'note' => null]);

		$this->assertSame(
			'MERGE INTO "UPSERT_TEST" t USING (SELECT CAST(:id AS INTEGER) AS id, CAST(:price AS DECIMAL(10,2)) AS price, CAST(:note AS VARCHAR) AS note FROM RDB$DATABASE) s ON (t."ID" = s.id)'
			. ' WHEN MATCHED THEN UPDATE SET t."PRICE" = s.price, t."NOTE" = s.note'
			. ' WHEN NOT MATCHED THEN INSERT ("ID", "PRICE", "NOTE") VALUES (s.id, s.price, s.note)',
			$command->getText()
		);
		$this->assertSame([':id' => 3, ':price' => '1.50', ':note' => null], $command->getBoundValues());
	}

	public function test_upsert_with_explicit_values_and_conflict_columns()
	{
		$builder = $this->make_merge_builder();

		$command = $builder->createUpsertCommand(['code' => 'abc', 'qty' => 2], ['qty', 'qty' => 9], ['code']);

		$this->assertSame(
			'MERGE INTO "UPSERT_TEST" t USING (SELECT CAST(:code AS CHAR(3)) AS code, CAST(:qty AS NUMERIC(5)) AS qty FROM RDB$DATABASE) s ON (t."CODE" = s.code)'
			. ' WHEN MATCHED THEN UPDATE SET t."QTY" = :_upsert_qty'
			. ' WHEN NOT MATCHED THEN INSERT ("CODE", "QTY") VALUES (s.code, s.qty)',
			$command->getText()
		);
		$this->assertSame([':code' => 'abc', ':qty' => 2, ':_upsert_qty' => 9], $command->getBoundValues());
	}

	public function test_merge_commands_clear_the_insert_identity()
	{
		$builder = $this->make_merge_builder();

		$insert = $builder->createInsertCommand(['name' => 'wei']);
		$this->assertSame('INSERT INTO "UPSERT_TEST"("NAME") VALUES (:name) RETURNING "ID"', $insert->getText());
		$this->assertSame($insert, PradoUnit::getProp($builder, '_insertCommand'));

		$builder->createInsertOrIgnoreCommand(['id' => 1]);
		$this->assertNull(PradoUnit::getProp($builder, '_insertCommand'));
		$this->assertNull($builder->getLastInsertID());

		PradoUnit::setProp($builder, '_insertCommand', $insert);
		PradoUnit::setProp($builder, '_lastInsertID', 5);
		$builder->createUpsertCommand(['id' => 1, 'name' => 'x']);
		$this->assertNull(PradoUnit::getProp($builder, '_insertCommand'));
		$this->assertNull($builder->getLastInsertID());
	}

	public function test_last_insert_id_without_a_statement_is_null()
	{
		$builder = $this->make_merge_builder();

		// The recording command never prepares, so it holds no PDOStatement.
		$builder->createInsertCommand(['name' => 'wei']);

		$this->assertNull($builder->getLastInsertID());
	}

	public function test_identity_column()
	{
		$this->assertSame('id', PradoUnit::invoke($this->make_merge_builder(), 'getIdentityColumn')->getColumnId());
		$this->assertNull(PradoUnit::invoke($this->make_insert_builder(false), 'getIdentityColumn'));
	}

	public function test_insert_or_ignore_requires_a_transaction()
	{
		$builder = $this->make_merge_builder(false);

		$this->expectException(TDbException::class);
		$builder->createInsertOrIgnoreCommand(['id' => 1]);
	}

	public function test_upsert_requires_a_transaction()
	{
		$builder = $this->make_merge_builder(false);

		$this->expectException(TDbException::class);
		$builder->createUpsertCommand(['id' => 1]);
	}

	public function test_null_limit_and_offset_are_ignored()
	{
		$builder = new TFirebirdCommandBuilder();

		$this->assertSame(self::$sql['simple'], $builder->applyLimitOffset(self::$sql['simple'], null, null));
		$this->assertSame('  SELECT FIRST 0 x FROM t', $builder->applyLimitOffset('  SELECT x FROM t', '0', null));
	}
}

/**
 * An SQLite connection creating {@see CommandBuilderFirebirdCommand} commands.
 */
class CommandBuilderFirebirdConnection extends TDbConnection
{
	public function createCommand($sql)
	{
		$this->assertActive();
		return new CommandBuilderFirebirdCommand($this, $sql);
	}
}

/**
 * A command that records bound values instead of preparing its SQL.
 */
class CommandBuilderFirebirdCommand extends TDbCommand
{
	private array $_bound = [];

	public function bindValue($name, $value, $dataType = null)
	{
		$this->_bound[$name] = $value;
	}

	public function getBoundValues(): array
	{
		return $this->_bound;
	}
}
