<?php

namespace Prado\Test\Unit\Data\DbSpecific\SqlSrv\Common;

use Prado\Data\Common\SqlSrv\TSqlSrvCommandBuilder;
use Prado\Data\Common\SqlSrv\TSqlSrvMetaData;
use Prado\Data\Common\SqlSrv\TSqlSrvTableColumn;
use Prado\Data\Common\SqlSrv\TSqlSrvTableInfo;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * SQLite connection that emulates the SQL Server INFORMATION_SCHEMA catalog.
 *
 * INFORMATION_SCHEMA is an attached in-memory database. SQL fragments that
 * SQLite cannot evaluate are rewritten to fixture equivalents before the
 * command is created; the remaining catalog SQL runs unchanged.
 */
class SqlSrvCatalogConnection extends TDbConnection
{
	/** @var array<string, string> SQL Server fragment => SQLite fragment. */
	private array $_rewrites = [
		"columnproperty(object_id(c.table_schema + '.' + c.table_name), c.column_name,'IsIdentity')" => 'c.IS_IDENTITY',
		'DB_NAME()' => "'prado_unitest'",
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
	 * Creates the INFORMATION_SCHEMA catalog and fills it with the fixture schema.
	 *
	 * Catalog prado_unitest:
	 * - dbo.customers (id INT IDENTITY PK, name NVARCHAR(50) NOT NULL)
	 * - dbo.orders (order_id INT IDENTITY PK, customer_id INT FK customers.id,
	 *   total DECIMAL(10,2) NULL DEFAULT ((0)), note NVARCHAR(MAX) NULL, rowver TIMESTAMP)
	 * - dbo.order_view VIEW (order_id, total)
	 * - sales.archive (archive_id INT PK)
	 */
	public function buildCatalog(): void
	{
		$this->setActive(true);
		$pdo = $this->getPdoInstance();
		$pdo->exec("ATTACH DATABASE ':memory:' AS INFORMATION_SCHEMA");
		$pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_CATALOG TEXT, TABLE_SCHEMA TEXT, TABLE_NAME TEXT, TABLE_TYPE TEXT)');
		$pdo->exec('CREATE TABLE INFORMATION_SCHEMA.COLUMNS (TABLE_CATALOG TEXT, TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT, ORDINAL_POSITION INTEGER, COLUMN_DEFAULT TEXT, IS_NULLABLE TEXT, DATA_TYPE TEXT, CHARACTER_MAXIMUM_LENGTH INTEGER, NUMERIC_PRECISION INTEGER, NUMERIC_SCALE INTEGER, IS_IDENTITY TEXT)');
		$pdo->exec('CREATE TABLE INFORMATION_SCHEMA.KEY_COLUMN_USAGE (CONSTRAINT_CATALOG TEXT, CONSTRAINT_SCHEMA TEXT, CONSTRAINT_NAME TEXT, TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT, ORDINAL_POSITION INTEGER)');
		$pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLE_CONSTRAINTS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, CONSTRAINT_NAME TEXT, CONSTRAINT_TYPE TEXT)');
		$pdo->exec('CREATE TABLE INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS (CONSTRAINT_CATALOG TEXT, CONSTRAINT_SCHEMA TEXT, CONSTRAINT_NAME TEXT, UNIQUE_CONSTRAINT_CATALOG TEXT, UNIQUE_CONSTRAINT_SCHEMA TEXT, UNIQUE_CONSTRAINT_NAME TEXT)');

