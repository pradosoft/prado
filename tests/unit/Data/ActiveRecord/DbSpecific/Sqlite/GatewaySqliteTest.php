<?php

namespace Prado\Test\Unit\Data\ActiveRecord\DbSpecific\Sqlite;

use Prado\Caching\TCache;
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordGateway;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\Common\Sqlite\TSqliteTableColumn;
use Prado\Data\DataGateway\TDataGatewayCommand;
use Prado\Data\IDataConnection;
use Prado\Data\TDbConnection;
use Prado\Test\Unit\PradoUnit;

class GwItem extends TActiveRecord
{
	public const TABLE = 'gw_items';

	public $id;
	public $name;
	public $note;
}

/** Without TABLE or table(), the table name is the short class name in lower case. */
class GwDefault extends TActiveRecord
{
	public $id;
}

class GwEmptyConst extends TActiveRecord
{
	public const TABLE = '';
}

class GwTableMethod extends TActiveRecord
{
	public $id;
	public $name;
	public $note;

	public function table()
	{
		return 'gw_items';
	}
}

class GwEmptyMethod extends TActiveRecord
{
	public function table()
	{
		return '';
	}
}

/** gw_plain has no primary key, so equals() compares every column. */
class GwPlain extends TActiveRecord
{
	public const TABLE = 'gw_plain';

	public $a;
	public $b;
}

class GwCommand extends TDataGatewayCommand
{
}

class GwGateway extends TActiveRecordGateway
{
}

/** A column the gateway leaves out of inserts and updates, as SQL Server timestamp columns are. */
class GwExcludedColumn extends TSqliteTableColumn
{
	public function getIsExcluded()
	{
		return true;
	}
}

/** An IDataConnection that is not a TDbConnection, delegating to one. */
class GwWrappedConnection implements IDataConnection
{
	public function __construct(private TDbConnection $_inner)
	{
	}

	public function getDriverName()
	{
		return $this->_inner->getDriverName();
	}

	public function getActive()
	{
		return $this->_inner->getActive();
	}

	public function setActive($value)
	{
		$this->_inner->setActive($value);
	}

	public function createCommand($query)
	{
		return $this->_inner->createCommand($query);
	}

	public function beginTransaction()
	{
		return $this->_inner->beginTransaction();
	}

	public function getCurrentTransaction()
	{
		return $this->_inner->getCurrentTransaction();
	}

	public function getLastTransaction(): ?\Prado\Data\IDataTransaction
	{
		return $this->_inner->getLastTransaction();
	}

	public function commit(): ?bool
	{
		return $this->_inner->commit();
	}

	public function rollback(): ?bool
	{
		return $this->_inner->rollback();
	}

	public function getDbMetaData()
	{
		return $this->_inner->getDbMetaData();
	}

	public function getLastInsertID($sequenceName = '')
	{
		return $this->_inner->getLastInsertID($sequenceName);
	}

	public function getConnectionString()
	{
		return $this->_inner->getConnectionString();
	}

	public function quoteString($str)
	{
		return $this->_inner->quoteString($str);
	}

	public function getColumnCase()
	{
		return $this->_inner->getColumnCase();
	}

	public function setColumnCase($value)
	{
		$this->_inner->setColumnCase($value);
	}

	public function getAttribute($name)
	{
		return $this->_inner->getAttribute($name);
	}

	public function setAttribute($name, $value)
	{
		$this->_inner->setAttribute($name, $value);
	}
}

/**
 * Covers TActiveRecordGateway over in-memory SQLite databases: connection routing,
 * table name resolution, the table information caches, the NOT NULL checks of insert
 * and update, and the gateway events. Each test runs with its own record manager.
 */
class GatewaySqliteTest extends \PHPUnit\Framework\TestCase
{
	private $_previous;
	private TActiveRecordManager $_manager;

	protected function setUp(): void
	{
		$this->_previous = TActiveRecordManager::getInstance();
		$this->_manager = TActiveRecordManager::getInstance(new TActiveRecordManager());
	}

	protected function tearDown(): void
	{
		TActiveRecordManager::getInstance($this->_previous);
	}

	private function database(string $name, string $extra = ''): TDbConnection
	{
		$connection = new TDbConnection('sqlite::memory:');
		$connection->setActive(true);
		$connection->getPdoInstance()->exec(
			"CREATE TABLE gw_items (id integer PRIMARY KEY AUTOINCREMENT, name varchar(20) NOT NULL, note varchar(20));
			CREATE TABLE gw_plain (a integer, b integer);
			CREATE TABLE gwdefault (id integer PRIMARY KEY);
			INSERT INTO gw_items (id, name) VALUES (1, '$name');" . $extra
		);
		return $connection;
	}

