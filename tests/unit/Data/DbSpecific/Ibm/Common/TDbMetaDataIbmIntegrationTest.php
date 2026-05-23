<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TDbMetaData — IBM DB2.
 *
 * Verifies schema introspection (getTableInfo, findTableNames, quoting) and
 * the TDbTableInfo / TDbTableColumn API against a real IBM DB2 database.
 *
 * DB2 DDL auto-commits and column/table names are returned in uppercase.
 *
 * Table schema used throughout (uppercase):
 *   META_TEST (
 *     ID    INTEGER NOT NULL PRIMARY KEY,
 *     NAME  VARCHAR(100) NOT NULL,
 *     SCORE DOUBLE,
 *     NOTE  VARCHAR(100) DEFAULT 'fallback'
 *   )
 */
class TDbMetaDataIbmIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openIbm(): TDbConnection
	{
		$conn = PradoUnit::setupIbmConnection();
		if (is_string($conn)) {
			$this->markTestSkipped($conn);
		}
		return $conn;
	}

	protected function setUp(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->_conn = $this->openIbm();

		// DB2 DDL auto-commits; drop any leftover table first.
		try {
			$this->_conn->createCommand('DROP TABLE META_TEST')->execute();
		} catch (\Exception $e) {
		}
		$this->_conn->createCommand(
			"CREATE TABLE META_TEST (ID INTEGER NOT NULL PRIMARY KEY, NAME VARCHAR(100) NOT NULL, SCORE DOUBLE, NOTE VARCHAR(100) DEFAULT 'fallback')"
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

	public function testIbmGetInstanceReturnsIbmMetaData(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->assertInstanceOf(TIbmMetaData::class, $meta);
	}

	// -----------------------------------------------------------------------
	// getTableInfo() — TDbTableInfo
	// -----------------------------------------------------------------------

	public function testIbmGetTableInfoReturnsTableInfo(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	public function testIbmGetTableInfoTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$this->assertSame('META_TEST', $info->getTableName());
	}

	public function testIbmGetTableInfoColumnNamesContainsAllColumns(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$names = $info->getColumnNames();
		$this->assertContains('"ID"',    $names);
		$this->assertContains('"NAME"',  $names);
		$this->assertContains('"SCORE"', $names);
		$this->assertContains('"NOTE"',  $names);
		$this->assertCount(4, $names);
	}

	public function testIbmGetTableInfoPrimaryKeys(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$pks = $info->getPrimaryKeys();
		$this->assertContains('id', $pks);
		$this->assertCount(1, $pks);
	}

	public function testIbmGetTableInfoGetColumnReturnsColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertNotNull($col);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableColumn::class, $col);
	}

	public function testIbmGetTableInfoGetColumnThrowsForMissingColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$info->getColumn('nonexistent_column');
	}

	public function testIbmGetTableInfoCachingReturnsSameObject(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info1 = $meta->getTableInfo('META_TEST');
		$info2 = $meta->getTableInfo('META_TEST');
		$this->assertSame($info1, $info2);
	}

	public function testIbmGetTableInfoThrowsForInvalidTable(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$meta->getTableInfo('NONEXISTENT_TABLE_XYZ');
	}

	// -----------------------------------------------------------------------
	// TDbTableColumn — column metadata
	// -----------------------------------------------------------------------

	public function testIbmPrimaryKeyColumnIsPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('id');
		$this->assertTrue($col->getIsPrimaryKey());
	}

	public function testIbmNonPrimaryKeyColumnIsNotPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getIsPrimaryKey());
	}

	public function testIbmPrimaryKeyColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('id');
		$this->assertStringContainsStringIgnoringCase('int', $col->getDbType());
	}

	public function testIbmVarcharColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertStringContainsStringIgnoringCase('varchar', $col->getDbType());
	}

	public function testIbmNotNullColumnDoesNotAllowNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getAllowNull());
	}

	public function testIbmNullableColumnAllowsNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('score');
		$this->assertTrue($col->getAllowNull());
	}

	public function testIbmColumnWithDefaultValueHasDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('META_TEST');
		$col = $info->getColumn('note');
		$this->assertNotNull($col->getDefaultValue());
	}

	public function testIbmColumnWithoutDefaultHasNullDefault(): void
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

	public function testIbmFindTableNamesContainsMetaTest(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		// TIbmMetaData::findTableNames() normalises names to lowercase.
		$this->assertContains('meta_test', $tables);
	}

	public function testIbmFindTableNamesReturnsArray(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertIsArray($tables);
	}

	// -----------------------------------------------------------------------
	// createCommandBuilder()
	// -----------------------------------------------------------------------

	public function testIbmCreateCommandBuilderReturnsBuilder(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$builder = $meta->createCommandBuilder('META_TEST');
		$this->assertInstanceOf(TDbCommandBuilder::class, $builder);
	}

	// -----------------------------------------------------------------------
	// Quoting helpers
	// -----------------------------------------------------------------------

	public function testIbmQuoteTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteTableName('FOO');
		// IBM DB2 uses double-quote quoting.
		$this->assertSame('"FOO"', $quoted);
	}

	public function testIbmQuoteColumnName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnName('BAR');
		$this->assertSame('"BAR"', $quoted);
	}

	public function testIbmQuoteColumnAlias(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnAlias('BAZ');
		$this->assertSame('"BAZ"', $quoted);
	}
}
