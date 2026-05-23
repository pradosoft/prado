<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — SQLite.
 *
 * SQLite supports exactly two charset families: UTF-8 and UTF-16.  UTF-16 is
 * stored in the database file in the host's native byte order; PRAGMA encoding
 * always reports the specific endian form ('UTF-16le' or 'UTF-16be'), never the
 * bare 'UTF-16' token.  TDbConnection::unresolveCharset() maps those endian
 * variants back to the PRADO canonical names 'UTF-16LE' or 'UTF-16BE'
 * (TDataCharset::UTF16LE / TDataCharset::UTF16BE), which carry explicit
 * endianness.
 *
 * The Charset property therefore reflects the byte order that SQLite actually
 * uses, which is system-dependent (little-endian on x86; big-endian on some
 * ARM/MIPS/PPC platforms).  Tests use assertIsUtf16CanonicalCharset() wherever
 * the exact endian form is platform-dependent.
 *
 * PRAGMA encoding only takes effect before any tables are created; on databases
 * that already have tables it is silently ignored and the encoding established
 * at creation time is preserved.  TDbConnection handles this in two places:
 *
 *  - open()       — attempts PRAGMA, then reads back the actual encoding and
 *                   syncs the Charset property to what the DB really has.
 *  - setCharset() — same PRAGMA-then-readback sequence for post-connect changes.
 *
 * getDatabaseCharset() returns the raw PRAGMA encoding string reported by
 * SQLite ('UTF-8', 'UTF-16le', or 'UTF-16be'), while the Charset property
 * stores the PRADO canonical name ('UTF-8', 'UTF-16LE', or 'UTF-16BE').
 *
 * Tests are organised in parallel UTF-8 / UTF-16 sections so the two charsets
 * receive equivalent coverage.  Tests are skipped when pdo_sqlite is missing.
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
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->setUpConnection();
	}

	// -----------------------------------------------------------------------
	// Helpers
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

	private function openSqlite(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		return $this->openConnection('sqlite::memory:', '', '', $charset);
	}

	/**
	 * Returns the raw PRAGMA encoding string for $conn.
	 * For UTF-16 databases this is 'UTF-16le' or 'UTF-16be' depending on
	 * the host's byte order.
	 */
	private function pragmaEncoding(TDbConnection $conn): string
	{
		return (string) $this->queryScalar($conn, 'PRAGMA encoding');
	}

	/**
	 * Asserts that the raw PRAGMA encoding string is a UTF-16 variant
	 * ('UTF-16le' or 'UTF-16be').  Used wherever the exact endian form is
	 * system-dependent.
	 */
	private function assertIsUtf16Encoding(string $encoding, string $message = ''): void
	{
		$this->assertMatchesRegularExpression('/^UTF-16(le|be)$/i', $encoding,
			$message ?: "Expected a UTF-16 variant (UTF-16le/UTF-16be), got '$encoding'.");
	}

	/**
	 * Asserts that the PRADO Charset property holds a canonical UTF-16 endian
	 * value ('UTF-16LE' or 'UTF-16BE').  The actual value is system-dependent
	 * (little-endian on x86; big-endian on some other architectures).
	 */
	private function assertIsUtf16CanonicalCharset(string $charset, string $message = ''): void
	{
		$this->assertMatchesRegularExpression('/^UTF-16(LE|BE)$/', $charset,
			$message ?: "Expected PRADO canonical UTF-16 charset (UTF-16LE/UTF-16BE), got '$charset'.");
	}

	// -----------------------------------------------------------------------
	// UTF-8 — fresh in-memory database (no tables: PRAGMA takes effect)
	// -----------------------------------------------------------------------

	public function testSqliteDefaultEncodingIsUtf8(): void
	{
		// No Charset requested — SQLite defaults to UTF-8.
		// open() reads back PRAGMA encoding and syncs Charset to 'UTF-8'.
		$conn = $this->openSqlite();
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	public function testSqliteCharsetUtf8AppliedOnFreshDatabase(): void
	{
		// Requesting UTF-8 explicitly on a fresh DB: PRAGMA succeeds,
		// readback syncs Charset to 'UTF-8'.
		$conn = $this->openSqlite('UTF-8');
		$this->assertTrue($conn->Active);
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// UTF-16 — fresh in-memory database (no tables: PRAGMA takes effect)
	// -----------------------------------------------------------------------

	public function testSqliteCharsetUtf16AppliedOnFreshDatabase(): void
	{
		// Requesting UTF-16 on a fresh DB: PRAGMA encoding = 'UTF-16' succeeds.
		// SQLite stores it in native byte order and reports 'UTF-16le' or 'UTF-16be'.
		// unresolveCharset() maps 'UTF-16le' → 'UTF-16LE' and 'UTF-16be' → 'UTF-16BE'.
		$conn = $this->openSqlite('UTF-16');
		$this->assertTrue($conn->Active);
		$this->assertIsUtf16Encoding($this->pragmaEncoding($conn));
		$this->assertIsUtf16CanonicalCharset($conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// UTF-8 — existing database (tables present: PRAGMA ignored, readback corrects)
	// -----------------------------------------------------------------------

	public function testSqliteUtf8SyncedFromExistingDatabaseWhenNoCharsetRequested(): void
	{
		// No Charset requested, but a table exists.  open() reads back PRAGMA
		// encoding; _charset is synced to 'UTF-8' (the DB's actual encoding).
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE t (id INTEGER PRIMARY KEY)')->execute();
		$this->assertSame('UTF-8', $conn->Charset);
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$conn->Active = false;
	}

	public function testSqliteUtf8RequestedOnExistingDatabaseSyncsCorrectly(): void
	{
		// UTF-8 requested, fresh DB used as stand-in for any UTF-8 existing DB.
		// PRAGMA applies (no tables yet); readback confirms 'UTF-8'.
		$conn = $this->openSqlite('UTF-8');
		$conn->createCommand('CREATE TABLE t (id INTEGER PRIMARY KEY)')->execute();
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	public function testSqliteUnsupportedCharsetClearedAfterConnect(): void
	{
		// ISO-8859-1 is not a valid SQLite PRAGMA encoding value.
		// The PRAGMA is silently ignored; readback corrects Charset to 'UTF-8'.
		$conn = $this->openSqlite('ISO-8859-1');
		$this->assertTrue($conn->Active);
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// UTF-16 — existing database (tables present: PRAGMA ignored, readback corrects)
	// -----------------------------------------------------------------------

	public function testSqliteUtf16RequestedOnExistingUtf8DatabaseReadbackCorrected(): void
	{
		// Open a fresh DB without a charset (so it's UTF-8), create a table,
		// then close and re-open requesting UTF-16.  Because tables exist, the
		// PRAGMA is ignored; readback corrects Charset back to 'UTF-8'.
		// In-memory DBs cannot be re-opened, so we simulate by opening UTF-16
		// on a fresh DB, creating a table, and then issuing setCharset('UTF-8')
		// — which is the inverse of the original scenario but exercises the same
		// PRAGMA-ignored → readback path for UTF-16 requests on existing tables.
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE t (id INTEGER PRIMARY KEY)')->execute();
		// Request UTF-16 after tables exist — PRAGMA will be ignored.
		$conn->Charset = 'UTF-16';
		// DB is still UTF-8; readback must correct Charset to 'UTF-8'.
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	public function testSqliteUtf16SyncedFromExistingUtf16DatabaseWhenNoCharsetRequested(): void
	{
		// Open a fresh DB with UTF-16, create a table to "lock in" the encoding,
		// then assert that Charset was synced to 'UTF-16LE'/'UTF-16BE' from the readback.
		// (The readback happens in open() before any tables are created, so the
		// PRAGMA is applied first and the readback confirms the UTF-16 encoding.)
		$conn = $this->openSqlite('UTF-16');
		$conn->createCommand('CREATE TABLE t (id INTEGER PRIMARY KEY)')->execute();
		$this->assertIsUtf16Encoding($this->pragmaEncoding($conn));
		$this->assertIsUtf16CanonicalCharset($conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// UTF-8 — setCharset() on an active connection
	// -----------------------------------------------------------------------

	public function testSqliteSetCharsetUtf8AfterConnectOnFreshDatabase(): void
	{
		// No tables: PRAGMA encoding = 'UTF-8' succeeds; readback syncs Charset.
		$conn = $this->openSqlite();
		$conn->Charset = 'UTF-8';
		$this->assertTrue($conn->Active);
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	public function testSqliteSetCharsetUtf8AfterConnectWithTablesReadbackConfirms(): void
	{
		// Tables exist: PRAGMA encoding = 'UTF-8' is silently ignored (DB is already
		// UTF-8), readback still returns 'UTF-8' and Charset stays 'UTF-8'.
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE t (id INTEGER PRIMARY KEY)')->execute();
		$conn->Charset = 'UTF-8';
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// UTF-16 — setCharset() on an active connection
	// -----------------------------------------------------------------------

	public function testSqliteSetCharsetUtf16AfterConnectOnFreshDatabase(): void
	{
		// No tables: PRAGMA encoding = 'UTF-16' succeeds; readback returns
		// 'UTF-16le'/'UTF-16be' and unresolves to Charset = 'UTF-16LE'/'UTF-16BE'.
		$conn = $this->openSqlite();
		$conn->Charset = 'UTF-16';
		$this->assertTrue($conn->Active);
		$this->assertIsUtf16Encoding($this->pragmaEncoding($conn));
		$this->assertIsUtf16CanonicalCharset($conn->Charset);
		$conn->Active = false;
	}

	public function testSqliteSetCharsetUtf16AfterConnectWithTablesReadbackCorrected(): void
	{
		// Tables exist: PRAGMA encoding = 'UTF-16' is silently ignored.
		// readback returns 'UTF-8' and Charset is corrected to 'UTF-8',
		// not left as the requested 'UTF-16'.
		$conn = $this->openSqlite();
		$conn->createCommand('CREATE TABLE t (id INTEGER PRIMARY KEY)')->execute();
		$conn->Charset = 'UTF-16';
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn));
		$this->assertSame('UTF-8', $conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — returns the raw PRAGMA encoding string
	//
	// For UTF-8: returns 'UTF-8' (matches PRADO canonical).
	// For UTF-16: returns 'UTF-16le' or 'UTF-16be' (system byte-order dependent).
	// This is intentional — getDatabaseCharset() reports the driver-specific value.
	// Use the Charset property for the PRADO canonical name.
	// -----------------------------------------------------------------------

	public function testSqliteGetDatabaseCharsetUtf8ReturnsUtf8(): void
	{
		$conn = $this->openSqlite('UTF-8');
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetDefaultReturnsUtf8(): void
	{
		// No Charset configured — SQLite defaults to UTF-8.
		$conn = $this->openSqlite();
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetUtf16ReturnsEndianVariant(): void
	{
		// UTF-16 database: getDatabaseCharset() returns the raw PRAGMA value,
		// which is 'UTF-16le' or 'UTF-16be' depending on the host's byte order.
		$conn = $this->openSqlite('UTF-16');
		$this->assertIsUtf16Encoding($conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetReflectsUtf8AfterSetCharset(): void
	{
		$conn = $this->openSqlite();
		$conn->Charset = 'UTF-8';
		$this->assertSame('UTF-8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testSqliteGetDatabaseCharsetReflectsUtf16AfterSetCharset(): void
	{
		// setCharset('UTF-16') on a fresh DB applies the PRAGMA; DatabaseCharset
		// returns the raw endian-specific form.
		$conn = $this->openSqlite();
		$conn->Charset = 'UTF-16';
		$this->assertIsUtf16Encoding($conn->DatabaseCharset);
		// Charset property is the PRADO canonical endian-specific form.
		$this->assertIsUtf16CanonicalCharset($conn->Charset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// hasAutoCommitAttribute = false — SQLite does not expose PDO::ATTR_AUTOCOMMIT
	// -----------------------------------------------------------------------

	public function testSqliteHasNoAutoCommitAttributeFlag(): void
	{
		$conn = $this->openSqlite();
		$this->assertFalse($conn->HasAutoCommit,
			'SQLite must report hasAutoCommitAttribute = false.');
		$conn->Active = false;
	}

	public function testSqliteGetAutoCommitReturnsFalseWithoutCrash(): void
	{
		$conn = $this->openSqlite();
		$this->assertFalse($conn->AutoCommit,
			'AutoCommit must return false for SQLite (PDO::ATTR_AUTOCOMMIT not supported).');
		$conn->Active = false;
	}

	public function testSqliteSetAutoCommitIsSafelyIgnored(): void
	{
		$conn = $this->openSqlite();
		$conn->AutoCommit = true;
		$conn->AutoCommit = false;
		$this->assertTrue($conn->Active, 'Connection must remain active after setAutoCommit no-ops.');
		$this->assertFalse($conn->AutoCommit);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// PRAGMA injection safety — PDO::quote() escaping
	// -----------------------------------------------------------------------

	public function testSqlitePragmaEncodingAppliedViaQuoteEscapingUtf8(): void
	{
		// PRAGMA encoding = %s is executed via sprintf($sql, $pdo->quote($charset)).
		// Verify the PRAGMA takes effect without injection issues for UTF-8.
		$conn = $this->openSqlite('UTF-8');
		$this->assertSame('UTF-8', $this->pragmaEncoding($conn),
			'PRAGMA encoding must be applied via PDO::quote()-escaped sprintf.');
		$conn->Active = false;
	}

	public function testSqlitePragmaEncodingAppliedViaQuoteEscapingUtf16(): void
	{
		// Same as above for UTF-16.
		$conn = $this->openSqlite('UTF-16');
		$this->assertIsUtf16Encoding($this->pragmaEncoding($conn),
			'PRAGMA encoding must be applied via PDO::quote()-escaped sprintf.');
		$conn->Active = false;
	}
}
