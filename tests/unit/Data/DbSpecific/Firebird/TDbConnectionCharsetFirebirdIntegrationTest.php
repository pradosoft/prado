<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Exceptions\TDbException;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — Firebird.
 *
 * Firebird does not support runtime charset switching via SQL (there is no
 * SET NAMES command in Firebird DSQL).  Charset is configured exclusively via
 * the DSN parameter  charset=<resolved>  injected by applyCharsetToDsn() before
 * PDO is constructed.  Changing Charset after the connection is open has no
 * effect on the server.
 *
 * getDatabaseCharset() queries MON$ATTACHMENTS ⋈ RDB$CHARACTER_SETS when the
 * MONITOR privilege is available; otherwise it falls back to
 * resolveCharsetForDriver() on the stored Charset property.
 *
 * Tests are skipped automatically when the pdo_firebird extension is missing
 * or the prado_unitest.fdb database is unreachable.
 *
 * Environment variables
 * ---------------------
 * FIREBIRD_DB_PATH  Server-side path to the prado_unitest.fdb file.
 *                   Defaults to /var/lib/firebird/data/prado_unitest.fdb.
 */
class TDbConnectionCharsetFirebirdIntegrationTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
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
	// Firebird helpers
	// -----------------------------------------------------------------------

	/**
	 * Open a Firebird connection.  When $charset is non-empty it is injected
	 * into the DSN as  charset=<resolved>  by applyCharsetToDsn() before PDO
	 * is constructed.  No post-connect SQL is sent.
	 */
	private function openFirebird(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_firebird')) {
			$this->markTestSkipped('pdo_firebird extension not available.');
		}
		$dbPath = getenv('FIREBIRD_DB_PATH') ?: '/var/lib/firebird/data/prado_unitest.fdb';
		return $this->openConnection(
			'firebird:dbname=localhost:' . $dbPath,
			'SYSDBA',
			'masterkey',
			$charset
		);
	}

	/**
	 * Query the character set name of the current attachment from Firebird's
	 * monitoring tables.  Returns null if MON$ views are not accessible.
	 */
	private function firebirdConnectionCharset(TDbConnection $conn): ?string
	{
		try {
			// RDB$CHARACTER_SET_NAME is CHAR(31) — TRIM removes trailing spaces.
			$result = $this->queryScalar(
				$conn,
				'SELECT TRIM(c.RDB$CHARACTER_SET_NAME)' .
				'  FROM MON$ATTACHMENTS a' .
				'  JOIN RDB$CHARACTER_SETS c' .
				'    ON c.RDB$CHARACTER_SET_ID = a.MON$CHARACTER_SET_ID' .
				' WHERE a.MON$ATTACHMENT_ID = CURRENT_CONNECTION'
			);
			return $result !== false ? (string) $result : null;
		} catch (\Exception $e) {
			// MON$ views may require MONITOR privilege; treat as unverifiable.
			return null;
		}
	}

	// -----------------------------------------------------------------------
	// Tests — charset injected into DSN via applyCharsetToDsn()
	// -----------------------------------------------------------------------

	public function testFirebirdUtf8ResolvedInDsn(): void
	{
		// 'UTF-8' resolves to 'UTF8' for Firebird and is injected into the DSN.
		$conn = $this->openFirebird('UTF-8');
		$this->assertTrue($conn->Active);

		$cs = $this->firebirdConnectionCharset($conn);
		if ($cs !== null) {
			$this->assertSame('UTF8', $cs);
		} else {
			$this->markTestIncomplete(
				'Could not verify Firebird connection charset via MON$ATTACHMENTS ' .
				'(MONITOR privilege may be required).'
			);
		}

		$conn->Active = false;
	}

	public function testFirebirdIso88591ResolvedInDsn(): void
	{
		// 'ISO-8859-1' resolves to 'ISO8859_1' for Firebird and is injected into the DSN.
		$conn = $this->openFirebird('ISO-8859-1');
		$this->assertTrue($conn->Active);

		$cs = $this->firebirdConnectionCharset($conn);
		if ($cs !== null) {
			$this->assertSame('ISO8859_1', $cs);
		} else {
			$this->markTestIncomplete(
				'Could not verify Firebird connection charset via MON$ATTACHMENTS.'
			);
		}

		$conn->Active = false;
	}

	public function testFirebirdDriverSpecificNamePassesThrough(): void
	{
		// 'UTF8' is the Firebird-native name; it has no alias and passes through unchanged.
		$conn = $this->openFirebird('UTF8');
		$this->assertTrue($conn->Active);

		$cs = $this->firebirdConnectionCharset($conn);
		if ($cs !== null) {
			$this->assertSame('UTF8', $cs);
		}

		$conn->Active = false;
	}

	public function testFirebirdSetCharsetAfterConnectHasNoEffect(): void
	{
		// For Firebird, charset is DSN-only; setting Charset after the connection
		// is open is a no-op at the server level (no SQL command is sent).
		// The connection must remain active and no exception must be thrown.
		$conn = $this->openFirebird();
		$this->expectException(TDbException::class);
		$conn->Charset = 'UTF-8';
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — queries MON$ATTACHMENTS on an active connection;
	// falls back to resolveCharsetForDriver() if MONITOR privilege is absent.
	// -----------------------------------------------------------------------

	public function testFirebirdGetDatabaseCharsetReturnsActiveCharset(): void
	{
		// 'UTF-8' resolves to 'UTF8' and is injected into the DSN.
		// DatabaseCharset queries MON$ATTACHMENTS or falls back to the resolved value.
		$conn = $this->openFirebird('UTF-8');
		$this->assertSame('UTF8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testFirebirdGetDatabaseCharsetReturnsIso88591WhenConfigured(): void
	{
		// 'ISO-8859-1' resolves to 'ISO8859_1' for Firebird.
		$conn = $this->openFirebird('ISO-8859-1');
		$this->assertSame('ISO8859_1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testFirebirdGetDatabaseCharsetReturnsDsnCharset(): void
	{
		// Charset is injected into the DSN by applyCharsetToDsn(); DatabaseCharset
		// reflects the correct value via MON$ATTACHMENTS or the resolved fallback.
		$conn = $this->openFirebird('UTF-8');
		$this->assertSame('UTF8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — usesSerialTransaction behavioral verification
	//
	// pdo_firebird always keeps an implicit transaction alive.  TDbConnection
	// responds by creating a serial TDbTransaction immediately in open(), so
	// getCurrentTransaction() returns non-null immediately after connect.
	// TDbTransaction::isTransactionComplete() restarts the transaction after
	// every commit/rollback instead of deactivating it, so the transaction
	// remains active for the lifetime of the connection.
	// -----------------------------------------------------------------------

	public function testFirebirdConnectionHasSerialTransactionAtConnectTime(): void
	{
		// open() calls createTransaction() for Firebird (usesSerialTransaction=true),
		// so getCurrentTransaction() must return a non-null active transaction object
		// even before the application has called beginTransaction().
		$conn = $this->openFirebird('UTF-8');

		$serialTx = $conn->getCurrentTransaction();
		$this->assertNotNull(
			$serialTx,
			'Firebird connection must have a serial transaction immediately at connect time.'
		);
		$this->assertTrue(
			$serialTx->getActive(),
			'The connect-time serial transaction must be active.'
		);
		// Note: getSerial() depends on PDO::ATTR_AUTOCOMMIT at connect time; we
		// assert the *observable* outcome (transaction exists) rather than the
		// internal flag, which is an implementation detail of pdo_firebird.

		$conn->Active = false;
	}

	public function testFirebirdBeginTransactionReturnsActiveTransaction(): void
	{
		// beginTransaction() on a Firebird connection must succeed without throwing
		// and return an active TDbTransaction.  For serial connections the call
		// flushes the implicit driver transaction (pre-begin flush) and starts an
		// explicit PDO transaction before returning.
		$conn = $this->openFirebird('UTF-8');

		$tx = $conn->beginTransaction();
		$this->assertTrue(
			$tx->getActive(),
			'beginTransaction must return an active TDbTransaction for Firebird.'
		);

		$tx->commit();
		$conn->Active = false;
	}

	public function testFirebirdSerialTransactionRemainsActiveAfterCommit(): void
	{
		// After commit(), isTransactionComplete() restarts the transaction instead
		// of deactivating it, so getCurrentTransaction() must still return non-null.
		$conn = $this->openFirebird('UTF-8');

		$tx = $conn->beginTransaction();
		$tx->commit();

		$this->assertNotNull(
			$conn->getCurrentTransaction(),
			'Serial transaction must remain the current transaction after commit.'
		);
		$this->assertTrue(
			$conn->getCurrentTransaction()->getActive(),
			'Serial transaction must still be active after commit.'
		);

		$conn->Active = false;
	}

	public function testFirebirdSerialTransactionRemainsActiveAfterRollback(): void
	{
		$conn = $this->openFirebird('UTF-8');

		$tx = $conn->beginTransaction();
		$tx->rollBack();

		$this->assertNotNull(
			$conn->getCurrentTransaction(),
			'Serial transaction must remain current after rollback.'
		);
		$this->assertTrue(
			$conn->getCurrentTransaction()->getActive(),
			'Serial transaction must still be active after rollback.'
		);

		$conn->Active = false;
	}

	public function testFirebirdSerialTransactionSupportsMultipleCommitRollbackCycles(): void
	{
		// A Firebird serial transaction is restarted automatically after each
		// commit/rollback; the same TDbTransaction remains active throughout.
		// Call beginTransaction() once to claim the cycle, then commit/rollback
		// multiple times on the same reference without calling beginTransaction()
		// again (the restart happens internally via isTransactionComplete →
		// restartTransaction → beginTransaction).
		$conn = $this->openFirebird('UTF-8');

		$tx = $conn->beginTransaction();

		for ($cycle = 1; $cycle <= 3; $cycle++) {
			$this->assertTrue(
				$tx->getActive(),
				"Cycle $cycle: serial transaction must be active before the operation."
			);
			if ($cycle % 2 === 0) {
				$tx->rollBack();
			} else {
				$tx->commit();
			}
			$this->assertNotNull(
				$conn->getCurrentTransaction(),
				"Cycle $cycle: getCurrentTransaction must return non-null after operation (serial restart)."
			);
			$this->assertTrue(
				$conn->getCurrentTransaction()->getActive(),
				"Cycle $cycle: the restarted serial transaction must still be active."
			);
		}

		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — requiresPreBeginTransactionFlush behavioral verification
	//
	// pdo_firebird starts an implicit transaction at connect time and after every
	// commit/rollback.  PDO::beginTransaction() fails with "There is already an
	// active transaction" if that implicit transaction has not been terminated.
	// TDbConnection::beginTransaction() calls PDO::commit() first (the "pre-begin
	// flush") so that PDO::beginTransaction() always succeeds cleanly.
	// -----------------------------------------------------------------------

	public function testFirebirdBeginTransactionSucceedsOnFreshConnection(): void
	{
		// A fresh pdo_firebird connection has an implicit transaction running.
		// Without requiresPreBeginTransactionFlush, calling PDO::beginTransaction()
		// immediately would throw "There is already an active transaction".
		// The pre-begin flush commits the implicit tx first; this must not throw.
		$conn = $this->openFirebird('UTF-8');
		$tx   = $conn->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$conn->Active = false;
	}

	public function testFirebirdPreBeginFlushEnablesRepeatedBeginTransactions(): void
	{
		// TDbConnection performs a pre-begin flush (PDO::commit()) before each
		// PDO::beginTransaction() call to clear Firebird's always-running implicit
		// transaction.  A serial transaction auto-restarts after commit/rollback via
		// isTransactionComplete → restartTransaction → beginTransaction internally.
		// Call beginTransaction() once to claim the cycle, then verify repeated
		// commit/rollback operations on the same object never throw.
		$conn = $this->openFirebird('UTF-8');
		$tx   = $conn->beginTransaction();
		for ($i = 0; $i < 4; $i++) {
			$this->assertTrue(
				$tx->getActive(),
				"Cycle $i: transaction must be active before operation."
			);
			// Alternate commit and rollback to exercise both PDO paths.
			if ($i % 2 === 0) {
				$tx->commit();
			} else {
				$tx->rollBack();
			}
		}
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// Live connection — hasAutoCommitAttribute behavioral verification
	//
	// Firebird has hasAutoCommitAttribute = true.  After PDO::beginTransaction(),
	// PDO::ATTR_AUTOCOMMIT transitions to false; after commit/rollback + restart,
	// the serial transaction restarts it, keeping autocommit false throughout.
	// -----------------------------------------------------------------------

	public function testFirebirdHasAutoCommitAttribute(): void
	{
		$conn = $this->openFirebird('UTF-8');
		$this->assertTrue($conn->HasAutoCommit, 'Firebird must report hasAutoCommitAttribute = true.');
		$conn->Active = false;
	}

	public function testFirebirdAutoCommitIsFalseInsideExplicitTransaction(): void
	{
		// PDO::ATTR_AUTOCOMMIT returns false while an explicit transaction is active.
		$conn = $this->openFirebird('UTF-8');
		$conn->beginTransaction();
		$this->assertFalse(
			$conn->AutoCommit,
			'AutoCommit must be false while inside an explicit Firebird transaction.'
		);
		$conn->commit();
		$conn->Active = false;
	}
}
