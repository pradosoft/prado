<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Mssql\TMssqlMetaData;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\TApplication;

/**
 * Live integration tests for TDbMetaData — Microsoft SQL Server.
 *
 * Verifies schema introspection (getTableInfo, findTableNames, quoting) and
 * the TDbTableInfo / TDbTableColumn API against a real SQL Server database.
 *
 * SQL Server uses bracket quoting for table/column names; column aliases use
 * double-quotes (as per TMssqlMetaData).
 *
 * Table schema used throughout:
 *   meta_test (
 *     id    INT PRIMARY KEY,
 *     name  NVARCHAR(100) NOT NULL,
 *     score FLOAT,
 *     note  NVARCHAR(100) DEFAULT 'fallback'
 *   )
 */
class TDbMetaDataMssqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openMssql(): TDbConnection
	{
		$conn = PradoUnit::setupMssqlConnection('prado_unitest');
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
		$this->_conn = $this->openMssql();

		try {
			$this->_conn->createCommand(
				"IF OBJECT_ID('meta_test', 'U') IS NOT NULL DROP TABLE meta_test"
			)->execute();
		} catch (\Exception $e) {
		}
		try {
			$this->_conn->createCommand(
				"CREATE TABLE meta_test (id INT PRIMARY KEY, name NVARCHAR(100) NOT NULL, score FLOAT, note NVARCHAR(100) DEFAULT 'fallback')"
			)->execute();
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot create meta_test table: ' . $e->getMessage());
		}
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand(
					"IF OBJECT_ID('meta_test', 'U') IS NOT NULL DROP TABLE meta_test"
				)->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbMetaData::getInstance()
	// -----------------------------------------------------------------------

	public function testGetInstanceReturnsMssqlMetaData(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->assertInstanceOf(TMssqlMetaData::class, $meta);
	}

	// -----------------------------------------------------------------------
	// getTableInfo() — TDbTableInfo
	// -----------------------------------------------------------------------

	public function testGetTableInfoReturnsTableInfo(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	public function testGetTableInfoTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertSame('meta_test', $info->getTableName());
	}

	public function testGetTableInfoColumnNamesContainsAllColumns(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$names = $info->getColumnNames();
		$this->assertContains('[id]',    $names);
		$this->assertContains('[name]',  $names);
		$this->assertContains('[score]', $names);
		$this->assertContains('[note]',  $names);
		$this->assertCount(4, $names);
	}

	public function testGetTableInfoPrimaryKeys(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$pks = $info->getPrimaryKeys();
		$this->assertContains('id', $pks);
		$this->assertCount(1, $pks);
	}

	public function testGetTableInfoGetColumnReturnsColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertNotNull($col);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableColumn::class, $col);
	}

	public function testGetTableInfoGetColumnThrowsForMissingColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$info->getColumn('nonexistent_column');
	}

	public function testGetTableInfoCachingReturnsSameObject(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info1 = $meta->getTableInfo('meta_test');
		$info2 = $meta->getTableInfo('meta_test');
		$this->assertSame($info1, $info2);
	}

	public function testGetTableInfoThrowsForInvalidTable(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$meta->getTableInfo('nonexistent_table_xyz');
	}

	// -----------------------------------------------------------------------
	// TDbTableColumn — column metadata
	// -----------------------------------------------------------------------

	public function testPrimaryKeyColumnIsPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('id');
		$this->assertTrue($col->getIsPrimaryKey());
	}

	public function testNonPrimaryKeyColumnIsNotPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getIsPrimaryKey());
	}

	public function testPrimaryKeyColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('id');
		$this->assertStringContainsStringIgnoringCase('int', $col->getDbType());
	}

	public function testVarcharColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertStringContainsStringIgnoringCase('nvarchar', $col->getDbType());
	}

	public function testNotNullColumnDoesNotAllowNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getAllowNull());
	}

	public function testNullableColumnAllowsNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('score');
		$this->assertTrue($col->getAllowNull());
	}

	public function testColumnWithDefaultValueHasDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('note');
		$this->assertNotNull($col->getDefaultValue());
	}

	public function testColumnWithoutDefaultHasNullDefault(): void
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

	public function testFindTableNamesContainsMetaTest(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertContains('meta_test', $tables);
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
		$builder = $meta->createCommandBuilder('meta_test');
		$this->assertInstanceOf(TDbCommandBuilder::class, $builder);
	}

	// -----------------------------------------------------------------------
	// Quoting helpers
	// -----------------------------------------------------------------------

	public function testQuoteTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteTableName('foo');
		// SQL Server uses bracket quoting.
		$this->assertSame('[foo]', $quoted);
	}

	public function testQuoteColumnName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnName('bar');
		$this->assertSame('[bar]', $quoted);
	}

	public function testQuoteColumnAlias(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnAlias('baz');
		// TMssqlMetaData uses double-quotes for aliases.
		$this->assertSame('"baz"', $quoted);
	}
}
