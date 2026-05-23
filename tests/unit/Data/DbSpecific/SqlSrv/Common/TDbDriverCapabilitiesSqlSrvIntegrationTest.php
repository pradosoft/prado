<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\SqlSrv\TSqlSrvMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — Microsoft SQL Server.
 *
 * Covers both the 'sqlsrv' and 'dblib' drivers (which share the same
 * TDbDriverCapabilities mapping) at the static level, and exercises live
 * behaviour through the 'sqlsrv' driver.
 *
 * Key MSSQL characteristics:
 *  - supportsCharset = true for both sqlsrv and dblib
 *  - hasAutoCommitAttribute = false  (sqlsrv/dblib do not expose PDO::ATTR_AUTOCOMMIT)
 *  - requiresPreBeginTransactionFlush = false
 *  - requiresPostTransactionFlush = false
 *  - supportsRuntimeCharsetSet = false (DSN-only charset)
 *  - requiresPostConnectCharset = false
 *  - getCharsetDsnParam: 'CharacterSet' for sqlsrv, 'charset' for dblib
 *  - getCharsetQuerySql = null (no runtime charset query)
 *
 * Tests are skipped automatically when pdo_sqlsrv is missing or the
 * SQL Server at localhost:1433 is unreachable.
 */
class TDbDriverCapabilitiesSqlSrvIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqlSrvConnection';
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

	private function openSqlsrv(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_sqlsrv')) {
			$this->markTestSkipped('pdo_sqlsrv extension not available.');
		}
		try {
			$conn = new TDbConnection(
				'sqlsrv:Server=localhost,1433;Database=prado_unitest;TrustServerCertificate=yes',
				'prado_unitest',
				'prado_unitest',
				$charset
			);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect to SQL Server: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags — sqlsrv
	// -----------------------------------------------------------------------

	public function testSqlsrvSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('sqlsrv'));
	}

	public function testSqlsrvHasAutoCommitAttributeIsFalse(): void
	{
		// sqlsrv does not expose PDO::ATTR_AUTOCOMMIT; reading or writing it
		// throws a PDOException.  hasAutoCommitAttribute must return false.
		$this->assertFalse(TDbDriverCapabilities::hasAutoCommitAttribute('sqlsrv'));
	}


	public function testSqlsrvRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('sqlsrv'));
	}

	public function testSqlsrvRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('sqlsrv'));
	}

	public function testSqlsrvDoesNotSupportRuntimeCharsetSet(): void
	{
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet('sqlsrv'));
	}

	public function testSqlsrvRequiresNoPostConnectCharset(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('sqlsrv'));
	}

	public function testSqlsrvCharsetSetSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql('sqlsrv'));
	}

	public function testSqlsrvCharsetPragmaSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('sqlsrv'));
	}

	public function testSqlsrvCharsetDsnParamIsCharacterSet(): void
	{
		// sqlsrv uses 'CharacterSet' (capital C, capital S) in the DSN.
		$this->assertSame('CharacterSet', TDbDriverCapabilities::getCharsetDsnParam('sqlsrv'));
	}

	public function testSqlsrvCharsetDsnPatternMatchesCharacterSetParam(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern('sqlsrv');
		$this->assertNotNull($pattern);
		$this->assertSame(1, preg_match($pattern, ';CharacterSet=UTF-8', $m));
		$this->assertSame('UTF-8', $m[1]);
	}

	public function testSqlsrvCharsetQuerySqlIsNull(): void
	{
		// No runtime charset query is available for MSSQL.
		$this->assertNull(TDbDriverCapabilities::getCharsetQuerySql('sqlsrv'));
	}

	public function testSqlsrvGetListTablesSqlContainsInformationSchema(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('sqlsrv');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('INFORMATION_SCHEMA.TABLES', $sql);
	}

	public function testSqlsrvMetaDataClassName(): void
	{
		$this->assertSame(TSqlSrvMetaData::class, TDbDriverCapabilities::getMetaDataClass('sqlsrv'));
	}

	// -----------------------------------------------------------------------
	// Static capability flags — dblib (mirrors sqlsrv except for charset param)
	// -----------------------------------------------------------------------

	public function testDblibSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('dblib'));
	}

	public function testDblibHasAutoCommitAttributeIsFalse(): void
	{
		// dblib does not expose PDO::ATTR_AUTOCOMMIT; reading or writing it
		// throws a PDOException.  hasAutoCommitAttribute must return false.
		$this->assertFalse(TDbDriverCapabilities::hasAutoCommitAttribute('dblib'));
	}


	public function testDblibRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('dblib'));
	}

	public function testDblibRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('dblib'));
	}

	public function testDblibDoesNotSupportRuntimeCharsetSet(): void
	{
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet('dblib'));
	}

	public function testDblibCharsetDsnParamIsCharset(): void
	{
		// dblib uses lowercase 'charset', unlike sqlsrv which uses 'CharacterSet'.
		$this->assertSame('charset', TDbDriverCapabilities::getCharsetDsnParam('dblib'));
	}

	public function testDblibCharsetQuerySqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetQuerySql('dblib'));
	}

	public function testDblibGetListTablesSqlMatchesSqlsrv(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getListTablesSql('sqlsrv'),
			TDbDriverCapabilities::getListTablesSql('dblib')
		);
	}

	public function testDblibMetaDataClassNameMatchesSqlsrv(): void
	{
		$this->assertSame(TSqlSrvMetaData::class, TDbDriverCapabilities::getMetaDataClass('dblib'));
	}

	// -----------------------------------------------------------------------
	// Charset resolution — sqlsrv
	// -----------------------------------------------------------------------

	public function testSqlsrvResolveUtf8ReturnsUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::resolveCharset('UTF-8', 'sqlsrv'));
	}

	public function testSqlsrvResolveLatin1ReturnsIso88591(): void
	{
		$this->assertSame('ISO-8859-1', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'sqlsrv'));
	}

	public function testSqlsrvResolveAsciiReturnsUsAscii(): void
	{
		// sqlsrv has no ASCII entry; resolveCharset normalizes to the IANA canonical name.
		$this->assertSame('US-ASCII', TDbDriverCapabilities::resolveCharset('ASCII', 'sqlsrv'));
		$this->assertSame('US-ASCII', TDbDriverCapabilities::resolveCharset('US-ASCII', 'sqlsrv'));
	}

	public function testSqlsrvResolveWin1250ReturnsIanaName(): void
	{
		// sqlsrv has no Windows-125x entry; resolveCharset normalizes to the IANA canonical name.
		$this->assertSame('windows-1250', TDbDriverCapabilities::resolveCharset('Windows-1250', 'sqlsrv'));
		$this->assertSame('windows-1250', TDbDriverCapabilities::resolveCharset('windows-1250', 'sqlsrv'));
	}

	public function testSqlsrvUnresolveUtf8ReturnsUtf8Standard(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::unresolveCharset('UTF-8', 'sqlsrv'));
	}

	// -----------------------------------------------------------------------
	// Charset resolution — dblib
	// -----------------------------------------------------------------------

	public function testDblibResolveUtf8ReturnsUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::resolveCharset('UTF-8', 'dblib'));
	}

	public function testDblibResolveLatin1ReturnsIso88591(): void
	{
		$this->assertSame('ISO-8859-1', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'dblib'));
	}

	public function testDblibResolveKoi8rReturnsKoi8R(): void
	{
		$this->assertSame('KOI8-R', TDbDriverCapabilities::resolveCharset('KOI8-R', 'dblib'));
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testSqlsrvScaffoldInputClass(): void
	{
		$this->assertSame('TSqlSrvScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('sqlsrv'));
	}

	public function testSqlsrvScaffoldInputFile(): void
	{
		$this->assertSame('/TSqlSrvScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('sqlsrv'));
	}

	public function testDblibScaffoldInputMatchesSqlsrv(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getScaffoldInputClass('sqlsrv'),
			TDbDriverCapabilities::getScaffoldInputClass('dblib')
		);
	}

	// -----------------------------------------------------------------------
	// Live connection — charset (DSN-based, no runtime query)
	//
	// MSSQL (sqlsrv) configures charset via the DSN 'CharacterSet=' parameter
	// only.  getCharsetQuerySql('sqlsrv') returns null, so DatabaseCharset
	// returns the driver-resolved form of the configured charset (for sqlsrv,
	// the canonical name is passed through unchanged — 'UTF-8' stays 'UTF-8').
	// -----------------------------------------------------------------------

	public function testSqlsrvDatabaseCharsetReturnsUtf8WhenConfigured(): void
	{
		$conn = $this->openSqlsrv('UTF-8');
		// getCharsetQuerySql is null for sqlsrv; getDatabaseCharset() returns
		// the driver-resolved charset injected into the DSN CharacterSet= param.
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqlsrvSupportsCharsetFlagMatchesLiveDriver(): void
	{
		$conn = $this->openSqlsrv();
		$this->assertTrue(TDbDriverCapabilities::supportsCharset($conn->getDriverName()));
		$conn->Active = false;
	}

	public function testSqlsrvDoesNotSupportRuntimeCharsetSetLive(): void
	{
		// supportsRuntimeCharsetSet is false for sqlsrv; verify against live driver.
		$conn = $this->openSqlsrv('UTF-8');
		$this->assertFalse(TDbDriverCapabilities::supportsRuntimeCharsetSet($conn->getDriverName()));
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — MetaData factory
	// -----------------------------------------------------------------------

	public function testSqlsrvMetaDataInstanceIsTSqlSrvMetaData(): void
	{
		$conn = $this->openSqlsrv();
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TSqlSrvMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testSqlsrvListTablesQueryReturnsArray(): void
	{
		$conn = $this->openSqlsrv();
		$result = $conn->createCommand(TDbDriverCapabilities::getListTablesSql('sqlsrv'))->queryAll();
		$this->assertIsArray($result);
		$conn->Active = false;
	}

	public function testSqlsrvListTablesQueryReturnsCreatedTable(): void
	{
		// Create a temporary table, run the INFORMATION_SCHEMA.TABLES query, verify
		// the name appears, then clean up.  sqlsrv stores table names case-insensitively.
		$conn = $this->openSqlsrv();
		$conn->createCommand('IF OBJECT_ID(\'caps_mssql_list_test\',\'U\') IS NOT NULL DROP TABLE caps_mssql_list_test')->execute();
		$conn->createCommand('CREATE TABLE caps_mssql_list_test (id INT NOT NULL PRIMARY KEY)')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('sqlsrv');
		$rows = $conn->createCommand($sql)->queryAll();

		// INFORMATION_SCHEMA.TABLES returns TABLE_NAME column.
		$names = array_map('strtolower', array_column($rows, 'TABLE_NAME'));
		$this->assertContains('caps_mssql_list_test', $names);

		$conn->createCommand('DROP TABLE caps_mssql_list_test')->execute();
		$conn->Active = false;
	}

	public function testSqlsrvListTablesQueryExcludesViews(): void
	{
		// The capability SQL filters TABLE_TYPE = 'BASE TABLE'; views must not appear.
		$conn = $this->openSqlsrv();
		$conn->createCommand('IF OBJECT_ID(\'caps_mssql_view_test\',\'V\') IS NOT NULL DROP VIEW caps_mssql_view_test')->execute();
		$conn->createCommand('CREATE VIEW caps_mssql_view_test AS SELECT 1 AS n')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('sqlsrv');
		$rows = $conn->createCommand($sql)->queryAll();
		$names = array_map('strtolower', array_column($rows, 'TABLE_NAME'));
		$this->assertNotContains('caps_mssql_view_test', $names);

		$conn->createCommand('DROP VIEW caps_mssql_view_test')->execute();
		$conn->Active = false;
	}

	public function testSqlsrvListTablesQueryDoesNotReturnDroppedTable(): void
	{
		$conn = $this->openSqlsrv();
		$conn->createCommand('IF OBJECT_ID(\'caps_mssql_dropped_test\',\'U\') IS NOT NULL DROP TABLE caps_mssql_dropped_test')->execute();
		$conn->createCommand('CREATE TABLE caps_mssql_dropped_test (id INT NOT NULL PRIMARY KEY)')->execute();
		$conn->createCommand('DROP TABLE caps_mssql_dropped_test')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('sqlsrv');
		$rows = $conn->createCommand($sql)->queryAll();
		$names = array_map('strtolower', array_column($rows, 'TABLE_NAME'));
		$this->assertNotContains('caps_mssql_dropped_test', $names);

		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testSqlsrvTransactionCommitSucceeds(): void
	{
		$conn = $this->openSqlsrv();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	public function testSqlsrvTransactionRollbackSucceeds(): void
	{
		$conn = $this->openSqlsrv();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	//
	// sqlsrv and dblib do not expose PDO::ATTR_AUTOCOMMIT (reading it throws a
	// PDOException). TDbConnection::HasAutoCommit returns false; AutoCommit
	// returns false gracefully without attempting to read the absent attribute.
	// -----------------------------------------------------------------------

	public function testSqlsrvHasNoAutoCommitAttributeViaConnection(): void
	{
		$conn = $this->openSqlsrv();
		$this->assertFalse(
			$conn->HasAutoCommit,
			'MSSQL (sqlsrv) must report HasAutoCommit = false via TDbConnection.'
		);
		$conn->Active = false;
	}

	public function testSqlsrvAutoCommitReturnsFalseWhenAttributeAbsent(): void
	{
		$conn = $this->openSqlsrv();
		$this->assertFalse(
			$conn->AutoCommit,
			'MSSQL (sqlsrv) AutoCommit must return false when the attribute is not supported.'
		);
		$conn->Active = false;
	}

	public function testSqlsrvGetLastTransactionReflectsNewestObject(): void
	{
		// After $conn->beginTransaction() creates $tx2, getLastTransaction()
		// must return $tx2, not the superseded $tx1.
		$conn = $this->openSqlsrv();
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
