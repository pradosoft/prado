<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * MysqlInsertOrIgnoreTest — comprehensive tests for MySQL insertOrIgnore behaviour.
 *
 * Tests both SQL generation and live execution against a MySQL database.
 * Skipped automatically when pdo_mysql is unavailable or the DB cannot be reached.
 *
 * Requires: prado_unitest database with upsert_test table (see tests/initdb_mysql.sql).
 *   upsert_test(id INT AUTO_INCREMENT PK, username VARCHAR(100) UNIQUE NOT NULL, score INT DEFAULT 0)
 */

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class MysqlInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMysqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getTestTables(): array
	{
		return ['upsert_test'];
	}

	protected function setUp(): void
	{
		if (static::$conn === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				static::$conn = $conn;
				static::$gateway = new TTableGateway('upsert_test', $conn);
			}
		}
		static::$conn->createCommand('DELETE FROM `upsert_test`')->execute();
		static::$conn->createCommand('ALTER TABLE `upsert_test` AUTO_INCREMENT = 1')->execute();
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
			static::$gateway = null;
		}
	}

	// -----------------------------------------------------------------------
	// SQL generation
	// -----------------------------------------------------------------------

	public function test_sql_uses_insert_ignore_into(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->insertOrIgnore(['username' => 'test', 'score' => 1]);
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('INSERT IGNORE INTO', $capturedSql);
		$this->assertStringContainsString('`username`', $capturedSql);
		$this->assertStringContainsString('`score`', $capturedSql);
		$this->assertStringContainsString(':username', $capturedSql);
		$this->assertStringContainsString(':score', $capturedSql);
		$this->assertStringNotContainsString('ON DUPLICATE KEY', $capturedSql);
	}

	public function test_sql_omits_id_when_not_supplied(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->insertOrIgnore(['username' => 'test', 'score' => 1]);
		$this->assertStringNotContainsString('`id`', $capturedSql);
	}

	// -----------------------------------------------------------------------
	// Insert new row
	// -----------------------------------------------------------------------

	public function test_new_row_is_inserted(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_new_row_returns_integer_last_insert_id(): void
	{
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_successive_inserts_return_incrementing_ids(): void
	{
		$id1 = (int) self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$id2 = (int) self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 2]);
		$this->assertGreaterThan($id1, $id2);
	}

	// -----------------------------------------------------------------------
	// Duplicate silently ignored (UNIQUE key on username)
	// -----------------------------------------------------------------------

	public function test_duplicate_username_returns_false(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$this->assertFalse($result);
	}

	public function test_duplicate_does_not_increase_row_count(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_existing_row_unchanged_after_ignored_insert(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_zero_score_value_is_stored_correctly(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 0]);
		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(0, (int) $row['score']);
		// 0 is falsy but must still be a successful insert returning an id
		$result = self::$gateway->insertOrIgnore(['username' => 'bob', 'score' => 0]);
		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_large_score_value(): void
	{
		$large = 2147483647; // INT max
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => $large]);
		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals($large, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Mixed: some conflict, some new
	// -----------------------------------------------------------------------

	public function test_only_conflicting_row_ignored_others_inserted(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$res2 = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]); // conflict
		$res3 = self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 20]); // new

		$this->assertFalse($res2);
		$this->assertGreaterThan(0, (int) $res3);
		$this->assertEquals(
			2,
			(int) self::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar()
		);
	}

	public function test_correct_values_after_mixed_inserts(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 55]);

		$alice = self::$gateway->find('username = ?', 'alice');
		$bob   = self::$gateway->find('username = ?', 'bob');
		$this->assertEquals(10, (int) $alice['score']);
		$this->assertEquals(55, (int) $bob['score']);
	}

	// -----------------------------------------------------------------------
	// Events
	// -----------------------------------------------------------------------

	public function test_oncreatecommand_event_is_raised(): void
	{
		$fired = false;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$fired): void {
			$this->assertInstanceOf(TDataGatewayEventParameter::class, $param);
			$fired = true;
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertTrue($fired, 'OnCreateCommand not raised');
	}

	public function test_onexecutecommand_event_is_raised(): void
	{
		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$this->assertInstanceOf(TDataGatewayResultEventParameter::class, $param);
			$captured = $param->getResult();
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertEquals(1, $captured, 'OnExecuteCommand result should be 1 for a fresh insert');
	}

	public function test_onexecutecommand_result_is_zero_on_conflict(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);

		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$captured = $param->getResult();
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$this->assertEquals(0, $captured, 'INSERT IGNORE returns 0 affected rows on conflict');
	}

	public function test_onexecutecommand_can_override_result(): void
	{
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param): void {
			$param->setResult(0);
		};

		$result = $gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Base class
	// -----------------------------------------------------------------------

	public function test_base_builder_throws_for_insertOrIgnore(): void
	{
		$meta      = new \Prado\Data\Common\Mysql\TMysqlMetaData(self::$conn);
		$tableInfo = $meta->getTableInfo('upsert_test');
		$base      = new TDbCommandBuilder(self::$conn, $tableInfo);

		$this->expectException(TDbException::class);
		$base->createInsertOrIgnoreCommand(['username' => 'x', 'score' => 1]);
	}
}
