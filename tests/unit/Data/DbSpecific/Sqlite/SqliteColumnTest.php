<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\Common\TDbTableColumn;
use Prado\Data\TDbConnection;

class SqliteColumnTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	private static string $_dbFile;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqliteConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getTestTables(): array
	{
		return [];
	}

	public static function setUpBeforeClass(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			return;
		}
		self::$_dbFile = sys_get_temp_dir() . '/prado_sqlite_column_test.db';
		@unlink(self::$_dbFile);

		$conn = new TDbConnection('sqlite:' . self::$_dbFile);
		$conn->Active = true;
		$conn->createCommand('
			CREATE TABLE table1 (
				id            INTEGER      NOT NULL PRIMARY KEY,
				name          VARCHAR(45)  NOT NULL,
				field1_int    INT          NOT NULL DEFAULT 0,
				field2_text   TEXT,
				field3_real   REAL         NOT NULL DEFAULT 10.0,
				field4_blob   BLOB,
				field5_num    NUMERIC(10,4) NOT NULL DEFAULT 0,
				field6_bool   BOOLEAN      NOT NULL DEFAULT 0,
				field7_date   DATE,
				field8_dec    DECIMAL(8,2)
			)
		')->execute();
		$conn->createCommand('
			CREATE TABLE ref_table (
				id         INTEGER NOT NULL PRIMARY KEY,
				table1_id  INTEGER REFERENCES table1(id)
			)
		')->execute();
		$conn->Active = false;
	}

	public static function tearDownAfterClass(): void
	{
		if (isset(self::$_dbFile)) {
			@unlink(self::$_dbFile);
		}
	}

	protected function setUp(): void
	{
		// setUpConnection() skips the test if pdo_sqlite is unavailable (via PradoUnit).
		// getTestTables() returns [] so no table existence checks are performed here;
		// the file DB and its tables are managed by setUpBeforeClass().
		$this->setUpConnection();
	}

	public function create_meta_data(): TSqliteMetaData
	{
		$conn = new TDbConnection('sqlite:' . self::$_dbFile);
		// Activate proactively: TSqliteMetaData::findTableNames() does not call
		// setActive(true) itself, unlike the other driver implementations.
		$conn->Active = true;
		return new TSqliteMetaData($conn);
	}

	public function test_columns()
	{
		$table = $this->create_meta_data()->getTableInfo('table1');

		$this->assertCount(10, $table->getColumns());
		$this->assertEquals('table1', $table->getTableName());
		$this->assertEquals("'table1'", $table->getTableFullName());
		$this->assertEquals(['id'], $table->getPrimaryKeys());

		$columns = [];

		$columns['id'] = [
			'ColumnName'    => '"id"',
			'ColumnIndex'   => 0,
			// 'integer' (exact lowercase type) + pk=1 → AutoIncrement
			'DbType'        => 'integer',
			// notnull check uses !== '99', so AllowNull is always true regardless of constraint
			'AllowNull'     => true,
			'IsPrimaryKey'  => true,
			'IsForeignKey'  => false,
			'AutoIncrement' => true,
			'DefaultValue'  => TDbTableColumn::UNDEFINED_VALUE,
		];

		$columns['name'] = [
			'ColumnName'    => '"name"',
			'ColumnIndex'   => 1,
			'DbType'        => 'varchar',
			'ColumnSize'    => 45,
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => TDbTableColumn::UNDEFINED_VALUE,
		];

		$columns['field1_int'] = [
			'ColumnName'    => '"field1_int"',
			'ColumnIndex'   => 2,
			// 'int' is NOT the same as 'integer' so AutoIncrement is false
			'DbType'        => 'int',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => '0',
		];

		$columns['field2_text'] = [
			'ColumnName'    => '"field2_text"',
			'ColumnIndex'   => 3,
			'DbType'        => 'text',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => TDbTableColumn::UNDEFINED_VALUE,
		];

		$columns['field3_real'] = [
			'ColumnName'    => '"field3_real"',
			'ColumnIndex'   => 4,
			'DbType'        => 'real',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => '10.0',
		];

		$columns['field4_blob'] = [
			'ColumnName'    => '"field4_blob"',
			'ColumnIndex'   => 5,
			'DbType'        => 'blob',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => TDbTableColumn::UNDEFINED_VALUE,
		];

		$columns['field5_num'] = [
			'ColumnName'       => '"field5_num"',
			'ColumnIndex'      => 6,
			// NUMERIC(10,4) → DbType stripped of parens
			'DbType'           => 'numeric',
			'NumericPrecision' => 10,
			'NumericScale'     => 4,
			'AllowNull'        => true,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => '0',
		];

		$columns['field6_bool'] = [
			'ColumnName'    => '"field6_bool"',
			'ColumnIndex'   => 7,
			'DbType'        => 'boolean',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => '0',
		];

		$columns['field7_date'] = [
			'ColumnName'    => '"field7_date"',
			'ColumnIndex'   => 8,
			'DbType'        => 'date',
			'AllowNull'     => true,
			'IsPrimaryKey'  => false,
			'IsForeignKey'  => false,
			'AutoIncrement' => false,
			'DefaultValue'  => TDbTableColumn::UNDEFINED_VALUE,
		];

		$columns['field8_dec'] = [
			'ColumnName'       => '"field8_dec"',
			'ColumnIndex'      => 9,
			// DECIMAL(8,2) → DbType stripped of parens
			'DbType'           => 'decimal',
			'NumericPrecision' => 8,
			'NumericScale'     => 2,
			'AllowNull'        => true,
			'IsPrimaryKey'     => false,
			'IsForeignKey'     => false,
			'AutoIncrement'    => false,
			'DefaultValue'     => TDbTableColumn::UNDEFINED_VALUE,
		];

		$this->assertColumn($columns, $table);
	}

	public function test_foreign_key_detection()
	{
		$table = $this->create_meta_data()->getTableInfo('ref_table');

		$this->assertCount(2, $table->getColumns());
		$this->assertEquals(['id'], $table->getPrimaryKeys());

		$id = $table->Columns['id'];
		$this->assertEquals('"id"', $id->ColumnName);
		$this->assertTrue($id->IsPrimaryKey);
		$this->assertFalse($id->IsForeignKey);
		$this->assertTrue($id->AutoIncrement); // INTEGER PK

		$fk = $table->Columns['table1_id'];
		$this->assertEquals('"table1_id"', $fk->ColumnName);
		$this->assertFalse($fk->IsPrimaryKey);
		$this->assertTrue($fk->IsForeignKey);
		$this->assertFalse($fk->AutoIncrement);
	}

	public function test_find_table_names()
	{
		$names = $this->create_meta_data()->findTableNames();

		$this->assertContains('table1', $names);
		$this->assertContains('ref_table', $names);
	}

	public function test_command_builder_insert()
	{
		$meta = $this->create_meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$data = ['name' => 'test', 'field1_int' => 1, 'field3_real' => 1.5];
		$insert = $builder->createInsertCommand($data);
		$this->assertStringContainsString('INSERT INTO', $insert->Text);
		$this->assertStringContainsString("'table1'", $insert->Text);
		$this->assertStringContainsString('"name"', $insert->Text);
	}

	public function test_command_builder_update()
	{
		$meta = $this->create_meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$update = $builder->createUpdateCommand(['name' => 'updated'], 'id=1');
		$this->assertStringContainsString('UPDATE', $update->Text);
		$this->assertStringContainsString('"name"', $update->Text);
		$this->assertStringContainsString('WHERE id=1', $update->Text);
	}

	public function test_command_builder_delete()
	{
		$meta = $this->create_meta_data();
		$builder = $meta->createCommandBuilder('table1');

		$delete = $builder->createDeleteCommand('id=1');
		$this->assertStringContainsString('DELETE FROM', $delete->Text);
		$this->assertStringContainsString("'table1'", $delete->Text);
		$this->assertStringContainsString('WHERE id=1', $delete->Text);
	}

	public function test_select_limit()
	{
		$meta = $this->create_meta_data();
		$builder = $meta->createCommandBuilder('table1');
		$query = 'SELECT * FROM ' . $meta->getTableInfo('table1')->getTableFullName();

		// limit only: no OFFSET clause emitted when offset < 0
		$limit = $builder->applyLimitOffset($query, 5);
		$this->assertEquals($query . ' LIMIT 5', $limit);

		// limit + offset
		$limit = $builder->applyLimitOffset($query, 5, 10);
		$this->assertEquals($query . ' LIMIT 5 OFFSET 10', $limit);

		// offset only: limit clause still written as -1 to satisfy SQL syntax
		$limit = $builder->applyLimitOffset($query, -1, 10);
		$this->assertEquals($query . ' LIMIT -1 OFFSET 10', $limit);

		// neither: SQL returned unchanged
		$limit = $builder->applyLimitOffset($query, -1, -1);
		$this->assertEquals($query, $limit);
	}

	public function test_integer_pk_autoincrement_vs_int_pk_no_autoincrement()
	{
		// Only exact type 'integer' (lowercase) triggers AutoIncrement in SQLite.
		// 'int', 'INT', 'INTEGER(10)', etc. do NOT set AutoIncrement.
		$conn = new TDbConnection('sqlite::memory:');
		$conn->Active = true;
		$conn->createCommand('CREATE TABLE t1 (id INTEGER PRIMARY KEY)')->execute();
		$conn->createCommand('CREATE TABLE t2 (id INT PRIMARY KEY)')->execute();
		$conn->createCommand('CREATE TABLE t3 (id INTEGER(10) PRIMARY KEY)')->execute();

		$meta = new TSqliteMetaData($conn);

		$this->assertTrue($meta->getTableInfo('t1')->Columns['id']->AutoIncrement);
		$this->assertFalse($meta->getTableInfo('t2')->Columns['id']->AutoIncrement);
		// 'integer(10)' → DbType='integer' after stripping parens, but AutoIncrement
		// is set BEFORE parsing parens (from the raw type), so it is false
		$this->assertFalse($meta->getTableInfo('t3')->Columns['id']->AutoIncrement);
	}

	public function assertColumn($columns, $table)
	{
		foreach ($columns as $id => $asserts) {
			$column = $table->Columns[$id];
			foreach ($asserts as $property => $assert) {
				$ofAssert = var_export($assert, true);
				$value = $column->{$property};
				$ofValue = var_export($value, true);
				$this->assertEquals(
					$value,
					$assert,
					"Column [{$id}] {$property} value {$ofValue} did not match {$ofAssert}"
				);
			}
		}
	}
}
