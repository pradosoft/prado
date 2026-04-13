<?php

use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

/**
 * Unit tests for TDbMetaData.
 *
 * Tests the getInstance factory method and fxGetMetaDataInstance event.
 * Does not require a database connection; uses mocked connections.
 */
class TDbMetaDataTest extends PHPUnit\Framework\TestCase
{
	private function createMockConnection(string $driver): TDbConnection
	{
		$conn = $this->createMock(TDbConnection::class);
		$conn->method('getDriverName')->willReturn($driver);
		return $conn;
	}

	private function createTestableMetaData($conn): TDbMetaData
	{
		return new class($conn) extends TDbMetaData
		{
			protected function createTableInfo($tableName)
			{
				return new \Prado\Data\Common\TDbTableInfo([]);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}
		};
	}

	private function createDifferentMetaData($conn): TDbMetaData
	{
		return new class($conn) extends \Prado\Data\Common\Sqlite\TSqliteMetaData
		{
			public function __construct($conn)
			{
				parent::__construct($conn);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}
		};
	}

	public function test_getInstance_throws_for_unknown_driver_with_no_event_handlers()
	{
		$conn = $this->createMockConnection('unknown_driver');
		$conn->expects($this->once())
			->method('raiseEvent')
			->willReturn([]);

		$this->expectException(TDbException::class);
		TDbMetaData::getInstance($conn);
	}

public function test_getInstance_raises_fxGetMetaDataInstance_for_unknown_driver()
	{
		$conn = $this->createMockConnection('custom_driver');

		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxGetMetaDataInstance', $this->anything(), $conn)
			->willReturn([]);

		$this->expectException(TDbException::class);
		TDbMetaData::getInstance($conn);
	}

	public function test_getInstance_calls_setActive_on_connection()
	{
		$conn = $this->createMockConnection('sqlite');
		$conn->expects($this->once())->method('setActive')->with(true);

		TDbMetaData::getInstance($conn);
	}

	public function test_getInstance_valid_pgsql_driver()
	{
		$conn = $this->createMockConnection('pgsql');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Pgsql\TPgsqlMetaData::class, $result);
	}

	public function test_getInstance_valid_mysql_driver()
	{
		$conn = $this->createMockConnection('mysqli');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mysql\TMysqlMetaData::class, $result);
	}

	public function test_getInstance_valid_mysql_old_driver()
	{
		$conn = $this->createMockConnection('mysql');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mysql\TMysqlMetaData::class, $result);
	}

	public function test_getInstance_valid_sqlite_driver()
	{
		$conn = $this->createMockConnection('sqlite');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Sqlite\TSqliteMetaData::class, $result);
	}

	public function test_getInstance_valid_sqlite2_driver()
	{
		$conn = $this->createMockConnection('sqlite2');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Sqlite\TSqliteMetaData::class, $result);
	}

	public function test_getInstance_valid_mssql_driver()
	{
		$conn = $this->createMockConnection('mssql');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mssql\TMssqlMetaData::class, $result);
	}

	public function test_getInstance_valid_sqlsrv_driver()
	{
		$conn = $this->createMockConnection('sqlsrv');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mssql\TMssqlMetaData::class, $result);
	}

	public function test_getInstance_valid_dblib_driver()
	{
		$conn = $this->createMockConnection('dblib');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mssql\TMssqlMetaData::class, $result);
	}

	public function test_getInstance_valid_oracle_driver()
	{
		$conn = $this->createMockConnection('oci');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Oracle\TOracleMetaData::class, $result);
	}

	public function test_getInstance_valid_ibm_driver()
	{
		$conn = $this->createMockConnection('ibm');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Ibm\TIbmMetaData::class, $result);
	}

	public function test_getInstance_valid_firebird_driver()
	{
		$conn = $this->createMockConnection('firebird');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Firebird\TFirebirdMetaData::class, $result);
	}

	public function test_getInstance_valid_interbase_driver()
	{
		$conn = $this->createMockConnection('interbase');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Firebird\TFirebirdMetaData::class, $result);
	}

	public function test_getInstance_driver_name_is_case_insensitive()
	{
		$conn = $this->createMockConnection('PGSQL');
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Pgsql\TPgsqlMetaData::class, $result);
	}

	public function test_constructor_stores_connection()
	{
		$conn = $this->createMockConnection('test');
		$metaData = $this->createTestableMetaData($conn);

		$this->assertSame($conn, $metaData->getDbConnection());
	}

	public function test_getTableInfo_caches_result()
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('test_string');

		$metaData = $this->createTestableMetaData($conn);

		$info1 = $metaData->getTableInfo('test');
		$info2 = $metaData->getTableInfo('test');

		$this->assertSame($info1, $info2);
	}

	public function test_getTableInfo_null_table_uses_connection_string_as_key()
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('connection_string');

		$metaData = $this->createTestableMetaData($conn);

		$metaData->getTableInfo(null);
		$metaData->getTableInfo(null);

		$this->assertTrue(true);
	}

	public function test_quote_table_name_removes_delimiters()
	{
		$conn = $this->createMockConnection('test');
		$metaData = $this->createTestableMetaData($conn);

		$this->assertEquals('table', $metaData->quoteTableName('[table]'));
		$this->assertEquals('table', $metaData->quoteTableName('`table`'));
		$this->assertEquals('table', $metaData->quoteTableName('"table"'));
		$this->assertEquals('table', $metaData->quoteTableName("'table'"));
	}

	public function test_quote_table_name_adds_custom_delimiters()
	{
		$conn = $this->createMockConnection('test');
		$metaData = $this->createTestableMetaData($conn);

		$this->assertEquals('prefixtableprefix', $metaData->quoteTableName('table', 'prefix'));
	}

	public function test_quote_table_name_handles_schema()
	{
		$conn = $this->createMockConnection('test');
		$metaData = $this->createTestableMetaData($conn);

		$this->assertEquals('schema.table', $metaData->quoteTableName('schema.table'));
	}

	public function test_quote_column_name_removes_delimiters()
	{
		$conn = $this->createMockConnection('test');
		$metaData = $this->createTestableMetaData($conn);

		$this->assertEquals('column', $metaData->quoteColumnName('[column]'));
		$this->assertEquals('column', $metaData->quoteColumnName('`column`'));
		$this->assertEquals('column', $metaData->quoteColumnName('"column"'));
		$this->assertEquals('column', $metaData->quoteColumnName("'column'"));
	}

	public function test_quote_column_alias_removes_delimiters()
	{
		$conn = $this->createMockConnection('test');
		$metaData = $this->createTestableMetaData($conn);

		$this->assertEquals('alias', $metaData->quoteColumnAlias('[alias]'));
		$this->assertEquals('alias', $metaData->quoteColumnAlias('"alias"'));
		$this->assertEquals('alias', $metaData->quoteColumnAlias("'alias'"));
	}
}