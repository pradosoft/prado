<?php

namespace Prado\Test\Unit\Data\DbSpecific\Ibm\Common;

use Prado\Data\Common\Ibm\TIbmCommandBuilder;
use Prado\Data\Common\Ibm\TIbmMetaData;
use Prado\Data\Common\Ibm\TIbmTableColumn;
use Prado\Data\Common\Ibm\TIbmTableInfo;
use Prado\Data\Common\TDbTableColumn;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * SQLite connection that emulates the DB2 SYSCAT catalog views.
 *
 * SYSCAT is an attached in-memory database. DB2 SQL fragments that SQLite
 * cannot parse are rewritten to SQLite equivalents before the command is
 * created; the remaining catalog SQL runs unchanged.
 */
class IbmCatalogConnection extends TDbConnection
{
	/** @var array<string, string> DB2 fragment => SQLite fragment. */
	private array $_rewrites = [
		'c.DEFAULT' => 'c."DEFAULT"',
		'SYSCAT.REFERENCES' => 'SYSCAT."REFERENCES"',
		'VALUES CURRENT SCHEMA' => "VALUES (' db2inst1 ')",
	];

	/** @var string[] SQL text of every command created, in order. */
	public array $commands = [];

	public function __construct()
	{
		parent::__construct('sqlite::memory:');
	}

	public function createCommand($sql)
	{
		$this->commands[] = $sql;
		return parent::createCommand(strtr($sql, $this->_rewrites));
	}

