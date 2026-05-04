<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

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
}
