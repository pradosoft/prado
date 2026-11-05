<?php

namespace Prado\Test\Unit\Data\DbSpecific\Oracle\Common;

use Prado\Data\Common\Oracle\TOracleCommandBuilder;
use Prado\Data\Common\Oracle\TOracleMetaData;
use Prado\Data\Common\Oracle\TOracleTableColumn;
use Prado\Data\Common\Oracle\TOracleTableInfo;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * TOracleMetaData over a connection double that answers the Oracle catalog
 * queries (ALL_TAB_COLUMNS, ALL_OBJECTS, ALL_CONSTRAINTS, USER_TABLES) with
 * canned rows, so the row processing runs without an Oracle server.
 */
class TOracleMetaDataTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Canned `ALL_TAB_COLUMNS` rows in the shape of the metadata query.
	 * @return array[]
	 */
	protected function columnRows(): array
	{
		return [
			['column_id' => 1, 'attname' => 'ID', 'type' => 'NUMBER(10,0)', 'atttypmod' => 22, 'attnotnull' => '1', 'atthasdef' => '0', 'adsrc' => null, 'attisserial' => '0'],
			['column_id' => 2, 'attname' => 'name', 'type' => 'VARCHAR2', 'atttypmod' => 45, 'attnotnull' => '1', 'atthasdef' => '1', 'adsrc' => "'anon'", 'attisserial' => '0'],
			['column_id' => 3, 'attname' => 'dept_id', 'type' => 'NUMBER(,)', 'atttypmod' => 22, 'attnotnull' => '0', 'atthasdef' => '0', 'adsrc' => null, 'attisserial' => '0'],
			['column_id' => 4, 'attname' => 'ratio', 'type' => 'NUMBER(5,)', 'atttypmod' => 0, 'attnotnull' => '0', 'atthasdef' => '0', 'adsrc' => null, 'attisserial' => '0'],
			['column_id' => 5, 'attname' => 'stamp', 'type' => 'TIMESTAMP(6)', 'atttypmod' => 11, 'attnotnull' => '0', 'atthasdef' => '0', 'adsrc' => null, 'attisserial' => '0'],
			['column_id' => 6, 'attname' => 'body', 'type' => 'CLOB', 'atttypmod' => 4000, 'attnotnull' => '0', 'atthasdef' => '0', 'adsrc' => null, 'attisserial' => '0'],
		];
	}

	/**
	 * @param array $catalog map of SQL fragment to canned rows; see {@see OracleCatalogConnection}.
	 * @return array{0: TOracleMetaData, 1: OracleCatalogConnection}
	 */
	protected function makeMetaData(array $catalog): array
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$conn = new OracleCatalogConnection($catalog);
		$conn->setActive(true);
		return [new TOracleMetaData($conn), $conn];
	}

	protected function tableCatalog(string $objectType = 'TABLE'): array
	{
		return [
			'ALL_TAB_COLUMNS' => $this->columnRows(),
			'select OBJECT_TYPE' => [['object_type' => $objectType]],
			'ALL_CONSTRAINTS' => [
				['consrc' => 'ID', 'contype' => 'P'],
				['consrc' => 'DEPT_ID', 'contype' => 'R'],
				['consrc' => 'NAME', 'contype' => 'C'],
			],
		];
	}

	public function test_table_info_class_and_tableless_info()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame(TOracleTableInfo::class, PradoUnit::invoke($meta, 'getTableInfoClass'));
		$info = $meta->getTableInfo();
		$this->assertInstanceOf(TOracleTableInfo::class, $info);
		$this->assertSame($info, $meta->getTableInfo());
	}

	public function test_explicit_default_schema()
	{
		[$meta, $conn] = $this->makeMetaData([]);

		$meta->setDefaultSchema('prado');

		$this->assertSame('prado', $meta->getDefaultSchema());
		$this->assertSame([], $conn->getQueries());
	}

	public function test_default_schema_is_the_connected_user()
	{
		[$meta, $conn] = $this->makeMetaData(['SELECT USER FROM DUAL' => [['user' => 'PRADO_USER']]]);

		$this->assertSame('prado_user', $meta->getDefaultSchema());
		$this->assertSame('prado_user', $meta->getDefaultSchema());
		$this->assertSame(['SELECT USER FROM DUAL'], $conn->getQueries());
	}

	public function test_default_schema_without_a_user_row_is_system()
	{
		[$meta] = $this->makeMetaData(['SELECT USER FROM DUAL' => []]);

		$this->assertSame('system', $meta->getDefaultSchema());
	}

	public function test_default_schema_on_query_failure_is_system()
	{
		[$meta] = $this->makeMetaData(['SELECT USER FROM DUAL' => new \RuntimeException('no DUAL')]);

		$this->assertSame('system', $meta->getDefaultSchema());
	}

	public function test_schema_table_name()
	{
		[$meta] = $this->makeMetaData([]);
		$meta->setDefaultSchema('prado');

		$this->assertSame(['hr', 'emp'], PradoUnit::invoke($meta, 'getSchemaTableName', 'hr.emp'));
		$this->assertSame(['HR', 'EMP'], PradoUnit::invoke($meta, 'getSchemaTableName', '"HR"."EMP"'));
		$this->assertSame(['prado', 'emp'], PradoUnit::invoke($meta, 'getSchemaTableName', 'emp'));
	}

	public function test_table_info_from_catalog_rows()
	{
		[$meta, $conn] = $this->makeMetaData($this->tableCatalog());

		$table = $meta->getTableInfo('prado.table1');

		$this->assertInstanceOf(TOracleTableInfo::class, $table);
		$this->assertSame('prado', $table->getSchemaName());
		$this->assertSame('table1', $table->getTableName());
		$this->assertSame('prado.table1', $table->getTableFullName());
		$this->assertFalse($table->getIsView());
		$this->assertSame(['id'], $table->getPrimaryKeys());
		$this->assertSame(['dept_id'], $table->getForeignKeys());
		$this->assertSame(['id', 'name', 'dept_id', 'ratio', 'stamp', 'body'], $table->getColumns()->getKeys());

		$queries = $conn->getQueries();
		$this->assertCount(3, $queries);
		foreach ($queries as $sql) {
			$this->assertStringContainsString("'TABLE1'", $sql);
			$this->assertStringContainsString("OWNER = 'PRADO'", $sql);
		}
		$this->assertSame(\PDO::CASE_LOWER, $conn->getPdoInstance()->getAttribute(\PDO::ATTR_CASE));
	}

	public function test_column_properties_from_catalog_rows()
	{
		[$meta] = $this->makeMetaData($this->tableCatalog());
		$columns = $meta->getTableInfo('prado.table1')->getColumns();

		$expected = [
			'id' => ['ColumnName' => 'id', 'ColumnId' => 'id', 'ColumnIndex' => 0, 'DbType' => 'NUMBER', 'AllowNull' => false, 'IsPrimaryKey' => true, 'IsForeignKey' => false, 'ColumnSize' => 22, 'NumericPrecision' => 10, 'NumericScale' => 0, 'DefaultValue' => TOracleTableColumn::UNDEFINED_VALUE],
			'name' => ['ColumnIndex' => 1, 'DbType' => 'VARCHAR2', 'AllowNull' => false, 'IsPrimaryKey' => false, 'IsForeignKey' => false, 'ColumnSize' => 45, 'NumericPrecision' => null, 'DefaultValue' => "'anon'"],
			'dept_id' => ['ColumnIndex' => 2, 'DbType' => 'NUMBER', 'AllowNull' => true, 'IsForeignKey' => true, 'NumericPrecision' => null, 'NumericScale' => null],
			'ratio' => ['ColumnIndex' => 3, 'DbType' => 'NUMBER', 'ColumnSize' => null, 'NumericPrecision' => 5, 'NumericScale' => null],
			'stamp' => ['ColumnIndex' => 4, 'DbType' => 'TIMESTAMP', 'ColumnSize' => 6, 'NumericPrecision' => null],
			'body' => ['ColumnIndex' => 5, 'DbType' => 'CLOB', 'ColumnSize' => 4000],
		];
		foreach ($expected as $id => $properties) {
			$this->assertInstanceOf(TOracleTableColumn::class, $columns[$id]);
			foreach ($properties as $property => $value) {
				$this->assertSame($value, $columns[$id]->{'get' . $property}(), "{$id}.{$property}");
			}
		}
	}

	public function test_view_is_detected()
	{
		[$meta] = $this->makeMetaData($this->tableCatalog('VIEW'));

		$this->assertTrue($meta->getTableInfo('prado.v_table1')->getIsView());
		$this->assertSame(1, PradoUnit::invoke($meta, 'getIsView', 'prado', 'v_table1'));
	}

	public function test_command_builder_for_table()
	{
		[$meta, $conn] = $this->makeMetaData($this->tableCatalog());

		$builder = $meta->createCommandBuilder('prado.table1');

		$this->assertInstanceOf(TOracleCommandBuilder::class, $builder);
		$this->assertSame($conn, $builder->getDbConnection());
		$this->assertSame($meta->getTableInfo('prado.table1'), $builder->getTableInfo());
	}

	public function test_missing_table_throws()
	{
		[$meta] = $this->makeMetaData(['ALL_TAB_COLUMNS' => [], 'select OBJECT_TYPE' => [], 'ALL_CONSTRAINTS' => []]);

		$this->expectException(TDbException::class);
		$meta->getTableInfo('prado.missing');
	}

	public function test_identifier_with_double_quote_throws()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame('table1', PradoUnit::invoke($meta, 'assertIdentifier', 'table1'));
		$this->expectException(TDbException::class);
		PradoUnit::invoke($meta, 'assertIdentifier', 'bad"name');
	}

	public function test_sequence_name()
	{
		[$meta] = $this->makeMetaData([]);
		$table = new TOracleTableInfo(['SchemaName' => 'prado', 'TableName' => 't']);

		$this->assertSame('prado.t_seq', PradoUnit::invoke($meta, 'getSequenceName', $table, "nextval('t_seq'::regclass)"));
		$this->assertSame('other.t_seq', PradoUnit::invoke($meta, 'getSequenceName', $table, "NEXTVAL('other.t_seq')"));
		$this->assertNull(PradoUnit::invoke($meta, 'getSequenceName', $table, '0'));
	}

	public function test_precision_type()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertTrue(PradoUnit::invoke($meta, 'isPrecisionType', ' Number '));
		$this->assertFalse(PradoUnit::invoke($meta, 'isPrecisionType', 'VARCHAR2'));
		$this->assertFalse(PradoUnit::invoke($meta, 'isPrecisionType', 'TIMESTAMP'));
	}

	public function test_primary_keys_from_definition()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame(['a', 'b'], PradoUnit::invoke($meta, 'getPrimaryKeys', 'PRIMARY KEY (a, b)'));
		$this->assertSame(['id'], PradoUnit::invoke($meta, 'getPrimaryKeys', 'primary  key  (id)'));
		$this->assertSame([], PradoUnit::invoke($meta, 'getPrimaryKeys', 'UNIQUE (a)'));
	}

	public function test_foreign_keys_from_definition()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame(
			['table' => 'hr.dept', 'keys' => ['a' => 'x', 'b' => 'y']],
			PradoUnit::invoke($meta, 'getForeignKeys', 'FOREIGN KEY (a, b) REFERENCES "hr"."dept"(x, y)')
		);
		$this->assertNull(PradoUnit::invoke($meta, 'getForeignKeys', 'PRIMARY KEY (a)'));
	}

	public function test_foreign_key_column()
	{
		[$meta] = $this->makeMetaData([]);
		$table = new TOracleTableInfo([], ['id'], ['dept_id', 'owner_id']);

		$this->assertTrue(PradoUnit::invoke($meta, 'isForeignKeyColumn', 'owner_id', $table));
		$this->assertFalse(PradoUnit::invoke($meta, 'isForeignKeyColumn', 'id', $table));
	}

	public function test_find_table_names_of_the_current_user()
	{
		[$meta, $conn] = $this->makeMetaData(['FROM user_tables' => [['TABLE_NAME' => 'TABLE1', 'TABLE_SCHEMA' => ''], ['table_name' => 'ADDRESS', 'table_schema' => '']]]);
		$meta->setDefaultSchema('prado');

		$this->assertSame(['TABLE1', 'ADDRESS'], $meta->findTableNames());
		$this->assertSame([], $conn->getCommands()[0]->getBoundValues());
	}

	public function test_find_table_names_of_another_schema()
	{
		[$meta, $conn] = $this->makeMetaData(["object_type = 'TABLE'" => [['table_name' => 'EMP', 'table_schema' => 'HR']]]);
		$meta->setDefaultSchema('prado');

		$this->assertSame(['HR.EMP'], $meta->findTableNames('HR'));
		$this->assertSame([':schema' => 'HR'], $conn->getCommands()[0]->getBoundValues());
	}

	public function test_find_table_names_of_the_default_schema_are_bare()
	{
		[$meta] = $this->makeMetaData(["object_type = 'TABLE'" => [['table_name' => 'EMP', 'table_schema' => 'PRADO']]]);
		$meta->setDefaultSchema('PRADO');

		$this->assertSame(['EMP'], $meta->findTableNames('PRADO'));
	}
}

