<?php

/**
 * SqlMapInsertOrIgnoreTest — integration tests for SqlMap <insertOrIgnore> and <upsert>.
 *
 * Bootstraps its own TSqlMapManager against a fresh SQLite file database so it
 * can create the upsert_test table independently of the shared SqlMap test DB.
 *
 * What is tested:
 *  - XML parsing: <insertOrIgnore> element → TInsertOrIgnoreMappedStatement
 *  - XML parsing: <upsert> element → TUpsertMappedStatement
 *  - XML attribute parsing: updateColumns / conflictColumns stored on TSqlMapUpsert
 *  - Execution: insertOrIgnore inserts a new row
 *  - Execution: insertOrIgnore on duplicate silently skips (row count unchanged)
 *  - Execution: insertOrIgnore on duplicate leaves original data unchanged
 *  - Execution: upsert inserts a new row
 *  - Execution: upsert on conflict updates the row in place
 *  - Execution: upsert does not create duplicate rows
 *
 * @since 4.3.3
 */

use Prado\Data\SqlMap\Configuration\TSqlMapUpsert;
use Prado\Data\SqlMap\Statements\TInsertMappedStatement;
use Prado\Data\SqlMap\Statements\TInsertOrIgnoreMappedStatement;
use Prado\Data\SqlMap\Statements\TUpsertMappedStatement;
use Prado\Data\SqlMap\TSqlMapManager;
use Prado\Data\TDbConnection;

class SqlMapInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	private static TDbConnection $conn;
	private static TSqlMapManager $manager;
	private static \Prado\Data\SqlMap\TSqlMapGateway $sqlmap;
	private static string $dbFile;

	public static function setUpBeforeClass(): void
	{
		// Use a per-run temp SQLite file so the test is fully isolated.
		self::$dbFile = sys_get_temp_dir() . '/prado_sqlmap_upsert_' . getmypid() . '.db';

		self::$conn = new TDbConnection('sqlite:' . self::$dbFile);
		self::$conn->Active = true;
		self::$conn->createCommand(
			'CREATE TABLE IF NOT EXISTS upsert_test (
				id       INTEGER PRIMARY KEY AUTOINCREMENT,
				username TEXT    NOT NULL,
				score    INTEGER NOT NULL DEFAULT 0,
				UNIQUE(username)
			)'
		)->execute();

		// Bootstrap TSqlMapManager with only the UpsertTest map.
		// We write a minimal sqlmap config to a temp file so configureXml() can
		// resolve the relative resource path to UpsertTest.xml.
		$mapsDir = realpath(__DIR__ . '/maps/sqlite');
		$configXml = <<<XML
			<?xml version="1.0" encoding="UTF-8"?>
			<sqlMapConfig>
				<sqlMaps>
					<sqlMap resource="{$mapsDir}/UpsertTest.xml"/>
				</sqlMaps>
			</sqlMapConfig>
			XML;

		$configFile = sys_get_temp_dir() . '/prado_sqlmap_upsert_cfg_' . getmypid() . '.xml';
		file_put_contents($configFile, $configXml);

		self::$manager = new TSqlMapManager(self::$conn);
		self::$manager->configureXml($configFile);
		self::$sqlmap = self::$manager->getSqlMapGateway();

		@unlink($configFile);
	}

	protected function setUp(): void
	{
		self::$conn->createCommand('DELETE FROM upsert_test')->execute();
	}

	public static function tearDownAfterClass(): void
	{
		if (isset(self::$conn) && self::$conn->Active) {
			self::$conn->Active = false;
		}
		if (isset(self::$dbFile) && file_exists(self::$dbFile)) {
			@unlink(self::$dbFile);
		}
	}

	// -----------------------------------------------------------------------
	// XML parsing: statement types
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_element_creates_TInsertOrIgnoreMappedStatement(): void
	{
		$stmt = self::$manager->getMappedStatement('InsertOrIgnoreUpsertRow');
		$this->assertInstanceOf(TInsertOrIgnoreMappedStatement::class, $stmt);
	}

	public function test_insertOrIgnore_mapped_statement_extends_TInsertMappedStatement(): void
	{
		$stmt = self::$manager->getMappedStatement('InsertOrIgnoreUpsertRow');
		$this->assertInstanceOf(TInsertMappedStatement::class, $stmt);
	}

	public function test_upsert_element_creates_TUpsertMappedStatement(): void
	{
		$stmt = self::$manager->getMappedStatement('UpsertRow');
		$this->assertInstanceOf(TUpsertMappedStatement::class, $stmt);
	}

	public function test_upsert_mapped_statement_extends_TInsertMappedStatement(): void
	{
		$stmt = self::$manager->getMappedStatement('UpsertRow');
		$this->assertInstanceOf(TInsertMappedStatement::class, $stmt);
	}

	// -----------------------------------------------------------------------
	// XML attribute parsing: updateColumns / conflictColumns on TSqlMapUpsert
	// -----------------------------------------------------------------------

	public function test_upsert_config_object_is_TSqlMapUpsert(): void
	{
		$stmt = self::$manager->getMappedStatement('UpsertRow');
		$this->assertInstanceOf(TSqlMapUpsert::class, $stmt->getStatement());
	}

	public function test_upsert_updateColumns_parsed_from_attribute(): void
	{
		$stmt   = self::$manager->getMappedStatement('UpsertRow');
		$config = $stmt->getStatement();
		$this->assertInstanceOf(TSqlMapUpsert::class, $config);
		$this->assertSame(['score'], $config->getUpdateColumns());
	}

	public function test_upsert_conflictColumns_parsed_from_attribute(): void
	{
		$stmt   = self::$manager->getMappedStatement('UpsertRow');
		$config = $stmt->getStatement();
		$this->assertInstanceOf(TSqlMapUpsert::class, $config);
		$this->assertSame(['username'], $config->getConflictColumns());
	}

	public function test_upsert_multi_updateColumns_parsed_and_trimmed(): void
	{
		$stmt   = self::$manager->getMappedStatement('UpsertRowMultiConflict');
		$config = $stmt->getStatement();
		$this->assertInstanceOf(TSqlMapUpsert::class, $config);
		$this->assertSame(['score', 'extra'], $config->getUpdateColumns());
	}

	public function test_upsert_multi_conflictColumns_parsed_and_trimmed(): void
	{
		$stmt   = self::$manager->getMappedStatement('UpsertRowMultiConflict');
		$config = $stmt->getStatement();
		$this->assertInstanceOf(TSqlMapUpsert::class, $config);
		$this->assertSame(['tenant_id', 'username'], $config->getConflictColumns());
	}

	// -----------------------------------------------------------------------
	// Execution: insertOrIgnore — new row
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_inserts_new_row(): void
	{
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 10]);

		$count = (int) self::$conn->createCommand(
			"SELECT COUNT(*) FROM upsert_test WHERE username='alice'"
		)->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_insertOrIgnore_stores_correct_data(): void
	{
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 42]);

		$row = self::$conn->createCommand(
			"SELECT username, score FROM upsert_test WHERE username='alice'"
		)->queryRow();
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(42, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Execution: insertOrIgnore — duplicate silently skipped
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_duplicate_does_not_increase_row_count(): void
	{
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 10]);
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 99]);

		$count = (int) self::$conn->createCommand(
			'SELECT COUNT(*) FROM upsert_test'
		)->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_insertOrIgnore_duplicate_leaves_original_score_unchanged(): void
	{
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 10]);
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 99]);

		$row = self::$conn->createCommand(
			"SELECT score FROM upsert_test WHERE username='alice'"
		)->queryRow();
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_insertOrIgnore_non_conflicting_rows_inserted(): void
	{
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 10]);
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 99]);
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'bob',   'score' => 20]);

		$count = (int) self::$conn->createCommand(
			'SELECT COUNT(*) FROM upsert_test'
		)->queryScalar();
		$this->assertEquals(2, $count);
	}

	// -----------------------------------------------------------------------
	// Execution: upsert — new row
	// -----------------------------------------------------------------------

	public function test_upsert_inserts_new_row(): void
	{
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 10]);

		$count = (int) self::$conn->createCommand(
			"SELECT COUNT(*) FROM upsert_test WHERE username='alice'"
		)->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_upsert_stores_correct_data_on_insert(): void
	{
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 55]);

		$row = self::$conn->createCommand(
			"SELECT username, score FROM upsert_test WHERE username='alice'"
		)->queryRow();
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(55, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Execution: upsert — conflict → update
	// -----------------------------------------------------------------------

	public function test_upsert_conflict_updates_score(): void
	{
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 10]);
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 99]);

		$row = self::$conn->createCommand(
			"SELECT score FROM upsert_test WHERE username='alice'"
		)->queryRow();
		$this->assertEquals(99, (int) $row['score']);
	}

	public function test_upsert_conflict_does_not_create_duplicate_rows(): void
	{
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 10]);
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 99]);

		$count = (int) self::$conn->createCommand(
			'SELECT COUNT(*) FROM upsert_test'
		)->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_upsert_does_not_affect_other_rows(): void
	{
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 10]);
		self::$sqlmap->insert('UpsertRow', ['username' => 'bob',   'score' => 20]);
		self::$sqlmap->insert('UpsertRow', ['username' => 'alice', 'score' => 99]);

		$row = self::$conn->createCommand(
			"SELECT score FROM upsert_test WHERE username='bob'"
		)->queryRow();
		$this->assertEquals(20, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Execution: insert and insertOrIgnore can coexist in the same manager
	// -----------------------------------------------------------------------

	public function test_plain_insert_and_insertOrIgnore_use_separate_statements(): void
	{
		self::$sqlmap->insert('InsertUpsertRow', ['username' => 'alice', 'score' => 10]);
		// This duplicate would throw on a plain INSERT but is silently skipped here
		self::$sqlmap->insert('InsertOrIgnoreUpsertRow', ['username' => 'alice', 'score' => 99]);

		$count = (int) self::$conn->createCommand(
			'SELECT COUNT(*) FROM upsert_test'
		)->queryScalar();
		$this->assertEquals(1, $count);

		$row = self::$conn->createCommand(
			"SELECT score FROM upsert_test WHERE username='alice'"
		)->queryRow();
		$this->assertEquals(10, (int) $row['score']);
	}
}
