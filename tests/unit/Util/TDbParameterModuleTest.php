<?php

namespace Prado\Test\Unit\Util;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\TDbParameterModule;
use Prado\Data\TDbDriver;
use Prado\Web\Services\TPageService;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Harness\Traits\PradoUnitModuleDependencyTrait;
use Prado\Data\TDbConnection;
use Prado\Util\Behaviors\TMapLazyLoadBehavior;
use Prado\Util\Behaviors\TMapRouteBehavior;

function dbParamTestFunction($data, $encode)
{
	if($encode)
		return serialize($data);
	else
		return unserialize($data);
}

class TDbParameterModuleTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;

	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TDbParameterModule();
	}

	protected function tearDown(): void
	{
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		\Prado::getApplication()->onBeginRequest->clear();
		$key = 'testparam';
		$key2 = 'testparam2';
		$key3 = 'testparam3';
		$key4 = 'testparam4';
		$this->obj->remove($key);
		$this->obj->remove($key2);
		$this->obj->remove($key3);
		$this->obj->remove($key4);
		
		$this->obj = null;
	}

	public function testInit()
	{
		$key = 'testparam';
		$value = 'test_value';
		$key2 = 'testparam2';
		$value2 = 'test_value2';
		$key3 = 'testparam3';
		$key4 = 'testparam4';
		
		$app = \Prado::getApplication();
		$params = $app->getParameters();
		
		self::assertNull($params->asa(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR));
		
		$this->obj->init(null);
		
		self::assertNull($this->obj->get($key, false));
		self::assertInstanceOf(TMapLazyLoadBehavior::class, $params->asa(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR));
		self::assertEquals(1, \Prado::getApplication()->onBeginRequest->count());
		
		try {
			$this->obj->setConnectionID('db');
			self::fail("setConnectionID failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setKeyField('param_key');
			self::fail("setKeyField failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setValueField('param_value');
			self::fail("setValueField failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setTableName('prado_params');
			self::fail("setTableName failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setAutoLoadField('initload');
			self::fail("setAutoLoadField failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setAutoLoadValue('\'yes\'');
			self::fail("setAutoLoadValue failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setAutoLoadValueFalse('\'no\'');
			self::fail("setAutoLoadValueFalse failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		try {
			$this->obj->setSerializer(TDbParameterModule::SERIALIZE_PHP);
			self::fail("setSerializer failed to throw TInvalidOperationException when module is already initialized.");
		} catch(TInvalidOperationException $e) {}
		
		$params[$key] = $value; 
		self::assertNull($this->obj->get($key, false));
		
		self::assertNull($params->asa(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR));
		$this->obj->attachParameterStorage(null, null);
		self::assertInstanceOf(TMapRouteBehavior::class, $params->asa(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR));
		
		$params[$key] = $value;
		//makes sure the behavior is working for setting the parameter when 
		self::assertEquals($value, $this->obj->get($key, false));
		
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		unset($params[$key]);
		
		//******   setting parameters from database
		$this->obj = new TDbParameterModule();
		self::assertNull($params[$key]);
		$this->obj->init(null);
		self::assertEquals($value, $params[$key]);
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->remove($key);
		self::assertFalse($this->obj->exists($key));
		$this->obj->set($key2, $value2, false);
		
		$arrayValue = ['propA' => 'data1', 'propB' => 'data2'];
		$objValue = new \stdClass;
		$objValue->propA = 'data1';
		$objValue->propB = 'data2';
		$this->obj->set($key3, $arrayValue);
		$this->obj->set($key4, $objValue);
		
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		unset($params[$key]);
		unset($params[$key2]);
		unset($params[$key3]);
		unset($params[$key4]);
		
		//******  lazy loading from database
		$this->obj = new TDbParameterModule();
		$this->obj->init(null);
		
		//Check that the key was NOT loaded, it was removed before
		self::assertNull($params[$key]);
		self::assertNull($this->obj->get($key, false));
		
		//Check lazy load key2
		self::assertFalse(isset($params[$key2]));
		self::assertTrue($this->obj->exists($key2));
		self::assertEquals($value2, $params[$key2]);
		
		$this->obj->attachParameterStorage(null, null);
		
		$params[$key2] = null;
		self::assertFalse($this->obj->exists($key2));
		self::assertTrue(isset($params[$key2]));
		self::assertNull($params[$key2]);
		
		$params[$key2] = $value2;
		self::assertTrue($this->obj->exists($key2));
		self::assertEquals($value2, $params[$key2]);
		unset($params[$key2]);
		self::assertFalse($this->obj->exists($key2));
		self::assertFalse(isset($params[$key2]));
		self::assertNull($params[$key2]);
		
		self::assertEquals($arrayValue, $this->obj->remove($key3));
		self::assertEquals($objValue, $this->obj->remove($key4));
		
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
		\Prado::getApplication()->getParameters()->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
		unset($params[$key]);
		$this->obj->remove($key2);
		unset($params[$key2]);
	}

	public function testConstruct()
	{
		self::assertInstanceOf(TDbParameterModule::class, $this->obj);
	}
	
	public function testGet()
	{
		$app = \Prado::getApplication();
		
		$key = 'testparam';
		$value = 'test_value';
		$app->getParameters()[$key] = $value;
		self::assertFalse($this->obj->exists($key));
		self::assertNull($this->obj->get($key, false));
		self::assertEquals($value, $this->obj->get($key, true));
		$this->obj->set($key, $value);
		self::assertEquals($value, $this->obj->get($key, false));
		self::assertEquals($value, $this->obj->remove($key));
		self::assertFalse($this->obj->exists($key));
	}

	public function testSet()
	{
		$key = 'testparam';
		$value = 'test_value';
		self::assertFalse($this->obj->exists($key));
		self::assertNull($this->obj->get($key, false));
		self::assertEquals($value, $this->obj->get($key, true));
		$this->obj->set($key, $value);
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->remove($key);
		self::assertFalse($this->obj->exists($key));
	}
	
	public function testExists()
	{
		$key = 'testparam';
		$value = 'test_value';
		self::assertFalse($this->obj->exists($key));
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		$this->obj->remove($key);
		self::assertFalse($this->obj->exists($key));
	}
	
	public function testRemove()
	{
		$key = 'testparam';
		$value = 'test_value';
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		
		self::assertEquals($value, $this->obj->remove($key));
		self::assertFalse($this->obj->exists($key));
	}
	
	public function testConnectionId()
	{
		// Default is blank string
		self::assertEquals('', $this->obj->getConnectionID());
		
		$this->obj->setConnectionID('database');
		self::assertEquals('database', $this->obj->getConnectionID());
		
		$this->obj->setConnectionID('db');
		self::assertEquals('db', $this->obj->getConnectionID());
		
		//change back so not to invalidate testing reset
		$this->obj->setConnectionID('');
	}

	public function testGetDbConnection()
	{
		self::assertInstanceOf(TDbConnection::class, $this->obj->getDbConnection());
	}

	public function testKeyField()
	{
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getKeyField()));
		self::assertTrue(strlen($this->obj->getKeyField()) > 0);
		
		$this->obj->setKeyField('options_key');
		self::assertEquals('options_key', $this->obj->getKeyField());
		
		$this->obj->setKeyField('param_key');
		self::assertEquals('param_key', $this->obj->getKeyField());
	}

	public function testValueField()
	{
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getValueField()));
		self::assertTrue(strlen($this->obj->getValueField()) > 0);
		
		$this->obj->setValueField('options_value');
		self::assertEquals('options_value', $this->obj->getValueField());
		
		$this->obj->setValueField('param_value');
		self::assertEquals('param_value', $this->obj->getValueField());
	}

	public function testTableName()
	{
		$tableName = $this->obj->getTableName();
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getTableName()));
		self::assertTrue(strlen($this->obj->getTableName()) > 0);
		
		$this->obj->setTableName('wp_options');
		self::assertEquals('wp_options', $this->obj->getTableName());
		
		$this->obj->setTableName('prado_parameters');
		self::assertEquals('prado_parameters', $this->obj->getTableName());
		$this->obj->setTableName($tableName);
	}

	public function testAutoLoadField()
	{
		// It is initially set by default, but to what does not matter
		self::assertTrue(is_string($this->obj->getAutoLoadField()));
		self::assertTrue(strlen($this->obj->getAutoLoadField()) > 0);
		
		$this->obj->setAutoLoadField('initload');
		self::assertEquals('initload', $this->obj->getAutoLoadField());
		
		$this->obj->setAutoLoadField('autoload');
		self::assertEquals('autoload', $this->obj->getAutoLoadField());
	}

	public function testAutoLoadValue()
	{
		$this->obj->setAutoLoadValue('1');
		self::assertEquals('1', $this->obj->getAutoLoadValue());
		$this->obj->setAutoLoadValue('true');
		self::assertEquals('true', $this->obj->getAutoLoadValue());
		$this->obj->setAutoLoadValue("\'true\'");
		self::assertEquals("\'true\'", $this->obj->getAutoLoadValue());
		$this->obj->setAutoLoadValue("\'yes\'");
		self::assertEquals("\'yes\'", $this->obj->getAutoLoadValue());
	}

	public function testIdentifierPropertiesRejectSql()
	{
		$tableName = $this->obj->getTableName();
		$autoLoadField = $this->obj->getAutoLoadField();
		foreach (['setKeyField', 'setValueField', 'setTableName', 'setAutoLoadField'] as $setter) {
			try {
				$this->obj->$setter('name; DROP TABLE parameters');
				self::fail("$setter accepted an SQL fragment.");
			} catch (TInvalidDataValueException $e) {
			}
		}
		$this->obj->setTableName('main.parameters');
		self::assertEquals('main.parameters', $this->obj->getTableName());
		$this->obj->setTableName('`my params`');
		self::assertEquals('`my params`', $this->obj->getTableName());
		$this->obj->setAutoLoadField('');
		self::assertEquals('', $this->obj->getAutoLoadField());
		$this->obj->setTableName($tableName);
		$this->obj->setAutoLoadField($autoLoadField);
	}

	public function testAutoLoadBindValueUnquotesSqlLiteral()
	{
		$this->obj->setAutoLoadValue("'yes'");
		$this->obj->setAutoLoadValueFalse("'it''s'");
		self::assertSame('yes', PradoUnit::invoke($this->obj, 'getAutoLoadBindValue', true));
		self::assertSame("it's", PradoUnit::invoke($this->obj, 'getAutoLoadBindValue', false));
		$this->obj->setAutoLoadValue('1');
		self::assertSame('1', PradoUnit::invoke($this->obj, 'getAutoLoadBindValue', true));
	}

	public function testAutoLoadValueIsBound()
	{
		$params = \Prado::getApplication()->getParameters();
		$this->obj->init(null);
		$this->obj->set('bindtest_on', 'on', true, false);
		$this->obj->set('bindtest_off', 'off', false, false);
		try {
			foreach (["0 OR 1=1" => false, "'1'" => true] as $autoLoadValue => $loaded) {
				$params->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
				unset($params['bindtest_on'], $params['bindtest_off']);
				$module = new TDbParameterModule();
				$module->setAutoLoadValue($autoLoadValue);
				$module->init(null);
				self::assertSame($loaded, isset($params['bindtest_on']), $autoLoadValue);
				self::assertFalse(isset($params['bindtest_off']), $autoLoadValue);
			}
		} finally {
			unset($params['bindtest_on'], $params['bindtest_off']);
			$this->obj->remove('bindtest_on');
			$this->obj->remove('bindtest_off');
		}
	}

	public function testAutoLoadValueFalse()
	{
		$this->obj->setAutoLoadValueFalse('0');
		self::assertEquals('0', $this->obj->getAutoLoadValueFalse());
		$this->obj->setAutoLoadValueFalse('false');
		self::assertEquals('false', $this->obj->getAutoLoadValueFalse());
		$this->obj->setAutoLoadValueFalse("\'false\'");
		self::assertEquals("\'false\'", $this->obj->getAutoLoadValueFalse());
		$this->obj->setAutoLoadValueFalse("\'no\'");
		self::assertEquals("\'no\'", $this->obj->getAutoLoadValueFalse());
	}

	public function testAutoCreateParamTable()
	{
		self::assertTrue($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable(false);
		self::assertFalse($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable(true);
		self::assertTrue($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable('false');
		self::assertFalse($this->obj->getAutoCreateParamTable());
		$this->obj->setAutoCreateParamTable('true');
		self::assertTrue($this->obj->getAutoCreateParamTable());
	}
	
	public function testSerializer()
	{
		$key = 'testparam';
		$value = ['propA' => 'data1', 'propB' => 'data2'];
		$value2 = new \stdClass;
		$value2->propA = 'data1';
		$value2->propB = 'data2';
		
		self::assertEquals(TDbParameterModule::SERIALIZE_PHP, $this->obj->getSerializer());
		$this->obj->setSerializer(TDbParameterModule::SERIALIZE_JSON);
		self::assertEquals(TDbParameterModule::SERIALIZE_JSON, $this->obj->getSerializer());
		self::assertFalse($this->obj->exists($key));
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->set($key, $value2);
		self::assertEquals($value, $this->obj->get($key, false));
		
		$this->obj->setSerializer(TDbParameterModule::SERIALIZE_PHP);
		self::assertEquals(TDbParameterModule::SERIALIZE_PHP, $this->obj->getSerializer());
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->set($key, $value2);
		self::assertEquals($value2, $this->obj->get($key, false));
		
		$this->obj->setSerializer(__NAMESPACE__ . '\\dbParamTestFunction');
		self::assertEquals(__NAMESPACE__ . '\\dbParamTestFunction', $this->obj->getSerializer());
		
		$this->obj->set($key, $value);
		self::assertTrue($this->obj->exists($key));
		self::assertEquals($value, $this->obj->get($key, false));
		$this->obj->set($key, $value2);
		self::assertEquals($value2, $this->obj->get($key, false));
	
		$this->obj->remove($key);
		try {
			$this->obj->setSerializer('dbParamTestFunction_nonexistant');
			self::fail("TDbParameterModule::setSerializer should raise TInvalidDataTypeException when givin a non-existant function to Serializer\n");
		} catch(TInvalidDataTypeException $e) {
		}
	}
	

	public function testCaptureParameterChanges()
	{
		$this->obj->setCaptureParameterChanges(false);
		self::assertFalse($this->obj->getCaptureParameterChanges());
		$this->obj->setCaptureParameterChanges(true);
		self::assertTrue($this->obj->getCaptureParameterChanges());
		$this->obj->setCaptureParameterChanges('false');
		self::assertFalse($this->obj->getCaptureParameterChanges());
		$this->obj->setCaptureParameterChanges('true');
		self::assertTrue($this->obj->getCaptureParameterChanges());
	}

	// -----------------------------------------------------------------------
	// IModuleDependency contract
	// -----------------------------------------------------------------------

	public function testImplementsIModuleDependency()
	{
		$this->assertInstanceOf(\Prado\IModuleDependency::class, $this->obj);
	}

	public function testGetModuleDependencies_noConnectionID_returnsNoDeps()
	{
		$this->assertModuleDependency(null, $this->obj->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_connectionIDSet_returnsIt()
	{
		// Use a local instance so the shared tearDown's $this->obj->remove()
		// doesn't try to materialize a connection against a non-existent module.
		$module = new TDbParameterModule();
		$module->setConnectionID('db');
		$this->assertModuleDependency('db', $module->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_returnsSameRegardlessOfIsPreInit()
	{
		$module = new TDbParameterModule();
		$module->setConnectionID('db');
		$this->assertModuleDependency(
			$module->getModuleDependencies(true),
			$module->getModuleDependencies(false)
		);
	}

	/** Runs a test body against a fresh TApplicationParameters, restoring the application's map after. */
	private function withFinalParameters(callable $body): void
	{
		$app = \Prado::getApplication();
		$saved = $app->getParameters();
		$params = new \Prado\TApplicationParameters();
		PradoUnit::setProp($app, '_parameters', $params);
		try {
			$body($params);
		} finally {
			$params->detachBehavior(TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR);
			$params->detachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR);
			PradoUnit::setProp($app, '_parameters', $saved);
		}
	}

	public function testInit_skipsRowOfFinalParameter()
	{
		$this->obj->set('testparam', 'db', true, false);
		$this->obj->set('testparam2', 'db2', true, false);
		$this->withFinalParameters(function ($params) {
			$params['testparam'] = 'cfg';
			$params->finalize('testparam');
			$logger = \Prado::getLogger();
			$logger->deleteLogs(\Prado\Util\Log\TLogger::WARNING, TDbParameterModule::class);

			$module = new TDbParameterModule();
			$module->init(null);

			self::assertEquals('cfg', $params['testparam']);
			self::assertEquals('db2', $params['testparam2']);
			$logs = $logger->deleteLogs(\Prado\Util\Log\TLogger::WARNING, TDbParameterModule::class);
			self::assertCount(1, $logs);
			self::assertStringContainsString('testparam', $logs[0][0]);
		});
	}

	public function testSet_finalParameterThrowsBeforeWritingRow()
	{
		$this->withFinalParameters(function ($params) {
			$params['testparam'] = 'cfg';
			$params->finalize('testparam');
			try {
				$this->obj->set('testparam', 'db');
				self::fail('set() on a final parameter must throw');
			} catch (TInvalidOperationException $e) {
				self::assertEquals('dbparametermodule_set_final', $e->getErrorCode());
			}
			self::assertNull($this->obj->get('testparam', false, false));
			self::assertEquals('cfg', $params['testparam']);
		});
	}

	public function testGet_doesNotSetFinalParameter()
	{
		$this->obj->set('testparam', 'db', false, false);
		$this->withFinalParameters(function ($params) {
			$params->finalize('testparam');
			self::assertEquals('db', $this->obj->get('testparam', false, true));
			self::assertFalse($params->contains('testparam'));
		});
	}

	public function testLazyLoad_finalAbsentParameterReadsNull()
	{
		$this->obj->set('testparam', 'db', false, false);
		$this->withFinalParameters(function ($params) {
			$params->finalize('testparam');
			$params->attachBehavior(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR, new TMapLazyLoadBehavior([$this->obj, 'getFromBehavior']));
			self::assertNull($params['testparam']);
			self::assertFalse($params->contains('testparam'));
		});
	}

	// === Driver tables ===

	/** @var array<int, array{0: TDbConnection, 1: string}> tables to drop after the test. */
	private array $_driverTables = [];

	/** @var TDbParameterModule[] modules kept alive so their connections stay open. */
	private array $_driverModules = [];

	public static function driverProvider(): array
	{
		return [
			'sqlite' => [TDbDriver::DRIVER_SQLITE],
			'mysql' => [TDbDriver::DRIVER_MYSQL],
			'pgsql' => [TDbDriver::DRIVER_PGSQL],
		];
	}

	private function driverConnection(string $driver): TDbConnection
	{
		$conn = match ($driver) {
			TDbDriver::DRIVER_SQLITE => new TDbConnection('sqlite::memory:'),
			TDbDriver::DRIVER_MYSQL => PradoUnit::setupMysqlConnection('prado_unitest'),
			TDbDriver::DRIVER_PGSQL => PradoUnit::setupPgsqlConnection('prado_unitest'),
		};
		if (is_array($conn)) {
			PradoUnit::failFirstThenSkip($this, $conn[0], null, $conn[1]);
		} elseif ($conn instanceof \Exception) {
			throw $conn;
		}
		$conn->setActive(true);
		return $conn;
	}

	private function driverModule(TDbConnection $conn, string $table, string $autoLoadField = 'autoload'): TDbParameterModule
	{
		$conn->createCommand('DROP TABLE IF EXISTS ' . $table)->execute();
		$this->_driverTables[] = [$conn, $table];
		$module = $this->_driverModules[] = new TDbParameterModule();
		PradoUnit::setProp($module, '_conn', $conn);
		$module->setTableName($table);
		$module->setAutoLoadField($autoLoadField);
		return $module;
	}

	private function detachShellHandler(?TDbParameterModule $module): void
	{
		$app = \Prado::getApplication();
		if ($module && $app->onAuthenticationComplete->contains([$module, 'registerShellAction'])) {
			$app->onAuthenticationComplete->remove([$module, 'registerShellAction']);
		}
	}

	private function dropDriverTables(): void
	{
		foreach ($this->_driverTables as [$conn, $table]) {
			$conn->setActive(true);
			$conn->createCommand('DROP TABLE IF EXISTS ' . $table)->execute();
		}
		$this->_driverTables = [];
		$this->_driverModules = [];
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testCreateDbTable_twoTablesInOneDatabase(string $driver)
	{
		$conn = $this->driverConnection($driver);
		try {
			$first = $this->driverModule($conn, 'dbparam_test_first');
			$second = $this->driverModule($conn, 'dbparam_test_second');

			$first->set('pkey', ['a' => 1], true, false);
			$second->set('pkey', 'second', false, false);
			self::assertSame(['a' => 1], $first->get('pkey', false, false));
			self::assertSame('second', $second->get('pkey', false, false));
			self::assertTrue($second->exists('pkey'));

			$first->set('pkey', 'replaced', true, false);
			self::assertSame('replaced', $first->get('pkey', false, false));
			self::assertSame('replaced', $first->remove('pkey'));
			self::assertFalse($first->exists('pkey'));
		} finally {
			$this->dropDriverTables();
		}
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testCreateDbTable_keyIsUnique(string $driver)
	{
		$conn = $this->driverConnection($driver);
		try {
			$module = $this->driverModule($conn, 'dbparam_test_unique', '');
			$module->set('pkey', 'value', true, false);
			$this->expectException(\Prado\Exceptions\TDbException::class);
			$conn->createCommand("INSERT INTO dbparam_test_unique (param_key, param_value) VALUES ('pkey', 'other')")->execute();
		} finally {
			$this->dropDriverTables();
		}
	}

	/**
	 * @dataProvider driverProvider
	 */
	public function testInit_loadsAutoLoadParameters(string $driver)
	{
		$conn = $this->driverConnection($driver);
		$params = \Prado::getApplication()->getParameters();
		$app = \Prado::getApplication();
		try {
			$module = $this->driverModule($conn, 'dbparam_test_autoload');
			$module->set('dbpAuto', 'auto', true, false);
			$module->set('dbpLazy', 'lazy', false, false);
			$module->setCaptureParameterChanges(false);
			$module->init(null);

			self::assertSame('auto', $params->itemAt('dbpAuto'));
			self::assertFalse($params->contains('dbpLazy'));
			self::assertFalse($app->onBeginRequest->contains([$module, 'attachTPageServiceHandler']));
		} finally {
			$this->detachShellHandler($module ?? null);
			$params->remove('dbpAuto');
			$params->remove('dbpLazy');
			$this->dropDriverTables();
		}
	}

	public function testInit_withoutAutoLoadFieldLoadsEveryParameter()
	{
		$conn = $this->driverConnection(TDbDriver::DRIVER_SQLITE);
		$params = \Prado::getApplication()->getParameters();
		$app = \Prado::getApplication();
		try {
			$module = $this->driverModule($conn, 'dbparam_test_all', '');
			$module->set('dbpAuto', 'auto', true, false);
			$module->set('dbpLazy', 'lazy', false, false);
			$module->setCaptureParameterChanges(false);
			$module->init(null);

			self::assertSame('auto', $params->itemAt('dbpAuto'));
			self::assertSame('lazy', $params->itemAt('dbpLazy'));
			self::assertNull($params->asa(TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR));
			$indexes = $conn->createCommand("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='dbparam_test_all'")->queryColumn();
			self::assertSame(['dbparam_test_all_tkey'], $indexes);
		} finally {
			$this->detachShellHandler($module ?? null);
			$params->remove('dbpAuto');
			$params->remove('dbpLazy');
			$this->dropDriverTables();
		}
	}

	public static function loadSerializerProvider(): array
	{
		return [
			'php' => [TDbParameterModule::SERIALIZE_PHP, serialize(['a' => 1]), ['a' => 1]],
			'php, not serialized' => [TDbParameterModule::SERIALIZE_PHP, 'plain', 'plain'],
			'json' => [TDbParameterModule::SERIALIZE_JSON, '{"a":1}', ['a' => 1]],
			'json, not json' => [TDbParameterModule::SERIALIZE_JSON, '{plain', '{plain'],
			'callable' => [__NAMESPACE__ . '\dbParamTestFunction', serialize(['a' => 1]), ['a' => 1]],
			'callable, null' => [fn ($value, $encode) => null, 'plain', 'plain'],
			'none' => [null, 'plain', 'plain'],
		];
	}

	/**
	 * @dataProvider loadSerializerProvider
	 */
	public function testInit_unserializesLoadedValues($serializer, string $stored, $expected)
	{
		$conn = $this->driverConnection(TDbDriver::DRIVER_SQLITE);
		$params = \Prado::getApplication()->getParameters();
		$app = \Prado::getApplication();
		try {
			$module = $this->driverModule($conn, 'dbparam_test_load', '');
			if ($serializer !== null) {
				$module->setSerializer($serializer);
			} else {
				PradoUnit::setProp($module, '_serializer', null);
			}
			$module->set('dbpLoad', 'x', true, false);
			$cmd = $conn->createCommand('UPDATE dbparam_test_load SET param_value=:value');
			$cmd->bindValue(':value', $stored);
			$cmd->execute();
			$module->init(null);

			self::assertSame($expected, $params->itemAt('dbpLoad'));
			self::assertSame($expected, $module->get('dbpLoad', false, false));
		} finally {
			$this->detachShellHandler($module ?? null);
			$params->remove('dbpLoad');
			$this->dropDriverTables();
		}
	}

	public function testSet_callableSerializer()
	{
		$conn = $this->driverConnection(TDbDriver::DRIVER_SQLITE);
		try {
			$module = $this->driverModule($conn, 'dbparam_test_callable', '');
			$module->setSerializer(fn ($value, $encode) => $encode ? 'encoded:' . json_encode($value) : json_decode(substr($value, 8), true));
			$module->set('pkey', ['a' => 1], true, false);
			self::assertSame('encoded:{"a":1}', $conn->createCommand('SELECT param_value FROM dbparam_test_callable')->queryScalar());
			self::assertSame(['a' => 1], $module->get('pkey', false, false));
		} finally {
			$this->dropDriverTables();
		}
	}

	public function testEnsureTable_missingTableWithoutAutoCreateThrows()
	{
		$conn = $this->driverConnection(TDbDriver::DRIVER_SQLITE);
		try {
			$module = $this->driverModule($conn, 'dbparam_test_missing');
			$module->setAutoCreateParamTable(false);
			$module->exists('pkey');
			self::fail('exists() did not throw for a missing table without AutoCreateParamTable.');
		} catch (TConfigurationException $e) {
			self::assertSame('dbparametermodule_table_nonexistent', $e->getErrorCode());
		} finally {
			$this->dropDriverTables();
		}
	}

	public function testBlankKeysThrow()
	{
		try {
			$this->obj->get('');
			self::fail('get() did not throw for a blank key.');
		} catch (TInvalidOperationException $e) {
			self::assertSame('dbparametermodule_get_no_blank_key', $e->getErrorCode());
		}
		try {
			$this->obj->set('', 'value');
			self::fail('set() did not throw for a blank key.');
		} catch (TInvalidOperationException $e) {
			self::assertSame('dbparametermodule_set_no_blank_key', $e->getErrorCode());
		}
	}

	public function testInvalidConnectionIdThrows()
	{
		$module = new TDbParameterModule();
		$module->setConnectionID('noSuchModule');
		try {
			$module->getDbConnection();
			self::fail('getDbConnection() did not throw for a ConnectionID without a module.');
		} catch (TConfigurationException $e) {
			self::assertSame('dbparametermodule_connectionid_invalid', $e->getErrorCode());
		}
	}

	public function testGetPermissions()
	{
		$permissions = $this->obj->getPermissions(null);
		self::assertCount(1, $permissions);
		self::assertInstanceOf(TPermissionEvent::class, $permissions[0]);
		self::assertSame(TDbParameterModule::PERM_PARAM_SHELL, $permissions[0]->getName());
	}

	public function testAttachTPageServiceHandler()
	{
		$app = \Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_service']);
		try {
			PradoUnit::setProp($app, '_service', null);
			$this->obj->attachTPageServiceHandler($app, null);

			$component = new \Prado\TComponent();
			PradoUnit::setProp($app, '_service', $component);
			$this->obj->attachTPageServiceHandler($app, null);
			self::assertFalse($component->hasEvent('onPreRunPage'));

			$service = new TPageService();
			PradoUnit::setProp($app, '_service', $service);
			$this->obj->attachTPageServiceHandler($app, null);
			self::assertTrue($service->getEventHandlers('onPreRunPage')->contains([$this->obj, 'attachParameterStorage']));
		} finally {
			PradoUnit::restore($app, $snapshot);
		}
	}

	public function testRegisterShellActionInAShellApplication()
	{
		$shell = new \Prado\Test\Unit\Harness\TTestShellApplication();
		try {
			$this->obj->registerShellAction(null, null);
			$actions = $shell->getShellActions();
			self::assertArrayHasKey(\Prado\Shell\Actions\TDbParameterAction::class, $actions);
			self::assertSame($this->obj, $actions[\Prado\Shell\Actions\TDbParameterAction::class]->getDbParameterModule());
		} finally {
			$shell->restoreApplication();
		}
	}

	public function testRegisterShellActionOutsideAShellOrWhenRefused()
	{
		$this->obj->registerShellAction(null, null);
		self::assertNotInstanceOf(\Prado\Shell\TShellApplication::class, \Prado::getApplication());

		$shell = new \Prado\Test\Unit\Harness\TTestShellApplication();
		try {
			$this->obj->attachBehavior('refuse', new \Prado\Test\Unit\Security\Permissions\TTestRefusePermissionChangesBehavior());
			$this->obj->registerShellAction(null, null);
			self::assertArrayNotHasKey(\Prado\Shell\Actions\TDbParameterAction::class, $shell->getShellActions());
		} finally {
			$this->obj->detachBehavior('refuse');
			$shell->restoreApplication();
		}
	}

	public function testIsFinalParameterWithPlainParameters()
	{
		$app = \Prado::getApplication();
		$saved = $app->getParameters();
		PradoUnit::setProp($app, '_parameters', new \Prado\Collections\TMap(['testparam' => 'v']));
		try {
			self::assertFalse(PradoUnit::invoke($this->obj, 'isFinalParameter', 'testparam'));
		} finally {
			PradoUnit::setProp($app, '_parameters', $saved);
		}
	}

	public static function bindValueProvider(): array
	{
		$cases = [];
		foreach ([true, false] as $autoLoad) {
			$side = $autoLoad ? 'true' : 'false';
			$cases["$side, short"] = [$autoLoad, 'y', 'y'];
			$cases["$side, unquoted"] = [$autoLoad, 'yes', 'yes'];
			$cases["$side, open quote"] = [$autoLoad, "'yes", "'yes"];
			$cases["$side, quoted"] = [$autoLoad, "'y''s'", "y's"];
		}
		return $cases;
	}

	/**
	 * @dataProvider bindValueProvider
	 */
	public function testAutoLoadBindValue(bool $autoLoad, string $value, string $expected)
	{
		$module = new TDbParameterModule();
		if ($autoLoad) {
			$module->setAutoLoadValue($value);
		} else {
			$module->setAutoLoadValueFalse($value);
		}
		self::assertSame($expected, PradoUnit::invoke($module, 'getAutoLoadBindValue', $autoLoad));
	}
}
