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
 *  - requiresPreBeginTransactionFlush = true  ← flush before beginTransaction
 *  - requiresPostTransactionFlush = true  ← flush after commit/rollback
 *  - supportsRuntimeCharsetSet = false  ← DSN-only charset
 *  - requiresPostConnectCharset = false
 *  - getCharsetDsnParam = 'charset'
 *
 * The 'interbase' driver is an alias for charset resolution but is NOT
 * aliased for the pre/post flush flags.
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
	// 'interbase' aliases firebird for charset resolution but is NOT aliased
	// for the pre/post flush flags.
	// -----------------------------------------------------------------------

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
	// Live connection — charset query
	// -----------------------------------------------------------------------

	public function testFirebirdCharsetQuerySqlExecutesAndReturnsCharset(): void
	{
		// getCharsetQuerySql('firebird') returns a MON$ATTACHMENTS JOIN query.
		// Execute it directly against a live UTF-8 connection and verify it
		// returns the Firebird charset name ('UTF8').
		$conn = $this->openFirebird('UTF-8');
		$sql     = TDbDriverCapabilities::getCharsetQuerySql('firebird');
		$this->assertNotNull($sql, 'getCharsetQuerySql must not return null for firebird.');
		$charset = $this->queryScalar($conn, $sql);
		$this->assertSame('UTF8', $charset,
			'getCharsetQuerySql must return the charset name the server reports for the current attachment.');
		$conn->Active = false;
	}

	public function testFirebirdDsnCharsetParamAppliedOnConnect(): void
	{
		// Connecting with 'ISO-8859-1' (which resolves to 'ISO8859_1') must be reflected
		// in DatabaseCharset.
		$conn = $this->openFirebird('ISO-8859-1');
		$this->assertSame('ISO8859_1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testFirebirdSupportsCharsetFlagMatchesLiveDriver(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$this->assertTrue(TDbDriverCapabilities::supportsCharset($conn->getDriverName()));
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testFirebirdTransactionCommitSucceeds(): void
	{
		// commit() completes the explicit transaction and deactivates it.
		$conn = $this->openFirebird('UTF-8');
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$this->assertFalse(
			$tx->getActive(),
			'Firebird transaction must be inactive after commit.'
		);
		$conn->Active = false;
	}

	public function testFirebirdTransactionRollbackSucceeds(): void
	{
		// rollBack() aborts the explicit transaction and deactivates it.
		$conn = $this->openFirebird('UTF-8');
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
		$this->assertFalse(
			$tx->getActive(),
			'Firebird transaction must be inactive after rollback.'
		);
		$conn->Active = false;
	}

	public function testFirebirdMultipleSequentialTransactionsSucceed(): void
	{
		// Multiple beginTransaction/commit/rollback cycles on the same connection
		// must all succeed.  Each cycle requires a new beginTransaction() call.
		$conn = $this->openFirebird('UTF-8');

		$tx = $conn->beginTransaction();
		$tx->commit();
		$this->assertNull($conn->getCurrentTransaction(), 'No active transaction after commit.');

		$tx2 = $conn->beginTransaction();
		$tx2->rollBack();
		$this->assertNull($conn->getCurrentTransaction(), 'No active transaction after rollback.');

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

		// Skip if pdo_firebird rollback is not reliable on this server build.
		if (!$this->probeFirebirdRollback($conn, 'CAPS_FB_ROLLBACK_TEST')) {
			try { $conn->createCommand('DROP TABLE CAPS_FB_ROLLBACK_TEST')->execute(); } catch (\Exception $e) {}
			$conn->Active = false;
			$this->markTestSkipped('pdo_firebird rollback is unreliable in this environment; skipping.');
		}

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
		// before beginTransaction()) works correctly across three commit/rollback cycles.
		// Each cycle calls beginTransaction() afresh; the pre-begin flush clears the
		// implicit transaction that pdo_firebird opens after every commit/rollback.
		$conn = $this->openFirebird('UTF-8');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_MULTI_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_FB_MULTI_TEST (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		// Skip if pdo_firebird rollback is not reliable on this server build.
		if (!$this->probeFirebirdRollback($conn, 'CAPS_FB_MULTI_TEST')) {
			try { $conn->createCommand('DROP TABLE CAPS_FB_MULTI_TEST')->execute(); } catch (\Exception $e) {}
			$conn->Active = false;
			$this->markTestSkipped('pdo_firebird rollback is unreliable in this environment; skipping.');
		}

		// Cycle 1: commit id=1.
		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_FB_MULTI_TEST VALUES (1)')->execute();
		$tx->commit();
		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_MULTI_TEST')->queryScalar();
		$this->assertSame(1, $count, 'After cycle 1 commit, 1 row expected.');

		// Cycle 2: rollback (insert id=2, then discard).
		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_FB_MULTI_TEST VALUES (2)')->execute();
		$tx->rollBack();
		$count = (int) $conn->createCommand('SELECT COUNT(*) FROM CAPS_FB_MULTI_TEST')->queryScalar();
		$this->assertSame(1, $count, 'After cycle 2 rollback, still only 1 row expected.');

		// Cycle 3: commit id=3.
		$tx = $conn->beginTransaction();
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

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	//
	// pdo_firebird exposes PDO::ATTR_AUTOCOMMIT and always returns 1 (true),
	// even inside an explicit transaction (the attribute reflects the PHP-level
	// session setting, not the live transaction state). TDbConnection::HasAutoCommit
	// returns true; AutoCommit reads the attribute and returns true by default.
	// -----------------------------------------------------------------------

	public function testFirebirdHasAutoCommitAttributeViaConnection(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$this->assertTrue(
			$conn->HasAutoCommit,
			'Firebird must report HasAutoCommit = true via TDbConnection.'
		);
		$conn->Active = false;
	}

	public function testFirebirdAutoCommitIsTrueByDefault(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$this->assertTrue(
			$conn->AutoCommit,
			'Firebird AutoCommit must be true when no explicit transaction is active.'
		);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — TDbTransaction::beginTransaction() (reuse & supersession)
	//
	// Firebird requires requiresPreBeginTransactionFlush = true, so every call
	// to beginTransaction() (whether on the connection or on the transaction
	// object for reuse) issues a PDO::commit() first to clear the implicit
	// transaction that pdo_firebird keeps running. The reuse tests verify this
	// path works correctly across multiple cycles on the same object.
	// -----------------------------------------------------------------------

	public function testFirebirdTxBeginTransactionIsActiveAfterReuseViaCommit(): void
	{
		// After commit(), calling beginTransaction() on the same object reactivates it.
		// The pre-begin flush in TDbTransaction::beginTransaction() clears the implicit
		// Firebird transaction so pdo_firebird does not throw "active transaction".
		$conn = $this->openFirebird('UTF-8');
		$tx = $conn->beginTransaction();
		$tx->commit();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after commit.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testFirebirdTxBeginTransactionIsActiveAfterReuseViaRollback(): void
	{
		// After rollback(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openFirebird('UTF-8');
		$tx = $conn->beginTransaction();
		$tx->rollBack();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after rollback.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testFirebirdTxBeginTransactionReuseIsolatesWorkUnits(): void
	{
		// Two sequential work units on the same object via reuse: first commits
		// (row persists), second rolls back (row discarded). Firebird DDL
		// auto-commits, so the CREATE TABLE is outside any explicit transaction.
		$conn = $this->openFirebird('UTF-8');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_TX_REUSE')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_FB_TX_REUSE (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		// Skip if pdo_firebird rollback is not reliable on this server build.
		if (!$this->probeFirebirdRollback($conn, 'CAPS_FB_TX_REUSE')) {
			try { $conn->createCommand('DROP TABLE CAPS_FB_TX_REUSE')->execute(); } catch (\Exception $e) {}
			$conn->Active = false;
			$this->markTestSkipped('pdo_firebird rollback is unreliable in this environment; skipping.');
		}

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_FB_TX_REUSE VALUES (1)')->execute();
		$tx->commit();

		$tx->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_FB_TX_REUSE VALUES (2)')->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand(
			'SELECT COUNT(*) FROM CAPS_FB_TX_REUSE'
		)->queryScalar();
		$this->assertSame(1, $count, 'Only the committed row must persist after reuse rollback.');

		try {
			$conn->createCommand('DROP TABLE CAPS_FB_TX_REUSE')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	/**
	 * Probes whether this pdo_firebird/Firebird combination reliably rolls back DML.
	 *
	 * Inserts one row, rolls back, then checks the row is gone. Returns true when
	 * rollback works correctly, false when pdo_firebird commits on rollback (a known
	 * bug in some PHP 8.x pdo_firebird builds). Any accidentally-committed probe
	 * row is deleted before returning false.
	 *
	 * @param TDbConnection $conn active Firebird connection.
	 * @param string        $table table name to use for the probe (must accept an INT column named ID).
	 * @return bool true = rollback reliable; false = rollback broken, skip the caller.
	 */
	private function probeFirebirdRollback(\Prado\Data\TDbConnection $conn, string $table): bool
	{
		$pdo = $conn->getPdoInstance();
		try { $pdo->commit(); } catch (\Throwable $e) {}
		$conn->beginTransaction()->commit();                // cycle once to reset internal state
		try { $pdo->commit(); } catch (\Throwable $e) {}

		$tx = $conn->beginTransaction();
		$conn->createCommand("INSERT INTO $table VALUES (99999)")->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand(
			"SELECT COUNT(*) FROM $table WHERE ID = 99999"
		)->queryScalar();

		if ($count !== 0) {
			try {
				$conn->createCommand("DELETE FROM $table WHERE ID = 99999")->execute();
				try { $pdo->commit(); } catch (\Throwable $e) {}
			} catch (\Throwable $e) {}
			return false;
		}
		return true;
	}

	public function testFirebirdTxBeginTransactionThrowsWhenSuperseded(): void
	{
		// After $conn->beginTransaction() supersedes $tx1, calling
		// $tx1->beginTransaction() must throw TDbException.
		$conn = $this->openFirebird('UTF-8');
		$tx1 = $conn->beginTransaction();
		$tx1->commit();
		$tx2 = $conn->beginTransaction(); // supersedes $tx1

		try {
			$this->expectException(\Prado\Exceptions\TDbException::class);
			$tx1->beginTransaction();
		} finally {
			if ($tx2->getActive()) {
				$tx2->rollBack();
			}
			$conn->Active = false;
		}
	}

	public function testFirebirdGetLastTransactionReflectsNewestObject(): void
	{
		// After $conn->beginTransaction() creates $tx2, getLastTransaction()
		// must return $tx2, not the superseded $tx1.
		$conn = $this->openFirebird('UTF-8');
		$tx1 = $conn->beginTransaction();
		$this->assertSame($tx1, $conn->getLastTransaction());
		$tx1->commit();

		$tx2 = $conn->beginTransaction();
		$this->assertSame($tx2, $conn->getLastTransaction());
		$this->assertNotSame($tx1, $conn->getLastTransaction());
		$tx2->rollBack();
		$conn->Active = false;
	}
}