	public function testSameConnectionString_eachRecordUsesItsOwnConnection()
	{
		$a = $this->database('in a');
		$b = $this->database('in b', 'CREATE TABLE gw_only_b (id integer PRIMARY KEY);');
		$this->assertSame('in a', (new GwItem([], $a))->findByPk(1)->name);
		$this->assertSame('in b', (new GwItem([], $b))->findByPk(1)->name);
		$this->assertSame('in a', (new GwItem([], $a))->findByPk(1)->name);

		$gateway = $this->_manager->getRecordGateway();
		$this->assertSame('gw_only_b', $gateway->getTableInfo($b, 'gw_only_b')->getTableName());
	}

	public function testFoundRecord_savesToTheFinderConnection()
	{
		$a = $this->database('in a');
		$b = $this->database('in b');
		$this->_manager->setDbConnection($a);

		$record = (new GwItem([], $b))->findByPk(1);
		$this->assertSame($b, $record->getDbConnection());
		$record->note = 'saved';
		$this->assertTrue($record->save());

		$this->assertSame('saved', $b->createCommand('SELECT note FROM gw_items')->queryScalar());
		$this->assertNull($a->createCommand('SELECT note FROM gw_items')->queryScalar());
		foreach ((new GwItem([], $b))->findAll() as $found) {
			$this->assertSame($b, $found->getDbConnection());
		}
	}

	public function testTableName_fromTableMethod()
	{
		$this->_manager->setDbConnection($this->database('method'));
		$this->assertSame('method', GwTableMethod::finder()->findByPk(1)->name);
	}

	public function testTableName_defaultsToShortClassName()
	{
		$this->_manager->setDbConnection($this->database('x'));
		$this->assertSame('gwdefault', (new GwDefault())->getRecordTableInfo()->getTableName());
	}

	public function testTableName_emptyConstantThrows()
	{
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/GwEmptyConst/');
		(new GwEmptyConst())->getRecordTableInfo();
	}

	public function testTableName_emptyMethodThrows()
	{
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/GwEmptyMethod/');
		(new GwEmptyMethod())->getRecordTableInfo();
	}

	public function testTableInfo_storedInAndReadFromApplicationCache()
	{
		$connection = $this->database('cached');
		$stored = [];
		$cache = $this->createMock(TCache::class);
		$cache->method('get')->willReturnCallback(function ($key) use (&$stored) {
			return $stored[$key] ?? false;
		});
		$cache->expects($this->once())->method('set')->willReturnCallback(function ($key, $value) use (&$stored) {
			$stored[$key] = $value;
			return true;
		});
		$this->_manager->setCache($cache);

		$info = $this->_manager->getRecordGateway()->getTableInfo($connection, 'gw_items');
		$this->assertSame(['sqlite::memory:gw_items'], array_keys($stored));

		$fresh = new TActiveRecordGateway($this->_manager);
		$this->assertSame($info, $fresh->getTableInfo($connection, 'gw_items'));
	}

	public function testTableInfo_forOtherDataConnection()
	{
		$wrapped = new GwWrappedConnection($this->database('wrapped'));
		$gateway = $this->_manager->getRecordGateway();
		$info = $gateway->getTableInfo($wrapped, 'gw_items');
		$this->assertSame(['id', 'name', 'note'], $info->getColumns()->getKeys());
		$this->assertSame($info, $gateway->getTableInfo($wrapped, 'gw_items'));
	}

	public function testTableInfo_readsMetaDataFromTheConnection()
	{
		$wrapped = new class ($this->database('meta')) extends GwWrappedConnection {
			public int $metaCalls = 0;

			public function getDbMetaData()
			{
				$this->metaCalls++;
				return parent::getDbMetaData();
			}
		};
		$gateway = $this->_manager->getRecordGateway();

		$info = $gateway->getTableInfo($wrapped, 'gw_items');
		$this->assertSame(['id', 'name', 'note'], $info->getColumns()->getKeys());
		$this->assertSame(1, $wrapped->metaCalls);

		$gateway->getTableInfo($wrapped, 'gw_plain');
		$this->assertSame(1, $wrapped->metaCalls, 'the connection is asked for its meta data once');
	}

	public function testDataGatewayClass_createsTheCommand()
	{
		$gateway = $this->_manager->getRecordGateway();
		$this->assertSame(TActiveRecordGateway::DEFAULT_DATA_GATEWAY_CLASS, $gateway->getDataGatewayClass());
		$gateway->setDataGatewayClass(GwCommand::class);
		$this->assertSame(GwCommand::class, $gateway->getDataGatewayClass());
		$this->assertInstanceOf(GwCommand::class, $gateway->getCommand(new GwItem([], $this->database('cmd'))));
	}