	/**
	 * Creates the SYSCAT catalog and fills it with the fixture schema.
	 *
	 * - DB2INST1.CUSTOMERS (ID INTEGER IDENTITY PK, NAME VARCHAR(50) NOT NULL)
	 * - DB2INST1.ORDERS (ORDER_ID INTEGER IDENTITY PK, CUSTOMER_ID INTEGER FK CUSTOMERS.ID,
	 *   TOTAL DECIMAL(10,2) NULL DEFAULT 0, RATE DOUBLE, NOTE CLOB NULL DEFAULT '')
	 * - DB2INST1.ORDER_VIEW VIEW (ORDER_ID)
	 * - SALES.ARCHIVE (ARCHIVE_ID BIGINT)
	 */
	public function buildCatalog(): void
	{
		$this->setActive(true);
		$pdo = $this->getPdoInstance();
		$pdo->exec("ATTACH DATABASE ':memory:' AS SYSCAT");
		$pdo->exec('CREATE TABLE SYSCAT.TABLES (TABSCHEMA TEXT, TABNAME TEXT, TYPE TEXT)');
		$pdo->exec('CREATE TABLE SYSCAT.COLUMNS (TABSCHEMA TEXT, TABNAME TEXT, COLNO INTEGER, COLNAME TEXT, TYPENAME TEXT, LENGTH INTEGER, SCALE INTEGER, NULLS TEXT, "DEFAULT" TEXT, IDENTITY TEXT, KEYSEQ INTEGER)');
		$pdo->exec('CREATE TABLE SYSCAT.TABCONST (CONSTNAME TEXT, TABSCHEMA TEXT, TABNAME TEXT, TYPE TEXT)');
		$pdo->exec('CREATE TABLE SYSCAT.KEYCOLUSE (CONSTNAME TEXT, TABSCHEMA TEXT, TABNAME TEXT, COLNAME TEXT, COLSEQ INTEGER)');
		$pdo->exec('CREATE TABLE SYSCAT."REFERENCES" (CONSTNAME TEXT, TABSCHEMA TEXT, TABNAME TEXT, REFKEYNAME TEXT, REFTABSCHEMA TEXT, REFTABNAME TEXT)');

		$pdo->exec("INSERT INTO SYSCAT.TABLES VALUES
			('DB2INST1', 'ORDERS', 'T'),
			('DB2INST1', 'CUSTOMERS', 'T'),
			('DB2INST1', 'ORDER_VIEW', 'V'),
			('SALES', 'ARCHIVE', 'T')");
		$pdo->exec("INSERT INTO SYSCAT.COLUMNS VALUES
			('DB2INST1', 'CUSTOMERS', 0, 'ID', 'INTEGER', 4, 0, 'N', NULL, 'Y', 1),
			('DB2INST1', 'CUSTOMERS', 1, 'NAME', 'VARCHAR', 50, 0, 'N', NULL, 'N', NULL),
			('DB2INST1', 'ORDERS', 0, 'ORDER_ID', 'INTEGER', 4, 0, 'N', NULL, 'Y ', 1),
			('DB2INST1', 'ORDERS', 1, 'CUSTOMER_ID', 'INTEGER', 4, 0, 'N', NULL, 'N', NULL),
			('DB2INST1', 'ORDERS', 2, 'TOTAL', 'DECIMAL', 10, 2, 'Y', '0', 'N', NULL),
			('DB2INST1', 'ORDERS', 3, 'RATE', 'DOUBLE', 8, 0, 'Y', NULL, 'N', NULL),
			('DB2INST1', 'ORDERS', 4, 'NOTE', 'CLOB', 0, 0, 'Y', '', 'N', NULL),
			('DB2INST1', 'ORDER_VIEW', 0, 'ORDER_ID', 'INTEGER', 4, 0, 'N', NULL, 'N', NULL),
			('SALES', 'ARCHIVE', 0, 'ARCHIVE_ID', 'BIGINT', 8, 0, 'N', NULL, 'N', NULL)");
		$pdo->exec("INSERT INTO SYSCAT.TABCONST VALUES
			('PK_CUSTOMERS', 'DB2INST1', 'CUSTOMERS', 'P'),
			('PK_ORDERS', 'DB2INST1', 'ORDERS', 'P'),
			('FK_ORDERS_CUSTOMERS', 'DB2INST1', 'ORDERS', 'F')");
		$pdo->exec("INSERT INTO SYSCAT.KEYCOLUSE VALUES
			('PK_CUSTOMERS', 'DB2INST1', 'CUSTOMERS', 'ID', 1),
			('PK_ORDERS', 'DB2INST1', 'ORDERS', 'ORDER_ID', 1),
			('FK_ORDERS_CUSTOMERS', 'DB2INST1', 'ORDERS', 'CUSTOMER_ID', 1)");
		$pdo->exec("INSERT INTO SYSCAT.\"REFERENCES\" VALUES
			('FK_ORDERS_CUSTOMERS', 'DB2INST1', 'ORDERS', 'PK_CUSTOMERS', 'DB2INST1', 'CUSTOMERS')");
	}
}

/**
 * Server-less tests for TIbmMetaData.
 *
 * The catalog queries run against {@see IbmCatalogConnection}, an in-memory
 * SQLite database shaped like the DB2 SYSCAT views.
 */
class TIbmMetaDataTest extends \PHPUnit\Framework\TestCase
{
	private IbmCatalogConnection $_conn;
	private TIbmMetaData $_meta;

	protected function setUp(): void
	{
		$this->_conn = new IbmCatalogConnection();
		$this->_conn->buildCatalog();
		$this->_meta = new TIbmMetaData($this->_conn);
	}

	protected function tearDown(): void
	{
		$this->_conn->setActive(false);
	}

	public function test_table_info_class(): void
	{
		$this->assertSame(TIbmTableInfo::class, PradoUnit::invoke($this->_meta, 'getTableInfoClass'));
		$this->assertInstanceOf(TIbmTableInfo::class, $this->_meta->getTableInfo());
	}

	public function test_quoting(): void
	{
		$this->assertSame('"accounts"', $this->_meta->quoteTableName('accounts'));
		$this->assertSame('"db2inst1"."accounts"', $this->_meta->quoteTableName('[db2inst1].accounts'));
		$this->assertSame('"user_name"', $this->_meta->quoteColumnName('`user_name`'));
		$this->assertSame('"total sum"', $this->_meta->quoteColumnAlias('[total sum]'));
	}

	public function test_default_schema_from_current_schema(): void
	{
		$this->assertSame('DB2INST1', $this->_meta->getDefaultSchema());
		$this->assertSame('DB2INST1', $this->_meta->getDefaultSchema());
		$this->assertSame(['VALUES CURRENT SCHEMA'], $this->_conn->commands);
	}

	public function test_default_schema_set(): void
	{
		$this->_meta->setDefaultSchema('sales');

		$this->assertSame('SALES', $this->_meta->getDefaultSchema());
		$this->assertSame([], $this->_conn->commands);
	}

	public function test_schema_table_name(): void
	{
		$this->assertSame(['SALES', 'ARCHIVE'], PradoUnit::invoke($this->_meta, 'getSchemaTableName', '"sales"."archive"'));
		$this->assertSame(['DB2INST1', 'ORDERS'], PradoUnit::invoke($this->_meta, 'getSchemaTableName', 'orders'));
	}

	public function test_assert_identifier(): void
	{
		$this->assertSame('ORDERS', PradoUnit::invoke($this->_meta, 'assertIdentifier', 'ORDERS'));

		$this->expectException(TDbException::class);
		PradoUnit::invoke($this->_meta, 'assertIdentifier', 'OR"DERS');
	}

	public function test_is_precision_type(): void
	{
		foreach (['decimal', 'NUMERIC', ' decfloat ', 'float', 'Double', 'real'] as $type) {
			$this->assertTrue(PradoUnit::invoke($this->_meta, 'isPrecisionType', $type), $type);
		}
		foreach (['integer', 'varchar', 'bigint', 'clob', 'timestamp'] as $type) {
			$this->assertFalse(PradoUnit::invoke($this->_meta, 'isPrecisionType', $type), $type);
		}
	}

	public function test_is_view(): void
	{
		$this->assertTrue(PradoUnit::invoke($this->_meta, 'getIsView', 'DB2INST1', 'ORDER_VIEW'));
		$this->assertFalse(PradoUnit::invoke($this->_meta, 'getIsView', 'DB2INST1', 'ORDERS'));
		$this->assertFalse(PradoUnit::invoke($this->_meta, 'getIsView', 'DB2INST1', 'MISSING'));
	}

	public function test_constraint_keys(): void
	{
		$this->assertSame(
			[['order_id'], [['keys' => ['customer_id' => 'id'], 'table' => 'customers']]],
			PradoUnit::invoke($this->_meta, 'getConstraintKeys', 'DB2INST1', 'ORDERS')
		);
		$this->assertSame([['id'], []], PradoUnit::invoke($this->_meta, 'getConstraintKeys', 'DB2INST1', 'CUSTOMERS'));
		$this->assertSame([[], []], PradoUnit::invoke($this->_meta, 'getConstraintKeys', 'SALES', 'ARCHIVE'));
	}

	public function test_table_info_unqualified(): void
	{
		$info = $this->_meta->getTableInfo('orders');

		$this->assertInstanceOf(TIbmTableInfo::class, $info);
		$this->assertSame('DB2INST1', $info->getSchemaName());
		$this->assertSame('ORDERS', $info->getTableName());
		$this->assertSame('"DB2INST1"."ORDERS"', $info->getTableFullName());
		$this->assertFalse($info->getIsView());
		$this->assertSame(['order_id'], $info->getPrimaryKeys());
		$this->assertSame([['keys' => ['customer_id' => 'id'], 'table' => 'customers']], $info->getForeignKeys());
		$this->assertSame(['order_id', 'customer_id', 'total', 'rate', 'note'], $info->getColumns()->getKeys());
	}

	public function test_table_info_columns(): void
	{
		$info = $this->_meta->getTableInfo('DB2INST1.ORDERS');

		$id = $info->getColumn('order_id');
		$this->assertInstanceOf(TIbmTableColumn::class, $id);
		$this->assertSame('"ORDER_ID"', $id->getColumnName());
		$this->assertSame('order_id', $id->getColumnId());
		$this->assertSame(0, $id->getColumnIndex());
		$this->assertFalse($id->getAllowNull());
		$this->assertTrue($id->getAutoIncrement());
		$this->assertTrue($id->hasSequence());
		$this->assertTrue($id->getIsPrimaryKey());
		$this->assertFalse($id->getIsForeignKey());
		$this->assertSame('integer', $id->getDbType());
		$this->assertSame('integer', $id->getPHPType());
		$this->assertSame(4, $id->getColumnSize());
		$this->assertNull($id->getNumericPrecision());
		$this->assertSame(TDbTableColumn::UNDEFINED_VALUE, $id->getDefaultValue());

		$fk = $info->getColumn('customer_id');
		$this->assertSame(1, $fk->getColumnIndex());
		$this->assertTrue($fk->getIsForeignKey());
		$this->assertFalse($fk->getIsPrimaryKey());
		$this->assertFalse($fk->getAutoIncrement());

		$total = $info->getColumn('total');
		$this->assertTrue($total->getAllowNull());
		$this->assertSame('0', $total->getDefaultValue());
		$this->assertSame('decimal', $total->getDbType());
		$this->assertSame('float', $total->getPHPType());
		$this->assertSame(10, $total->getNumericPrecision());
		$this->assertSame(2, $total->getNumericScale());
		$this->assertNull($total->getColumnSize());

		$rate = $info->getColumn('rate');
		$this->assertSame(8, $rate->getNumericPrecision());
		$this->assertNull($rate->getNumericScale());

		$note = $info->getColumn('note');
		$this->assertSame('clob', $note->getDbType());
		$this->assertSame('string', $note->getPHPType());
		$this->assertSame(TDbTableColumn::UNDEFINED_VALUE, $note->getDefaultValue());
		$this->assertNull($note->getColumnSize());
	}

	public function test_table_info_view(): void
	{
		$info = $this->_meta->getTableInfo('order_view');

		$this->assertTrue($info->getIsView());
		$this->assertSame([], $info->getPrimaryKeys());
		$this->assertSame(['order_id'], $info->getColumns()->getKeys());
	}

	public function test_table_info_other_schema(): void
	{
		$info = $this->_meta->getTableInfo('"sales"."archive"');

		$this->assertSame('SALES', $info->getSchemaName());
		$this->assertSame('"SALES"."ARCHIVE"', $info->getTableFullName());
		$this->assertSame('integer', $info->getColumn('archive_id')->getPHPType());
		$this->assertNotContains('VALUES CURRENT SCHEMA', $this->_conn->commands);
	}

	public function test_table_info_missing_table_throws(): void
	{
		$this->expectException(TDbException::class);
		$this->_meta->getTableInfo('no_such_table');
	}

	public function test_is_foreign_key_column(): void
	{
		$info = new TIbmTableInfo([], [], [['keys' => ['a' => 'x', 'b' => 'y'], 'table' => 't1'], ['keys' => ['c' => 'z'], 'table' => 't2']]);

		$this->assertTrue(PradoUnit::invoke($this->_meta, 'isForeignKeyColumn', 'b', $info));
		$this->assertTrue(PradoUnit::invoke($this->_meta, 'isForeignKeyColumn', 'c', $info));
		$this->assertFalse(PradoUnit::invoke($this->_meta, 'isForeignKeyColumn', 'x', $info));
	}

	public function test_create_command_builder(): void
	{
		$builder = $this->_meta->createCommandBuilder('orders');

		$this->assertInstanceOf(TIbmCommandBuilder::class, $builder);
		$this->assertSame($this->_conn, $builder->getDbConnection());
		$this->assertSame($this->_meta->getTableInfo('orders'), $builder->getTableInfo());
	}

	public function test_find_table_names_default_schema(): void
	{
		$this->assertSame(['customers', 'orders'], $this->_meta->findTableNames());
		$this->assertSame(['customers', 'orders'], $this->_meta->findTableNames('db2inst1'));
	}

	public function test_find_table_names_other_schema(): void
	{
		$this->assertSame(['sales.archive'], $this->_meta->findTableNames('Sales'));
		$this->assertSame([], $this->_meta->findTableNames('NOBODY'));
	}
}
