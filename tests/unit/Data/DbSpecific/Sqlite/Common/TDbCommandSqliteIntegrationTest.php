<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\Data\TDbDataReader;
use Prado\TApplication;

/**
 * Live integration tests for TDbCommand and TDbDataReader — SQLite.
 *
 * Exercises query methods, parameter binding, and the TDbDataReader
 * iteration API against a real in-memory SQLite database.
 *
 * Table schema used throughout:
 *   cmd_test (id INTEGER PRIMARY KEY, name TEXT, score REAL, active INTEGER, note TEXT)
 *
 * Seed rows:
 *   (1, 'Alice', 9.5, 1, 'first')
 *   (2, 'Bob',   7.3, 0, NULL)
 *   (3, 'Carol', 8.1, 1, 'third')
 */
class TDbCommandSqliteIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openSqlite(): TDbConnection
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		try {
			$conn = new TDbConnection('sqlite::memory:');
			$conn->Active = true;
			return $conn;
		} catch (\Exception $e) {
			$this->markTestSkipped('Cannot open SQLite in-memory database: ' . $e->getMessage());
		}
	}

	protected function setUp(): void
	{
		static $booted = false;
		if (!$booted) {
			new TApplication(__DIR__ . '/../../../../Security/app', false, TApplication::CONFIG_TYPE_PHP);
			$booted = true;
		}
		$this->_conn = $this->openSqlite();
		$this->_conn->createCommand(
			'CREATE TABLE cmd_test (id INTEGER PRIMARY KEY, name TEXT, score REAL, active INTEGER, note TEXT)'
		)->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (1, 'Alice', 9.5, 1, 'first')")->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (2, 'Bob',   7.3, 0, NULL)")->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (3, 'Carol', 8.1, 1, 'third')")->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand('DROP TABLE cmd_test')->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbCommand — execute()
	// -----------------------------------------------------------------------

	public function testSqliteExecuteRunsDdlWithoutError(): void
	{
		// execute() on a non-query statement must not throw.
		$this->_conn->createCommand('CREATE TABLE exec_ddl_test (x INTEGER)')->execute();
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM exec_ddl_test')->queryScalar();
		$this->assertSame(0, $count);
		$this->_conn->createCommand('DROP TABLE exec_ddl_test')->execute();
	}

	public function testSqliteExecuteReturnsRowCountForInsert(): void
	{
		$affected = $this->_conn->createCommand(
			"INSERT INTO cmd_test VALUES (99, 'Zoe', 5.0, 0, NULL)"
		)->execute();
		$this->assertSame(1, $affected);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryAll()
	// -----------------------------------------------------------------------

	public function testSqliteQueryAllReturnsAllRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryAll();
		$this->assertCount(3, $rows);
		$this->assertSame('Alice', $rows[0]['name']);
		$this->assertSame('Bob',   $rows[1]['name']);
		$this->assertSame('Carol', $rows[2]['name']);
	}

	public function testSqliteQueryAllReturnsAssocArraysByDefault(): void
	{
		$rows = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->queryAll();
		$this->assertArrayHasKey('id',   $rows[0]);
		$this->assertArrayHasKey('name', $rows[0]);
	}

	public function testSqliteQueryAllReturnsEmptyArrayWhenNoRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->queryAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryRow()
	// -----------------------------------------------------------------------

	public function testSqliteQueryRowReturnsFirstRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryRow();
		$this->assertIsArray($row);
		$this->assertSame('Alice', $row['name']);
	}

	public function testSqliteQueryRowReturnsFalseWhenNoRows(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->queryRow();
		$this->assertFalse($row);
	}

	public function testSqliteQueryRowReturnsOnlyOneRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryRow();
		// Only a single array (one row), not a nested array.
		$this->assertArrayHasKey('name', $row);
		$this->assertArrayNotHasKey(0, $row);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryScalar()
	// -----------------------------------------------------------------------

	public function testSqliteQueryScalarReturnsFirstColumnFirstRow(): void
	{
		$scalar = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->queryScalar();
		$this->assertSame('Alice', $scalar);
	}

	public function testSqliteQueryScalarReturnsFalseWhenNoRows(): void
	{
		$scalar = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = 999')->queryScalar();
		$this->assertFalse($scalar);
	}

	public function testSqliteQueryScalarWorksForCountAggregate(): void
	{
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM cmd_test')->queryScalar();
		$this->assertSame(3, $count);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryColumn()
	// -----------------------------------------------------------------------

	public function testSqliteQueryColumnReturnsFirstColumnOfAllRows(): void
	{
		$names = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->queryColumn();
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testSqliteQueryColumnReturnsEmptyArrayWhenNoRows(): void
	{
		$result = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = 999')->queryColumn();
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	public function testSqliteQueryColumnWorksForNumericColumn(): void
	{
		$ids = $this->_conn->createCommand('SELECT id FROM cmd_test ORDER BY id')->queryColumn();
		$this->assertCount(3, $ids);
		$this->assertSame('1', (string) $ids[0]);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — parameter binding
	// -----------------------------------------------------------------------

	public function testSqliteBindParameterWithPositionalPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = ?');
		$id = 2;
		$cmd->bindParameter(1, $id);
		$this->assertSame('Bob', $cmd->queryScalar());
	}

	public function testSqliteBindValueWithNamedPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', $cmd->queryScalar());
	}

	public function testSqliteBindValueTypeInt(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 1, \PDO::PARAM_INT);
		$this->assertSame('Alice', $cmd->queryScalar());
	}

	public function testSqliteBindValueTypeStr(): void
	{
		$cmd = $this->_conn->createCommand("SELECT id FROM cmd_test WHERE name = :name");
		$cmd->bindValue(':name', 'Carol', \PDO::PARAM_STR);
		$this->assertSame('3', (string) $cmd->queryScalar());
	}

	public function testSqlitePreparedStatementCanBeExecutedMultipleTimes(): void
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

	public function testSqliteQueryRowReturnsNullForNullColumn(): void
	{
		$row = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->queryRow();
		$this->assertNull($row['note']);
	}

	public function testSqliteQueryScalarReturnsNullForNullColumn(): void
	{
		$scalar = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->queryScalar();
		$this->assertNull($scalar);
	}

	// -----------------------------------------------------------------------
	// TDbDataReader — via query()
	// -----------------------------------------------------------------------

	public function testSqliteQueryReturnsDataReader(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		$this->assertInstanceOf(TDbDataReader::class, $reader);
		$reader->close();
	}

	public function testSqliteDataReaderReadReturnsRowsThenFalse(): void
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

	public function testSqliteDataReaderReadAllReturnsAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->query();
		$rows = $reader->readAll();
		$this->assertCount(3, $rows);
		$reader->close();
	}

	public function testSqliteDataReaderReadColumnByIndex(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->query();
		$name = $reader->readColumn(1); // second column = name
		$this->assertSame('Alice', $name);
		$reader->close();
	}

	public function testSqliteDataReaderForeachIteratesAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->query();
		$names = [];
		foreach ($reader as $row) {
			$names[] = $row['name'];
		}
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testSqliteDataReaderGetColumnCount(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name, score FROM cmd_test')->query();
		$this->assertSame(3, $reader->getColumnCount());
		$reader->close();
	}

	public function testSqliteDataReaderNullValueReturnedForNullColumn(): void
	{
		$reader = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->query();
		$row = $reader->read();
		$this->assertNull($row['note']);
		$reader->close();
	}

	public function testSqliteDataReaderEmptyResultSetReadReturnsFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->query();
		$this->assertFalse($reader->read());
		$reader->close();
	}

	public function testSqliteDataReaderClosePreventsFurtherReading(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		$reader->close();
		$this->assertTrue($reader->getIsClosed());
	}

	public function testSqliteDataReaderRewindThrowsOnSecondIteration(): void
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

	public function testSqliteDataReaderFetchModeNum(): void
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