	/**
	 * TSqliteMetaData reports every column as nullable, so the test marks the name
	 * column NOT NULL in the table information the gateway checks.
	 */
	private function requireName(GwItem $record): void
	{
		PradoUnit::invoke($record->getRecordTableInfo()->getColumn('name'), 'setInfo', 'AllowNull', false);
	}

	public function testInsert_nullInNotNullColumnThrows()
	{
		$record = new GwItem(['note' => 'no name'], $this->database('x'));
		$this->requireName($record);
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/name/');
		$record->save();
	}

	public function testUpdate_nullInNotNullColumnThrows()
	{
		$record = (new GwItem([], $this->database('x')))->findByPk(1);
		$this->requireName($record);
		$record->name = null;
		$this->expectException(TActiveRecordException::class);
		$this->expectExceptionMessageMatches('/name/');
		$record->save();
	}

	public function testInsert_readsBackIdentity()
	{
		$connection = $this->database('x');
		$record = new GwItem(['name' => 'second'], $connection);
		$this->assertTrue($record->save());
		$this->assertEquals(2, $record->id);
		$this->assertTrue($record->delete());
		$this->assertSame(1, (int) $connection->createCommand('SELECT COUNT(*) FROM gw_items')->queryScalar());
	}

	public function testEvents_raisedOnGatewayAndRecord()
	{
		$finder = new GwItem([], $this->database('events'));
		$gateway = $this->_manager->getRecordGateway();
		$seen = [];
		$gateway->OnCreateCommand[] = function ($sender, $param) use (&$seen) {
			$seen[] = 'gateway create';
		};
		$gateway->OnExecuteCommand[] = function ($sender, $param) use (&$seen) {
			$seen[] = 'gateway execute';
		};
		$finder->OnCreateCommand[] = function ($sender, $param) use (&$seen) {
			$seen[] = 'record create';
		};
		$finder->OnExecuteCommand[] = function ($sender, $param) use (&$seen) {
			$seen[] = 'record execute';
		};
		$finder->findByPk(1);
		$this->assertSame(['gateway create', 'record create', 'gateway execute', 'record execute'], $seen);
	}

	public function testUpdateAssociatedRecords_delegatesToRelationContext()
	{
		$gateway = new GwGateway($this->_manager);
		$record = new GwItem([], $this->database('x'));
		$this->assertTrue(PradoUnit::invoke($gateway, 'updateAssociatedRecords', $record, true));
		$this->assertTrue(PradoUnit::invoke($gateway, 'updateAssociatedRecords', $record));
	}

	public function testEquals_withoutPrimaryKeyComparesAllColumns()
	{
		$connection = $this->database('x');
		$one = new GwPlain(['a' => 1, 'b' => 2], $connection);
		$same = new GwPlain(['a' => '1', 'b' => 2], $connection);
		$other = new GwPlain(['a' => 1, 'b' => 3], $connection);
		$this->assertTrue($one->equals($same));
		$this->assertFalse($one->equals($same, true));
		$this->assertFalse($one->equals($other));
		$this->assertFalse($one->equals(new GwItem(['id' => 1], $connection)));
	}

	public function testEquals_comparesPrimaryKeyOnly()
	{
		$connection = $this->database('x');
		$one = (new GwItem([], $connection))->findByPk(1);
		$copy = new GwItem(['id' => 1, 'name' => 'changed'], $connection);
		$this->assertTrue($one->equals($copy));
		$this->assertTrue($one->equals($copy, true));
		$copy->id = '1';
		$this->assertFalse($one->equals($copy, true));
	}

	public function testToArrayAndToJson()
	{
		$record = (new GwItem([], $this->database('json')))->findByPk(1);
		$this->assertSame(['id' => 1, 'name' => 'json', 'note' => null], $record->toArray());
		$this->assertSame('{"id":1,"name":"json","note":null}', $record->toJSON());
	}

	public function testExcludedColumn_isNotWritten()
	{
		$connection = $this->database('x');
		$record = new GwItem(['name' => 'excluded', 'note' => 'ignored'], $connection);
		$columns = $record->getRecordTableInfo()->getColumns();
		$note = $columns->itemAt('note');
		$columns->add('note', new GwExcludedColumn(PradoUnit::getProp($note, '_info')));

		$this->assertTrue($record->save());
		$this->assertNull($connection->createCommand("SELECT note FROM gw_items WHERE name = 'excluded'")->queryScalar());
		$record->note = 'still ignored';
		$this->assertTrue($record->save());
		$this->assertNull($connection->createCommand("SELECT note FROM gw_items WHERE name = 'excluded'")->queryScalar());
	}
}