		$pdo->exec("INSERT INTO INFORMATION_SCHEMA.TABLES VALUES
			('prado_unitest', 'dbo', 'customers', 'BASE TABLE'),
			('prado_unitest', 'dbo', 'orders', 'BASE TABLE'),
			('prado_unitest', 'dbo', 'order_view', 'VIEW'),
			('prado_unitest', 'sales', 'archive', 'BASE TABLE')");
		$pdo->exec("INSERT INTO INFORMATION_SCHEMA.COLUMNS VALUES
			('prado_unitest', 'dbo', 'customers', 'id', 1, NULL, 'NO', 'int', NULL, 10, 0, '1'),
			('prado_unitest', 'dbo', 'customers', 'name', 2, NULL, 'NO', 'nvarchar', 50, NULL, NULL, '0'),
			('prado_unitest', 'dbo', 'orders', 'order_id', 1, NULL, 'NO', 'int', NULL, 10, 0, '1'),
			('prado_unitest', 'dbo', 'orders', 'customer_id', 2, NULL, 'NO', 'int', NULL, 10, 0, '0'),
			('prado_unitest', 'dbo', 'orders', 'total', 3, '((0))', 'YES', 'decimal', NULL, 10, 2, '0'),
			('prado_unitest', 'dbo', 'orders', 'note', 4, NULL, 'YES', 'nvarchar', -1, NULL, NULL, '0'),
			('prado_unitest', 'dbo', 'orders', 'rowver', 5, NULL, 'NO', 'timestamp', NULL, NULL, NULL, '0'),
			('prado_unitest', 'dbo', 'order_view', 'order_id', 1, NULL, 'NO', 'int', NULL, 10, 0, '0'),
			('prado_unitest', 'dbo', 'order_view', 'total', 2, NULL, 'YES', 'decimal', NULL, 10, 2, '0'),
			('prado_unitest', 'sales', 'archive', 'archive_id', 1, NULL, 'NO', 'int', NULL, 10, 0, '0')");
		$pdo->exec("INSERT INTO INFORMATION_SCHEMA.TABLE_CONSTRAINTS VALUES
			('dbo', 'customers', 'PK_customers', 'PRIMARY KEY'),
			('dbo', 'orders', 'PK_orders', 'PRIMARY KEY'),
			('dbo', 'orders', 'FK_orders_customers', 'FOREIGN KEY'),
			('sales', 'archive', 'PK_archive', 'PRIMARY KEY')");
		$pdo->exec("INSERT INTO INFORMATION_SCHEMA.KEY_COLUMN_USAGE VALUES
			('prado_unitest', 'dbo', 'PK_customers', 'dbo', 'customers', 'id', 1),
			('prado_unitest', 'dbo', 'PK_orders', 'dbo', 'orders', 'order_id', 1),
			('prado_unitest', 'dbo', 'FK_orders_customers', 'dbo', 'orders', 'customer_id', 1),
			('prado_unitest', 'sales', 'PK_archive', 'sales', 'archive', 'archive_id', 1)");
		$pdo->exec("INSERT INTO INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS VALUES
			('prado_unitest', 'dbo', 'FK_orders_customers', 'prado_unitest', 'dbo', 'PK_customers')");
	}
}

/**
 * Server-less tests for TSqlSrvMetaData.
 *
 * The catalog queries run against {@see SqlSrvCatalogConnection}, an in-memory
 * SQLite database shaped like the SQL Server INFORMATION_SCHEMA views.
 */
class TSqlSrvMetaDataTest extends \PHPUnit\Framework\TestCase
{
	private SqlSrvCatalogConnection $_conn;
	private TSqlSrvMetaData $_meta;

	protected function setUp(): void
	{
		$this->_conn = new SqlSrvCatalogConnection();
		$this->_conn->buildCatalog();
		$this->_meta = new TSqlSrvMetaData($this->_conn);
	}

	protected function tearDown(): void
	{
		$this->_conn->setActive(false);
	}

	public function test_table_info_class(): void
	{
		$this->assertSame(TSqlSrvTableInfo::class, PradoUnit::invoke($this->_meta, 'getTableInfoClass'));
		$this->assertInstanceOf(TSqlSrvTableInfo::class, $this->_meta->getTableInfo());
	}

	public function test_quoting(): void
	{
		$this->assertSame('[accounts]', $this->_meta->quoteTableName('accounts'));
		$this->assertSame('[dbo].[accounts]', $this->_meta->quoteTableName('[dbo].accounts'));
		$this->assertSame('[user_name]', $this->_meta->quoteColumnName('"user_name"'));
		$this->assertSame('"total sum"', $this->_meta->quoteColumnAlias('[total sum]'));
	}

	public function test_catalog_schema_table_name(): void
	{
		$this->assertSame([null, null, 'orders'], PradoUnit::invoke($this->_meta, 'getCatalogSchemaTableName', 'orders'));
		$this->assertSame([null, 'dbo', 'orders'], PradoUnit::invoke($this->_meta, 'getCatalogSchemaTableName', '[dbo].[orders]'));
		$this->assertSame(['prado_unitest', 'dbo', 'orders'], PradoUnit::invoke($this->_meta, 'getCatalogSchemaTableName', '"prado_unitest"."dbo"."orders"'));
		$this->assertSame(['a', 'b', 'c'], PradoUnit::invoke($this->_meta, 'getCatalogSchemaTableName', 'a.b.c.d'));
	}

	public function test_table_info_unqualified(): void
	{
		$info = $this->_meta->getTableInfo('orders');

		$this->assertInstanceOf(TSqlSrvTableInfo::class, $info);
		$this->assertSame('prado_unitest', $info->getCatalogName());
		$this->assertSame('dbo', $info->getSchemaName());
		$this->assertSame('orders', $info->getTableName());
		$this->assertSame('[prado_unitest].[dbo].[orders]', $info->getTableFullName());
		$this->assertFalse($info->getIsView());
		$this->assertSame(['order_id'], $info->getPrimaryKeys());
		$this->assertSame([['keys' => ['customer_id' => 'id'], 'table' => 'customers']], $info->getForeignKeys());
		$indexes = [];
		foreach ($info->getColumns() as $name => $column) {
			$indexes[$name] = $column->getColumnIndex();
		}
		asort($indexes);
		$this->assertSame(['order_id' => 0, 'customer_id' => 1, 'total' => 2, 'note' => 3, 'rowver' => 4], $indexes);
		$this->assertStringNotContainsString(':schema', $this->_conn->commands[0]);
		$this->assertStringNotContainsString(':catalog', $this->_conn->commands[0]);
	}

	public function test_table_info_columns(): void
	{
		$info = $this->_meta->getTableInfo('orders');

		$id = $info->getColumn('order_id');
		$this->assertInstanceOf(TSqlSrvTableColumn::class, $id);
		$this->assertSame('[order_id]', $id->getColumnName());
		$this->assertSame('order_id', $id->getColumnId());
		$this->assertSame(0, $id->getColumnIndex());
		$this->assertFalse($id->getAllowNull());
		$this->assertTrue($id->getIsPrimaryKey());
		$this->assertFalse($id->getIsForeignKey());
		$this->assertTrue($id->getAutoIncrement());
		$this->assertTrue($id->hasSequence());
		$this->assertSame('int', $id->getDbType());
		$this->assertNull($id->getColumnSize());
		$this->assertSame(10, $id->getNumericPrecision());
		$this->assertSame(0, $id->getNumericScale());

		$fk = $info->getColumn('customer_id');
		$this->assertSame(1, $fk->getColumnIndex());
		$this->assertFalse($fk->getIsPrimaryKey());
		$this->assertTrue($fk->getIsForeignKey());
		$this->assertFalse($fk->getAutoIncrement());

		$total = $info->getColumn('total');
		$this->assertTrue($total->getAllowNull());
		$this->assertSame('((0))', $total->getDefaultValue());
		$this->assertSame('decimal', $total->getDbType());
		$this->assertSame(10, $total->getNumericPrecision());
		$this->assertSame(2, $total->getNumericScale());
		$this->assertSame('string', $total->getPHPType());

		$note = $info->getColumn('note');
		$this->assertSame(-1, $note->getColumnSize());
		$this->assertNull($note->getNumericPrecision());
		$this->assertNull($note->getNumericScale());
		$this->assertFalse($note->getIsExcluded());

		$this->assertTrue($info->getColumn('rowver')->getIsExcluded());
	}

	public function test_table_info_schema_qualified(): void
	{
		$info = $this->_meta->getTableInfo('[dbo].[customers]');

		$this->assertSame('customers', $info->getTableName());
		$this->assertSame(['id'], $info->getPrimaryKeys());
		$this->assertSame([], $info->getForeignKeys());
		$this->assertSame(['id', 'name'], $info->getColumns()->getKeys());
		$this->assertSame(50, $info->getColumn('name')->getColumnSize());
		$this->assertStringContainsString(' AND t.table_schema = :schema', $this->_conn->commands[0]);
		$this->assertStringNotContainsString('t.table_catalog = :catalog', $this->_conn->commands[0]);
	}

	public function test_table_info_catalog_qualified(): void
	{
		$info = $this->_meta->getTableInfo('prado_unitest.sales.archive');

		$this->assertSame('prado_unitest', $info->getCatalogName());
		$this->assertSame('sales', $info->getSchemaName());
		$this->assertSame(['archive_id'], $info->getPrimaryKeys());
		$this->assertSame(['archive_id'], $info->getColumns()->getKeys());
		$this->assertStringContainsString(' AND t.table_schema = :schema AND t.table_catalog = :catalog', $this->_conn->commands[0]);
	}

	public function test_table_info_view(): void
	{
		$info = $this->_meta->getTableInfo('order_view');

		$this->assertTrue($info->getIsView());
		$this->assertSame([], $info->getPrimaryKeys());
		$this->assertSame(['order_id', 'total'], $info->getColumns()->getKeys());
		$this->assertFalse($info->getColumn('order_id')->getAutoIncrement());
	}

	public function test_table_info_wrong_catalog_throws(): void
	{
		$this->expectException(TDbException::class);
		$this->_meta->getTableInfo('other_db.dbo.orders');
	}

	public function test_table_info_missing_table_throws(): void
	{
		$this->expectException(TDbException::class);
		$this->_meta->getTableInfo('no_such_table');
	}

	public function test_is_foreign_key_column(): void
	{
		$info = new TSqlSrvTableInfo([], [], [['keys' => ['a' => 'x', 'b' => 'y'], 'table' => 't1'], ['keys' => ['c' => 'z'], 'table' => 't2']]);

		$this->assertTrue(PradoUnit::invoke($this->_meta, 'isForeignKeyColumn', 'b', $info));
		$this->assertTrue(PradoUnit::invoke($this->_meta, 'isForeignKeyColumn', 'c', $info));
		$this->assertFalse(PradoUnit::invoke($this->_meta, 'isForeignKeyColumn', 'x', $info));
	}

	public function test_create_command_builder(): void
	{
		$builder = $this->_meta->createCommandBuilder('orders');

		$this->assertInstanceOf(TSqlSrvCommandBuilder::class, $builder);
		$this->assertSame($this->_conn, $builder->getDbConnection());
		$this->assertSame($this->_meta->getTableInfo('orders'), $builder->getTableInfo());
	}

	public function test_find_table_names_default_schema(): void
	{
		$this->assertSame(['customers', 'orders'], $this->_meta->findTableNames());
		$this->assertSame(['customers', 'orders'], $this->_meta->findTableNames(TSqlSrvMetaData::DEFAULT_SCHEMA));
	}

	public function test_find_table_names_other_schema(): void
	{
		$this->assertSame([], $this->_meta->findTableNames('nobody'));
		$this->assertSame(['sales.archive'], $this->_meta->findTableNames('sales'));
	}

	public function test_table_info_same_name_in_two_schemas(): void
	{
		$this->_conn->getPdoInstance()->exec("INSERT INTO INFORMATION_SCHEMA.TABLES VALUES ('prado_unitest', 'dbo', 'archive', 'BASE TABLE')");
		$this->_conn->getPdoInstance()->exec("INSERT INTO INFORMATION_SCHEMA.COLUMNS VALUES ('prado_unitest', 'dbo', 'archive', 'legacy_id', 1, NULL, 'NO', 'int', NULL, 10, 0, '0')");

		$this->assertSame(['archive_id'], $this->_meta->getTableInfo('sales.archive')->getColumns()->getKeys());
	}
}
