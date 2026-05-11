<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\Data\TDbDataReader;
use Prado\TApplication;

/**
 * Live integration tests for TDbCommand and TDbDataReader — IBM DB2.
 *
 * Exercises query methods, parameter binding, and the TDbDataReader
 * iteration API against a real IBM DB2 database.
 *
 * IBM DB2 DDL auto-commits outside explicit transactions. Column names are
 * returned in uppercase.
 *
 * Table schema used throughout (uppercase, as DB2 returns):
 *   CMD_TEST (ID INTEGER NOT NULL PRIMARY KEY, NAME VARCHAR(100),
 *             SCORE DOUBLE, ACTIVE SMALLINT, NOTE VARCHAR(100))
 *
 * Seed rows:
 *   (1, 'Alice', 9.5, 1, 'first')
 *   (2, 'Bob',   7.3, 0, NULL)
 *   (3, 'Carol', 8.1, 1, 'third')
 */
class TDbCommandIbmIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openIbm(): TDbConnection
	{
		$conn = PradoUnit::setupIbmConnection();
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
		$this->_conn = $this->openIbm();

		// DB2 DDL auto-commits; drop any leftover table before creating.
		try {
			$this->_conn->createCommand('DROP TABLE CMD_TEST')->execute();
		} catch (\Exception $e) {
			// Table may not exist yet — that's fine.
		}
		$this->_conn->createCommand(
			'CREATE TABLE CMD_TEST (ID INTEGER NOT NULL PRIMARY KEY, NAME VARCHAR(100), SCORE DOUBLE, ACTIVE SMALLINT, NOTE VARCHAR(100))'
		)->execute();
		$this->_conn->createCommand("INSERT INTO CMD_TEST VALUES (1, 'Alice', 9.5, 1, 'first')")->execute();
		$this->_conn->createCommand("INSERT INTO CMD_TEST VALUES (2, 'Bob',   7.3, 0, NULL)")->execute();
		$this->_conn->createCommand("INSERT INTO CMD_TEST VALUES (3, 'Carol', 8.1, 1, 'third')")->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_conn && $this->_conn->getActive()) {
			try {
				$this->_conn->createCommand('DROP TABLE CMD_TEST')->execute();
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
		try {
			$this->_conn->createCommand('DROP TABLE EXEC_DDL_TEST')->execute();
		} catch (\Exception $e) {
		}
		$this->_conn->createCommand('CREATE TABLE EXEC_DDL_TEST (X INTEGER)')->execute();
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM EXEC_DDL_TEST')->queryScalar();
		$this->assertSame(0, $count);
		$this->_conn->createCommand('DROP TABLE EXEC_DDL_TEST')->execute();
	}

	public function testExecuteReturnsRowCountForInsert(): void
	{
		$affected = $this->_conn->createCommand(
			"INSERT INTO CMD_TEST VALUES (99, 'Zoe', 5.0, 0, NULL)"
		)->execute();
		$this->assertSame(1, $affected);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryAll()
	// -----------------------------------------------------------------------

	public function testQueryAllReturnsAllRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->queryAll();
		$this->assertCount(3, $rows);
		$this->assertSame('Alice', rtrim($rows[0]['NAME']));
		$this->assertSame('Bob',   rtrim($rows[1]['NAME']));
		$this->assertSame('Carol', rtrim($rows[2]['NAME']));
	}

	public function testQueryAllReturnsAssocArraysByDefault(): void
	{
		$rows = $this->_conn->createCommand('SELECT ID, NAME FROM CMD_TEST ORDER BY ID')->queryAll();
		$this->assertArrayHasKey('ID',   $rows[0]);
		$this->assertArrayHasKey('NAME', $rows[0]);
	}

	public function testQueryAllReturnsEmptyArrayWhenNoRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM CMD_TEST WHERE ID = 999')->queryAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryRow()
	// -----------------------------------------------------------------------

	public function testQueryRowReturnsFirstRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->queryRow();
		$this->assertIsArray($row);
		$this->assertSame('Alice', rtrim($row['NAME']));
	}

	public function testQueryRowReturnsFalseWhenNoRows(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM CMD_TEST WHERE ID = 999')->queryRow();
		$this->assertFalse($row);
	}

	public function testQueryRowReturnsOnlyOneRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->queryRow();
		// Only a single array (one row), not a nested array.
		$this->assertArrayHasKey('NAME', $row);
		$this->assertArrayNotHasKey(0, $row);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryScalar()
	// -----------------------------------------------------------------------

	public function testQueryScalarReturnsFirstColumnFirstRow(): void
	{
		$scalar = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST ORDER BY ID')->queryScalar();
		$this->assertSame('Alice', rtrim($scalar));
	}

	public function testQueryScalarReturnsFalseWhenNoRows(): void
	{
		$scalar = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = 999')->queryScalar();
		$this->assertFalse($scalar);
	}

	public function testQueryScalarWorksForCountAggregate(): void
	{
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM CMD_TEST')->queryScalar();
		$this->assertSame(3, $count);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryColumn()
	// -----------------------------------------------------------------------

	public function testQueryColumnReturnsFirstColumnOfAllRows(): void
	{
		$names = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST ORDER BY ID')->queryColumn();
		$this->assertCount(3, $names);
		$this->assertSame('Alice', rtrim($names[0]));
		$this->assertSame('Bob',   rtrim($names[1]));
		$this->assertSame('Carol', rtrim($names[2]));
	}

	public function testQueryColumnReturnsEmptyArrayWhenNoRows(): void
	{
		$result = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = 999')->queryColumn();
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	public function testQueryColumnWorksForNumericColumn(): void
	{
		$ids = $this->_conn->createCommand('SELECT ID FROM CMD_TEST ORDER BY ID')->queryColumn();
		$this->assertCount(3, $ids);
		$this->assertSame('1', (string) $ids[0]);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — parameter binding
	// -----------------------------------------------------------------------

	public function testBindParameterWithPositionalPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = ?');
		$id = 2;
		$cmd->bindParameter(1, $id);
		$this->assertSame('Bob', rtrim($cmd->queryScalar()));
	}

	public function testBindValueWithNamedPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', rtrim($cmd->queryScalar()));
	}

	public function testBindValueTypeInt(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
		$cmd->bindValue(':id', 1, \PDO::PARAM_INT);
		$this->assertSame('Alice', rtrim($cmd->queryScalar()));
	}

	public function testBindValueTypeStr(): void
	{
		$cmd = $this->_conn->createCommand("SELECT ID FROM CMD_TEST WHERE NAME = :name");
		$cmd->bindValue(':name', 'Carol', \PDO::PARAM_STR);
		$this->assertSame('3', (string) $cmd->queryScalar());
	}

	public function testPreparedStatementCanBeExecutedMultipleTimes(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
		$cmd->bindValue(':id', 1);
		$this->assertSame('Alice', rtrim($cmd->queryScalar()));

		$cmd->bindValue(':id', 2);
		$this->assertSame('Bob', rtrim($cmd->queryScalar()));

		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', rtrim($cmd->queryScalar()));
	}

	// -----------------------------------------------------------------------
	// TDbCommand — NULL values
	// -----------------------------------------------------------------------

	public function testQueryRowReturnsNullForNullColumn(): void
	{
		$row = $this->_conn->createCommand('SELECT NOTE FROM CMD_TEST WHERE ID = 2')->queryRow();
		$this->assertNull($row['NOTE']);
	}

	public function testQueryScalarReturnsNullForNullColumn(): void
	{
		$scalar = $this->_conn->createCommand('SELECT NOTE FROM CMD_TEST WHERE ID = 2')->queryScalar();
		$this->assertNull($scalar);
	}

	// -----------------------------------------------------------------------
	// TDbDataReader — via query()
	// -----------------------------------------------------------------------

	public function testQueryReturnsDataReader(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST')->query();
		$this->assertInstanceOf(TDbDataReader::class, $reader);
		$reader->close();
	}

	public function testDataReaderReadReturnsRowsThenFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT ID FROM CMD_TEST ORDER BY ID')->query();
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
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->query();
		$rows = $reader->readAll();
		$this->assertCount(3, $rows);
		$reader->close();
	}

	public function testDataReaderReadColumnByIndex(): void
	{
		$reader = $this->_conn->createCommand('SELECT ID, NAME FROM CMD_TEST ORDER BY ID')->query();
		$name = $reader->readColumn(1); // second column = NAME
		$this->assertSame('Alice', rtrim($name));
		$reader->close();
	}

	public function testDataReaderForeachIteratesAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST ORDER BY ID')->query();
		$names = [];
		foreach ($reader as $row) {
			$names[] = rtrim($row['NAME']);
		}
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testDataReaderGetColumnCount(): void
	{
		$reader = $this->_conn->createCommand('SELECT ID, NAME, SCORE FROM CMD_TEST')->query();
		$this->assertSame(3, $reader->getColumnCount());
		$reader->close();
	}

	public function testDataReaderNullValueReturnedForNullColumn(): void
	{
		$reader = $this->_conn->createCommand('SELECT NOTE FROM CMD_TEST WHERE ID = 2')->query();
		$row = $reader->read();
		$this->assertNull($row['NOTE']);
		$reader->close();
	}

	public function testDataReaderEmptyResultSetReadReturnsFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST WHERE ID = 999')->query();
		$this->assertFalse($reader->read());
		$reader->close();
	}

	public function testDataReaderClosePreventsFurtherReading(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST')->query();
		$reader->close();
		$this->assertTrue($reader->getIsClosed());
	}

	public function testDataReaderRewindThrowsOnSecondIteration(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST')->query();
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
		$reader = $this->_conn->createCommand('SELECT ID, NAME FROM CMD_TEST ORDER BY ID')->query();
		$reader->setFetchMode(\PDO::FETCH_NUM);
		$row = $reader->read();
		// Numeric-indexed: 0 = ID, 1 = NAME.
		$this->assertArrayHasKey(0, $row);
		$this->assertArrayHasKey(1, $row);
		$this->assertArrayNotHasKey('ID', $row);
		$reader->close();
	}
}
