<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\Data\TDbDataReader;
use Prado\TApplication;

/**
 * Live integration tests for TDbCommand and TDbDataReader — Oracle.
 *
 * Exercises query methods, parameter binding, and the TDbDataReader
 * iteration API against a real Oracle database.
 *
 * Oracle DDL auto-commits; CREATE TABLE and DROP TABLE are issued outside
 * any explicit transaction. Column names are returned in uppercase.
 *
 * Table schema used throughout (uppercase, as Oracle returns):
 *   CMD_TEST (ID NUMBER(10) NOT NULL PRIMARY KEY, NAME VARCHAR2(100),
 *             SCORE BINARY_DOUBLE, ACTIVE NUMBER(1), NOTE VARCHAR2(100))
 *
 * Seed rows:
 *   (1, 'Alice', 9.5, 1, 'first')
 *   (2, 'Bob',   7.3, 0, NULL)
 *   (3, 'Carol', 8.1, 1, 'third')
 */
class TDbCommandOracleIntegrationTest extends PHPUnit\Framework\TestCase
{
	private ?TDbConnection $_conn = null;

	private function openOracle(): TDbConnection
	{
		$conn = PradoUnit::setupOracleConnection();
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
		$this->_conn = $this->openOracle();

		// Oracle DDL auto-commits; drop any leftover table before creating.
		try {
			$this->_conn->createCommand('DROP TABLE CMD_TEST')->execute();
		} catch (\Exception $e) {
			// Table may not exist yet — that's fine.
		}
		$this->_conn->createCommand(
			'CREATE TABLE CMD_TEST (ID NUMBER(10) NOT NULL PRIMARY KEY, NAME VARCHAR2(100), SCORE BINARY_DOUBLE, ACTIVE NUMBER(1), NOTE VARCHAR2(100))'
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

	public function testOracleExecuteRunsDdlWithoutError(): void
	{
		// execute() on a non-query statement must not throw.
		try {
			$this->_conn->createCommand('DROP TABLE EXEC_DDL_TEST')->execute();
		} catch (\Exception $e) {
		}
		$this->_conn->createCommand('CREATE TABLE EXEC_DDL_TEST (X NUMBER(10))')->execute();
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM EXEC_DDL_TEST')->queryScalar();
		$this->assertSame(0, $count);
		$this->_conn->createCommand('DROP TABLE EXEC_DDL_TEST')->execute();
	}

	public function testOracleExecuteReturnsRowCountForInsert(): void
	{
		$affected = $this->_conn->createCommand(
			"INSERT INTO CMD_TEST VALUES (99, 'Zoe', 5.0, 0, NULL)"
		)->execute();
		$this->assertSame(1, $affected);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryAll()
	// -----------------------------------------------------------------------

	public function testOracleQueryAllReturnsAllRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->queryAll();
		$this->assertCount(3, $rows);
		$this->assertSame('Alice', $rows[0]['NAME']);
		$this->assertSame('Bob',   $rows[1]['NAME']);
		$this->assertSame('Carol', $rows[2]['NAME']);
	}

	public function testOracleQueryAllReturnsAssocArraysByDefault(): void
	{
		$rows = $this->_conn->createCommand('SELECT ID, NAME FROM CMD_TEST ORDER BY ID')->queryAll();
		$this->assertArrayHasKey('ID',   $rows[0]);
		$this->assertArrayHasKey('NAME', $rows[0]);
	}

	public function testOracleQueryAllReturnsEmptyArrayWhenNoRows(): void
	{
		$rows = $this->_conn->createCommand('SELECT * FROM CMD_TEST WHERE ID = 999')->queryAll();
		$this->assertIsArray($rows);
		$this->assertCount(0, $rows);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryRow()
	// -----------------------------------------------------------------------

	public function testOracleQueryRowReturnsFirstRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->queryRow();
		$this->assertIsArray($row);
		$this->assertSame('Alice', $row['NAME']);
	}

	public function testOracleQueryRowReturnsFalseWhenNoRows(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM CMD_TEST WHERE ID = 999')->queryRow();
		$this->assertFalse($row);
	}

	public function testOracleQueryRowReturnsOnlyOneRow(): void
	{
		$row = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->queryRow();
		// Only a single array (one row), not a nested array.
		$this->assertArrayHasKey('NAME', $row);
		$this->assertArrayNotHasKey(0, $row);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryScalar()
	// -----------------------------------------------------------------------

	public function testOracleQueryScalarReturnsFirstColumnFirstRow(): void
	{
		$scalar = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST ORDER BY ID')->queryScalar();
		$this->assertSame('Alice', $scalar);
	}

	public function testOracleQueryScalarReturnsFalseWhenNoRows(): void
	{
		$scalar = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = 999')->queryScalar();
		$this->assertFalse($scalar);
	}

	public function testOracleQueryScalarWorksForCountAggregate(): void
	{
		$count = (int) $this->_conn->createCommand('SELECT COUNT(*) FROM CMD_TEST')->queryScalar();
		$this->assertSame(3, $count);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — queryColumn()
	// -----------------------------------------------------------------------

	public function testOracleQueryColumnReturnsFirstColumnOfAllRows(): void
	{
		$names = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST ORDER BY ID')->queryColumn();
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testOracleQueryColumnReturnsEmptyArrayWhenNoRows(): void
	{
		$result = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = 999')->queryColumn();
		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	public function testOracleQueryColumnWorksForNumericColumn(): void
	{
		$ids = $this->_conn->createCommand('SELECT ID FROM CMD_TEST ORDER BY ID')->queryColumn();
		$this->assertCount(3, $ids);
		$this->assertSame('1', (string) $ids[0]);
	}

	// -----------------------------------------------------------------------
	// TDbCommand — parameter binding
	// -----------------------------------------------------------------------

	public function testOracleBindParameterWithNamedPlaceholder(): void
	{
		// Oracle uses named placeholders (:name). TDbCommand::bindParameter()
		// internally falls back to PDOStatement::bindValue() for pdo_oci because
		// PDOStatement::bindParam() segfaults in some PHP 8.2 builds of pdo_oci.
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
		$id = 2;
		$cmd->bindParameter(':id', $id);
		$this->assertSame('Bob', $cmd->queryScalar());
	}

	public function testOracleBindValueWithNamedPlaceholder(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
		$cmd->bindValue(':id', 3);
		$this->assertSame('Carol', $cmd->queryScalar());
	}

	public function testOracleBindValueTypeInt(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
		$cmd->bindValue(':id', 1, \PDO::PARAM_INT);
		$this->assertSame('Alice', $cmd->queryScalar());
	}

	public function testOracleBindValueTypeStr(): void
	{
		$cmd = $this->_conn->createCommand("SELECT ID FROM CMD_TEST WHERE NAME = :name");
		$cmd->bindValue(':name', 'Carol', \PDO::PARAM_STR);
		$this->assertSame('3', (string) $cmd->queryScalar());
	}

	public function testOraclePreparedStatementCanBeExecutedMultipleTimes(): void
	{
		$cmd = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST WHERE ID = :id');
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

	public function testOracleQueryRowReturnsNullForNullColumn(): void
	{
		$row = $this->_conn->createCommand('SELECT NOTE FROM CMD_TEST WHERE ID = 2')->queryRow();
		$this->assertNull($row['NOTE']);
	}

	public function testOracleQueryScalarReturnsNullForNullColumn(): void
	{
		$scalar = $this->_conn->createCommand('SELECT NOTE FROM CMD_TEST WHERE ID = 2')->queryScalar();
		$this->assertNull($scalar);
	}

	// -----------------------------------------------------------------------
	// TDbDataReader — via query()
	// -----------------------------------------------------------------------

	public function testOracleQueryReturnsDataReader(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST')->query();
		$this->assertInstanceOf(TDbDataReader::class, $reader);
		$reader->close();
	}

	public function testOracleDataReaderReadReturnsRowsThenFalse(): void
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

	public function testOracleDataReaderReadAllReturnsAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST ORDER BY ID')->query();
		$rows = $reader->readAll();
		$this->assertCount(3, $rows);
		$reader->close();
	}

	public function testOracleDataReaderReadColumnByIndex(): void
	{
		$reader = $this->_conn->createCommand('SELECT ID, NAME FROM CMD_TEST ORDER BY ID')->query();
		$name = $reader->readColumn(1); // second column = NAME
		$this->assertSame('Alice', $name);
		$reader->close();
	}

	public function testOracleDataReaderForeachIteratesAllRows(): void
	{
		$reader = $this->_conn->createCommand('SELECT NAME FROM CMD_TEST ORDER BY ID')->query();
		$names = [];
		foreach ($reader as $row) {
			$names[] = $row['NAME'];
		}
		$this->assertSame(['Alice', 'Bob', 'Carol'], $names);
	}

	public function testOracleDataReaderGetColumnCount(): void
	{
		$reader = $this->_conn->createCommand('SELECT ID, NAME, SCORE FROM CMD_TEST')->query();
		$this->assertSame(3, $reader->getColumnCount());
		$reader->close();
	}

	public function testOracleDataReaderNullValueReturnedForNullColumn(): void
	{
		$reader = $this->_conn->createCommand('SELECT NOTE FROM CMD_TEST WHERE ID = 2')->query();
		$row = $reader->read();
		$this->assertNull($row['NOTE']);
		$reader->close();
	}

	public function testOracleDataReaderEmptyResultSetReadReturnsFalse(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST WHERE ID = 999')->query();
		$this->assertFalse($reader->read());
		$reader->close();
	}

	public function testOracleDataReaderClosePreventsFurtherReading(): void
	{
		$reader = $this->_conn->createCommand('SELECT * FROM CMD_TEST')->query();
		$reader->close();
		$this->assertTrue($reader->getIsClosed());
	}

	public function testOracleDataReaderRewindThrowsOnSecondIteration(): void
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

	public function testOracleDataReaderFetchModeNum(): void
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
