<?php

use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Exceptions\TConfigurationException;

/**
 * Unit tests for TScaffoldInputBase.
 *
 * Tests the createInputBuilder factory method. The fxActiveRecordScaffoldInputClass
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
		// TDbDriverCapabilities::createScaffoldInput raises fxActiveRecordScaffoldInputClass
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
		// The fxActiveRecordScaffoldInputClass event must be raised on the connection
		// with the caller class and connection as arguments. This is delegated to
		// TDbDriverCapabilities::createScaffoldInput, which calls $connection->raiseEvent().
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();

		$conn->expects($this->once())
			->method('raiseEvent')
			->with('fxActiveRecordScaffoldInputClass', $this->anything(), $conn)
			->willReturn([]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_throws_when_event_returns_instance_instead_of_class_name()
	{
		// Event handlers must return a class name string implementing IScaffoldInput.
		// If a handler returns an IScaffoldInput instance instead, TDbDriverCapabilities
		// throws TConfigurationException before instantiation.
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();
		$badReturn = $this->createMock(\Prado\Data\ActiveRecord\Scaffold\InputBuilder\IScaffoldInput::class);
		$conn->method('raiseEvent')->willReturn([$badReturn]);

		$this->expectException(TConfigurationException::class);
		TScaffoldInputBase::createInputBuilder($record);
	}

	public function test_createInputBuilder_throws_when_event_class_does_not_implement_IScaffoldInput()
	{
		// If the class name returned by the event does not implement IScaffoldInput,
		// TDbDriverCapabilities::createScaffoldInput must throw TConfigurationException
		// before attempting instantiation.
		$record = $this->createMockRecord('custom_driver');
		$conn = $record->getDbConnection();
		$conn->method('raiseEvent')->willReturn([\stdClass::class]);

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