<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Mysql\TMysqlMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — MySQL.
 *
 * Verifies static capability flags against live MySQL behaviour and confirms
 * that every TDbDriverCapabilities method returns the correct value for the
 * 'mysql' driver.
 *
 * Key MySQL characteristics:
 *  - supportsCharset = true (SET NAMES + DSN charset= param)
 *  - hasAutoCommitAttribute = true
 *  - requiresPreBeginTransactionFlush = false
 *  - requiresPostTransactionFlush = false
 *  - supportsRuntimeCharsetSet = true (SET NAMES command)
 *  - requiresPostConnectCharset = false (charset is set via DSN before connect)
 *  - getCharsetDsnParam = 'charset'
 *  - UTF-8 resolves to 'utf8mb4'
 *
 * Tests are skipped automatically when pdo_mysql is missing or the
 * prado_unitest database is unreachable.
 */
class TDbDriverCapabilitiesMysqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

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

	private function openMysql(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_mysql')) {
			$this->markTestSkipped('pdo_mysql extension not available.');
		}
		try {
			$conn = new TDbConnection(
				'mysql:host=localhost;dbname=prado_unitest',
				'prado_unitest',
				'prado_unitest',
				$charset
			);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect to MySQL: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags
	// -----------------------------------------------------------------------

	public function testMysqlSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('mysql'));
	}

	public function testMysqlHasAutoCommitAttribute(): void
	{
		$this->assertTrue(TDbDriverCapabilities::hasAutoCommitAttribute('mysql'));
	}


	public function testMysqlRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('mysql'));
	}

	public function testMysqlRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('mysql'));
	}

	public function testMysqlSupportsRuntimeCharsetSet(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsRuntimeCharsetSet('mysql'));
	}

	public function testMysqlRequiresNoPostConnectCharset(): void
	{
		// MySQL charset is injected into the DSN and set via SET NAMES on connect;
		// no additional post-connect SQL is required.
		$this->assertFalse(TDbDriverCapabilities::requiresPostConnectCharset('mysql'));
	}

	public function testMysqlCharsetSetSqlIsSetNames(): void
	{
		$this->assertSame('SET NAMES ?', TDbDriverCapabilities::getCharsetSetSql('mysql'));
	}

	public function testMysqlCharsetPragmaSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('mysql'));
	}

	public function testMysqlCharsetDsnParamIsCharset(): void
	{
		$this->assertSame('charset', TDbDriverCapabilities::getCharsetDsnParam('mysql'));
	}

	public function testMysqlCharsetDsnPatternMatchesCharsetParam(): void
	{
		$pattern = TDbDriverCapabilities::getCharsetDsnPattern('mysql');
		$this->assertNotNull($pattern);
		$this->assertSame(1, preg_match($pattern, ';charset=utf8mb4', $m));
		$this->assertSame('utf8mb4', $m[1]);
	}

	public function testMysqlCharsetQuerySqlSelectsCharacterSetConnection(): void
	{
		$this->assertSame('SELECT @@character_set_connection', TDbDriverCapabilities::getCharsetQuerySql('mysql'));
	}

	public function testMysqlGetListTablesSqlIsShowTables(): void
	{
		$this->assertSame('SHOW TABLES', TDbDriverCapabilities::getListTablesSql('mysql'));
	}

	public function testMysqlMetaDataClassName(): void
	{
		$this->assertSame(TMysqlMetaData::class, TDbDriverCapabilities::getMetaDataClass('mysql'));
	}

	// -----------------------------------------------------------------------
	// Charset resolution
	// -----------------------------------------------------------------------

	public function testMysqlResolveUtf8ReturnsUtf8mb4(): void
	{
		$this->assertSame('utf8mb4', TDbDriverCapabilities::resolveCharset('UTF-8', 'mysql'));
	}

	public function testMysqlResolveLatin1ReturnsLatin1(): void
	{
		$this->assertSame('latin1', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'mysql'));
	}

	public function testMysqlResolveLatin2ReturnsLatin2(): void
	{
		$this->assertSame('latin2', TDbDriverCapabilities::resolveCharset('ISO-8859-2', 'mysql'));
	}

	public function testMysqlResolveAsciiReturnsAscii(): void
	{
		$this->assertSame('ascii', TDbDriverCapabilities::resolveCharset('ASCII', 'mysql'));
	}

	public function testMysqlResolveWin1250ReturnsCp1250(): void
	{
		$this->assertSame('cp1250', TDbDriverCapabilities::resolveCharset('Windows-1250', 'mysql'));
	}

	public function testMysqlResolveKoi8rReturnsKoi8r(): void
	{
		$this->assertSame('koi8r', TDbDriverCapabilities::resolveCharset('KOI8-R', 'mysql'));
	}

	public function testMysqlUnresolveUtf8mb4ReturnsUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::unresolveCharset('utf8mb4', 'mysql'));
	}

	public function testMysqlUnresolveLatin1ReturnsLatin1Standard(): void
	{
		$this->assertSame('ISO-8859-1', TDbDriverCapabilities::unresolveCharset('latin1', 'mysql'));
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testMysqlScaffoldInputClass(): void
	{
		$this->assertSame('TMysqlScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('mysql'));
	}

	public function testMysqlScaffoldInputFile(): void
	{
		$this->assertSame('/TMysqlScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('mysql'));
	}

	// -----------------------------------------------------------------------
	// Live connection — charset
	// -----------------------------------------------------------------------

	public function testMysqlCharsetQuerySqlExecutesAndReturnsUtf8mb4(): void
	{
		$conn = $this->openMysql('UTF-8');
		$charset = $this->queryScalar($conn, TDbDriverCapabilities::getCharsetQuerySql('mysql'));
		$this->assertSame('utf8mb4', $charset);
		$conn->Active = false;
	}

	public function testMysqlCharsetQuerySqlReturnsLatin1WhenSetToIso88591(): void
	{
		$conn = $this->openMysql('ISO-8859-1');
		$charset = $this->queryScalar($conn, TDbDriverCapabilities::getCharsetQuerySql('mysql'));
		$this->assertSame('latin1', $charset);
		$conn->Active = false;
	}

	public function testMysqlDatabaseCharsetReturnsUtf8mb4WhenUtf8Configured(): void
	{
		$conn = $this->openMysql('UTF-8');
		$this->assertSame('utf8mb4', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testMysqlSetCharsetAfterConnectAppliesNewCharset(): void
	{
		$conn = $this->openMysql();
		$conn->Charset = 'UTF-8';
		$charset = $this->queryScalar($conn, TDbDriverCapabilities::getCharsetQuerySql('mysql'));
		$this->assertSame('utf8mb4', $charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — MetaData factory
	// -----------------------------------------------------------------------

	public function testMysqlMetaDataInstanceIsTMysqlMetaData(): void
	{
		$conn = $this->openMysql();
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TMysqlMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testMysqlListTablesQueryReturnsArray(): void
	{
		$conn = $this->openMysql();
		$result = $conn->createCommand(TDbDriverCapabilities::getListTablesSql('mysql'))->queryAll();
		$this->assertIsArray($result);
		$conn->Active = false;
	}

	public function testMysqlListTablesQueryReturnsCreatedTable(): void
	{
		// Create a temporary table, run SHOW TABLES, verify the name appears
		// in the result set, then clean up.  MySQL's SHOW TABLES returns one
		// row per table; the column name is "Tables_in_<dbname>" so we read
		// the first value of each row to stay DB-name-agnostic.
		$conn = $this->openMysql();
		$conn->createCommand('DROP TABLE IF EXISTS caps_mysql_list_test')->execute();
		$conn->createCommand('CREATE TABLE caps_mysql_list_test (id INT NOT NULL PRIMARY KEY)')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('mysql');
		$rows = $conn->createCommand($sql)->queryAll();

		// SHOW TABLES: each row has one column; extract the first value per row.
		$names = array_map(fn($row) => array_values($row)[0], $rows);
		$this->assertContains('caps_mysql_list_test', $names);

		$conn->createCommand('DROP TABLE IF EXISTS caps_mysql_list_test')->execute();
		$conn->Active = false;
	}

	public function testMysqlListTablesQueryDoesNotReturnDroppedTable(): void
	{
		$conn = $this->openMysql();
		$conn->createCommand('DROP TABLE IF EXISTS caps_mysql_dropped_test')->execute();
		$conn->createCommand('CREATE TABLE caps_mysql_dropped_test (id INT NOT NULL PRIMARY KEY)')->execute();
		$conn->createCommand('DROP TABLE caps_mysql_dropped_test')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('mysql');
		$rows = $conn->createCommand($sql)->queryAll();
		$names = array_map(fn($row) => array_values($row)[0], $rows);
		$this->assertNotContains('caps_mysql_dropped_test', $names);

		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testMysqlTransactionCommitPersistsData(): void
	{
		$conn = $this->openMysql();
		$conn->createCommand('CREATE TABLE IF NOT EXISTS caps_tx_test (id INT PRIMARY KEY)')->execute();
		$conn->createCommand('DELETE FROM caps_tx_test')->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO caps_tx_test VALUES (1)')->execute();
		$tx->commit();

		$count = (int) $this->queryScalar($conn, 'SELECT COUNT(*) FROM caps_tx_test');
		$this->assertSame(1, $count);
		$conn->createCommand('DROP TABLE caps_tx_test')->execute();
		$conn->Active = false;
	}

	public function testMysqlTransactionRollbackDiscardsData(): void
	{
		$conn = $this->openMysql();
		$conn->createCommand('CREATE TABLE IF NOT EXISTS caps_tx_test2 (id INT PRIMARY KEY)')->execute();
		$conn->createCommand('DELETE FROM caps_tx_test2')->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO caps_tx_test2 VALUES (1)')->execute();
		$tx->rollBack();

		$count = (int) $this->queryScalar($conn, 'SELECT COUNT(*) FROM caps_tx_test2');
		$this->assertSame(0, $count);
		$conn->createCommand('DROP TABLE caps_tx_test2')->execute();
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	//
	// MySQL exposes PDO::ATTR_AUTOCOMMIT. TDbConnection::HasAutoCommit is true;
	// TDbConnection::AutoCommit reads and writes the live session flag.
	// -----------------------------------------------------------------------

	public function testMysqlAutoCommitAttributeIsReadable(): void
	{
		$conn = $this->openMysql();
		$value = $conn->getPdoInstance()->getAttribute(\PDO::ATTR_AUTOCOMMIT);
		$this->assertNotNull($value);
		$conn->Active = false;
	}

	public function testMysqlHasAutoCommitAttributeViaConnection(): void
	{
		$conn = $this->openMysql();
		$this->assertTrue(
			$conn->HasAutoCommit,
			'MySQL must report HasAutoCommit = true via TDbConnection.'
		);
		$conn->Active = false;
	}

	public function testMysqlAutoCommitIsTrueByDefault(): void
	{
		$conn = $this->openMysql();
		$this->assertTrue(
			$conn->AutoCommit,
			'MySQL AutoCommit must be true when no explicit transaction is active.'
		);
		$conn->Active = false;
	}

	public function testMysqlAutoCommitCanBeSetToFalseAndBack(): void
	{
		$conn = $this->openMysql();
		$conn->AutoCommit = false;
		$this->assertFalse(
			$conn->AutoCommit,
			'MySQL AutoCommit must be false after setAutoCommit(false).'
		);
		$conn->AutoCommit = true;
		$this->assertTrue(
			$conn->AutoCommit,
			'MySQL AutoCommit must return to true after setAutoCommit(true).'
		);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — TDbTransaction::beginTransaction() (reuse & supersession)
	// -----------------------------------------------------------------------

	public function testMysqlTxBeginTransactionIsActiveAfterReuseViaCommit(): void
	{
		// After commit(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openMysql();
		$tx = $conn->beginTransaction();
		$tx->commit();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after commit.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testMysqlTxBeginTransactionIsActiveAfterReuseViaRollback(): void
	{
		// After rollback(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openMysql();
		$tx = $conn->beginTransaction();
		$tx->rollBack();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after rollback.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testMysqlTxBeginTransactionReuseIsolatesWorkUnits(): void
	{
		// Two sequential work units on the same object: first commits (row persists),
		// second rolls back (row discarded).
		$conn = $this->openMysql();
		$conn->createCommand(
			'CREATE TABLE IF NOT EXISTS caps_mysql_tx_reuse (id INT PRIMARY KEY)'
		)->execute();
		$conn->createCommand('DELETE FROM caps_mysql_tx_reuse')->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO caps_mysql_tx_reuse VALUES (1)')->execute();
		$tx->commit();

		$tx->beginTransaction();
		$conn->createCommand('INSERT INTO caps_mysql_tx_reuse VALUES (2)')->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand(
			'SELECT COUNT(*) FROM caps_mysql_tx_reuse'
		)->queryScalar();
		$this->assertSame(1, $count, 'Only the committed row must persist after reuse rollback.');
		$conn->createCommand('DROP TABLE caps_mysql_tx_reuse')->execute();
		$conn->Active = false;
	}

	public function testMysqlTxBeginTransactionThrowsWhenSuperseded(): void
	{
		// After $conn->beginTransaction() supersedes $tx1, calling
		// $tx1->beginTransaction() must throw TDbException.
		$conn = $this->openMysql();
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

	public function testMysqlGetLastTransactionReflectsNewestObject(): void
	{
		// After $conn->beginTransaction() creates $tx2, getLastTransaction()
		// must return $tx2, not the superseded $tx1.
		$conn = $this->openMysql();
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
