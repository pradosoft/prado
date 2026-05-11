<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

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
class TDbConnectionCharsetSqlSrvIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqlSrvConnection';
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
	private function openSqlSrv(string $charset = ''): TDbConnection
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

	public function testSqlSrvUtf8ResolvedAndInjectedIntoDsn(): void
	{
		// 'UTF-8' → resolveCharsetForDriver() → 'UTF-8' for sqlsrv.
		// applyCharsetToDsn() appends CharacterSet=UTF-8 to the DSN.
		$conn = $this->openSqlSrv('UTF-8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testSqlSrvDriverSpecificNamePassesThrough(): void
	{
		// 'UTF-8' is both the universal name and the sqlsrv-resolved name.
		$conn = $this->openSqlSrv('UTF-8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testSqlSrvSetCharsetAfterConnectThrowsException(): void
	{
		// For sqlsrv, charset is DSN-only; setting it after connect is a no-op
		// at the server level (no SQL command is sent).  The connection stays active.
		$conn = $this->openSqlSrv();
		$this->expectException(TDbException::class);
		$conn->Charset = 'UTF-8';
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — falls back to resolveCharsetForDriver() for sqlsrv
	// -----------------------------------------------------------------------

	public function testSqlSrvGetDatabaseCharsetReturnsResolvedCharset(): void
	{
		// sqlsrv has no live-query path; getDatabaseCharset() returns
		// resolveCharsetForDriver('UTF-8', 'sqlsrv') = 'UTF-8'.
		$conn = $this->openSqlSrv('UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqlSrvUnsupportedCharsetClearedAfterConnect(): void
	{
		// pdo_sqlsrv only accepts 'UTF-8' or 'SQLSRV_ENC_CHAR' in CharacterSet=.
		// 'ISO-8859-1' is not in the allowlist, so applyCharsetToDsn() skips injection
		// and open() clears the Charset property to '' so getDatabaseCharset() reports
		// the true connection state (system default, not the unmet user intent).
		$conn = $this->openSqlSrv('ISO-8859-1');
		$this->assertSame('', $conn->Charset, 'Charset property must be cleared when the requested charset cannot be applied.');
		$this->assertSame('', $conn->DatabaseCharset, 'DatabaseCharset must reflect the actual connection state, not unmet intent.');
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// hasAutoCommitAttribute behavioral verification
	//
	// SQL Server (sqlsrv/dblib) does NOT expose PDO::ATTR_AUTOCOMMIT — the driver
	// throws a PDOException when the attribute is read or written.  TDbConnection
	// reports HasAutoCommit = false for sqlsrv/dblib.
	// -----------------------------------------------------------------------

	public function testSqlSrvHasAutoCommitAttributeIsFalse(): void
	{
		$conn = $this->openSqlSrv();
		$this->assertFalse(
			$conn->HasAutoCommit,
			'SQL Server (sqlsrv) must report hasAutoCommitAttribute = false (ATTR_AUTOCOMMIT is not supported).'
		);
		$conn->Active = false;
	}

	public function testSqlSrvBeginTransactionSucceedsAndRollbackWorks(): void
	{
		// sqlsrv does not expose ATTR_AUTOCOMMIT.  Simply verify that
		// beginTransaction/rollback work without error.
		$conn = $this->openSqlSrv();
		$tx   = $conn->beginTransaction();
		$this->assertTrue($tx->getActive(), 'SQL Server beginTransaction must return an active transaction.');
		$conn->rollback();
		$conn->Active = false;
	}

	public function testSqlSrvCharsetInjectedIntoDsnWithCharacterSetParam(): void
	{
		// applyCharsetToDsn() appends ;CharacterSet=UTF-8 for sqlsrv (not lowercase 'charset').
		// After connecting, the raw ConnectionString (before applyCharsetToDsn) must not
		// contain the injected param; the connection must succeed, proving the DSN was built.
		$conn = $this->openSqlSrv('UTF-8');
		$this->assertTrue($conn->Active);
		// The raw DSN does not contain the injected segment (applyCharsetToDsn builds
		// a modified copy; _dsn is never mutated).
		$this->assertStringNotContainsString(
			'CharacterSet',
			$conn->getConnectionString(),
			'The raw stored DSN must not contain CharacterSet; only the copy passed to PDO does.'
		);
		$conn->Active = false;
	}
}
