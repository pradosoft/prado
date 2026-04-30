<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — MySQL.
 *
 * Charset is applied via  SET NAMES <resolved>  after the connection opens,
 * and also injected into the DSN as  charset=<resolved>  before PDO is
 * constructed (so both code paths are exercised on connect).
 *
 * Tests are skipped automatically when the pdo_mysql extension is missing or
 * the prado_unitest database is unreachable.
 *
 * Credentials expected: host=localhost, db=prado_unitest, user=prado_unitest,
 * password=prado_unitest (same as the rest of the test suite).
 */
class TDbConnectionCharsetMysqlIntegrationTest extends PHPUnit\Framework\TestCase
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
	// Shared helpers
	// -----------------------------------------------------------------------

	private function openConnection(string $dsn, string $user, string $pass, string $charset = ''): TDbConnection
	{
		try {
			$conn = new TDbConnection($dsn, $user, $pass, $charset);
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot connect (' . $dsn . '): ' . $e->getMessage());
		}
	}

	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// MySQL helpers
	// -----------------------------------------------------------------------

	private function openMysql(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_mysql')) {
			$this->markTestSkipped('pdo_mysql extension not available.');
		}
		return $this->openConnection(
			'mysql:host=localhost;dbname=prado_unitest',
			'prado_unitest',
			'prado_unitest',
			$charset
		);
	}

	/** Query the active client charset reported by MySQL. */
	private function mysqlClientCharset(TDbConnection $conn): string
	{
		return (string) $this->queryScalar($conn, 'SELECT @@character_set_client');
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	public function testMysqlUtf8ResolvedToUtf8mb4(): void
	{
		// Universal 'UTF-8' must become 'utf8mb4' in MySQL (proper 4-byte UTF-8).
		$conn = $this->openMysql('UTF-8');
		$this->assertSame('utf8mb4', $this->mysqlClientCharset($conn));
		$conn->Active = false;
	}

	public function testMysqlIso88591ResolvedToLatin1(): void
	{
		$conn = $this->openMysql('ISO-8859-1');
		$this->assertSame('latin1', $this->mysqlClientCharset($conn));
		$conn->Active = false;
	}

	public function testMysqlDriverSpecificNamePassesThrough(): void
	{
		// 'utf8mb4' is already in the alias table and should map to itself.
		$conn = $this->openMysql('utf8mb4');
		$this->assertSame('utf8mb4', $this->mysqlClientCharset($conn));
		$conn->Active = false;
	}

	public function testMysqlLatin1PassesThrough(): void
	{
		// 'latin1' is a canonical alias key; MySQL-specific name is also 'latin1'.
		$conn = $this->openMysql('latin1');
		$this->assertSame('latin1', $this->mysqlClientCharset($conn));
		$conn->Active = false;
	}

	public function testMysqlSetCharsetAfterConnect(): void
	{
		// setCharset() on an already-active connection must re-apply the charset.
		$conn = $this->openMysql();               // no charset → DB default
		$conn->Charset = 'UTF-8';                 // triggers setConnectionCharset()
		$this->assertSame('utf8mb4', $this->mysqlClientCharset($conn));
		$conn->Active = false;
	}

	public function testMysqlSetCharsetAfterConnectIso88591(): void
	{
		$conn = $this->openMysql();
		$conn->Charset = 'ISO-8859-1';
		$this->assertSame('latin1', $this->mysqlClientCharset($conn));
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — queries @@character_set_client on an active connection
	// -----------------------------------------------------------------------

	public function testMysqlGetDatabaseCharsetReturnsActiveClientCharset(): void
	{
		// 'UTF-8' resolves to 'utf8mb4'; DatabaseCharset queries @@character_set_client
		// and returns the driver-specific name actually in use.
		$conn = $this->openMysql('UTF-8');
		$this->assertSame('utf8mb4', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testMysqlGetDatabaseCharsetReturnsLatin1WhenSetToIso88591(): void
	{
		$conn = $this->openMysql('ISO-8859-1');
		$this->assertSame('latin1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testMysqlGetDatabaseCharsetReflectsCharsetChangedAfterConnect(): void
	{
		// Change Charset after connect; DatabaseCharset must reflect the new value.
		$conn = $this->openMysql();
		$conn->Charset = 'ISO-8859-1';
		$this->assertSame('latin1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// hasAutoCommitAttribute = true behavioral verification
	//
	// MySQL exposes PDO::ATTR_AUTOCOMMIT.  TDbConnection::getAutoCommit() reads
	// it; setAutoCommit() writes it.  Outside of an explicit transaction, MySQL
	// defaults to autocommit-on.  These tests verify that TDbConnection can read
	// and write the attribute without error, and that its value reflects the real
	// connection state.
	// -----------------------------------------------------------------------

	public function testMysqlHasAutoCommitAttribute(): void
	{
		$conn = $this->openMysql();
		$this->assertTrue(
			$conn->HasAutoCommit,
			'MySQL must report hasAutoCommitAttribute = true.'
		);
		$conn->Active = false;
	}

	public function testMysqlAutoCommitIsTrueByDefault(): void
	{
		// MySQL defaults to autocommit mode outside of an explicit transaction.
		$conn = $this->openMysql();
		$this->assertTrue(
			$conn->AutoCommit,
			'MySQL AutoCommit must be true when no explicit transaction is active.'
		);
		$conn->Active = false;
	}

	public function testMysqlSetAutoCommitToFalseDisablesAutocommit(): void
	{
		$conn = $this->openMysql();
		$conn->AutoCommit = false;
		$this->assertFalse(
			$conn->AutoCommit,
			'AutoCommit must be false after setAutoCommit(false) on MySQL.'
		);
		// Re-enable so subsequent work on the same session is not surprised.
		$conn->AutoCommit = true;
		$conn->Active = false;
	}

	public function testMysqlBeginTransactionSucceedsAndRollbackWorks(): void
	{
		// PDO::ATTR_AUTOCOMMIT on MySQL reflects the PHP-level session setting (1 by
		// default) and does NOT transition to 0 when PDO::beginTransaction() is called.
		// MySQL's transaction implementation uses SET autocommit=0 internally, but the
		// PDO attribute getter returns the cached initial value, not the live session
		// state.  Use PDO::inTransaction() (not ATTR_AUTOCOMMIT) to detect transaction
		// state in MySQL.  This test simply verifies that beginTransaction/rollback
		// work without throwing for MySQL.
		$conn = $this->openMysql();
		$tx   = $conn->beginTransaction();
		$this->assertTrue($tx->getActive(), 'MySQL beginTransaction must return an active transaction.');
		$conn->rollback();
		$conn->Active = false;
	}

	public function testMysqlAutoCommitOffCreatesSerialTransaction(): void
	{
		// When AutoCommit is disabled on a MySQL connection, createTransaction()
		// must produce a serial TDbTransaction so that each commit/rollback
		// automatically restarts a new transaction (maintaining the non-autocommit
		// session contract).
		$conn = $this->openMysql();
		$conn->AutoCommit = false;
		$tx = $conn->beginTransaction();
		$this->assertTrue(
			$tx->getSerial(),
			'With AutoCommit=false, MySQL beginTransaction must return a serial transaction.'
		);
		$conn->rollback();
		// After rollback the serial restart fires; the transaction must remain active.
		$this->assertTrue(
			$tx->getActive(),
			'After rollback with AutoCommit=false, the serial transaction must remain active.'
		);
		$conn->Active = false;
	}

	public function testMysqlSetCharsetUsesParameterisedSql(): void
	{
		// getCharsetSetSql('mysql') returns 'SET NAMES ?' — a PDO-parameterised
		// statement.  TDbConnection executes it via $pdo->prepare($sql)->execute([$charset])
		// so the charset value is bound as a parameter, not concatenated into SQL.
		// Verify the functional outcome: setting UTF-8 results in utf8mb4 on the server.
		$conn = $this->openMysql();
		$conn->Charset = 'UTF-8';
		$this->assertSame(
			'utf8mb4',
			$this->mysqlClientCharset($conn),
			'SET NAMES ? must have been executed with \'utf8mb4\' as the parameter value.'
		);
		$conn->Active = false;
	}
}