/**
 * An SQLite connection whose commands answer from canned catalog rows. Each
 * catalog key is an SQL fragment; the first key found in a command's SQL selects
 * its rows. A Throwable in place of rows is thrown when the command runs.
 */
class OracleCatalogConnection extends TDbConnection
{
	/** @var OracleCatalogCommand[] */
	private array $_commands = [];

	public function __construct(private array $_catalog)
	{
		parent::__construct('sqlite::memory:');
	}

	public function createCommand($sql)
	{
		$rows = [];
		foreach ($this->_catalog as $fragment => $answer) {
			if (str_contains($sql, $fragment)) {
				$rows = $answer;
				break;
			}
		}
		return $this->_commands[] = new OracleCatalogCommand($this, $sql, $rows);
	}

	/** @return OracleCatalogCommand[] commands created, in order. */
	public function getCommands(): array
	{
		return $this->_commands;
	}

	/** @return string[] SQL text of the commands created, in order. */
	public function getQueries(): array
	{
		return array_map(fn ($command) => $command->getText(), $this->_commands);
	}
}

/**
 * A command that returns canned rows and records its bound values.
 */
class OracleCatalogCommand extends TDbCommand
{
	private array $_bound = [];

	public function __construct(TDbConnection $connection, $text, private array|\Throwable $_rows)
	{
		parent::__construct($connection, $text);
	}

	public function bindValue($name, $value, $dataType = null)
	{
		$this->_bound[$name] = $value;
	}

	public function getBoundValues(): array
	{
		return $this->_bound;
	}

	protected function rows(): array
	{
		if ($this->_rows instanceof \Throwable) {
			throw $this->_rows;
		}
		return $this->_rows;
	}

	public function query()
	{
		return new \ArrayIterator($this->rows());
	}

	public function queryScalar()
	{
		$rows = $this->rows();
		return $rows === [] ? false : reset($rows[0]);
	}
}
