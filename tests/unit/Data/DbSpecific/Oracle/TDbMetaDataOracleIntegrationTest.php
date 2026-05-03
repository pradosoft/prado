<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TDbMetaData — Oracle.
 *
 * Verifies schema introspection (getTableInfo, findTableNames, quoting) and
 * the TDbTableInfo / TDbTableColumn API against a real Oracle database.
 *
 * Oracle DDL auto-commits and column/table names are returned in uppercase.
 * Oracle's TOracleMetaData inherits quoting from TDbMetaData (no delimiters).
 *
 * Table schema used throughout (uppercase):
 *   META_TEST (
 *     ID    NUMBER(10)   NOT NULL PRIMARY KEY,
 *     NAME  VARCHAR2(100) NOT NULL,
 *     SCORE BINARY_DOUBLE,
 *     NOTE  VARCHAR2(100) DEFAULT 'fallback'
 *   )
 */
class TDbMetaDataOracleIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openOracle(): TDbConnection
	{
		$conn = PradoUnit::setupOciConnection();
		if (is_string($conn)) {
			$this->markTestSkipped($conn);
		}
		return $conn;
	}

	protected function setUp(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->_conn = $this->openOracle();

		// Oracle DDL auto-commits; drop any leftover table first.
		try {
			$this->_conn->createCommand('DROP TABLE META_TEST')->execute();
		} catch (\Exception $e) {
		}
		$this->_conn->createCommand(
			"CREATE TABLE META_TEST (ID NUMBER(10) NOT NULL PRIMARY KEY, NAME VARCHAR2(100) NOT NULL, SCORE BINARY_DOUBLE, NOTE VARCHAR2(100) DEFAULT 'fallback')"
		)->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand('DROP TABLE META_TEST')->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbMetaData::getInstance()
	// -----------------------------------------------------------------------

	public function testGetInstanceReturnsOracleMetaData(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->assertInstanceOf(TOracleMetaData::class, $meta);
	}

	// -----------------------------------------------------------------------
	// getTableInfo() — TDbTableInfo
	// -----------------------------------------------------------------------

	public function testGetTableInfoReturnsTableInfo(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	public function testGetTableInfoTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$this->assertSame('META_TEST', $info->getTableName());
	}

	public function testGetTableInfoColumnNamesContainsAllColumns(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$names = $info->getColumnNames();
		// TOracleMetaData stores column names in lowercase (LOWER(COLUMN_NAME)).
		$this->assertContains('id',    $names);
		$this->assertContains('name',  $names);
		$this->assertContains('score', $names);
		$this->assertContains('note',  $names);
		$this->assertCount(4, $names);
	}

	public function testGetTableInfoPrimaryKeys(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$pks = $info->getPrimaryKeys();
		$this->assertContains('id', $pks);
		$this->assertCount(1, $pks);
	}

	public function testGetTableInfoGetColumnReturnsColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertNotNull($col);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableColumn::class, $col);
	}

	public function testGetTableInfoGetColumnThrowsForMissingColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$info->getColumn('nonexistent_column');
	}

	public function testGetTableInfoCachingReturnsSameObject(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info1 = $meta->getTableInfo('META_TEST');
		$info2 = $meta->getTableInfo('META_TEST');
		$this->assertSame($info1, $info2);
	}

	public function testGetTableInfoThrowsForInvalidTable(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$meta->getTableInfo('NONEXISTENT_TABLE_XYZ');
	}

	// -----------------------------------------------------------------------
	// TDbTableColumn — column metadata
	// -----------------------------------------------------------------------

	public function testPrimaryKeyColumnIsPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('id');
		$this->assertTrue($col->getIsPrimaryKey());
	}

	public function testNonPrimaryKeyColumnIsNotPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getIsPrimaryKey());
	}

	public function testPrimaryKeyColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('id');
		// Oracle NUMBER maps to 'number' in the catalog.
		$this->assertStringContainsStringIgnoringCase('number', $col->getDbType());
	}

	public function testVarcharColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertStringContainsStringIgnoringCase('varchar', $col->getDbType());
	}

	public function testNotNullColumnDoesNotAllowNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getAllowNull());
	}

	public function testNullableColumnAllowsNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('score');
		$this->assertTrue($col->getAllowNull());
	}

	public function testColumnWithDefaultValueHasDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('note');
		$this->assertNotNull($col->getDefaultValue());
	}

	public function testColumnWithoutDefaultHasNullDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		// SCORE has no DEFAULT clause.
		$col = $info->getColumn('score');
		$this->assertSame(\Prado\Data\Common\TDbTableColumn::UNDEFINED_VALUE, $col->getDefaultValue());
	}

	// -----------------------------------------------------------------------
	// findTableNames()
	// -----------------------------------------------------------------------

	public function testFindTableNamesContainsMetaTest(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		// Oracle returns uppercase table names.
		$this->assertContains('META_TEST', $tables);
	}

	public function testFindTableNamesReturnsArray(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertIsArray($tables);
	}

	// -----------------------------------------------------------------------
	// createCommandBuilder()
	// -----------------------------------------------------------------------

	public function testCreateCommandBuilderReturnsBuilder(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$builder = $meta->createCommandBuilder('META_TEST');
		$this->assertInstanceOf(TDbCommandBuilder::class, $builder);
	}

	// -----------------------------------------------------------------------
	// Quoting helpers
	// -----------------------------------------------------------------------

	public function testQuoteTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteTableName('FOO');
		// TOracleMetaData inherits base TDbMetaData quoting — no delimiters by default.
		$this->assertSame('FOO', $quoted);
	}

	public function testQuoteColumnName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnName('BAR');
		$this->assertSame('BAR', $quoted);
	}

	public function testQuoteColumnAlias(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnAlias('BAZ');
		$this->assertSame('BAZ', $quoted);
	}
}
