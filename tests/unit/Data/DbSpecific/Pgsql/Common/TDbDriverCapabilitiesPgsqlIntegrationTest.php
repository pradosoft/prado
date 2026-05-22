<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriverCapabilities;
use Prado\TApplication;

/**
 * Integration tests for TDbDriverCapabilities — PostgreSQL.
 *
 * Verifies static capability flags and live behaviour for the 'pgsql' driver.
 *
 * Key PostgreSQL characteristics:
 *  - supportsCharset = true (via SET client_encoding TO, no DSN param)
 *  - hasAutoCommitAttribute = false  (pdo_pgsql does not expose PDO::ATTR_AUTOCOMMIT)
 *  - requiresPreBeginTransactionFlush = false
 *  - requiresPostTransactionFlush = false
 *  - supportsRuntimeCharsetSet = true
 *  - requiresPostConnectCharset = true  ← unique among supported drivers;
 *      PostgreSQL has no DSN charset param so charset is applied after connect.
 *  - getCharsetDsnParam = null
 *  - UTF-8 resolves to 'UTF8' (PostgreSQL encoding name)
 *
 * Tests are skipped automatically when pdo_pgsql is missing or the
 * prado_unitest database is unreachable.
 */
class TDbDriverCapabilitiesPgsqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupPgsqlConnection';
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
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->setUpConnection();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	private function openPgsql(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_pgsql')) {
			$this->markTestSkipped('pdo_pgsql extension not available.');
		}
		$cred = getenv('SCRUTINIZER') ? 'scrutinizer' : 'prado_unitest';
		try {
			$conn = new TDbConnection(
				'pgsql:host=localhost;dbname=prado_unitest',
				$cred,
				$cred,
				$charset
			);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect to PostgreSQL: ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// Static capability flags
	// -----------------------------------------------------------------------

	public function testPgsqlSupportsCharset(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsCharset('pgsql'));
	}

	public function testPgsqlHasAutoCommitAttributeIsFalse(): void
	{
		// pdo_pgsql does not expose PDO::ATTR_AUTOCOMMIT; reading or writing it
		// throws a PDOException.  hasAutoCommitAttribute must return false.
		$this->assertFalse(TDbDriverCapabilities::hasAutoCommitAttribute('pgsql'));
	}


	public function testPgsqlRequiresNoPreBeginTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPreBeginTransactionFlush('pgsql'));
	}

	public function testPgsqlRequiresNoPostTransactionFlush(): void
	{
		$this->assertFalse(TDbDriverCapabilities::requiresPostTransactionFlush('pgsql'));
	}

	public function testPgsqlSupportsRuntimeCharsetSet(): void
	{
		$this->assertTrue(TDbDriverCapabilities::supportsRuntimeCharsetSet('pgsql'));
	}

	public function testPgsqlRequiresPostConnectCharset(): void
	{
		// PostgreSQL has no DSN charset parameter.  Charset must be applied via
		// SET client_encoding immediately after the connection opens.
		$this->assertTrue(TDbDriverCapabilities::requiresPostConnectCharset('pgsql'));
	}

	public function testPgsqlCharsetSetSqlIsSetClientEncoding(): void
	{
		$this->assertSame('SET client_encoding TO %s', TDbDriverCapabilities::getCharsetSetSql('pgsql'));
	}

	public function testPgsqlCharsetPragmaSqlIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetPragmaSql('pgsql'));
	}

	public function testPgsqlCharsetDsnParamIsNull(): void
	{
		// PostgreSQL has no DSN charset parameter; charset is applied post-connect.
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnParam('pgsql'));
	}

	public function testPgsqlCharsetDsnPatternIsNull(): void
	{
		$this->assertNull(TDbDriverCapabilities::getCharsetDsnPattern('pgsql'));
	}

	public function testPgsqlCharsetQuerySqlIsPgClientEncoding(): void
	{
		$this->assertSame('SELECT pg_client_encoding()', TDbDriverCapabilities::getCharsetQuerySql('pgsql'));
	}

	public function testPgsqlGetListTablesSqlContainsInformationSchema(): void
	{
		$sql = TDbDriverCapabilities::getListTablesSql('pgsql');
		$this->assertNotNull($sql);
		$this->assertStringContainsString('information_schema.tables', $sql);
	}

	public function testPgsqlMetaDataClassName(): void
	{
		$this->assertSame(TPgsqlMetaData::class, TDbDriverCapabilities::getMetaDataClass('pgsql'));
	}

	// -----------------------------------------------------------------------
	// Charset resolution
	// -----------------------------------------------------------------------

	public function testPgsqlResolveUtf8ReturnsUTF8(): void
	{
		$this->assertSame('UTF8', TDbDriverCapabilities::resolveCharset('UTF-8', 'pgsql'));
	}

	public function testPgsqlResolveLatin1ReturnsLATIN1(): void
	{
		$this->assertSame('LATIN1', TDbDriverCapabilities::resolveCharset('ISO-8859-1', 'pgsql'));
	}

	public function testPgsqlResolveLatin2ReturnsLATIN2(): void
	{
		$this->assertSame('LATIN2', TDbDriverCapabilities::resolveCharset('ISO-8859-2', 'pgsql'));
	}

	public function testPgsqlResolveAsciiReturnsSqlAscii(): void
	{
		$this->assertSame('SQL_ASCII', TDbDriverCapabilities::resolveCharset('ASCII', 'pgsql'));
	}

	public function testPgsqlResolveWin1250ReturnsWIN1250(): void
	{
		$this->assertSame('WIN1250', TDbDriverCapabilities::resolveCharset('Windows-1250', 'pgsql'));
	}

	public function testPgsqlResolveKoi8rReturnsKOI8R(): void
	{
		$this->assertSame('KOI8R', TDbDriverCapabilities::resolveCharset('KOI8-R', 'pgsql'));
	}

	public function testPgsqlUnresolveUTF8ReturnsUtf8(): void
	{
		$this->assertSame('UTF-8', TDbDriverCapabilities::unresolveCharset('UTF8', 'pgsql'));
	}

	public function testPgsqlUnresolveLATIN1ReturnsLatin1Standard(): void
	{
		$this->assertSame('ISO-8859-1', TDbDriverCapabilities::unresolveCharset('LATIN1', 'pgsql'));
	}

	// -----------------------------------------------------------------------
	// Scaffold factory
	// -----------------------------------------------------------------------

	public function testPgsqlScaffoldInputClass(): void
	{
		$this->assertSame('TPgsqlScaffoldInput', TDbDriverCapabilities::getScaffoldInputClass('pgsql'));
	}

	public function testPgsqlScaffoldInputFile(): void
	{
		$this->assertSame('/TPgsqlScaffoldInput.php', TDbDriverCapabilities::getScaffoldInputFile('pgsql'));
	}

	// -----------------------------------------------------------------------
	// Live connection — charset
	// -----------------------------------------------------------------------

	public function testPgsqlCharsetQuerySqlExecutesAndReturnsUtf8WhenSet(): void
	{
		$conn = $this->openPgsql('UTF-8');
		$charset = $this->queryScalar($conn, TDbDriverCapabilities::getCharsetQuerySql('pgsql'));
		$this->assertSame('UTF8', $charset);
		$conn->Active = false;
	}

	public function testPgsqlCharsetQuerySqlReturnsLATIN1WhenSetToIso88591(): void
	{
		$conn = $this->openPgsql('ISO-8859-1');
		$charset = $this->queryScalar($conn, TDbDriverCapabilities::getCharsetQuerySql('pgsql'));
		$this->assertSame('LATIN1', $charset);
		$conn->Active = false;
	}

	public function testPgsqlDatabaseCharsetReturnsUtf8WhenConfigured(): void
	{
		$conn = $this->openPgsql('UTF-8');
		$this->assertSame('UTF8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testPgsqlSetCharsetAfterConnectAppliesNewEncoding(): void
	{
		$conn = $this->openPgsql();
		$conn->Charset = 'UTF-8';
		$charset = $this->queryScalar($conn, TDbDriverCapabilities::getCharsetQuerySql('pgsql'));
		$this->assertSame('UTF8', $charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — MetaData factory
	// -----------------------------------------------------------------------

	public function testPgsqlMetaDataInstanceIsTPgsqlMetaData(): void
	{
		$conn = $this->openPgsql();
		$meta = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(TPgsqlMetaData::class, $meta);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — list tables
	// -----------------------------------------------------------------------

	public function testPgsqlListTablesQueryReturnsArray(): void
	{
		$conn = $this->openPgsql();
		$result = $conn->createCommand(TDbDriverCapabilities::getListTablesSql('pgsql'))->queryAll();
		$this->assertIsArray($result);
		$conn->Active = false;
	}

	public function testPgsqlListTablesQueryReturnsCreatedTable(): void
	{
		// Create a temporary table in the public schema, verify it appears in the
		// information_schema.tables result set, then clean up.  The capability SQL
		// filters to table_schema = 'public' and table_type = 'BASE TABLE'.
		$conn = $this->openPgsql();
		$conn->createCommand('DROP TABLE IF EXISTS caps_pg_list_test')->execute();
		$conn->createCommand('CREATE TABLE caps_pg_list_test (id INT NOT NULL PRIMARY KEY)')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('pgsql');
		$rows = $conn->createCommand($sql)->queryAll();

		$names = array_column($rows, 'table_name');
		$this->assertContains('caps_pg_list_test', $names);

		$conn->createCommand('DROP TABLE IF EXISTS caps_pg_list_test')->execute();
		$conn->Active = false;
	}

	public function testPgsqlListTablesQueryDoesNotReturnDroppedTable(): void
	{
		$conn = $this->openPgsql();
		$conn->createCommand('DROP TABLE IF EXISTS caps_pg_dropped_test')->execute();
		$conn->createCommand('CREATE TABLE caps_pg_dropped_test (id INT NOT NULL PRIMARY KEY)')->execute();
		$conn->createCommand('DROP TABLE caps_pg_dropped_test')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('pgsql');
		$rows = $conn->createCommand($sql)->queryAll();
		$names = array_column($rows, 'table_name');
		$this->assertNotContains('caps_pg_dropped_test', $names);

		$conn->Active = false;
	}

	public function testPgsqlListTablesQueryExcludesViews(): void
	{
		// Views must not appear — the SQL filters to table_type = 'BASE TABLE'.
		$conn = $this->openPgsql();
		$conn->createCommand('CREATE OR REPLACE VIEW caps_pg_view_test AS SELECT 1 AS n')->execute();

		$sql  = TDbDriverCapabilities::getListTablesSql('pgsql');
		$rows = $conn->createCommand($sql)->queryAll();
		$names = array_column($rows, 'table_name');
		$this->assertNotContains('caps_pg_view_test', $names);

		$conn->createCommand('DROP VIEW IF EXISTS caps_pg_view_test')->execute();
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — transactions
	// -----------------------------------------------------------------------

	public function testPgsqlTransactionCommitPersistsData(): void
	{
		$conn = $this->openPgsql();
		$conn->createCommand('CREATE TABLE IF NOT EXISTS caps_pg_tx (id INT PRIMARY KEY)')->execute();
		$conn->createCommand('DELETE FROM caps_pg_tx')->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO caps_pg_tx VALUES (1)')->execute();
		$tx->commit();

		$count = (int) $this->queryScalar($conn, 'SELECT COUNT(*) FROM caps_pg_tx');
		$this->assertSame(1, $count);
		$conn->createCommand('DROP TABLE caps_pg_tx')->execute();
		$conn->Active = false;
	}

	public function testPgsqlTransactionRollbackDiscardsData(): void
	{
		$conn = $this->openPgsql();
		$conn->createCommand('CREATE TABLE IF NOT EXISTS caps_pg_tx2 (id INT PRIMARY KEY)')->execute();
		$conn->createCommand('DELETE FROM caps_pg_tx2')->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO caps_pg_tx2 VALUES (1)')->execute();
		$tx->rollBack();

		$count = (int) $this->queryScalar($conn, 'SELECT COUNT(*) FROM caps_pg_tx2');
		$this->assertSame(0, $count);
		$conn->createCommand('DROP TABLE caps_pg_tx2')->execute();
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute live verification
	//
	// pdo_pgsql does not expose PDO::ATTR_AUTOCOMMIT (reading it throws a
	// PDOException). TDbConnection::HasAutoCommit returns false; AutoCommit
	// returns false gracefully without attempting to read the absent attribute.
	// -----------------------------------------------------------------------

	public function testPgsqlHasNoAutoCommitAttributeViaConnection(): void
	{
		$conn = $this->openPgsql();
		$this->assertFalse(
			$conn->HasAutoCommit,
			'PostgreSQL must report HasAutoCommit = false via TDbConnection.'
		);
		$conn->Active = false;
	}

	public function testPgsqlAutoCommitReturnsFalseWhenAttributeAbsent(): void
	{
		// TDbConnection::getAutoCommit() returns false when hasAutoCommitAttribute
		// is false, without attempting to read PDO::ATTR_AUTOCOMMIT from pdo_pgsql.
		$conn = $this->openPgsql();
		$this->assertFalse(
			$conn->AutoCommit,
			'PostgreSQL AutoCommit must return false when the attribute is not supported.'
		);
		$conn->Active = false;
	}

	public function testPgsqlRawAutoCommitAttributeThrows(): void
	{
		// Directly reading PDO::ATTR_AUTOCOMMIT on a pgsql connection throws a
		// PDOException, confirming that TDbDriverCapabilities correctly marks
		// pgsql as hasAutoCommitAttribute = false so TDbConnection never reads it.
		$conn = $this->openPgsql();
		$this->expectException(\PDOException::class);
		$conn->getPdoInstance()->getAttribute(\PDO::ATTR_AUTOCOMMIT);
	}

	// -----------------------------------------------------------------------
	// Live connection — TDbTransaction::beginTransaction() (reuse & supersession)
	// -----------------------------------------------------------------------

	public function testPgsqlTxBeginTransactionIsActiveAfterReuseViaCommit(): void
	{
		// After commit(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openPgsql();
		$tx = $conn->beginTransaction();
		$tx->commit();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after commit.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testPgsqlTxBeginTransactionIsActiveAfterReuseViaRollback(): void
	{
		// After rollback(), calling beginTransaction() on the same object reactivates it.
		$conn = $this->openPgsql();
		$tx = $conn->beginTransaction();
		$tx->rollBack();
		$this->assertFalse($tx->getActive(), 'Transaction must be inactive after rollback.');

		$returned = $tx->beginTransaction();
		$this->assertSame($tx, $returned, 'beginTransaction() must return $this.');
		$this->assertTrue($tx->getActive(), 'Transaction must be active after reuse.');
		$tx->rollBack();
		$conn->Active = false;
	}

	public function testPgsqlTxBeginTransactionReuseIsolatesWorkUnits(): void
	{
		// Two sequential work units on the same object: first commits (row persists),
		// second rolls back (row discarded).
		$conn = $this->openPgsql();
		$conn->createCommand(
			'CREATE TABLE IF NOT EXISTS caps_pgsql_tx_reuse (id INT PRIMARY KEY)'
		)->execute();
		$conn->createCommand('DELETE FROM caps_pgsql_tx_reuse')->execute();

		$tx = $conn->beginTransaction();
		$conn->createCommand('INSERT INTO caps_pgsql_tx_reuse VALUES (1)')->execute();
		$tx->commit();

		$tx->beginTransaction();
		$conn->createCommand('INSERT INTO caps_pgsql_tx_reuse VALUES (2)')->execute();
		$tx->rollBack();

		$count = (int) $conn->createCommand(
			'SELECT COUNT(*) FROM caps_pgsql_tx_reuse'
		)->queryScalar();
		$this->assertSame(1, $count, 'Only the committed row must persist after reuse rollback.');
		$conn->createCommand('DROP TABLE caps_pgsql_tx_reuse')->execute();
		$conn->Active = false;
	}

	public function testPgsqlTxBeginTransactionThrowsWhenSuperseded(): void
	{
		// After $conn->beginTransaction() supersedes $tx1, calling
		// $tx1->beginTransaction() must throw TDbException.
		$conn = $this->openPgsql();
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

	public function testPgsqlGetLastTransactionReflectsNewestObject(): void
	{
		// After $conn->beginTransaction() creates $tx2, getLastTransaction()
		// must return $tx2, not the superseded $tx1.
		$conn = $this->openPgsql();
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
