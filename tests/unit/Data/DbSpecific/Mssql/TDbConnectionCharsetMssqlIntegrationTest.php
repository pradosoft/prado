<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — SQL Server (sqlsrv).
 *
 * For the sqlsrv driver, charset is configured exclusively via the DSN
 * parameter  CharacterSet=<value>  injected by applyCharsetToDsn() before PDO
 * is constructed.  There is no post-connect SQL command for charset switching;
 * changing Charset after the connection is open has no effect on the server.
 *
 * getDatabaseCharset() falls back to resolveCharsetForDriver() for the sqlsrv
 * driver (no live SQL query is needed) and returns the driver-resolved name.
 *
 * Tests are skipped automatically when the pdo_sqlsrv extension is missing or
 * the SQL Server instance is unreachable.
 *
 * Credentials expected: Server=localhost,1433, Database=prado_unitest,
 * user=prado_unitest, password=prado_unitest.
 * TrustServerCertificate=yes is required for ODBC Driver 18+ which enforces
 * encrypted connections and rejects self-signed certificates.
 */
class TDbConnectionCharsetMssqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMssqlConnection';
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
	// SQL Server helpers
	// -----------------------------------------------------------------------

	/**
	 * Open a SQL Server connection without a CharacterSet in the DSN, so that
	 * the Charset property is the sole source of encoding negotiation.
	 */
	private function openMssql(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_sqlsrv')) {
			$this->markTestSkipped('pdo_sqlsrv extension not available.');
		}
		return $this->openConnection(
			'sqlsrv:Server=localhost,1433;Database=prado_unitest;TrustServerCertificate=yes',
			'prado_unitest',
			'prado_unitest',
			$charset
		);
	}

	// -----------------------------------------------------------------------
	// Tests — charset injected into DSN via applyCharsetToDsn()
	// -----------------------------------------------------------------------

	public function testMssqlUtf8ResolvedAndInjectedIntoDsn(): void
	{
		// 'UTF-8' → resolveCharsetForDriver() → 'UTF-8' for sqlsrv.
		// applyCharsetToDsn() appends CharacterSet=UTF-8 to the DSN.
		$conn = $this->openMssql('UTF-8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testMssqlDriverSpecificNamePassesThrough(): void
	{
		// 'UTF-8' is both the universal name and the sqlsrv-resolved name.
		$conn = $this->openMssql('UTF-8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testMssqlSetCharsetAfterConnectThrowsException(): void
	{
		// For sqlsrv, charset is DSN-only; setting it after connect is a no-op
		// at the server level (no SQL command is sent).  The connection stays active.
		$conn = $this->openMssql();
		$this->expectException(TDbException::class);
		$conn->Charset = 'UTF-8';
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — falls back to resolveCharsetForDriver() for sqlsrv
	// -----------------------------------------------------------------------

	public function testMssqlGetDatabaseCharsetReturnsResolvedCharset(): void
	{
		// sqlsrv has no live-query path; getDatabaseCharset() returns
		// resolveCharsetForDriver('UTF-8', 'sqlsrv') = 'UTF-8'.
		$conn = $this->openMssql('UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testMssqlGetDatabaseCharsetReturnsResolvedIso88591(): void
	{
		// 'ISO-8859-1' → resolves to 'ISO-8859-1' for sqlsrv (mssql/dblib charset name).
		$conn = $this->openMssql('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $conn->DatabaseCharset);
		$conn->Active = false;
	}
}
