<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — SQLite.
 *
 * Exercises every TDbDriverCapabilities method that is specific to SQLite,
 * using a real in-memory SQLite connection so that static capability claims
 * can be verified against live behaviour.
 *
 * Key SQLite characteristics:
 *  - supportsCharset = true (via PRAGMA encoding, UTF-8 / UTF-16 only)
 *  - hasAutoCommitAttribute = false (PDO::ATTR_AUTOCOMMIT not implemented)
 *  - usesSerialTransaction = false
 *  - requiresPreBeginTransactionFlush = false
 *  - requiresPostTransactionFlush = false
 *  - supportsRuntimeCharsetSet = true (PRAGMA encoding on empty DB)
 *  - requiresPostConnectCharset = false
 *  - getCharsetDsnParam = null
 *
 * Tests are skipped automatically when the pdo_sqlite extension is missing.
 */
class TDbDriverCapabilitiesSqliteIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqliteConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null; // uses :memory:
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

	private function openSqlite(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		try {
			$conn = new TDbConnection('sqlite::memory:', '', '', $charset);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot open SQLite: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags
	// -----------------------------------------------------------------------

	public function testSqliteSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('sqlite'));
	}

	public function testSqliteHasNoAutoCommitAttribute(): void
	{
		// SQLite does not expose PDO::ATTR_AUTOCOMMIT; hasAutoCommitAttribute must return false.
		$this->assertFalse(TDbDriverCapabilities::hasAutoCommitAttribute('sqlite'));
	}

	public function testSqliteDoesNotUseSerialTransaction(): void
	{
		$this->assertFalse(TDbDriverCapabilities::usesSerialTransaction('sqlite'));
	}

	public function testSqliteRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('sqlite'));
	}

	public function testSqliteRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('sqlite'));
	}

	public function testSqliteSupportsRuntimeCharsetSet(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsRuntimeCharsetSet('sqlite'));
	}

	public function testSqliteRequiresNoPostConnectCharset(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('sqlite'));
	}

	public function testSqliteHasNoDsnCharsetParam(): void
	{
		// SQLite uses PRAGMA encoding, not a DSN charset parameter.
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam('sqlite'));
	}

	public function testSqliteHasNoDsnCharsetPattern(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnPattern('sqlite'));
	}

	public function testSqliteCharsetSetSqlIsNull(): void
	{
		// SQLite charset is set via PRAGMA, not a SQL SET command.
		$this->assertNull(TDbDriverCapabilities::getCharsetSetSql('sqlite'));
	}

	public function testSqliteCharsetPragmaSqlContainsPragmaEncoding(): void
	{
		$pragma = TDbDriverCapabilities::getCharsetPragmaSql('sqlite');
		$this->assertNotNull($pragma);
		$this->assertStringContainsString('PRAGMA encoding', $pragma);
	}

	public function testSqliteCharsetQuerySqlIsPragmaEncoding(): void
	{
		$this->assertSame('PRAGMA encoding', TDbDriverCapabilities::getCharsetQuerySql('sqlite'));
	}

	public function testSqliteGetListTablesSqlContainsSqliteMaster(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('sqlite');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('sqlite_master', $sql);
	}

	public function testSqliteMetaDataClassName(): void
	{
		$this->assertSame(TSqliteMetaData::class, TDbDriverCapabilities::getMetaDataClass('sqlite'));
	}

	public function testSqlite2MetaDataClassNameMatchesSqlite(): void
	{
		$this->assertSame(TSqliteMetaData::class, TDbDriverCapabilities::getMetaDataClass('sqlite2'));
	}

	public function testSqliteGetListTablesSqlExcludesSqliteSequence(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('sqlite');
		$this->assertStringContainsString('sqlite_sequence', $sql);
	}

	// -----------------------------------------------------------------------
	// Charset resolution
	// -----------------------------------------------------------------------

	public function testSqliteResolveUtf8ReturnsUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::resolveCharset('UTF-8', 'sqlite'));
	}

	public function testSqliteResolveUtf16ReturnsUtf16(): void
	{
		$this->assertSame('UTF-16', TDbDriverCapabilities::resolveCharset('UTF-16', 'sqlite'));
	}

	public function testSqliteLatin1ResolvesToUtf8(): void
	{
		// SQLite does not support ISO-8859-1; the table maps it to UTF-8 and the
		// PRAGMA is silently ignored.  The connection remains UTF-8.
		$this->assertSame('UTF-8', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'sqlite'));
	}

	public function testSqliteWin1250ResolvesToUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::resolveCharset('Windows-1250', 'sqlite'));
	}

	public function testSqliteAsciiResolvesToUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::resolveCharset('ASCII', 'sqlite'));
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testSqliteScaffoldInputClass(): void
	{
		$this->assertSame('TSqliteScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('sqlite'));
	}

	public function testSqliteScaffoldInputFile(): void
	{
		$this->assertSame('/TSqliteScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('sqlite'));
	}

	public function testSqlite2ScaffoldInputMatchesSqlite(): void
	{
		$this->assertSame(
			TDbDriverCapabilities::getScaffoldInputClass('sqlite'),
			TDbDriverCapabilities::getScaffoldInputClass('sqlite2')
		);
	}

	// -----------------------------------------------------------------------
	// Live connection — charset query
	// -----------------------------------------------------------------------

	public function testSqliteCharsetQuerySqlExecutesAndReturnsUtf8(): void
	{
		$conn = $this->openSqlite();
		$sql = TDbDriverCapabilities::getCharsetQuerySql('sqlite');
		$encoding = $this->queryScalar($conn, $sql);
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetReturnsUtf8(): void
	{
		$conn = $this->openSqlite('UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetWithNoCharsetStillReturnsUtf8(): void
	{
		$conn = $this->openSqlite();
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteUnsupportedCharsetSilentlyRemainsUtf8(): void
	{
		// ISO-8859-1 maps to 'UTF-8' in the resolve table, PRAGMA is silently ignored.
		$conn = $this->openSqlite('ISO-8859-1');
		$this->assertTrue($conn->Active);
		$encoding = $this->queryScalar($conn, 'PRAGMA encoding');
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testSqliteListTablesQueryReturnsEmptyArrayForEmptyDb(): void
	{
		$conn = $this->openSqlite();
		$sql = TDbDriverCapabilities::getListTablesSql('sqlite');
		$result = $conn->createCommand($sql)->queryAll();
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
		$conn->Active = false;
	}

	public function testSqliteListTablesQueryReturnsCreatedTable(): void
	{
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE foo (id INTEGER PRIMARY KEY)')->execute();
		$sql = TDbDriverCapabilities::getListTablesSql('sqlite');
		$rows = $conn->createCommand($sql)->queryAll();
		$names = array_column($rows, 'tbl_name');
		$this->assertContains('foo', $names);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — MetaData factory
	// -----------------------------------------------------------------------

	public function testSqliteMetaDataInstanceIsTSqliteMetaData(): void
	{
		$conn = $this->openSqlite();
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TSqliteMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testSqliteTransactionCommitPersistsData(): void
	{
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE tx_test (id INTEGER PRIMARY KEY)')->execute();
		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO tx_test VALUES (1)')->execute();
		$tx->commit();
		$count = (int) $this->queryScalar($conn, 'SELECT COUNT(*) FROM tx_test');
		$this->assertSame(1, $count);
		$conn->Active = false;
	}

	public function testSqliteTransactionRollbackDiscardsData(): void
	{
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE tx_test (id INTEGER PRIMARY KEY)')->execute();
		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO tx_test VALUES (1)')->execute();
		$tx->rollBack();
		$count = (int) $this->queryScalar($conn, 'SELECT COUNT(*) FROM tx_test');
		$this->assertSame(0, $count);
		$conn->Active = false;
	}

	public function testSqliteTransactionCommitDeactivatesTransaction(): void
	{
		$conn = $this->openSqlite();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	public function testSqliteTransactionRollbackDeactivatesTransaction(): void
	{
		$conn = $this->openSqlite();
		$tx = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
		$this->assertFalse($tx->getActive());
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	// -----------------------------------------------------------------------

	public function testSqliteHasNoAutoCommitAttributeLive(): void
	{
		// Confirmed by the capability flag: SQLite PDO does not implement
		// PDO::ATTR_AUTOCOMMIT.  TDbConnection must not attempt to read or write it.
		$conn = $this->openSqlite();
		$this->assertFalse(TDbDriverCapabilities::hasAutoCommitAttribute($conn->getDriverName()));
		$conn->Active = false;
	}
}
