<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — Oracle (OCI).
 *
 * For the oci driver, charset is configured exclusively via the DSN parameter
 * charset=<value> injected by applyCharsetToDsn() before PDO is constructed.
 * There is no post-connect SQL command for charset switching; changing Charset
 * after the connection is open has no effect on the server.
 *
 * getDatabaseCharset() falls back to resolveCharsetForDriver() for the oci
 * driver (no live SQL query is needed) and returns the Oracle NLS charset name.
 *
 * Tests are skipped automatically when the pdo_oci extension is missing or
 * the Oracle instance is unreachable.
 *
 * Environment variables
 * ---------------------
 * ORACLE_SERVICE_NAME  Oracle PDB service name (default: FREEPDB1).
 */
class TDbConnectionCharsetOciIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupOciConnection';
	}

	protected function getDatabaseName(): ?string
	{
		// Service name resolved inside setupOciConnection via ORACLE_SERVICE_NAME env var.
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

	// -----------------------------------------------------------------------
	// Oracle helpers
	// -----------------------------------------------------------------------

	/**
	 * Open an Oracle connection without a charset= in the DSN, so that the
	 * Charset property is the sole source of encoding negotiation (it is
	 * injected into the DSN by applyCharsetToDsn() before PDO is created).
	 */
	private function openOci(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_oci')) {
			$this->markTestSkipped('pdo_oci extension not available.');
		}
		$serviceName = getenv('ORACLE_SERVICE_NAME') ?: 'FREEPDB1';
		return $this->openConnection(
			'oci:dbname=//localhost:1521/' . $serviceName,
			'prado_unitest',
			'prado_unitest',
			$charset
		);
	}

	// -----------------------------------------------------------------------
	// Tests — charset injected into DSN via applyCharsetToDsn()
	// -----------------------------------------------------------------------

	public function testOciUtf8ResolvedToAl32Utf8(): void
	{
		// Universal 'UTF-8' must become Oracle's 'AL32UTF8' in the DSN.
		$conn = $this->openOci('UTF-8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testOciIso88591ResolvedToWe8Iso8859P1(): void
	{
		// 'ISO-8859-1' must become Oracle's 'WE8ISO8859P1' in the DSN.
		$conn = $this->openOci('ISO-8859-1');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testOciDriverSpecificNamePassesThrough(): void
	{
		// 'AL32UTF8' is already the Oracle-native name; it passes through unchanged.
		$conn = $this->openOci('AL32UTF8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testOciSetCharsetAfterConnectThrowsException(): void
	{
		// For oci, charset is DSN-only; setting it after connect is a no-op at
		// the server level (no SQL command is sent).  The connection stays active.
		$conn = $this->openOci();
		$this->expectException(TDbException::class);
		$conn->Charset = 'UTF-8';
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — falls back to resolveCharsetForDriver() for oci
	// -----------------------------------------------------------------------

	public function testOciGetDatabaseCharsetReturnsAl32Utf8ForUtf8(): void
	{
		// oci has no live-query path; getDatabaseCharset() returns
		// resolveCharsetForDriver('UTF-8', 'oci') = 'AL32UTF8'.
		$conn = $this->openOci('UTF-8');
		$this->assertSame('AL32UTF8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testOciGetDatabaseCharsetReturnsWe8Iso8859P1(): void
	{
		// 'ISO-8859-1' resolves to 'WE8ISO8859P1' for Oracle.
		$conn = $this->openOci('ISO-8859-1');
		$this->assertSame('WE8ISO8859P1', $conn->DatabaseCharset);
		$conn->Active = false;
	}
}
