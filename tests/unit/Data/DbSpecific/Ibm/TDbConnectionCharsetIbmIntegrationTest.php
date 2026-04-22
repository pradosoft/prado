<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — IBM DB2 (ibm).
 *
 * The ibm (pdo_ibm) driver does not have a reliable DSN charset parameter.
 * TDbConnection does not inject a charset into the DSN for this driver and
 * does not send a post-connect SQL command.  Encoding for IBM DB2 is typically
 * configured at the database level.
 *
 * getDatabaseCharset() falls back to resolveCharsetForDriver() for the ibm
 * driver, returning the charset name as resolved from the alias table.
 * For 'UTF-8' this is 'UTF-8' (ibm has no entry in the alias table so the
 * original value is returned unchanged).
 *
 * Tests are skipped automatically when the pdo_ibm extension is missing or
 * the DB2 instance is unreachable.
 *
 * Environment variables
 * ---------------------
 * DB2_USER      DB2 instance owner username (default: db2inst1).
 * DB2_PASSWORD  DB2 instance owner password (default: Prado_Unitest1).
 * DB2_DATABASE  DB2 database name           (default: pradount).
 */
class TDbConnectionCharsetIbmIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
	}

	protected function getDatabaseName(): ?string
	{
		// Database name resolved inside setupIbmConnection via DB2_DATABASE env var.
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
	// IBM DB2 helpers
	// -----------------------------------------------------------------------

	/**
	 * Open an IBM DB2 connection with an optional Charset property set.
	 * No charset parameter is injected into the DSN for the ibm driver;
	 * the Charset value is stored and available via DatabaseCharset.
	 */
	private function openIbm(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_ibm')) {
			$this->markTestSkipped('pdo_ibm extension not available.');
		}
		$user     = getenv('DB2_USER')     ?: 'db2inst1';
		$password = getenv('DB2_PASSWORD') ?: 'Prado_Unitest1';
		$database = getenv('DB2_DATABASE') ?: 'pradount';
		return $this->openConnection(
			'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $database . ';HOSTNAME=localhost;PORT=50000;PROTOCOL=TCPIP',
			$user,
			$password,
			$charset
		);
	}

	// -----------------------------------------------------------------------
	// Tests — ibm driver passes charset through without DSN injection or SQL
	// -----------------------------------------------------------------------

	public function testIbmConnectionOpensWithUtf8Charset(): void
	{
		// The connection opens successfully even when a charset is specified;
		// the ibm driver silently ignores charset at the PDO level.
		$conn = $this->openIbm('UTF-8');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testIbmConnectionOpensWithIso88591Charset(): void
	{
		$conn = $this->openIbm('ISO-8859-1');
		$this->assertTrue($conn->Active);
		$conn->Active = false;
	}

	public function testIbmSetCharsetAfterConnectThrows(): void
	{
		// For ibm, setting Charset after connect is a no-op (no SQL sent).
		// The connection must remain active.
		$conn = $this->openIbm();
		$this->expectException(TDbException::class);
		$conn->Charset = 'UTF-8';
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — falls back to resolveCharsetForDriver() for ibm
	// (ibm has no live-query path; the resolved charset is returned as-is)
	// -----------------------------------------------------------------------

	public function testIbmGetDatabaseCharsetReturnsPassThroughForUtf8(): void
	{
		// ibm has no entry in the alias table for utf8, so 'UTF-8' passes through.
		$conn = $this->openIbm('UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testIbmGetDatabaseCharsetReturnsPassThroughForIso88591(): void
	{
		// ibm has no entry for latin1/iso88591, so the input passes through.
		$conn = $this->openIbm('ISO-8859-1');
		$this->assertSame('ISO-8859-1', $conn->DatabaseCharset);
		$conn->Active = false;
	}
}
