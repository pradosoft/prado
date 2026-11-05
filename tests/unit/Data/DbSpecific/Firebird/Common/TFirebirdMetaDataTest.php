<?php

namespace Prado\Test\Unit\Data\DbSpecific\Firebird\Common;

use Prado\Data\Common\Firebird\TFirebirdCommandBuilder;
use Prado\Data\Common\Firebird\TFirebirdMetaData;
use Prado\Data\Common\Firebird\TFirebirdTableColumn;
use Prado\Data\Common\Firebird\TFirebirdTableInfo;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;
use Prado\Test\Unit\PradoUnit;

/**
 * TFirebirdMetaData over a connection double that answers the RDB$ system table
 * queries with canned rows, so the row processing runs without a Firebird server.
 */
class TFirebirdMetaDataTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @param string $name field name
	 * @param int $type RDB$FIELD_TYPE code
	 * @param array $fields other row fields
	 * @return array an RDB$RELATION_FIELDS + RDB$FIELDS row
	 */
	protected function field(string $name, int $type, array $fields = []): array
	{
		return $fields + [
			'FIELD_POSITION' => 0,
			'FIELD_NAME' => $name,
			'FIELD_TYPE' => $type,
			'FIELD_LENGTH' => null,
			'FIELD_PRECISION' => null,
			'FIELD_SCALE' => 0,
			'FIELD_SUB_TYPE' => null,
			'NULL_FLAG' => null,
			'DEFAULT_SOURCE' => null,
			'IDENTITY_TYPE' => null,
		];
	}

	protected function tableCatalog(int $viewCount = 0): array
	{
		return [
			'RDB$RELATION_FIELDS' => [
				$this->field('ID', 8, ['FIELD_LENGTH' => 4, 'NULL_FLAG' => 1, 'IDENTITY_TYPE' => 1]),
				$this->field('NAME   ', 37, ['FIELD_LENGTH' => 45, 'NULL_FLAG' => 1, 'DEFAULT_SOURCE' => "DEFAULT 'anon'"]),
				$this->field('DEPT_ID', 16, ['FIELD_LENGTH' => 8]),
				$this->field('PRICE', 27, ['FIELD_LENGTH' => 8, 'FIELD_SCALE' => -2]),
				$this->field('RATE', 25, ['FIELD_LENGTH' => 16, 'FIELD_PRECISION' => 34]),
				$this->field('BODY', 261, ['FIELD_LENGTH' => 8, 'FIELD_SUB_TYPE' => 1]),
				$this->field('DATA', 261, ['FIELD_LENGTH' => 8]),
				$this->field('ODD', 99, ['FIELD_SCALE' => null]),
				$this->field('FLAG', 23, ['FIELD_LENGTH' => 1, 'IDENTITY_TYPE' => 0, 'DEFAULT_SOURCE' => '  default TRUE ']),
			],
			'RDB$VIEW_BLR IS NOT NULL' => [['COUNT' => $viewCount]],
			"CONSTRAINT_TYPE = 'PRIMARY KEY'" => [['FIELD_NAME' => 'ID ']],
			"CONSTRAINT_TYPE = 'FOREIGN KEY'" => [
				['CONSTNAME' => 'FK_DEPT', 'COLNAME' => 'DEPT_ID', 'REFTABNAME' => 'DEPARTMENTS', 'REFCOLNAME' => 'ID'],
				['CONSTNAME' => 'FK_PAIR', 'COLNAME' => 'A', 'REFTABNAME' => 'PAIRS', 'REFCOLNAME' => 'X'],
				['CONSTNAME' => 'FK_PAIR', 'COLNAME' => 'B', 'REFTABNAME' => 'PAIRS', 'REFCOLNAME' => 'Y'],
			],
		];
	}

	/**
	 * @param array $catalog map of SQL fragment to canned rows; see {@see FirebirdCatalogConnection}.
	 * @return array{0: TFirebirdMetaData, 1: FirebirdCatalogConnection}
	 */
	protected function makeMetaData(array $catalog): array
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$conn = new FirebirdCatalogConnection($catalog);
		$conn->setActive(true);
		return [new TFirebirdMetaData($conn), $conn];
	}

	public function test_table_info_class_and_tableless_info()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame(TFirebirdTableInfo::class, PradoUnit::invoke($meta, 'getTableInfoClass'));
		$this->assertInstanceOf(TFirebirdTableInfo::class, $meta->getTableInfo());
	}

	public function test_identifiers_are_double_quoted()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame('"table1"', $meta->quoteTableName('table1'));
		$this->assertSame('"db"."table1"', $meta->quoteTableName('`db`.table1'));
		$this->assertSame('"name"', $meta->quoteColumnName('[name]'));
		$this->assertSame('"total"', $meta->quoteColumnAlias("'total'"));
	}

	public function test_schema_table_name_has_no_schema()
	{
		[$meta] = $this->makeMetaData([]);

		$this->assertSame([null, 'TABLE1'], PradoUnit::invoke($meta, 'getSchemaTableName', '"table1"'));
		$this->assertSame([null, 'MY_TABLE'], PradoUnit::invoke($meta, 'getSchemaTableName', 'My_Table'));
	}

	public function test_table_info_from_catalog_rows()
	{
		[$meta, $conn] = $this->makeMetaData($this->tableCatalog());

		$table = $meta->getTableInfo('"table1"');

		$this->assertInstanceOf(TFirebirdTableInfo::class, $table);
		$this->assertSame('TABLE1', $table->getTableName());
		$this->assertSame('"TABLE1"', $table->getTableFullName());
		$this->assertFalse($table->getIsView());
		$this->assertSame(['id'], $table->getPrimaryKeys());
		$this->assertSame([
			['keys' => ['dept_id' => 'id'], 'table' => 'departments'],
			['keys' => ['a' => 'x', 'b' => 'y'], 'table' => 'pairs'],
		], $table->getForeignKeys());
		$this->assertSame(['id', 'name', 'dept_id', 'price', 'rate', 'body', 'data', 'odd', 'flag'], $table->getColumns()->getKeys());

		$commands = $conn->getCommands();
		$this->assertCount(4, $commands);
		foreach ($commands as $command) {
			$this->assertSame([':table' => 'TABLE1'], $command->getBoundValues());
		}
	}

	public function test_column_properties_from_catalog_rows()
	{
		[$meta] = $this->makeMetaData($this->tableCatalog());
		$columns = $meta->getTableInfo('table1')->getColumns();

		$undefined = TFirebirdTableColumn::UNDEFINED_VALUE;
		$expected = [
			'id' => ['ColumnName' => '"ID"', 'ColumnId' => 'id', 'ColumnIndex' => 0, 'DbType' => 'INTEGER', 'PHPType' => 'integer', 'AllowNull' => false, 'AutoIncrement' => true, 'IsPrimaryKey' => true, 'IsForeignKey' => false, 'ColumnSize' => 4, 'DefaultValue' => $undefined],
			'name' => ['ColumnName' => '"NAME"', 'ColumnId' => 'name', 'ColumnIndex' => 1, 'DbType' => 'VARCHAR', 'PHPType' => 'string', 'AllowNull' => false, 'AutoIncrement' => false, 'ColumnSize' => 45, 'DefaultValue' => "'anon'"],
			'dept_id' => ['DbType' => 'BIGINT', 'PHPType' => 'integer', 'AllowNull' => true, 'IsPrimaryKey' => false, 'IsForeignKey' => true, 'ColumnSize' => 8],
			'price' => ['DbType' => 'DOUBLE PRECISION', 'PHPType' => 'float', 'ColumnSize' => null, 'NumericPrecision' => null, 'NumericScale' => 2],
			'rate' => ['DbType' => 'DECFLOAT(34)', 'PHPType' => 'float', 'ColumnSize' => null, 'NumericPrecision' => 34, 'NumericScale' => null],
			'body' => ['DbType' => 'TEXT', 'PHPType' => 'string', 'ColumnSize' => 8],
			'data' => ['DbType' => 'BLOB', 'ColumnSize' => 8],
			'odd' => ['DbType' => 'UNKNOWN(99)', 'PHPType' => 'string', 'ColumnSize' => null],
			'flag' => ['DbType' => 'BOOLEAN', 'PHPType' => 'boolean', 'AutoIncrement' => true, 'DefaultValue' => 'TRUE'],
		];
		foreach ($expected as $id => $properties) {
			$this->assertInstanceOf(TFirebirdTableColumn::class, $columns[$id]);
			foreach ($properties as $property => $value) {
				$this->assertSame($value, $columns[$id]->{'get' . $property}(), "{$id}.{$property}");
			}
		}
	}

	public function test_view_is_detected()
	{
		[$meta] = $this->makeMetaData($this->tableCatalog(1));

		$this->assertTrue($meta->getTableInfo('v_table1')->getIsView());
	}

	public function test_table_without_keys()
	{
		$catalog = $this->tableCatalog();
		$catalog["CONSTRAINT_TYPE = 'PRIMARY KEY'"] = [];
		$catalog["CONSTRAINT_TYPE = 'FOREIGN KEY'"] = [];
		[$meta] = $this->makeMetaData($catalog);

		$table = $meta->getTableInfo('table1');

		$this->assertSame([], $table->getPrimaryKeys());
		$this->assertSame([], $table->getForeignKeys());
		$this->assertFalse($table->getColumn('dept_id')->getIsForeignKey());
	}

	public function test_command_builder_for_table()
	{
		[$meta, $conn] = $this->makeMetaData($this->tableCatalog());

		$builder = $meta->createCommandBuilder('table1');

		$this->assertInstanceOf(TFirebirdCommandBuilder::class, $builder);
		$this->assertSame($conn, $builder->getDbConnection());
		$this->assertSame('id', PradoUnit::invoke($builder, 'getIdentityColumn')->getColumnId());
	}

	public function test_missing_table_throws()
	{
		[$meta] = $this->makeMetaData([]);

		$this->expectException(TDbException::class);
		$meta->getTableInfo('missing');
	}

	public function test_precision_type()
	{
		[$meta] = $this->makeMetaData([]);

		$precision = [];
		foreach (['decimal', ' NUMERIC ', 'DECFLOAT(16)', 'DECFLOAT(34)', 'FLOAT', 'DOUBLE PRECISION', 'INTEGER', 'VARCHAR', 'INT128'] as $type) {
			$precision[$type] = PradoUnit::invoke($meta, 'isPrecisionType', $type);
		}

		$this->assertSame(['decimal' => true, ' NUMERIC ' => true, 'DECFLOAT(16)' => true, 'DECFLOAT(34)' => true, 'FLOAT' => true, 'DOUBLE PRECISION' => true, 'INTEGER' => false, 'VARCHAR' => false, 'INT128' => false], $precision);
	}

	public function test_find_table_names_are_lower_case()
	{
		[$meta, $conn] = $this->makeMetaData(['RDB$SYSTEM_FLAG = 0' => [['RELATION_NAME' => 'ADDRESS'], ['RELATION_NAME' => 'TABLE1']]]);

		$this->assertSame(['address', 'table1'], $meta->findTableNames('ignored'));
		$this->assertStringContainsString('RDB$VIEW_BLR IS NULL', $conn->getCommands()[0]->getText());
	}
}

/**
 * An SQLite connection whose commands answer from canned catalog rows. Each
 * catalog key is an SQL fragment; the first key found in a command's SQL selects its rows.
 */
class FirebirdCatalogConnection extends TDbConnection
{
	/** @var FirebirdCatalogCommand[] */
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
		return $this->_commands[] = new FirebirdCatalogCommand($this, $sql, $rows);
	}

	/** @return FirebirdCatalogCommand[] commands created, in order. */
	public function getCommands(): array
	{
		return $this->_commands;
	}
}

/**
 * A command that returns canned rows and records its bound values.
 */
class FirebirdCatalogCommand extends TDbCommand
{
	private array $_bound = [];

	public function __construct(TDbConnection $connection, $text, private array $_rows)
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

	public function query()
	{
		return new \ArrayIterator($this->_rows);
	}

	public function queryScalar()
	{
		return $this->_rows === [] ? false : reset($this->_rows[0]);
	}
}
