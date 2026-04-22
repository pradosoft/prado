<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — SQLite.
 *
 * TDbConnection applies the Charset property to SQLite via  PRAGMA encoding = <value>.
 * The PRAGMA only takes effect before any tables are created; on databases that
 * already have tables it is silently ignored so the connection remains usable.
 *
 * For new in-memory databases (used here) the PRAGMA succeeds and the encoding
 * reported by a subsequent  PRAGMA encoding  query reflects the configured value.
 *
 * Tests are skipped automatically when the pdo_sqlite extension is missing.
 */
class TDbConnectionCharsetSqliteIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqliteConnection';
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
			new TApplication(__DIR__ . '/../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->setUpConnection();
	}

	// -----------------------------------------------------------------------
	// Shared helpers
	// -----------------------------------------------------------------------

	/**
	 * Create and activate a TDbConnection, marking the test skipped on any
	 * connection error (missing extension, server not running, DB not found).
	 */
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

	/** Query a scalar value from an active connection. */
	private function queryScalar(TDbConnection $conn, string $sql): mixed
	{
		return $conn->createCommand($sql)->queryScalar();
	}

	// -----------------------------------------------------------------------
	// SQLite helpers
	// -----------------------------------------------------------------------

	private function openSqlite(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		// Use an in-memory DB so no file cleanup is needed.
		return $this->openConnection('sqlite::memory:', '', '', $charset);
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	public function testSqliteIsAlwaysUtf8(): void
	{
		$conn = $this->openSqlite();
		$encoding = $this->queryScalar($conn, 'PRAGMA encoding');
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	public function testSqliteCharsetAppliedViaEncoding(): void
	{
		// On a fresh in-memory database (no tables yet) PRAGMA encoding succeeds.
		$conn = $this->openSqlite('UTF-8');
		$this->assertTrue($conn->Active);
		$encoding = $this->queryScalar($conn, 'PRAGMA encoding');
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	public function testSqliteSetCharsetAfterConnectDoesNotThrow(): void
	{
		// Setting Charset on an active connection triggers setConnectionCharset().
		// For an in-memory DB with no tables PRAGMA encoding succeeds; errors on
		// populated databases are silently ignored — either way, no exception is thrown.
		$conn = $this->openSqlite();
		$conn->Charset = 'UTF-8';
		$this->assertTrue($conn->Active);
		$encoding = $this->queryScalar($conn, 'PRAGMA encoding');
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	public function testSqliteUnsupportedCharsetFailsSilently(): void
	{
		// ISO-8859-1 is not a valid SQLite PRAGMA encoding value; the PRAGMA is
		// silently ignored and the connection remains active and usable as UTF-8.
		$conn = $this->openSqlite('ISO-8859-1');
		$this->assertTrue($conn->Active);
		// Encoding is still UTF-8 (default) since PRAGMA was ignored.
		$encoding = $this->queryScalar($conn, 'PRAGMA encoding');
		$this->assertSame('UTF-8', $encoding);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — queries PRAGMA encoding on an active connection
	// -----------------------------------------------------------------------

	public function testSqliteGetDatabaseCharsetReturnsActiveEncoding(): void
	{
		// On a fresh in-memory DB, PRAGMA encoding = 'UTF-8' succeeds.
		// DatabaseCharset queries the DB directly rather than returning the stored value.
		$conn = $this->openSqlite('UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetReturnsDefaultEncodingWhenNoCharsetSet(): void
	{
		// When no Charset is configured, DatabaseCharset still queries PRAGMA encoding
		// and returns the database's actual encoding (always UTF-8 for new DBs).
		$conn = $this->openSqlite();
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetReflectsEncodingAfterSetCharset(): void
	{
		// Setting Charset after connect re-runs PRAGMA encoding; DatabaseCharset
		// reads back from the DB and reflects the applied value.
		$conn = $this->openSqlite();
		$conn->Charset = 'UTF-8';
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}
}
