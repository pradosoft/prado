<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — Oracle (OCI).
 *
 * Verifies static capability flags and live behaviour for the 'oci' driver.
 *
 * Key Oracle characteristics:
 *  - supportsCharset = true (DSN charset= param only)
 *  - hasAutoCommitAttribute = true
 *  - requiresPreBeginTransactionFlush = false
 *  - requiresPostTransactionFlush = false
 *  - supportsRuntimeCharsetSet = false (DSN-only charset)
 *  - requiresPostConnectCharset = false
 *  - getCharsetDsnParam = 'charset'
 *  - getCharsetQuerySql = null (no runtime charset query)
 *  - UTF-8 resolves to 'AL32UTF8' (Oracle's full Unicode encoding)
 *
 * Tests are skipped automatically when pdo_oci is missing or the Oracle
 * instance is unreachable.
 *
 * Environment variables
 * ---------------------
 * ORACLE_SERVICE_NAME  Oracle service name. Defaults to FREEPDB1.
 */
class TDbDriverCapabilitiesOracleIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupOciConnection';
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

	private function openOci(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_oci')) {
			$this->markTestSkipped('pdo_oci extension not available.');
		}
		$serviceName = getenv('ORACLE_SERVICE_NAME') ?: 'FREEPDB1';
		try {
			$conn = new TDbConnection(
				'oci:dbname=//localhost:1521/' . $serviceName,
				'prado_unitest',
				'prado_unitest',
				$charset
			);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect to Oracle: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags
	// -----------------------------------------------------------------------

	public function testOciSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('oci'));
	}

	public function testOciHasAutoCommitAttribute(): void
	{
		$this->assertTrue(TDbDriverCapabilities::hasAutoCommitAttribute('oci'));
	}


	public function testOciRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('oci'));
	}

	public function testOciRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('oci'));
	}

	public function testOciDoesNotSupportRuntimeCharsetSet(): void
	{
		// Oracle charset is configured at DSN level; no runtime SQL command exists.
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet('oci'));
	}

	public function testOciRequiresNoPostConnectCharset(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('oci'));
	}

	public function testOciCharsetSetSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql('oci'));
	}

	public function testOciCharsetPragmaSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('oci'));
	}

	public function testOciCharsetDsnParamIsCharset(): void
	{
		$this->assertSame('charset', TDbDriverCapabilities::getCharsetDsnParam('oci'));
	}

	public function testOciCharsetDsnPatternMatchesCharsetParam(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern('oci');
		$this->assertNotNull($pattern);
		$this->assertSame(1, preg_match($pattern, ';charset=AL32UTF8', $m));
		$this->assertSame('AL32UTF8', $m[1]);
	}

	public function testOciCharsetQuerySqlIsNull(): void
	{
		// Oracle does not support a simple runtime charset query via PDO.
		$this->assertNull(TDbDriverCapabilities::getCharsetQuerySql('oci'));
	}

	public function testOciGetListTablesSqlContainsUserTables(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('oci');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('user_tables', $sql);
	}

	public function testOciMetaDataClassName(): void
	{
		$this->assertSame(TOracleMetaData::class, TDbDriverCapabilities::getMetaDataClass('oci'));
	}

	// -----------------------------------------------------------------------
	// Charset resolution
	// -----------------------------------------------------------------------

	public function testOciResolveUtf8ReturnsAl32Utf8(): void
	{
		$this->assertSame('AL32UTF8', TDbDriverCapabilities::resolveCharset('UTF-8', 'oci'));
	}

	public function testOciResolveUtf16ReturnsAl16Utf16(): void
	{
		$this->assertSame('AL16UTF16', TDbDriverCapabilities::resolveCharset('UTF-16', 'oci'));
	}

	public function testOciResolveLatin1ReturnsWe8Iso8859P1(): void
	{
		$this->assertSame('WE8ISO8859P1', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'oci'));
	}

	public function testOciResolveLatin2ReturnsEe8Iso8859P2(): void
	{
		$this->assertSame('EE8ISO8859P2', TDbDriverCapabilities::resolveCharset('ISO-8859-2', 'oci'));
	}

	public function testOciResolveAsciiReturnsUs7Ascii(): void
	{
		$this->assertSame('US7ASCII', TDbDriverCapabilities::resolveCharset('ASCII', 'oci'));
	}

	public function testOciResolveWin1250ReturnsEe8Mswin1250(): void
	{
		$this->assertSame('EE8MSWIN1250', TDbDriverCapabilities::resolveCharset('Windows-1250', 'oci'));
	}

	public function testOciResolveWin1251ReturnsCl8Mswin1251(): void
	{
		$this->assertSame('CL8MSWIN1251', TDbDriverCapabilities::resolveCharset('Windows-1251', 'oci'));
	}

	public function testOciResolveWin1252ReturnsWe8Mswin1252(): void
	{
		$this->assertSame('WE8MSWIN1252', TDbDriverCapabilities::resolveCharset('Windows-1252', 'oci'));
	}

	public function testOciResolveKoi8rReturnsCl8Koi8r(): void
	{
		$this->assertSame('CL8KOI8R', TDbDriverCapabilities::resolveCharset('KOI8-R', 'oci'));
	}

	public function testOciResolveKoi8uReturnsCl8Koi8u(): void
	{
		$this->assertSame('CL8KOI8U', TDbDriverCapabilities::resolveCharset('KOI8-U', 'oci'));
	}

	public function testOciUnresolveAl32Utf8ReturnsUtf8Standard(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::unresolveCharset('AL32UTF8', 'oci'));
	}

	public function testOciUnresolveWe8Iso8859P1ReturnsLatin1Standard(): void
	{
		$this->assertSame('ISO-8859-1', TDbDriverCapabilities::unresolveCharset('WE8ISO8859P1', 'oci'));
	}

	// -----------------------------------------------------------------------
	// Live connection — charset (DSN-based, no runtime query)
	//
	// Oracle configures charset via the DSN 'charset=' parameter only.
	// getCharsetQuerySql('oci') returns null, so DatabaseCharset returns the
	// driver-resolved form of whatever was passed to TDbConnection (e.g.
	// 'UTF-8' → 'AL32UTF8').  This exercises the DSN-injection path.
	// -----------------------------------------------------------------------

	public function testOciDatabaseCharsetReturnsAl32Utf8WhenUtf8Configured(): void
	{
		$conn = $this->openOci('UTF-8');
		// getCharsetQuerySql is null for oci; getDatabaseCharset() returns
		// the driver-resolved charset name that was injected into the DSN.
		$this->assertSame('AL32UTF8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testOciDatabaseCharsetReturnsWe8Iso8859P1WhenLatin1Configured(): void
	{
		$conn = $this->openOci('ISO-8859-1');
		$this->assertSame('WE8ISO8859P1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testOciSupportsCharsetFlagMatchesLiveDriver(): void
	{
		$conn = $this->openOci();
		$this->assertTrue(TDbDriverCapabilities::supportsCharset($conn->getDriverName()));
		$conn->Active = false;
	}

	public function testOciDoesNotSupportRuntimeCharsetSetLive(): void
	{
		// supportsRuntimeCharsetSet is false for oci; verify this matches the live driver.
		$conn = $this->openOci('UTF-8');
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet($conn->getDriverName()));
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testOciScaffoldInputClass(): void
	{
		$this->assertSame('TOracleScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('oci'));
	}

	public function testOciScaffoldInputFile(): void
	{
		$this->assertSame('/TOracleScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('oci'));
	}

	// -----------------------------------------------------------------------
	// Live connection — MetaData factory
	// -----------------------------------------------------------------------

	public function testOciMetaDataInstanceIsTOracleMetaData(): void
	{
		$conn = $this->openOci();
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TOracleMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testOciListTablesQueryReturnsArray(): void
	{
		$conn = $this->openOci();
		$result = $conn->createCommand(TDbDriverCapabilities::getListTablesSql('oci'))->queryAll();
		$this->assertIsArray($result);
		$conn->Active = false;
	}

	public function testOciListTablesQueryReturnsCreatedTable(): void
	{
		// Oracle stores table names in uppercase in user_tables.
		// The capability SQL is: SELECT table_name FROM user_tables.
		// PDO/oci may return column keys as TABLE_NAME; normalise to lower-case.
		$conn = $this->openOci();

		try {
			$conn->createCommand('DROP TABLE CAPS_OCI_LIST_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_OCI_LIST_TEST (ID NUMBER(10) NOT NULL PRIMARY KEY)'
		)->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('oci');
		$rows = $conn->createCommand($sql)->queryAll();

		// Normalise column key casing: pdo_oci may return TABLE_NAME in uppercase.
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'table_name');
		$this->assertContains('CAPS_OCI_LIST_TEST', $names);

		try {
			$conn->createCommand('DROP TABLE CAPS_OCI_LIST_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testOciListTablesQueryDoesNotReturnDroppedTable(): void
	{
		$conn = $this->openOci();

		try {
			$conn->createCommand('DROP TABLE CAPS_OCI_DROPPED_TEST')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_OCI_DROPPED_TEST (ID NUMBER(10) NOT NULL PRIMARY KEY)'
		)->execute();
		$conn->createCommand('DROP TABLE CAPS_OCI_DROPPED_TEST')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('oci');
		$rows = $conn->createCommand($sql)->queryAll();
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'table_name');
		$this->assertNotContains('CAPS_OCI_DROPPED_TEST', $names);

		$conn->Active = false;
	}

	public function testOciListTablesQueryExcludesSystemTables(): void
	{
		// user_tables only returns tables owned by the current user — not system
		// tables from SYS or SYSTEM.
		$conn = $this->openOci();
		$sql  = TDbDriverCapabilities::getListTablesSql('oci');
		$rows = $conn->createCommand($sql)->queryAll();
		$rows  = array_map(fn($r) => array_change_key_case($r, CASE_LOWER), $rows);
		$names = array_column($rows, 'table_name');
		// System tables must not leak into user_tables.
		$this->assertNotContains('ALL_TABLES', $names);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testOciTransactionCommitSucceeds(): void
	{
		$conn = $this->openOci();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	public function testOciTransactionRollbackSucceeds(): void
	{
		$conn = $this->openOci();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	//
	// pdo_oci exposes PDO::ATTR_AUTOCOMMIT. TDbConnection::HasAutoCommit is
	// true; AutoCommit reads the live session flag and returns true by default.
	// -----------------------------------------------------------------------

	public function testOciHasAutoCommitAttributeViaConnection(): void
	{
		$conn = $this->openOci();
		$this->assertTrue(
			$conn->HasAutoCommit,
			'Oracle (pdo_oci) must report HasAutoCommit = true via TDbConnection.'
		);
		$conn->Active = false;
	}

	public function testOciAutoCommitIsTrueByDefault(): void
	{
		$conn = $this->openOci();
		$this->assertTrue(
			$conn->AutoCommit,
			'Oracle AutoCommit must be true when no explicit transaction is active.'
		);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — TDbTransaction::beginTransaction() (reuse & supersession)
	//
	// Oracle DDL auto-commits, so CREATE/DROP TABLE statements execute outside
	// any explicit transaction and do not need to be wrapped in one.
	// -----------------------------------------------------------------------

	public function testOciTxBeginTransactionIsActiveAfterReuseViaCommit(): void
	{
		// After commit(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openOci();
		$tx = $conn->beginTransaction();
		$tx->commit();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after commit.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testOciTxBeginTransactionIsActiveAfterReuseViaRollback(): void
	{
		// After rollback(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openOci();
		$tx = $conn->beginTransaction();
		$tx->rollBack();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after rollback.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testOciTxBeginTransactionReuseIsolatesWorkUnits(): void
	{
		// Two sequential work units on the same object: first commits (row persists),
		// second rolls back (row discarded). Oracle DDL auto-commits.
		$conn = $this->openOci();

		try {
			$conn->createCommand('DROP TABLE CAPS_OCI_TX_REUSE')->execute();
		} catch (\Exception $e) {
		}
		$conn->createCommand(
			'CREATE TABLE CAPS_OCI_TX_REUSE (ID NUMBER(10) NOT NULL PRIMARY KEY)'
		)->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_OCI_TX_REUSE VALUES (1)')->execute();
		$tx->commit();

		$tx->beginTransaction();
		$conn->createCommand('INSERT INTO CAPS_OCI_TX_REUSE VALUES (2)')->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand(
			'SELECT COUNT(*) FROM CAPS_OCI_TX_REUSE'
		)->queryScalar();
		$this->assertSame(1, $count, 'Only the committed row must persist after reuse rollback.');

		try {
			$conn->createCommand('DROP TABLE CAPS_OCI_TX_REUSE')->execute();
		} catch (\Exception $e) {
		}
		$conn->Active = false;
	}

	public function testOciTxBeginTransactionThrowsWhenSuperseded(): void
	{
		// After $conn->beginTransaction() supersedes $tx1, calling
		// $tx1->beginTransaction() must throw TDbException.
		$conn = $this->openOci();
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

	public function testOciGetLastTransactionReflectsNewestObject(): void
	{
		// After $conn->beginTransaction() creates $tx2, getLastTransaction()
		// must return $tx2, not the superseded $tx1.
		$conn = $this->openOci();
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
