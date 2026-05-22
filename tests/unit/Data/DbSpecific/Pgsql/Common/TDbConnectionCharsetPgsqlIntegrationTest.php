<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Integration tests for TDbConnection charset handling — PostgreSQL.
 *
 * Charset is applied via  SET client_encoding TO <resolved>  after the
 * connection opens.  PostgreSQL has no standard DSN charset parameter, so
 * only the post-connect SQL path is exercised here.
 *
 * Tests are skipped automatically when the pdo_pgsql extension is missing or
 * the prado_unitest database is unreachable.
 *
 * On Scrutinizer CI the user/password is 'scrutinizer'; elsewhere both default
 * to 'prado_unitest'.
 */
class TDbConnectionCharsetPgsqlIntegrationTest extends PHPUnit\Framework\TestCase
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
	// PostgreSQL helpers
	// -----------------------------------------------------------------------

	private function openPgsql(string $charset = ''): TDbConnection
	{
		if (!extension_loaded('pdo_pgsql')) {
			$this->markTestSkipped('pdo_pgsql extension not available.');
		}
		$cred = getenv('SCRUTINIZER') ? 'scrutinizer' : 'prado_unitest';
		return $this->openConnection(
			'pgsql:host=localhost;dbname=prado_unitest',
			$cred,
			$cred,
			$charset
		);
	}

	/** Query the active client encoding reported by PostgreSQL. */
	private function pgsqlClientEncoding(TDbConnection $conn): string
	{
		return (string) $this->queryScalar($conn, 'SELECT pg_client_encoding()');
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	public function testPgsqlUtf8ResolvedToUTF8(): void
	{
		// Universal 'UTF-8' must become 'UTF8' for PostgreSQL.
		$conn = $this->openPgsql('UTF-8');
		$this->assertSame('UTF8', $this->pgsqlClientEncoding($conn));
		$conn->Active = false;
	}

	public function testPgsqlIso88591ResolvedToLatin1(): void
	{
		$conn = $this->openPgsql('ISO-8859-1');
		$this->assertSame('LATIN1', $this->pgsqlClientEncoding($conn));
		$conn->Active = false;
	}

	public function testPgsqlDriverSpecificNamePassesThrough(): void
	{
		// PostgreSQL-specific 'UTF8' has no alias; it passes through unchanged.
		$conn = $this->openPgsql('UTF8');
		$this->assertSame('UTF8', $this->pgsqlClientEncoding($conn));
		$conn->Active = false;
	}

	public function testPgsqlSetCharsetAfterConnect(): void
	{
		$conn = $this->openPgsql();
		$conn->Charset = 'UTF-8';
		$this->assertSame('UTF8', $this->pgsqlClientEncoding($conn));
		$conn->Active = false;
	}

	public function testPgsqlSetCharsetAfterConnectIso88591(): void
	{
		$conn = $this->openPgsql();
		$conn->Charset = 'ISO-8859-1';
		$this->assertSame('LATIN1', $this->pgsqlClientEncoding($conn));
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// getDatabaseCharset() — queries pg_client_encoding() on an active connection
	// -----------------------------------------------------------------------

	public function testPgsqlGetDatabaseCharsetReturnsActiveClientEncoding(): void
	{
		// 'UTF-8' resolves to 'UTF8'; DatabaseCharset queries pg_client_encoding().
		$conn = $this->openPgsql('UTF-8');
		$this->assertSame('UTF8', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testPgsqlGetDatabaseCharsetReturnsLatin1WhenSetToIso88591(): void
	{
		$conn = $this->openPgsql('ISO-8859-1');
		$this->assertSame('LATIN1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	public function testPgsqlGetDatabaseCharsetReflectsCharsetChangedAfterConnect(): void
	{
		$conn = $this->openPgsql();
		$conn->Charset = 'ISO-8859-1';
		$this->assertSame('LATIN1', $conn->DatabaseCharset);
		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// requiresPostConnectCharset behavioral verification
	//
	// PostgreSQL has no DSN charset parameter (getCharsetDsnParam('pgsql') = null).
	// TDbConnection::applyCharsetToDsn() returns the DSN unchanged for pgsql.
	// Instead, TDbConnection::open() calls setConnectionCharset() immediately after
	// connecting, which executes SET client_encoding TO <quoted> via $pdo->exec()
	// (PostgreSQL's SET command does not accept bind parameters in prepared stmts).
	// This means:
	//   (a) the raw DSN stored in ConnectionString must NOT contain 'charset'
	//   (b) the charset IS applied (verified by pg_client_encoding())
	// -----------------------------------------------------------------------

	public function testPgsqlCharsetIsAppliedPostConnectNotViaDsn(): void
	{
		// Open with charset set; pgsql has no DSN charset param so applyCharsetToDsn()
		// must NOT append charset=... to the raw DSN.
		$conn = $this->openPgsql('UTF-8');

		// (a) Raw DSN string must not contain a charset parameter.
		$this->assertStringNotContainsString(
			'charset',
			strtolower($conn->getConnectionString()),
			'PostgreSQL DSN must not have a charset parameter — charset is applied post-connect via SQL.'
		);

		// (b) Charset IS applied: pg_client_encoding() must reflect the requested encoding.
		$activeEncoding = $this->pgsqlClientEncoding($conn);
		$this->assertSame(
			'UTF8',
			$activeEncoding,
			'SET client_encoding must have been issued post-connect so pg_client_encoding() reflects it.'
		);

		$conn->Active = false;
	}

	public function testPgsqlCharsetAppliedPostConnectForIso88591(): void
	{
		$conn = $this->openPgsql('ISO-8859-1');

		$this->assertStringNotContainsString('charset', strtolower($conn->getConnectionString()));
		$this->assertSame('LATIN1', $this->pgsqlClientEncoding($conn));

		$conn->Active = false;
	}

	// -----------------------------------------------------------------------
	// hasAutoCommitAttribute behavioral verification
	//
	// PostgreSQL does NOT expose PDO::ATTR_AUTOCOMMIT — pdo_pgsql throws a
	// PDOException when the attribute is read or written.  TDbConnection reports
	// HasAutoCommit = false for pgsql, and AutoCommit access is not applicable.
	// -----------------------------------------------------------------------

	public function testPgsqlHasAutoCommitAttributeIsFalse(): void
	{
		$conn = $this->openPgsql();
		$this->assertFalse(
			$conn->HasAutoCommit,
			'PostgreSQL must report hasAutoCommitAttribute = false (ATTR_AUTOCOMMIT is not supported).'
		);
		$conn->Active = false;
	}

	public function testPgsqlBeginTransactionSucceedsAndRollbackWorks(): void
	{
		// pgsql does not expose ATTR_AUTOCOMMIT.  Simply verify that
		// beginTransaction/rollback work without error.
		$conn = $this->openPgsql();
		$tx   = $conn->beginTransaction();
		$this->assertTrue($tx->getActive(), 'PostgreSQL beginTransaction must return an active transaction.');
		$conn->rollback();
		$conn->Active = false;
	}

	public function testPgsqlSetCharsetUsesExecSql(): void
	{
		// getCharsetSetSql('pgsql') returns 'SET client_encoding TO %s' — a sprintf
		// format string.  PostgreSQL's SET command does not accept bind parameters in
		// prepared statements, so TDbConnection executes it via
		// $pdo->exec(sprintf($sql, $pdo->quote($charset))) instead.
		// Verify the functional outcome: pg_client_encoding() must reflect the change.
		$conn = $this->openPgsql();
		$conn->Charset = 'UTF-8';
		$this->assertSame(
			'UTF8',
			$this->pgsqlClientEncoding($conn),
			'SET client_encoding must have been executed with \'UTF8\' as the quoted value.'
		);
		$conn->Active = false;
	}
}
