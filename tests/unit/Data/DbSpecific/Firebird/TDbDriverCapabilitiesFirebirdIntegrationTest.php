<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Firebird\TFirebirdMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — Firebird.
 *
 * Verifies static capability flags and live behaviour for the 'firebird'
 * driver, with explicit attention to the 'interbase' alias and the unique
 * implicit-transaction flush requirements.
 *
 * Key Firebird characteristics:
 *  - supportsCharset = true (DSN charset= param only, no runtime SQL)
 *  - hasAutoCommitAttribute = true
 *  - usesSerialTransaction = true  ← always has an implicit transaction
 *  - requiresPreBeginTransactionFlush = true  ← flush before beginTransaction
 *  - requiresPostTransactionFlush = true  ← flush after commit/rollback
 *  - supportsRuntimeCharsetSet = false  ← DSN-only charset
 *  - requiresPostConnectCharset = false
 *  - getCharsetDsnParam = 'charset'
 *
 * The 'interbase' driver is an alias for charset resolution and
 * usesSerialTransaction but is NOT aliased for the flush flags.
 *
 * Tests are skipped automatically when pdo_firebird is missing or the
 * prado_unitest.fdb database is unreachable.
 *
 * Environment variables
 * ---------------------
 * FIREBIRD_DB_PATH  Server-side path to the prado_unitest.fdb file.
 *                   Defaults to /var/lib/firebird/data/prado_unitest.fdb.
 */
class TDbDriverCapabilitiesFirebirdIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

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
		return [];
	}

	protected function setUp(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->setUpConnection();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function openFirebird(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_firebird')) {
			$this->markTestSkipped('pdo_firebird extension not available.');
		}
		$dbPath = getenv('FIREBIRD_DB_PATH') ?: '/var/lib/firebird/data/prado_unitest.fdb';
		try {
			$conn = new TDbConnection(
				'firebird:dbname=localhost:' . $dbPath,
				'SYSDBA',
				'masterkey',
				$charset
			);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect to Firebird: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags — firebird
	// -----------------------------------------------------------------------

	public function testFirebirdSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('firebird'));
	}

	public function testFirebirdHasAutoCommitAttribute(): void
	{
		$this->assertTrue(TDbDriverCapabilities::hasAutoCommitAttribute('firebird'));
	}

	public function testFirebirdUsesSerialTransaction(): void
	{
		// pdo_firebird always maintains an implicit transaction; serial mode is required.
		$this->assertTrue(TDbDriverCapabilities::usesSerialTransaction('firebird'));
	}

	public function testFirebirdRequiresPreBeginTransactionFlush(): void
	{
		// Before beginTransaction(), the implicit transaction must be flushed.
		$this->assertTrue(TDbDriverCapabilities::requiresPreBeginTransactionFlush('firebird'));
	}

	public function testFirebirdRequiresPostTransactionFlush(): void
	{
		// After commit() or rollBack(), the new implicit transaction must be flushed.
		$this->assertTrue(TDbDriverCapabilities::requiresPostTransactionFlush('firebird'));
	}

	public function testFirebirdDoesNotSupportRuntimeCharsetSet(): void
	{
		// Firebird charset is DSN-only; supportsRuntimeCharsetSet must be false.
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet('firebird'));
	}

	public function testFirebirdRequiresNoPostConnectCharset(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('firebird'));
	}

	public function testFirebirdCharsetSetSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql('firebird'));
	}

	public function testFirebirdCharsetPragmaSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('firebird'));
	}

	public function testFirebirdCharsetDsnParamIsCharset(): void
	{
		$this->assertSame('charset', TDbDriverCapabilities::getCharsetDsnParam('firebird'));
	}

	public function testFirebirdCharsetDsnPatternMatchesCharsetParam(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern('firebird');
		$this->assertNotNull($pattern);
		$this->assertSame(1, preg_match($pattern, ';charset=UTF8', $m));
		$this->assertSame('UTF8', $m[1]);
	}

	public function testFirebirdCharsetQuerySqlContainsMonAttachments(): void
	{
		$sql = TDbDriverCapabilities::getCharsetQuerySql('firebird');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('MON$ATTACHMENTS', $sql);
		$this->assertStringContainsString('RDB$CHARACTER_SETS', $sql);
	}

	public function testFirebirdGetListTablesSqlContainsRdbRelations(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('firebird');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('RDB$RELATIONS', $sql);
	}

	public function testFirebirdMetaDataClassName(): void
	{
		$this->assertSame(TFirebirdMetaData::class, TDbDriverCapabilities::getMetaDataClass('firebird'));
	}

	// -----------------------------------------------------------------------
	// Static capability flags — interbase alias
	//
	// 'interbase' aliases firebird for charset resolution and usesSerialTransaction
	// but is NOT aliased for the pre/post flush flags.
	// -----------------------------------------------------------------------

	public function testInterbaseUsesSerialTransaction(): void
	{
		$this->assertTrue(TDbDriverCapabilities::usesSerialTransaction('interbase'));
	}

	public function testInterbaseDoesNotRequirePreBeginTransactionFlush(): void
	{
		// The flush flag is not aliased; only 'firebird' requires the pre-begin flush.
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('interbase'));
	}

	public function testInterbaseDoesNotRequirePostTransactionFlush(): void
	{
		// The flush flag is not aliased; only 'firebird' requires the post-transaction flush.
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('interbase'));
	}

	public function testInterbaseCharsetDsnParamIsCharset(): void
	{
		$this->assertSame('charset', TDbDriverCapabilities::getCharsetDsnParam('interbase'));
	}

	public function testInterbaseGetListTablesSqlMatchesFirebird(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getListTablesSql('firebird'),
			TDbDriverCapabilities::getListTablesSql('interbase')
		);
	}

	public function testInterbaseMetaDataClassNameMatchesFirebird(): void
	{
		$this->assertSame(TFirebirdMetaData::class, TDbDriverCapabilities::getMetaDataClass('interbase'));
	}

	// -----------------------------------------------------------------------
	// Charset resolution
	// -----------------------------------------------------------------------

	public function testFirebirdResolveUtf8ReturnsUTF8(): void
	{
		$this->assertSame('UTF8', TDbDriverCapabilities::resolveCharset('UTF-8', 'firebird'));
	}

	public function testFirebirdResolveInterbaseUtf8MatchesFirebirdViaAlias(): void
	{
		// 'interbase' is aliased to 'firebird' for charset resolution.
		$this->assertSame('UTF8', TDbDriverCapabilities::resolveCharset('UTF-8', 'interbase'));
	}

	public function testFirebirdResolveLatin1ReturnsISO8859_1(): void
	{
		$this->assertSame('ISO8859_1', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'firebird'));
	}

	public function testFirebirdResolveLatin2ReturnsISO8859_2(): void
	{
		$this->assertSame('ISO8859_2', TDbDriverCapabilities::resolveCharset('ISO-8859-2', 'firebird'));
	}

	public function testFirebirdResolveAsciiReturnsASCII(): void
	{
		$this->assertSame('ASCII', TDbDriverCapabilities::resolveCharset('ASCII', 'firebird'));
	}

	public function testFirebirdResolveWin1250ReturnsWIN1250(): void
	{
		$this->assertSame('WIN1250', TDbDriverCapabilities::resolveCharset('Windows-1250', 'firebird'));
	}

	public function testFirebirdResolveKoi8rReturnsKOI8R(): void
	{
		$this->assertSame('KOI8R', TDbDriverCapabilities::resolveCharset('KOI8-R', 'firebird'));
	}

	public function testFirebirdUnresolveUTF8ReturnsUtf8Standard(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::unresolveCharset('UTF8', 'firebird'));
	}

	public function testFirebirdUnresolveISO8859_1ReturnsLatin1Standard(): void
	{
		$this->assertSame('ISO-8859-1', TDbDriverCapabilities::unresolveCharset('ISO8859_1', 'firebird'));
	}

	public function testInterbaseUnresolveMatchesFirebird(): void
	{
		// Charset unresolution also uses the interbase→firebird alias.
		$this->assertSame(
			TDbDriverCapabilities::unresolveCharset('UTF8', 'firebird'),
			TDbDriverCapabilities::unresolveCharset('UTF8', 'interbase')
		);
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testFirebirdScaffoldInputClass(): void
	{
		$this->assertSame('TFirebirdScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('firebird'));
	}

	public function testFirebirdScaffoldInputFile(): void
	{
		$this->assertSame('/TFirebirdScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('firebird'));
	}

	public function testInterbaseScaffoldInputMatchesFirebird(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getScaffoldInputClass('firebird'),
			TDbDriverCapabilities::getScaffoldInputClass('interbase')
		);
	}

	// -----------------------------------------------------------------------
	// Live connection — basic connectivity
	// -----------------------------------------------------------------------

	public function testFirebirdDriverNameIsFirebird(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$this->assertSame('firebird', $conn->getDriverName());
		$conn->Active = false;
	}

	public function testFirebirdMetaDataInstanceIsTFirebirdMetaData(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TFirebirdMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testFirebirdListTablesQueryReturnsArray(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$sql  = TDbDriverCapabilities::getListTablesSql('firebird');
		$result = $conn->createCommand($sql)->queryAll();
		$this->assertIsArray($result);
		$conn->Active = false;
	}

	public function testFirebirdListTablesQueryReturnsCreatedTable(): void
	{
		// DDL in Firebird auto-commits the current implicit transaction.  Create a
		// table, query RDB$RELATIONS via getListTablesSql, verify the table name
		// appears (Firebird stores identifiers as uppercase unless quoted), then drop it.
		$conn = $this->openFirebird('UTF-8');

		// Drop if exists from a previous run (Firebird has no DROP TABLE IF EXISTS
		// before Firebird 5; use a try/catch guard instead).
		try {
			$conn->createCommand('DROP TABLE CAPS_FB_LIST_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand('CREATE TABLE CAPS_FB_LIST_TEST (ID INTEGER NOT NULL PRIMARY KEY)')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('firebird');
		$rows = $conn->createCommand($sql)->queryAll();

		// The query returns TRIM(RDB$RELATION_NAME) AS tbl_name.
		// pdo_firebird returns column aliases in uppercase ('TBL_NAME'), so
		// normalise all row keys to lowercase before extracting the column.
		// Firebird stores table names in uppercase by default.
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'tbl_name');
		$this->assertContains('CAPS_FB_LIST_TEST', $names);

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_LIST_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testFirebirdListTablesQueryExcludesSystemTables(): void
	{
		// The capability SQL filters RDB$SYSTEM_FLAG = 0; Firebird system tables
		// (e.g. RDB$RELATIONS itself) must not appear in the result.
		$conn = $this->openFirebird('UTF-8');
		$sql  = TDbDriverCapabilities::getListTablesSql('firebird');
		$rows = $conn->createCommand($sql)->queryAll();
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'tbl_name');
		$this->assertNotContains('RDB$RELATIONS', $names);
		$conn->Active = false;
	}

	public function testFirebirdListTablesQueryExcludesViews(): void
	{
		// The capability SQL filters RDB$VIEW_BLR IS NULL; views must not appear.
		$conn = $this->openFirebird('UTF-8');

		try {
			$conn->createCommand('DROP VIEW CAPS_FB_VIEW_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand('CREATE VIEW CAPS_FB_VIEW_TEST AS SELECT 1 AS N FROM RDB$DATABASE')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('firebird');
		$rows = $conn->createCommand($sql)->queryAll();
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'tbl_name');
		$this->assertNotContains('CAPS_FB_VIEW_TEST', $names);

		try {
			$conn->createCommand('DROP VIEW CAPS_FB_VIEW_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — charset
	// -----------------------------------------------------------------------

	public function testFirebirdDatabaseCharsetReturnsUtf8WhenConfigured(): void
	{
		// DatabaseCharset queries MON$ATTACHMENTS or falls back to the resolved value.
		$conn = $this->openFirebird('UTF-8');
		$charset = $conn->DatabaseCharset;
		$this->assertSame('UTF8', $charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testFirebirdTransactionCommitSucceeds(): void
	{
		// For Firebird serial transactions, commit() completes the explicit PDO
		// transaction and immediately restarts a new one — the TDbTransaction
		// object remains active throughout (it is never deactivated).
		$conn = $this->openFirebird('UTF-8');
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit(); // serial restart: does NOT deactivate the transaction
		$this->assertTrue(
			$tx->getActive(),
			'Firebird serial transaction must remain active after commit (serial restart).'
		);
		$conn->Active = false;
	}

	public function testFirebirdTransactionRollbackSucceeds(): void
	{
		// Same serial-restart behaviour applies to rollBack().
		$conn = $this->openFirebird('UTF-8');
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack(); // serial restart: does NOT deactivate the transaction
		$this->assertTrue(
			$tx->getActive(),
			'Firebird serial transaction must remain active after rollback (serial restart).'
		);
		$conn->Active = false;
	}

	public function testFirebirdMultipleSequentialTransactionsSucceed(): void
	{
		// For a Firebird serial transaction, commit/rollback triggers an automatic
		// internal restart (isTransactionComplete → restartTransaction).  The caller
		// must NOT call beginTransaction() again after each cycle; the same $tx
		// reference remains valid and active.
		$conn = $this->openFirebird('UTF-8');

		$tx = $conn->beginTransaction();
		$tx->commit();
		// Serial restart keeps the transaction alive.
		$this->assertNotNull(
			$conn->getCurrentTransaction(),
			'Serial transaction must remain current after commit.'
		);

		$tx->rollBack();
		// Serial restart again.
		$this->assertNotNull(
			$conn->getCurrentTransaction(),
			'Serial transaction must remain current after rollback.'
		);

		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — requiresPostTransactionFlush behavioral verification
	//
	// pdo_firebird opens a new implicit transaction inside isc_commit_transaction
	// before the Transaction Inventory Page (TIP) is updated.  That new implicit
	// transaction's MVCC snapshot can therefore miss the just-committed data.
	// TDbTransaction::commit() issues a second PDO::commit() (the "flush") to
	// force pdo_firebird to open a fresh implicit transaction with an up-to-date
	// TIP, making committed data immediately visible to subsequent reads on the
	// same connection.
	// -----------------------------------------------------------------------

	public function testFirebirdPostTransactionFlushMakesCommittedDataImmediatelyVisible(): void
	{
		// This test verifies the observable effect of requiresPostTransactionFlush.
		// After committing an INSERT, the row must be visible immediately on the
		// same connection without re-opening it.
		$conn = $this->openFirebird('UTF-8');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_FLUSH_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_FB_FLUSH_TEST (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_FB_FLUSH_TEST VALUES (1)')->execute();
		$tx->commit();

		// Without the post-transaction flush, the implicit Firebird transaction
		// that pdo_firebird opens internally right after PDO::commit() may hold
		// a stale MVCC snapshot and return 0 here.  With the flush (a second
		// PDO::commit()), a fresh implicit transaction with a current snapshot is
		// used, so the count must be 1.
		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_FLUSH_TEST')->queryScalar();
		$this->assertSame(
			1,
			$count,
			'requiresPostTransactionFlush must flush the implicit Firebird transaction ' .
			'so that committed data is immediately visible on the same connection.'
		);

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_FLUSH_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testFirebirdRollbackDataIsNotVisibleAfterFlush(): void
	{
		// A rolled-back INSERT must not be visible even with the post-flush.
		$conn = $this->openFirebird('UTF-8');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_ROLLBACK_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_FB_ROLLBACK_TEST (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_FB_ROLLBACK_TEST VALUES (1)')->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_ROLLBACK_TEST')->queryScalar();
		$this->assertSame(0, $count, 'Rolled-back data must not be visible after the post-flush.');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_ROLLBACK_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testFirebirdThreeSequentialTransactionsWithDataPersistCorrectly(): void
	{
		// Verify that the pre-begin flush (clearing the implicit Firebird transaction
		// before beginTransaction()) and the serial restart (re-starting an explicit
		// transaction after every commit/rollback) work correctly across three cycles.
		//
		// IMPORTANT: For serial Firebird transactions, beginTransaction() is called
		// once.  After each commit/rollback the serial restart calls PDO::beginTransaction()
		// internally, so the caller must reuse the same $tx reference — not call
		// beginTransaction() again (which would find inTransaction()=true and throw).
		$conn = $this->openFirebird('UTF-8');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_MULTI_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_FB_MULTI_TEST (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		$tx = $conn->beginTransaction();

		// Cycle 1: commit id=1; serial restart starts a fresh explicit tx.
		$conn->createCommand('INSERT INTO CAPS_FB_MULTI_TEST VALUES (1)')->execute();
		$tx->commit();
		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_MULTI_TEST')->queryScalar();
		$this->assertSame(1, $count, 'After cycle 1 commit, 1 row expected.');

		// Cycle 2: rollback (insert id=2, then discard); serial restart again.
		$conn->createCommand('INSERT INTO CAPS_FB_MULTI_TEST VALUES (2)')->execute();
		$tx->rollBack();
		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_MULTI_TEST')->queryScalar();
		$this->assertSame(1, $count, 'After cycle 2 rollback, still only 1 row expected.');

		// Cycle 3: commit id=3; serial restart again.
		$conn->createCommand('INSERT INTO CAPS_FB_MULTI_TEST VALUES (3)')->execute();
		$tx->commit();
		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_MULTI_TEST')->queryScalar();
		$this->assertSame(2, $count, 'After cycle 3 commit, 2 rows expected (id=1 and id=3).');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_MULTI_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}
}
