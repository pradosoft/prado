<?php

namespace Prado\Test\Unit\Data\DbSpecific\Pgsql\Common;

use Prado\Data\Common\Pgsql\TPgsqlMetaData;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\TApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\TApplicationMode;

/**
 * Live integration tests for TDbMetaData — PostgreSQL.
 *
 * Verifies schema introspection (getTableInfo, findTableNames, quoting) and
 * the TDbTableInfo / TDbTableColumn API against a real PostgreSQL database.
 *
 * Table schema used throughout:
 *   meta_test (
 *     id    SERIAL PRIMARY KEY,
 *     name  VARCHAR(100) NOT NULL,
 *     score DOUBLE PRECISION,
 *     note  VARCHAR(100) DEFAULT 'fallback'
 *   )
 */
class TDbMetaDataPgsqlIntegrationTest extends \PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openPgsql(): TDbConnection
	{
		$conn = PradoUnit::setupPgsqlConnection('prado_unitest');
		if (is_array($conn)) {
			PradoUnit::failFirstThenSkip($this, $conn[0], null, $conn[1]);
		}
		return $conn;
	}

	protected function setUp(): void
	{
		static $booted = false;
		if (!$booted) {
			(new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP))->setMode(TApplicationMode::Debug);
			$booted = true;
		}
		$this->_conn = $this->openPgsql();
		$this->_conn->createCommand('DROP TABLE IF EXISTS meta_test')->execute();
		$this->_conn->createCommand(
			"CREATE TABLE meta_test (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, score DOUBLE PRECISION, note VARCHAR(100) DEFAULT 'fallback')"
		)->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand('DROP TABLE IF EXISTS meta_test')->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbMetaData::getInstance()
	// -----------------------------------------------------------------------

	public function testPgsqlGetInstanceReturnsPgsqlMetaData(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->assertInstanceOf(TPgsqlMetaData::class, $meta);
	}

	// -----------------------------------------------------------------------
	// getTableInfo() — TDbTableInfo
	// -----------------------------------------------------------------------

	public function testPgsqlGetTableInfoReturnsTableInfo(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	public function testPgsqlGetTableInfoTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertSame('meta_test', $info->getTableName());
	}

	public function testPgsqlGetTableInfoColumnNamesContainsAllColumns(): void
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

	public function testPgsqlGetTableInfoPrimaryKeys(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$pks = $info->getPrimaryKeys();
		$this->assertContains('id', $pks);
		$this->assertCount(1, $pks);
	}

	public function testPgsqlGetTableInfoGetColumnReturnsColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertNotNull($col);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableColumn::class, $col);
	}

	public function testPgsqlGetTableInfoGetColumnThrowsForMissingColumn(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$info->getColumn('nonexistent_column');
	}

	public function testPgsqlGetTableInfoCachingReturnsSameObject(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info1 = $meta->getTableInfo('meta_test');
		$info2 = $meta->getTableInfo('meta_test');
		$this->assertSame($info1, $info2);
	}

	public function testPgsqlGetTableInfoThrowsForInvalidTable(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$meta->getTableInfo('nonexistent_table_xyz');
	}

	// -----------------------------------------------------------------------
	// TDbTableColumn — column metadata
	// -----------------------------------------------------------------------

	public function testPgsqlPrimaryKeyColumnIsPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('id');
		$this->assertTrue($col->getIsPrimaryKey());
	}

	public function testPgsqlNonPrimaryKeyColumnIsNotPrimaryKey(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getIsPrimaryKey());
	}

	public function testPgsqlPrimaryKeyColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('id');
		// PostgreSQL SERIAL resolves to int4 / integer in the catalog.
		$this->assertStringContainsStringIgnoringCase('int', $col->getDbType());
	}

	public function testPgsqlVarcharColumnDbType(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		// PostgreSQL may report 'character varying' or 'varchar'.
		$this->assertMatchesRegularExpression('/varchar|character varying/i', $col->getDbType());
	}

	public function testPgsqlNotNullColumnDoesNotAllowNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('name');
		$this->assertFalse($col->getAllowNull());
	}

	public function testPgsqlNullableColumnAllowsNull(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('score');
		$this->assertTrue($col->getAllowNull());
	}

	public function testPgsqlColumnWithDefaultValueHasDefault(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$col = $info->getColumn('note');
		$this->assertNotNull($col->getDefaultValue());
	}

	public function testPgsqlColumnWithoutDefaultHasNullDefault(): void
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

	public function testPgsqlFindTableNamesContainsMetaTest(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertContains('meta_test', $tables);
	}

	public function testPgsqlFindTableNamesReturnsArray(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$tables = $meta->findTableNames();
		$this->assertIsArray($tables);
	}

	// -----------------------------------------------------------------------
	// createCommandBuilder()
	// -----------------------------------------------------------------------

	public function testPgsqlCreateCommandBuilderReturnsBuilder(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$builder = $meta->createCommandBuilder('meta_test');
		$this->assertInstanceOf(TDbCommandBuilder::class, $builder);
	}

	// -----------------------------------------------------------------------
	// Quoting helpers
	// -----------------------------------------------------------------------

	public function testPgsqlQuoteTableName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteTableName('foo');
		// PostgreSQL uses double-quote quoting.
		$this->assertSame('"foo"', $quoted);
	}

	public function testPgsqlQuoteColumnName(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnName('bar');
		$this->assertSame('"bar"', $quoted);
	}

	public function testPgsqlQuoteColumnAlias(): void
	{
		$meta = TDbMetaData::getInstance($this->_conn);
		$quoted = $meta->quoteColumnAlias('baz');
		$this->assertSame('"baz"', $quoted);
	}

	// -----------------------------------------------------------------------
	// Views, identifiers, schemas, sequences, and foreign key definitions
	// -----------------------------------------------------------------------

	public function testPgsqlGetTableInfoOfAViewIsAView(): void
	{
		$this->_conn->createCommand('CREATE VIEW cov_meta_view AS SELECT id, name FROM meta_test')->execute();
		try {
			$meta = new TPgsqlMetaData($this->_conn);
			$this->assertTrue($meta->getTableInfo('cov_meta_view')->getIsView());
			$this->assertFalse($meta->getTableInfo('meta_test')->getIsView());
		} finally {
			$this->_conn->createCommand('DROP VIEW IF EXISTS cov_meta_view')->execute();
		}
	}

	public function testPgsqlAssertIdentifierRejectsADoubleQuote(): void
	{
		$meta = new TPgsqlMetaData($this->_conn);
		$this->assertSame('meta_test', PradoUnit::invoke($meta, 'assertIdentifier', 'meta_test'));
		try {
			PradoUnit::invoke($meta, 'assertIdentifier', 'bad"name');
			$this->fail('Expected TDbException for an identifier with a double quote');
		} catch (TDbException $e) {
			$this->assertSame('dbcommon_invalid_identifier_name', $e->getErrorCode());
		}
	}

	public function testPgsqlFindTableNamesOfTheDefaultSchemaAreUnprefixed(): void
	{
		$meta = new TPgsqlMetaData($this->_conn);
		$this->assertContains('information_schema.sql_features', $meta->findTableNames('information_schema'));

		$meta->setDefaultSchema('information_schema');
		$this->assertSame('information_schema', $meta->getDefaultSchema());
		$names = $meta->findTableNames('');
		$this->assertContains('sql_features', $names);
		$this->assertNotContains('meta_test', $names);
	}

	public function testPgsqlSequenceNameFromAColumnDefault(): void
	{
		$meta = new TPgsqlMetaData($this->_conn);
		$info = $meta->getTableInfo('meta_test');
		$this->assertSame('other.cov_seq', PradoUnit::invoke($meta, 'getSequenceName', $info, "nextval('other.cov_seq'::regclass)"));
		$this->assertSame('public.cov_seq', PradoUnit::invoke($meta, 'getSequenceName', $info, "nextval('cov_seq'::regclass)"));
		$this->assertNull(PradoUnit::invoke($meta, 'getSequenceName', $info, '42'));
	}

	public function testPgsqlForeignKeysFromAConstraintDefinition(): void
	{
		$meta = new TPgsqlMetaData($this->_conn);
		$this->assertSame(
			['table' => 'public.parent', 'keys' => ['a' => 'x', 'b' => 'y']],
			PradoUnit::invoke($meta, 'getForeignKeys', 'FOREIGN KEY (a, b) REFERENCES "public"."parent"(x, y)')
		);
		$this->assertNull(PradoUnit::invoke($meta, 'getForeignKeys', 'CHECK (score > 0)'));
	}
}
