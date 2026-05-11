<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — IBM DB2.
 *
 * Verifies static capability flags and live behaviour for the 'ibm' driver.
 *
 * Key IBM DB2 characteristics:
 *  - supportsCharset = false  ← unique among supported drivers; DB2 has no
 *      charset support through PDO
 *  - hasAutoCommitAttribute = true
 *  - requiresPreBeginTransactionFlush = false
 *  - requiresPostTransactionFlush = false
 *  - supportsRuntimeCharsetSet = false
 *  - requiresPostConnectCharset = false
 *  - getCharsetDsnParam = null
 *  - getCharsetQuerySql = null
 *  - getCharsetSetSql = null
 *
 * Tests are skipped automatically when pdo_ibm is missing or the
 * IBM DB2 instance is unreachable.
 *
 * Environment variables
 * ---------------------
 * DB2_USER      IBM DB2 username.  Defaults to db2inst1.
 * DB2_PASSWORD  IBM DB2 password.  Defaults to Prado_Unitest1.
 * DB2_DATABASE  IBM DB2 database name.  Defaults to pradount.
 */
class TDbDriverCapabilitiesIbmIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
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
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->setUpConnection();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function openIbm(): TDbConnection
	{
		if (!extension_loaded('pdo_ibm')) {
			$this->markTestSkipped('pdo_ibm extension not available.');
		}
		$user     = getenv('DB2_USER')     ?: 'db2inst1';
		$password = getenv('DB2_PASSWORD') ?: 'Prado_Unitest1';
		$dbname   = getenv('DB2_DATABASE') ?: 'pradount';
		try {
			$conn = new TDbConnection(
				'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $dbname .
				';HOSTNAME=localhost;PORT=50000;PROTOCOL=TCPIP',
				$user,
				$password
			);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect to IBM DB2: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags
	// -----------------------------------------------------------------------

	public function testIbmDoesNotSupportCharset(): void
	{
		// IBM DB2 has no charset support via PDO; this is unique among all
		// supported Prado drivers.
		$this->assertFalse(TDbDriverCapabilities::supportsCharset('ibm'));
	}

	public function testIbmHasAutoCommitAttribute(): void
	{
		$this->assertTrue(TDbDriverCapabilities::hasAutoCommitAttribute('ibm'));
	}


	public function testIbmRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('ibm'));
	}

	public function testIbmRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('ibm'));
	}

	public function testIbmDoesNotSupportRuntimeCharsetSet(): void
	{
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet('ibm'));
	}

	public function testIbmRequiresNoPostConnectCharset(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('ibm'));
	}

	public function testIbmCharsetSetSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql('ibm'));
	}

	public function testIbmCharsetPragmaSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('ibm'));
	}

	public function testIbmCharsetDsnParamIsNull(): void
	{
		// IBM DB2 has no charset DSN parameter.
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam('ibm'));
	}

	public function testIbmCharsetDsnPatternIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnPattern('ibm'));
	}

	public function testIbmCharsetQuerySqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetQuerySql('ibm'));
	}

	public function testIbmGetListTablesSqlContainsSyscatTables(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('ibm');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('SYSCAT.TABLES', $sql);
	}

	public function testIbmMetaDataClassName(): void
	{
		$this->assertSame(TIbmMetaData::class, TDbDriverCapabilities::getMetaDataClass('ibm'));
	}

	// -----------------------------------------------------------------------
	// Charset — confirm all charset methods return null / false
	// -----------------------------------------------------------------------

	public function testIbmAllCharsetMethodsReturnNullOrFalse(): void
	{
		// Exhaustive verification that no charset method returns a non-null / truthy
		// value for IBM DB2, since supportsCharset = false.
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql('ibm'));
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('ibm'));
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam('ibm'));
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnPattern('ibm'));
		$this->assertNull(TDbDriverCapabilities::getCharsetQuerySql('ibm'));
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet('ibm'));
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('ibm'));
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testIbmScaffoldInputClass(): void
	{
		$this->assertSame('TIbmScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('ibm'));
	}

	public function testIbmScaffoldInputFile(): void
	{
		$this->assertSame('/TIbmScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('ibm'));
	}

	// -----------------------------------------------------------------------
	// Live connection — MetaData factory
	// -----------------------------------------------------------------------

	public function testIbmMetaDataInstanceIsTIbmMetaData(): void
	{
		$conn = $this->openIbm();
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TIbmMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testIbmListTablesQueryReturnsArray(): void
	{
		$conn = $this->openIbm();
		$result = $conn->createCommand(TDbDriverCapabilities::getListTablesSql('ibm'))->queryAll();
		$this->assertIsArray($result);
		$conn->Active = false;
	}

	public function testIbmListTablesQueryReturnsCreatedTable(): void
	{
		// IBM DB2 stores table names in uppercase in SYSCAT.TABLES.
		// The capability SQL filters TABSCHEMA = CURRENT SCHEMA AND TYPE = 'T'.
		// Column key is TABNAME.
		$conn = $this->openIbm();

		try {
			$conn->createCommand('DROP TABLE CAPS_IBM_LIST_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_IBM_LIST_TEST (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('ibm');
		$rows = $conn->createCommand($sql)->queryAll();

		// pdo_ibm may return column keys in uppercase (TABNAME).
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'tabname');
		$this->assertContains('CAPS_IBM_LIST_TEST', $names);

		try {
			$conn->createCommand('DROP TABLE CAPS_IBM_LIST_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testIbmListTablesQueryFiltersByCurrentSchema(): void
	{
		// The SQL filters TABSCHEMA = CURRENT SCHEMA, so only tables in the
		// connecting user's schema appear — not tables from SYSIBM or SYSCAT.
		$conn = $this->openIbm();
		$sql  = TDbDriverCapabilities::getListTablesSql('ibm');
		$rows = $conn->createCommand($sql)->queryAll();
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'tabname');
		// SYSCAT system tables must not appear in the user-schema result.
		$this->assertNotContains('TABLES', $names);
		$conn->Active = false;
	}

	public function testIbmListTablesQueryDoesNotReturnDroppedTable(): void
	{
		$conn = $this->openIbm();

		try {
			$conn->createCommand('DROP TABLE CAPS_IBM_DROPPED_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_IBM_DROPPED_TEST (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();
		$conn->createCommand('DROP TABLE CAPS_IBM_DROPPED_TEST')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('ibm');
		$rows = $conn->createCommand($sql)->queryAll();
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'tabname');
		$this->assertNotContains('CAPS_IBM_DROPPED_TEST', $names);

		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testIbmTransactionCommitSucceeds(): void
	{
		$conn = $this->openIbm();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	public function testIbmTransactionRollbackSucceeds(): void
	{
		$conn = $this->openIbm();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — confirm charset is not applied
	// -----------------------------------------------------------------------

	public function testIbmDriverNameIsIbm(): void
	{
		$conn = $this->openIbm();
		$this->assertSame('ibm', $conn->getDriverName());
		$conn->Active = false;
	}

	public function testIbmSupportsCharsetFlagMatchesLiveDriver(): void
	{
		// Confirm that the static capability flag aligns with the live driver string.
		$conn = $this->openIbm();
		$this->assertFalse(TDbDriverCapabilities::supportsCharset($conn->getDriverName()));
		$conn->Active = false;
	}

	public function testIbmDatabaseCharsetReturnsEmptyWhenNoCharsetConfigured(): void
	{
		// supportsCharset = false and getCharsetQuerySql = null: DatabaseCharset falls
		// back to the raw Charset property which is empty when none was configured.
		$conn = $this->openIbm();
		$this->assertSame('', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testIbmDoesNotSupportRuntimeCharsetSetLive(): void
	{
		// supportsRuntimeCharsetSet is false for ibm; confirm against live driver.
		$conn = $this->openIbm();
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet($conn->getDriverName()));
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	//
	// pdo_ibm exposes PDO::ATTR_AUTOCOMMIT. TDbConnection::HasAutoCommit is
	// true; AutoCommit reads the live session flag and returns true by default.
	// -----------------------------------------------------------------------

	public function testIbmHasAutoCommitAttributeViaConnection(): void
	{
		$conn = $this->openIbm();
		$this->assertTrue(
			$conn->HasAutoCommit,
			'IBM DB2 must report HasAutoCommit = true via TDbConnection.'
		);
		$conn->Active = false;
	}

	public function testIbmAutoCommitIsTrueByDefault(): void
	{
		$conn = $this->openIbm();
		$this->assertTrue(
			$conn->AutoCommit,
			'IBM DB2 AutoCommit must be true when no explicit transaction is active.'
		);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — TDbTransaction::beginTransaction() (reuse & supersession)
	// -----------------------------------------------------------------------

	public function testIbmTxBeginTransactionIsActiveAfterReuseViaCommit(): void
	{
		// After commit(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openIbm();
		$tx = $conn->beginTransaction();
		$tx->commit();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after commit.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testIbmTxBeginTransactionIsActiveAfterReuseViaRollback(): void
	{
		// After rollback(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openIbm();
		$tx = $conn->beginTransaction();
		$tx->rollBack();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after rollback.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testIbmTxBeginTransactionReuseIsolatesWorkUnits(): void
	{
		// Two sequential work units on the same object: first commits (row persists),
		// second rolls back (row discarded). IBM DB2 DDL auto-commits.
		$conn = $this->openIbm();

		try {
			$conn->createCommand('DROP TABLE CAPS_IBM_TX_REUSE')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_IBM_TX_REUSE (ID INTEGER NOT NULL PRIMARY KEY)'
		)->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_IBM_TX_REUSE VALUES (1)')->execute();
		$tx->commit();

		$tx->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_IBM_TX_REUSE VALUES (2)')->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand(
			'SELECT COUNT(*) FROM CAPS_IBM_TX_REUSE'
		)->queryScalar();
		$this->assertSame(1, $count, 'Only the committed row must persist after reuse rollback.');

		try {
			$conn->createCommand('DROP TABLE CAPS_IBM_TX_REUSE')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testIbmTxBeginTransactionThrowsWhenSuperseded(): void
	{
		// After $conn->beginTransaction() supersedes $tx1, calling
		// $tx1->beginTransaction() must throw TDbException.
		$conn = $this->openIbm();
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

	public function testIbmGetLastTransactionReflectsNewestObject(): void
	{
		// After $conn->beginTransaction() creates $tx2, getLastTransaction()
		// must return $tx2, not the superseded $tx1.
		$conn = $this->openIbm();
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
