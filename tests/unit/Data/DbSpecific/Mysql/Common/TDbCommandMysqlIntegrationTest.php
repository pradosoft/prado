<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\Data\TDbDataReader;
use Prado\TApplication;

/**
 * Live integration tests for TDbCommand and TDbDataReader — MySQL.
 *
 * Exercises query methods, parameter binding, and the TDbDataReader
 * iteration API against a real MySQL database.
 *
 * Table schema used throughout:
 *   cmd_test (id INT PRIMARY KEY, name VARCHAR(100), score DOUBLE, active TINYINT(1), note VARCHAR(100))
 *
 * Seed rows:
 *   (1, 'Alice', 9.5, 1, 'first')
 *   (2, 'Bob',   7.3, 0, NULL)
 *   (3, 'Carol', 8.1, 1, 'third')
 */
class TDbCommandMysqlIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openMysql(): TDbConnection
	{
		$conn = PradoUnit::setupMysqlConnection('prado_unitest');
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
		$this->_conn = $this->openMysql();
		$this->_conn->createCommand(
			'CREATE TABLE IF NOT EXISTS cmd_test (id INT PRIMARY KEY, name VARCHAR(100), score DOUBLE, active TINYINT(1), note VARCHAR(100))'
		)->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (1, 'Alice', 9.5, 1, 'first')")->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (2, 'Bob',   7.3, 0, NULL)")->execute();
		$this->_conn->createCommand("INSERT INTO cmd_test VALUES (3, 'Carol', 8.1, 1, 'third')")->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand('DROP TABLE IF EXISTS cmd_test')->execute();
			} catch (\Exception $e) {
			}
			$this->_conn->Active = false;
		}
		$this->_conn = null;
	}

	// -----------------------------------------------------------------------
	// TDbCommand — execute()
	// -----------------------------------------------------------------------

	public function testExecuteRunsDdlWithoutError(): void
	{
		// execute() on a non-query statement must not throw.
		$this->_conn->createCommand('CREATE TABLE IF NOT EXISTS exec_ddl_test (x INT)')->execute();
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM exec_ddl_test')->queryScalar();
		$this->assertSame(0, $count);
		$this->_conn->createCommand('DROP TABLE IF EXISTS exec_ddl_test')->execute();
	}

	public function testExecuteReturnsRowCountForInsert(): void
	{
		$affected = $this->_conn->createCommand(
			"INSERT INTO cmd_test VALUES (99, 'Zoe', 5.0, 0, NULL)"
		)->execute();
		$this->assertSame(1, $affected);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryAll()
	// -----------------------------------------------------------------------

	public function testQueryAllReturnsAllRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryAll();
		$this->assertCount(3, $rows);
		$this->assertSame('Alice', $rows[0]['name']);
		$this->assertSame('Bob',   $rows[1]['name']);
		$this->assertSame('Carol', $rows[2]['name']);
	}

	public function testQueryAllReturnsAssocArraysByDefault(): void
	{
		$rows = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->queryAll();
		$this->assertArrayHasKey('id',   $rows[0]);
		$this->assertArrayHasKey('name', $rows[0]);
	}

	public function testQueryAllReturnsEmptyArrayWhenNoRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->queryAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryRow()
	// -----------------------------------------------------------------------

	public function testQueryRowReturnsFirstRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryRow();
		$this->assertIsArray($row);
		$this->assertSame('Alice', $row['name']);
	}

	public function testQueryRowReturnsFalseWhenNoRows(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->queryRow();
		$this->assertFalse($row);
	}

	public function testQueryRowReturnsOnlyOneRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->queryRow();
		// Only a single array (one row), not a nested array.
		$this->assertArrayHasKey('name', $row);
		$this->assertArrayNotHasKey(0, $row);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryScalar()
	// -----------------------------------------------------------------------

	public function testQueryScalarReturnsFirstColumnFirstRow(): void
	{
		$scalar = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->queryScalar();
		$this->assertSame('Alice', $scalar);
	}

	public function testQueryScalarReturnsFalseWhenNoRows(): void
	{
		$scalar = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = 999')->queryScalar();
		$this->assertFalse($scalar);
	}

	public function testQueryScalarWorksForCountAggregate(): void
	{
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM cmd_test')->queryScalar();
		$this->assertSame(3, $count);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryColumn()
	// -----------------------------------------------------------------------

	public function testQueryColumnReturnsFirstColumnOfAllRows(): void
	{
		$names = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->queryColumn();
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testQueryColumnReturnsEmptyArrayWhenNoRows(): void
	{
		$result = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = 999')->queryColumn();
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	public function testQueryColumnWorksForNumericColumn(): void
	{
		$ids = $this->_conn->createCommand('SELECT id FROM cmd_test ORDER BY id')->queryColumn();
		$this->assertCount(3, $ids);
		$this->assertSame('1', (string) $ids[0]);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — parameter binding
	// -----------------------------------------------------------------------

	public function testBindParameterWithPositionalPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = ?');
		$id = 2;
		$cmd->bindParameter(1, $id);
		$this->assertSame('Bob', $cmd->queryScalar());
	}

	public function testBindValueWithNamedPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', $cmd->queryScalar());
	}

	public function testBindValueTypeInt(): void
	{
		$cmd = $this->_conn->createCommand('SELECT name FROM cmd_test WHERE id = :id');
		$cmd->bindValue(':id', 1, \PDO::PARAM_INT);
		$this->assertSame('Alice', $cmd->queryScalar());
	}

	public function testBindValueTypeStr(): void
	{
		$cmd = $this->_conn->createCommand("SELECT id FROM cmd_test WHERE name = :name");
		$cmd->bindValue(':name', 'Carol', \PDO::PARAM_STR);
		$this->assertSame('3', (string) $cmd->queryScalar());
	}

	public function testPreparedStatementCanBeExecutedMultipleTimes(): void
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

	public function testQueryRowReturnsNullForNullColumn(): void
	{
		$row = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->queryRow();
		$this->assertNull($row['note']);
	}

	public function testQueryScalarReturnsNullForNullColumn(): void
	{
		$scalar = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->queryScalar();
		$this->assertNull($scalar);
	}

	// -----------------------------------------------------------------------
	// TDbDataReader — via query()
	// -----------------------------------------------------------------------

	public function testQueryReturnsDataReader(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		$this->assertInstanceOf(TDbDataReader::class, $reader);
		$reader->close();
	}

	public function testDataReaderReadReturnsRowsThenFalse(): void
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

	public function testDataReaderReadAllReturnsAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test ORDER BY id')->query();
		$rows = $reader->readAll();
		$this->assertCount(3, $rows);
		$reader->close();
	}

	public function testDataReaderReadColumnByIndex(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name FROM cmd_test ORDER BY id')->query();
		$name = $reader->readColumn(1); // second column = name
		$this->assertSame('Alice', $name);
		$reader->close();
	}

	public function testDataReaderForeachIteratesAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT name FROM cmd_test ORDER BY id')->query();
		$names = [];
		foreach ($reader as $row) {
			$names[] = $row['name'];
		}
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testDataReaderGetColumnCount(): void
	{
		$reader = $this->_conn->createCommand('SELECT id, name, score FROM cmd_test')->query();
		$this->assertSame(3, $reader->getColumnCount());
		$reader->close();
	}

	public function testDataReaderNullValueReturnedForNullColumn(): void
	{
		$reader = $this->_conn->createCommand('SELECT note FROM cmd_test WHERE id = 2')->query();
		$row = $reader->read();
		$this->assertNull($row['note']);
		$reader->close();
	}

	public function testDataReaderEmptyResultSetReadReturnsFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test WHERE id = 999')->query();
		$this->assertFalse($reader->read());
		$reader->close();
	}

	public function testDataReaderClosePreventsFurtherReading(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM cmd_test')->query();
		$reader->close();
		$this->assertTrue($reader->getIsClosed());
	}

	public function testDataReaderRewindThrowsOnSecondIteration(): void
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

	public function testDataReaderFetchModeNum(): void
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
