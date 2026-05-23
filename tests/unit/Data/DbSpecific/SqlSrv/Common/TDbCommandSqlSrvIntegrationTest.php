<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\Data\TDbDataReader;
use Prado\TApplication;

/**
 * Live integration tests for TDbCommand and TDbDataReader — Microsoft SQL Server.
 *
 * Exercises query methods, parameter binding, and the TDbDataReader
 * iteration API against a real SQL Server database.
 *
 * Table schema used throughout:
 *   cmd_test (id INT PRIMARY KEY, name NVARCHAR(100), score FLOAT,
 *             active BIT, note NVARCHAR(100))
 *
 * Seed rows:
 *   (1, 'Alice', 9.5, 1, 'first')
 *   (2, 'Bob',   7.3, 0, NULL)
 *   (3, 'Carol', 8.1, 1, 'third')
 */
class TDbCommandSqlSrvIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openSqlSrv(): TDbConnection
	{
		$conn = PradoUnit::setupSqlSrvConnection('prado_unitest');
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
		$this->_conn = $this->openSqlSrv();

		// Drop table if it exists from a previous run, then create fresh.
		try {
			$this->_conn->createCommand(
				"IF OBJECT_ID('cmd_test', 'U') IS NOT NULL DROP TABLE cmd_test"
			)->execute();
		} catch (\Exception $e) {
		}
		try {
			$this->_conn->createCommand(
				'CREATE TABLE cmd_test (id INT PRIMARY KEY, name NVARCHAR(100), score FLOAT, active BIT, note NVARCHAR(100))'
			)->execute();
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot create cmd_test table: ' . $e->getMessage());
		}
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (1, 'Alice', 9.5, 1, 'first')")->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (2, 'Bob',   7.3, 0, NULL)")->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (3, 'Carol', 8.1, 1, 'third')")->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand(
					"IF OBJECT_ID('cmd_test', 'U') IS NOT NULL DROP TABLE cmd_test"
				)->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbCommand — execute()
	// -----------------------------------------------------------------------

	public function testSqlsrvExecuteRunsDdlWithoutError(): void
	{
		// execute() on a non-query statement must not throw.
		try {
			$this->_conn->createCommand(
				"IF OBJECT_ID('exec_ddl_test', 'U') IS NOT NULL DROP TABLE exec_ddl_test"
			)->execute();
		} catch (\Exception $e) {
		}
		$this->_conn->createCommand('CREATE TABLE exec_ddl_test (x INT)')->execute();
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM exec_ddl_test')->queryScalar();
		$this->assertSame(0, $count);
		$this->_conn->createCommand('DROP TABLE exec_ddl_test')->execute();
	}

	public function testSqlsrvExecuteReturnsRowCountForInsert(): void
	{
		$affected = $this->_conn->createCommand(
			"INSERT INTO cmd_test VALUES (99, 'Zoe', 5.0, 0, NULL)"
		)->execute();
		$this->assertSame(1, $affected);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryAll()
	// -----------------------------------------------------------------------

	public function testSqlsrvQueryAllReturnsAllRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryAll();
		$this->assertCount(3, $rows);
		$this->assertSame('Alice', $rows[0]['name']);
		$this->assertSame('Bob',   $rows[1]['name']);
		$this->assertSame('Carol', $rows[2]['name']);
	}

	public function testSqlsrvQueryAllReturnsAssocArraysByDefault(): void
	{
		$rows = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->queryAll();
		$this->assertArrayHasKey('id',   $rows[0]);
		$this->assertArrayHasKey('name', $rows[0]);
	}

	public function testSqlsrvQueryAllReturnsEmptyArrayWhenNoRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->queryAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryRow()
	// -----------------------------------------------------------------------

	public function testSqlsrvQueryRowReturnsFirstRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryRow();
		$this->assertIsArray($row);
		$this->assertSame('Alice', $row['name']);
	}

	public function testSqlsrvQueryRowReturnsFalseWhenNoRows(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->queryRow();
		$this->assertFalse($row);
	}

	public function testSqlsrvQueryRowReturnsOnlyOneRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryRow();
		// Only a single array (one row), not a nested array.
		$this->assertArrayHasKey('name', $row);
		$this->assertArrayNotHasKey(0, $row);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryScalar()
	// -----------------------------------------------------------------------

	public function testSqlsrvQueryScalarReturnsFirstColumnFirstRow(): void
	{
		$scalar = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->queryScalar();
		$this->assertSame('Alice', $scalar);
	}

	public function testSqlsrvQueryScalarReturnsFalseWhenNoRows(): void
	{
		$scalar = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = 999')->queryScalar();
		$this->assertFalse($scalar);
	}

	public function testSqlsrvQueryScalarWorksForCountAggregate(): void
	{
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM cmd_test')->queryScalar();
		$this->assertSame(3, $count);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryColumn()
	// -----------------------------------------------------------------------

	public function testSqlsrvQueryColumnReturnsFirstColumnOfAllRows(): void
	{
		$names = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->queryColumn();
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testSqlsrvQueryColumnReturnsEmptyArrayWhenNoRows(): void
	{
		$result = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = 999')->queryColumn();
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	public function testSqlsrvQueryColumnWorksForNumericColumn(): void
	{
		$ids = $this->_conn->createCommand('SELECT id FROM cmd_test ORDER BY id')->queryColumn();
		$this->assertCount(3, $ids);
		$this->assertSame('1', (string) $ids[0]);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — parameter binding
	// -----------------------------------------------------------------------

	public function testSqlsrvBindParameterWithPositionalPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = ?');
		$id = 2;
		$cmd->bindParameter(1, $id);
		$this->assertSame('Bob', $cmd->queryScalar());
	}

	public function testSqlsrvBindValueWithNamedPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', $cmd->queryScalar());
	}

	public function testSqlsrvBindValueTypeInt(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 1, \PDO::PARAM_INT);
		$this->assertSame('Alice', $cmd->queryScalar());
	}

	public function testSqlsrvBindValueTypeStr(): void
	{
		$cmd = $this->_conn->createCommand("SELECT id FROM cmd_test WHERE name = :name");
		$cmd->bindValue(':name', 'Carol', \PDO::PARAM_STR);
		$this->assertSame('3', (string) $cmd->queryScalar());
	}

	public function testSqlsrvPreparedStatementCanBeExecutedMultipleTimes(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 1);
		$this->assertSame('Alice', $cmd->queryScalar());

		$cmd->bindValue(':id', 2);
		$this->assertSame('Bob', $cmd->queryScalar());

		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', $cmd->queryScalar());
	}

	// -----------------------------------------------------------------------
	// TDbCommand — NULL values
	// -----------------------------------------------------------------------

	public function testSqlsrvQueryRowReturnsNullForNullColumn(): void
	{
		$row = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->queryRow();
		$this->assertNull($row['note']);
	}

	public function testSqlsrvQueryScalarReturnsNullForNullColumn(): void
	{
		$scalar = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->queryScalar();
		$this->assertNull($scalar);
	}

	// -----------------------------------------------------------------------
	// TDbDataReader — via query()
	// -----------------------------------------------------------------------

	public function testSqlsrvQueryReturnsDataReader(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		$this->assertInstanceOf(TDbDataReader::class, $reader);
		$reader->close();
	}

	public function testSqlsrvDataReaderReadReturnsRowsThenFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT id FROM cmd_test ORDER BY id')->query();
		$row1 = $reader->read();
		$row2 = $reader->read();
		$row3 = $reader->read();
		$done = $reader->read();

		$this->assertIsArray($row1);
		$this->assertIsArray($row2);
		$this->assertIsArray($row3);
		$this->assertFalse($done);
		$reader->close();
	}

	public function testSqlsrvDataReaderReadAllReturnsAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->query();
		$rows = $reader->readAll();
		$this->assertCount(3, $rows);
		$reader->close();
	}

	public function testSqlsrvDataReaderReadColumnByIndex(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->query();
		$name = $reader->readColumn(1); // second column = name
		$this->assertSame('Alice', $name);
		$reader->close();
	}

	public function testSqlsrvDataReaderForeachIteratesAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->query();
		$names = [];
		foreach ($reader as $row) {
			$names[] = $row['name'];
		}
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testSqlsrvDataReaderGetColumnCount(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name, score FROM cmd_test')->query();
		$this->assertSame(3, $reader->getColumnCount());
		$reader->close();
	}

	public function testSqlsrvDataReaderNullValueReturnedForNullColumn(): void
	{
		$reader = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->query();
		$row = $reader->read();
		$this->assertNull($row['note']);
		$reader->close();
	}

	public function testSqlsrvDataReaderEmptyResultSetReadReturnsFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->query();
		$this->assertFalse($reader->read());
		$reader->close();
	}

	public function testSqlsrvDataReaderClosePreventsFurtherReading(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		$reader->close();
		$this->assertTrue($reader->getIsClosed());
	}

	public function testSqlsrvDataReaderRewindThrowsOnSecondIteration(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		// First complete iteration.
		foreach ($reader as $row) {
		}
		// Second iteration must throw TDbException (rewind not supported).
		$this->expectException(\Prado\Exceptions\TDbException::class);
		foreach ($reader as $row) {
		}
	}

	public function testSqlsrvDataReaderFetchModeNum(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->query();
		$reader->setFetchMode(\PDO::FETCH_NUM);
		$row = $reader->read();
		// Numeric-indexed: 0 = id, 1 = name.
		$this->assertArrayHasKey(0, $row);
		$this->assertArrayHasKey(1, $row);
		$this->assertArrayNotHasKey('id', $row);
		$reader->close();
	}
}
