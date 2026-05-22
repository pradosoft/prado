<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TDbMetaData — SQLite.
 *
 * Verifies schema introspection (getTableInfo, findTableNames, quoting) and
 * the TDbTableInfo / TDbTableColumn API against a real in-memory SQLite DB.
 *
 * Table schema used throughout:
 *   meta_test (
 *     id    INTEGER PRIMARY KEY,
 *     name  TEXT NOT NULL,
 *     score REAL,
 *     note  TEXT DEFAULT 'fallback'
 *   )
 */
class TDbMetaDataSqliteIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openSqlite(): TDbConnection
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		try {
			$conn = new TDbConnection('sqlite::memory:');
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot open SQLite in-memory database: ' . $e->getMessage());
		}
	}

	protected function setUp(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->_conn = $this->openSqlite();
		$this->_conn->createCommand(
			"CREATE TABLE meta_test (id INTEGER PRIMARY KEY, name TEXT NOT NULL, score REAL, note TEXT DEFAULT 'fallback')"
		)->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand('DROP TABLE meta_test')->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbMetaData::getInstance()
	// -----------------------------------------------------------------------

	public function testSqliteGetInstanceReturnsSqliteMetaData(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->assertInstanceOf(TSqliteMetaData::class, $meta);
	}

	// -----------------------------------------------------------------------
	// getTableInfo() — TDbTableInfo
	// -----------------------------------------------------------------------

	public function testSqliteGetTableInfoReturnsTableInfo(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	public function testSqliteGetTableInfoTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertSame('meta_test', $info->getTableName());
	}

	public function testSqliteGetTableInfoColumnNamesContainsAllColumns(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$names = $info->getColumnNames();
		$this->assertContains('"id"',    $names);
		$this->assertContains('"name"',  $names);
		$this->assertContains('"score"', $names);
		$this->assertContains('"note"',  $names);
		$this->assertCount(4, $names);
	}

	public function testSqliteGetTableInfoPrimaryKeys(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$pks = $info->getPrimaryKeys();
		$this->assertContains('id', $pks);
		$this->assertCount(1, $pks);
	}

	public function testSqliteGetTableInfoGetColumnReturnsColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertNotNull($col);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableColumn::class, $col);
	}

	public function testSqliteGetTableInfoGetColumnThrowsForMissingColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$info->getColumn('nonexistent_column');
	}

	public function testSqliteGetTableInfoCachingReturnsSameObject(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info1 = $meta->getTableInfo('meta_test');
		$info2 = $meta->getTableInfo('meta_test');
		$this->assertSame($info1, $info2);
	}

	public function testSqliteGetTableInfoThrowsForInvalidTable(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$meta->getTableInfo('nonexistent_table_xyz');
	}

	// -----------------------------------------------------------------------
	// TDbTableColumn — column metadata
	// -----------------------------------------------------------------------

	public function testSqlitePrimaryKeyColumnIsPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('id');
		$this->assertTrue($col->getIsPrimaryKey());
	}

	public function testSqliteNonPrimaryKeyColumnIsNotPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getIsPrimaryKey());
	}

	public function testSqlitePrimaryKeyColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('id');
		$this->assertStringContainsStringIgnoringCase('integer', $col->getDbType());
	}

	public function testSqliteTextColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertStringContainsStringIgnoringCase('text', $col->getDbType());
	}

	public function testSqliteColumnWithDefaultValueHasDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('note');
		$this->assertNotNull($col->getDefaultValue());
	}

	public function testSqliteColumnWithoutDefaultHasNullDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		// score has no DEFAULT clause.
		$col = $info->getColumn('score');
		$this->assertSame(\Prado\Data\Common\TDbTableColumn::UNDEFINED_VALUE, $col->getDefaultValue());
	}

	// -----------------------------------------------------------------------
	// findTableNames()
	// -----------------------------------------------------------------------

	public function testSqliteFindTableNamesContainsMetaTest(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertContains('meta_test', $tables);
	}

	public function testSqliteFindTableNamesReturnsArray(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertIsArray($tables);
	}

	// -----------------------------------------------------------------------
	// createCommandBuilder()
	// -----------------------------------------------------------------------

	public function testSqliteCreateCommandBuilderReturnsBuilder(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$builder = $meta->createCommandBuilder('meta_test');
		$this->assertInstanceOf(TDbCommandBuilder::class, $builder);
	}

	// -----------------------------------------------------------------------
	// Quoting helpers
	// -----------------------------------------------------------------------

	public function testSqliteQuoteTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteTableName('foo');
		// SQLite quoteTableName uses double-quotes (SQL standard identifier quoting).
		// Single-quotes are string literals and cause SQLITE_RANGE when ORDER BY
		// references quoted column names against a single-quoted table source.
		$this->assertSame('"foo"', $quoted);
	}

	public function testSqliteQuoteColumnName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnName('bar');
		$this->assertSame('"bar"', $quoted);
	}

	public function testSqliteQuoteColumnAlias(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnAlias('baz');
		$this->assertSame('"baz"', $quoted);
	}
}
