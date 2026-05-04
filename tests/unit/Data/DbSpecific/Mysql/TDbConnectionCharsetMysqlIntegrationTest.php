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
}
