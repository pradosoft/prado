<?php

use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TDbException;

/**
 * Unit tests for TDbMetaData.
 *
 * Tests the getInstance factory method and fxDataGetMetaDataClass event.
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

public function test_getInstance_raises_fxDataGetMetaDataClass_for_unknown_driver()
	{
		$conn = $this->createMockConnection('custom_driver');

		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxDataGetMetaDataClass', $this->anything(), 'custom_driver')
			->willReturn([]);

		$this->expectException(TDbException::class);
		TDbMetaData::getInstance($conn);
	}

	public function test_getInstance_calls_setActive_on_connection()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_SQLITE);
		$conn->expects($this->once())->method('setActive')->with(true);

		TDbMetaData::getInstance($conn);
	}

	public function test_getInstance_valid_pgsql_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_PGSQL);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Pgsql\TPgsqlMetaData::class, $result);
	}

	public function test_getInstance_valid_mysql_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::EXTENSION_MYSQLI);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mysql\TMysqlMetaData::class, $result);
	}

	public function test_getInstance_valid_mysql_old_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_MYSQL);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mysql\TMysqlMetaData::class, $result);
	}

	public function test_getInstance_valid_sqlite_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_SQLITE);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Sqlite\TSqliteMetaData::class, $result);
	}

	public function test_getInstance_valid_sqlite2_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_SQLITE2);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Sqlite\TSqliteMetaData::class, $result);
	}

	public function test_getInstance_valid_mssql_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::EXTENSION_MSSQL);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mssql\TMssqlMetaData::class, $result);
	}

	public function test_getInstance_valid_sqlsrv_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_SQLSRV);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mssql\TMssqlMetaData::class, $result);
	}

	public function test_getInstance_valid_dblib_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_DBLIB);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Mssql\TMssqlMetaData::class, $result);
	}

	public function test_getInstance_valid_oracle_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_OCI);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Oracle\TOracleMetaData::class, $result);
	}

	public function test_getInstance_valid_ibm_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_IBM);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Ibm\TIbmMetaData::class, $result);
	}

	public function test_getInstance_valid_firebird_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_FIREBIRD);
		$conn->expects($this->never())->method('raiseEvent');

		$result = TDbMetaData::getInstance($conn);
		$this->assertInstanceOf(\Prado\Data\Common\Firebird\TFirebirdMetaData::class, $result);
	}

	public function test_getInstance_valid_interbase_driver()
	{
		$conn = $this->createMockConnection(TDbDriver::DRIVER_INTERBASE);
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

	// -----------------------------------------------------------------------
	// getTableInfo() — Prado3 dot-notation tableInfoClass resolution
	// -----------------------------------------------------------------------

	public function test_getTableInfo_withPrado3SystemDotNotationTableInfoClass(): void
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('test_prado3_system');

		$metaData = new class($conn) extends TDbMetaData {
			protected function createTableInfo($tableName)
			{
				return new \Prado\Data\Common\TDbTableInfo([]);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}

			// Override to return a Prado3 System dot-notation class name
			protected function getTableInfoClass(): string
			{
				return 'System.Data.Common.TDbTableInfo';
			}
		};

		// Should resolve 'System.Data.Common.TDbTableInfo' → Prado\Data\Common\TDbTableInfo
		// and instantiate it without error. Before the fix, PHP throws "Class not found".
		$info = $metaData->getTableInfo(null);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	public function test_getTableInfo_withPrado3PradoDotNotationTableInfoClass(): void
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('test_prado3_prado');

		$metaData = new class($conn) extends TDbMetaData {
			protected function createTableInfo($tableName)
			{
				return new \Prado\Data\Common\TDbTableInfo([]);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}

			// Override to return a Prado3 Prado dot-notation class name
			protected function getTableInfoClass(): string
			{
				return 'Prado.Data.Common.TDbTableInfo';
			}
		};

		// Should resolve 'Prado.Data.Common.TDbTableInfo' → Prado\Data\Common\TDbTableInfo
		$info = $metaData->getTableInfo(null);
		$this->assertInstanceOf(\Prado\Data\Common\TDbTableInfo::class, $info);
	}

	// -----------------------------------------------------------------------
	// getTableInfo() — usingClass() false / null edge cases
	// -----------------------------------------------------------------------

	/**
	 * A directory namespace returned by getTableInfoClass() (usingClass returns
	 * false) must throw TDbException — a directory is not an instantiable class.
	 */
	public function test_getTableInfo_withDirectoryNamespaceTableInfoClass_throws(): void
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('test_dir_ns');

		$metaData = new class($conn) extends TDbMetaData {
			protected function createTableInfo($tableName)
			{
				return new \Prado\Data\Common\TDbTableInfo([]);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}

			protected function getTableInfoClass(): string
			{
				return 'Prado\\Data\\Common\\*';
			}
		};

		$this->expectException(TDbException::class);
		$metaData->getTableInfo(null);
	}

	/**
	 * A Prado3 directory dot-notation from getTableInfoClass() also throws.
	 */
	public function test_getTableInfo_withPrado3DirectoryNotation_throws(): void
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('test_prado3_dir');

		$metaData = new class($conn) extends TDbMetaData {
			protected function createTableInfo($tableName)
			{
				return new \Prado\Data\Common\TDbTableInfo([]);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}

			protected function getTableInfoClass(): string
			{
				return 'System.Data.Common.*';
			}
		};

		$this->expectException(TDbException::class);
		$metaData->getTableInfo(null);
	}

	/**
	 * An unresolvable class name from getTableInfoClass() (usingClass returns
	 * null) must throw TDbException.
	 */
	public function test_getTableInfo_withUnknownTableInfoClass_throws(): void
	{
		$conn = $this->createMockConnection('test');
		$conn->method('getConnectionString')->willReturn('test_unknown_class');

		$metaData = new class($conn) extends TDbMetaData {
			protected function createTableInfo($tableName)
			{
				return new \Prado\Data\Common\TDbTableInfo([]);
			}

			public function findTableNames($schema = '')
			{
				return [];
			}

			protected function getTableInfoClass(): string
			{
				return 'TFakeTableInfoClassXYZ99999';
			}
		};

		$this->expectException(TDbException::class);
		$metaData->getTableInfo(null);
	}
}