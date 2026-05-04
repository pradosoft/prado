<?php

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Exceptions\TConfigurationException;

/**
 * Unit tests for TScaffoldInputBase.
 *
 * Tests the createInputBuilder factory method. The fxActiveRecordCreateScaffoldInput
 * global event is managed by TDbDriverCapabilities::createScaffoldInput; these tests
 * verify that the event is raised on the connection for unknown drivers (the connection
 * mock intercepts the call regardless of which class triggers it).
 */
class TScaffoldInputBaseTest extends PHPUnit\Framework\TestCase
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
		// TDbDriverCapabilities::createScaffoldInput raises fxActiveRecordCreateScaffoldInput
		// on the connection; when handlers return nothing, TConfigurationException is thrown.
		$record = $this->createMockRecord('unknown_driver');
		$conn = $record->getDbConnection();
		$conn->expects($this->once())
			->method('raiseEvent')
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_fxEvent_raised_with_correct_parameters()
	{
		// The fxActiveRecordCreateScaffoldInput event must be raised on the connection
		// with the caller class and connection as arguments. This is delegated to
		// TDbDriverCapabilities::createScaffoldInput, which calls $connection->raiseEvent().
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();

		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordCreateScaffoldInput', $this->anything(), $conn)
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_throws_when_event_returns_wrong_type()
	{
		// If an event handler returns an object that is not a TScaffoldInputBase
		// subclass, createInputBuilder must throw TConfigurationException.
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();
		$conn->method('raiseEvent')->willReturn([new \stdClass()]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_calls_setActive_on_connection()
	{
		$record = $this->createMockRecord('sqlite');
		$conn = $record->getDbConnection();
		$conn->expects($this->once())->method('setActive')->with(true);

		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_valid_mysql_old_driver()
	{
		$record = $this->createMockRecord('mysql');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TMysqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlite_driver()
	{
		$record = $this->createMockRecord('sqlite');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_sqlite2_driver()
	{
		$record = $this->createMockRecord('sqlite2');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TSqliteScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_pgsql_driver()
	{
		$record = $this->createMockRecord('pgsql');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_ibm_driver()
	{
		$record = $this->createMockRecord('ibm');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TIbmScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_valid_firebird_driver()
	{
		$record = $this->createMockRecord('firebird');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TFirebirdScaffoldInput::class, $result);
	}

	public function test_createInputBuilder_driver_name_is_case_insensitive()
	{
		$record = $this->createMockRecord('PGSQL');
		$conn = $record->getDbConnection();
		$conn->expects($this->never())->method('raiseEvent');

		$result = TScaffoldInputBase::createInputBuilder($record);
		$this->assertInstanceOf(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\TPgsqlScaffoldInput::class, $result);
	}

	public function test_default_id_constant()
	{
		$this->assertEquals('scaffold_input', TScaffoldInputBase::DEFAULT_ID);
	}
}