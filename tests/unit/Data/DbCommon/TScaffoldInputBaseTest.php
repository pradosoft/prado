<?php

namespace Prado\Test\Unit\Data\DbCommon;

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput;
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\TDbDriver;
use Prado\Exceptions\TConfigurationException;

/**
 * Unit tests for TScaffoldInputBase.
 *
 * Tests the createInputBuilder factory method and fxActiveRecordScaffoldInputClass event.
 */
class TScaffoldInputBaseTest extends \PHPUnit\Framework\TestCase
{
	private function createMockRecord(string $driver): TActiveRecord
	{
		$conn = $this->createMock(\Prado\Data\TDbConnection::class);
		$conn->method('getDriverName')->willReturn($driver);

		$record = $this->createMock(TActiveRecord::class);
		$record->method('getDbConnection')->willReturn($conn);
		return $record;
	}

	public function test_createInputBuilder_throws_for_unknown_driver_with_no_event_handlers()
	{
		$record = $this->createMockRecord('unknown_driver');
		$conn = $record->getDbConnection();
		$conn->expects($this->once())
			->method('raiseEvent')
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_raises_fxActiveRecordScaffoldInputClass_for_unknown_driver()
	{
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();

		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordScaffoldInputClass', $this->anything(), 'custom_driver')
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_calls_setActive_on_connection()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLITE);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('setActive')->with(true);

		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_valid_mysql_driver()
	{
		$record = $this->createMockRecord(TDbDriver::EXTENSION_MYSQLI);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_mysql_old_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_MYSQL);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlite_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLITE);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlite2_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_SQLITE2);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_pgsql_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_PGSQL);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_mssql_driver()
	{
		$record = $this->createMockRecord(TDbDriver::EXTENSION_MSSQL);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqlSrvScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_ibm_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_IBM);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TIbmScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_firebird_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_FIREBIRD);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_interbase_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_INTERBASE);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_driver_name_is_case_insensitive()
	{
		$record = $this->createMockRecord('PGSQL');
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('raiseEvent')->willReturn([]);

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_event_class_overrides_built_in_driver()
	{
		$record = $this->createMockRecord(TDbDriver::EXTENSION_MYSQLI);
		$conn = $record->getDbConnection();
		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordScaffoldInputClass', $conn, TDbDriver::EXTENSION_MYSQLI)
			->willReturn([TPgsqlScaffoldInput::class]);

		$this->assertInstanceOf(TPgsqlScaffoldInput::class, TScaffoldInputBase::createInputBuilder($record));
	}

	public function test_createInputBuilder_rejects_invalid_event_class_for_built_in_driver()
	{
		$record = $this->createMockRecord(TDbDriver::DRIVER_MYSQL);
		$record->getDbConnection()->method('raiseEvent')->willReturn([\stdClass::class]);

		try {
			TScaffoldInputBase::createInputBuilder($record);
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('ar_not_input_base', $e->getErrorCode());
		}
	}

	public function test_createInputBuilder_listening_handler_serves_only_its_connection()
	{
		$createConnection = fn () => new class () extends \Prado\TComponent {
			public function setActive($value)
			{
			}

			public function getDriverName()
			{
				return TDbDriver::EXTENSION_MYSQLI;
			}
		};
		$pluginConn = $createConnection();
		$otherConn = $createConnection();
		$provider = new class ($pluginConn) extends \Prado\TComponent {
			public function __construct(private object $_conn)
			{
				parent::__construct();
			}

			public function fxActiveRecordScaffoldInputClass($sender, $driver)
			{
				return $sender === $this->_conn ? TPgsqlScaffoldInput::class : null;
			}
		};
		$createRecord = function (object $conn): TActiveRecord {
			$record = $this->createMock(TActiveRecord::class);
			$record->method('getDbConnection')->willReturn($conn);
			return $record;
		};

		$provider->listen();
		try {
			$this->assertInstanceOf(TPgsqlScaffoldInput::class, TScaffoldInputBase::createInputBuilder($createRecord($pluginConn)));
			$this->assertInstanceOf(TMysqlScaffoldInput::class, TScaffoldInputBase::createInputBuilder($createRecord($otherConn)));
		} finally {
			$provider->unlisten();
		}
	}

	public function test_createInputBuilder_uses_first_class_from_fxActiveRecordScaffoldInputClass()
	{
		$record = $this->createMockRecord('custom_driver');
		$record->getDbConnection()->method('raiseEvent')
			->willReturn([TPgsqlScaffoldInput::class, TMysqlScaffoldInput::class]);

		$this->assertInstanceOf(TPgsqlScaffoldInput::class, TScaffoldInputBase::createInputBuilder($record));
	}

	public function test_default_id_constant()
	{
		$this->assertEquals('scaffold_input', TScaffoldInputBase::DEFAULT_ID);
	}
}