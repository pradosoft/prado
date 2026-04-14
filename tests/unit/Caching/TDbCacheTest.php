<?php

use Prado\Caching\TDbCache;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;

if (!defined('TEST_CACHE_DB_DIR')) {
	define('TEST_CACHE_DB_DIR', __DIR__ . '/../Data/db');
}

class TDbCacheTest extends PHPUnit\Framework\TestCase
{
	private $_cache;
	private $_dbDir;
	private $_dbFile;
	private $_dbFiles = [];

	protected function setUp(): void
	{
		$this->_dbDir = TEST_CACHE_DB_DIR;
		if (!is_dir($this->_dbDir)) {
			mkdir($this->_dbDir, 0777, true);
		}
		$this->_dbFile = $this->_dbDir . '/test_cache.db';
		$this->_dbFiles = [$this->_dbFile];
		
		$app = new TApplication(__DIR__ . '/../Data/SqlMap/app', false, TApplication::CONFIG_TYPE_PHP);
		$app->setRuntimePath($this->_dbDir);

		$this->_cache = new TDbCache();
		$this->_cache->setPrimaryCache(false);
	}

	protected function tearDown(): void
	{
		if ($this->_cache) {
			$this->_cache->flush();
			$this->_cache->deactivateDbConnection(true);
			$this->_cache = null;
		}
		foreach($this->_dbFiles as $dbFile) {
			@unlink($dbFile);
		}
	}

	protected function initCache(): void
	{
		$this->_dbFiles[] = $this->_dbFile;
		$this->_cache->setConnectionString('sqlite:' . $this->_dbFile);
		$this->_cache->init(null);
	}

	public function testInit()
	{
		$this->_cache->init(null);
		$this->assertInstanceOf(TDbCache::class, $this->_cache);
	}

	public function testDefaultValues()
	{
		$this->_cache->init(null);

		$this->assertEquals('pradocache', $this->_cache->getCacheTableName());
		$this->assertTrue($this->_cache->getAutoCreateCacheTable());
		$this->assertEquals(60, $this->_cache->getFlushInterval());
		$this->assertEquals('', $this->_cache->getConnectionString());
		$this->assertEquals('', $this->_cache->getUsername());
		$this->assertEquals('', $this->_cache->getPassword());
	}

	public function testCacheTableName()
	{
		$this->_cache->setCacheTableName('custom_cache_table');
		$this->assertEquals('custom_cache_table', $this->_cache->getCacheTableName());
	}

	public function testAutoCreateCacheTable()
	{
		$this->_cache->setAutoCreateCacheTable(false);
		$this->assertFalse($this->_cache->getAutoCreateCacheTable());

		$this->_cache->setAutoCreateCacheTable(true);
		$this->assertTrue($this->_cache->getAutoCreateCacheTable());

		$this->_cache->setAutoCreateCacheTable('false');
		$this->assertFalse($this->_cache->getAutoCreateCacheTable());

		$this->_cache->setAutoCreateCacheTable('true');
		$this->assertTrue($this->_cache->getAutoCreateCacheTable());
	}

	public function testFlushInterval()
	{
		$this->_cache->setFlushInterval(120);
		$this->assertEquals(120, $this->_cache->getFlushInterval());

		$this->_cache->setFlushInterval('30');
		$this->assertEquals(30, $this->_cache->getFlushInterval());

		$this->_cache->setFlushInterval(0);
		$this->assertEquals(0, $this->_cache->getFlushInterval());
	}

	public function testConnectionString()
	{
		$this->_cache->setConnectionString('sqlite:' . $this->_dbFile);
		$this->assertEquals('sqlite:' . $this->_dbFile, $this->_cache->getConnectionString());
	}

	public function testUsername()
	{
		$this->_cache->setUsername('testuser');
		$this->assertEquals('testuser', $this->_cache->getUsername());
	}

	public function testPassword()
	{
		$this->_cache->setPassword('testpassword');
		$this->assertEquals('testpassword', $this->_cache->getPassword());
	}

	public function testGetDbConnection()
	{
		$this->initCache();

		$db = $this->_cache->getDbConnection();
		$this->assertInstanceOf(TDbConnection::class, $db);
		$this->assertTrue($db->getActive());
	}

	public function testGetHasDbConnection()
	{
		$this->assertFalse($this->_cache->getHasDbConnection());

		$this->initCache();
		$this->_cache->getDbConnection();

		$this->assertTrue($this->_cache->getHasDbConnection());
	}

	public function testConnectionID()
	{
		$this->assertEquals('', $this->_cache->getConnectionID());

		$this->_cache->setConnectionID('db');
		$this->assertEquals('db', $this->_cache->getConnectionID());

		$this->_cache->setConnectionID('');
		$this->assertEquals('', $this->_cache->getConnectionID());
	}

	public function testDeactivateDbConnection()
	{
		$this->initCache();

		$db = $this->_cache->getDbConnection();
		$this->assertTrue($this->_cache->getHasDbConnection());

		$this->_cache->deactivateDbConnection();
		$this->assertFalse($db->getActive());
		$this->assertTrue($this->_cache->getHasDbConnection());

		$this->_cache->deactivateDbConnection(true);
		$this->assertFalse($this->_cache->getHasDbConnection());
	}

	public function testBasicGetSet()
	{
		$this->initCache();

		$key = 'test_key';
		$value = 'test_value';

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testSetWithExpiration()
	{
		$this->initCache();

		$key = 'expire_key';
		$value = 'expire_value';

		$this->_cache->set($key, $value, 2);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);

		sleep(3);
		$result = $this->_cache->get($key);
		$this->assertFalse($result);
	}

	public function testSetNeverExpires()
	{
		$this->initCache();

		$key = 'never_expire_key';
		$value = 'never_expire_value';

		$this->_cache->set($key, $value, 0);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testAddWhenNotExists()
	{
		$this->initCache();

		$key = 'add_key';
		$value = 'add_value';

		$result = $this->_cache->add($key, $value);
		$this->assertTrue($result);

		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testAddWhenExists()
	{
		$this->initCache();

		$key = 'add_exists_key';
		$value1 = 'first_value';
		$value2 = 'second_value';

		$this->_cache->add($key, $value1);
		$result = $this->_cache->add($key, $value2);
		$this->assertFalse($result);

		$result = $this->_cache->get($key);
		$this->assertEquals($value1, $result);
	}

	public function testDelete()
	{
		$this->initCache();

		$key = 'delete_key';
		$value = 'delete_value';

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);

		$this->_cache->delete($key);
		$result = $this->_cache->get($key);
		$this->assertFalse($result);
	}

	public function testFlush()
	{
		$this->initCache();

		$this->_cache->set('key1', 'value1');
		$this->_cache->set('key2', 'value2');
		$this->_cache->set('key3', 'value3');

		$result = $this->_cache->flush();
		$this->assertTrue($result);

		$this->assertFalse($this->_cache->get('key1'));
		$this->assertFalse($this->_cache->get('key2'));
		$this->assertFalse($this->_cache->get('key3'));
	}

	public function testFlushCacheExpired()
	{
		$this->_cache->setFlushInterval(1);
		$this->initCache();

		$this->_cache->set('expire1', 'value1', 1);
		$this->_cache->set('expire2', 'value2', 5);
		$this->_cache->set('expire3', 'value3', 10);

		sleep(2);

		$this->_cache->flushCacheExpired();

		$this->assertFalse($this->_cache->get('expire1'));
		$this->assertEquals('value2', $this->_cache->get('expire2'));
		$this->assertEquals('value3', $this->_cache->get('expire3'));
	}

	public function testFlushCacheExpiredForce()
	{
		$this->_cache->setFlushInterval(0);
		$this->initCache();

		$this->_cache->set('expire1', 'value1', 1);
		$this->_cache->set('expire2', 'value2', 5);

		sleep(2);

		$this->_cache->flushCacheExpired(true);

		$this->assertFalse($this->_cache->get('expire1'));
		$this->assertEquals('value2', $this->_cache->get('expire2'));
	}

	public function testSetWithArrayValue()
	{
		$this->initCache();

		$key = 'array_key';
		$value = ['a' => 1, 'b' => 2, 'c' => [1, 2, 3]];

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testSetWithObjectValue()
	{
		$this->initCache();

		$key = 'object_key';
		$value = new stdClass();
		$value->prop1 = 'value1';
		$value->prop2 = 123;

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testSetWithNullValue()
	{
		$this->initCache();

		$key = 'null_key';

		$result = $this->_cache->set($key, null);
		$this->assertTrue($result);

		$result = $this->_cache->get($key);
		$this->assertFalse($result);
	}

	public function testEmptyValueWithExpire()
	{
		$this->initCache();

		$key = 'empty_key';

		$result = $this->_cache->set($key, '', 0);
		$this->assertTrue($result);

		$result = $this->_cache->get($key);
		$this->assertFalse($result);
	}

	public function testArrayAccessOffsetExists()
	{
		$this->initCache();

		$key = 'offset_exists_key';
		$value = 'offset_exists_value';

		$this->_cache->set($key, $value);
		$this->assertTrue(isset($this->_cache[$key]));

		$this->_cache->delete($key);
		$this->assertFalse(isset($this->_cache[$key]));
	}

	public function testArrayAccessOffsetGet()
	{
		$this->initCache();

		$key = 'offset_get_key';
		$value = 'offset_get_value';

		$this->_cache->set($key, $value);
		$this->assertEquals($value, $this->_cache[$key]);
	}

	public function testArrayAccessOffsetSet()
	{
		$this->initCache();

		$key = 'offset_set_key';
		$value = 'offset_set_value';

		$this->_cache[$key] = $value;
		$this->assertEquals($value, $this->_cache->get($key));
	}

	public function testArrayAccessOffsetUnset()
	{
		$this->initCache();

		$key = 'offset_unset_key';
		$value = 'offset_unset_value';

		$this->_cache->set($key, $value);
		unset($this->_cache[$key]);
		$this->assertFalse($this->_cache->get($key));
	}

	public function testKeyPrefix()
	{
		$this->initCache();

		$prefix = 'test_prefix_';
		$this->_cache->setKeyPrefix($prefix);
		$this->assertEquals($prefix, $this->_cache->getKeyPrefix());

		$key = 'prefixed_key';
		$value = 'prefixed_value';

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testPrimaryCache()
	{
		$this->_cache->setPrimaryCache(true);
		$this->assertTrue($this->_cache->getPrimaryCache());

		$this->_cache->setPrimaryCache(false);
		$this->assertFalse($this->_cache->getPrimaryCache());

		$this->_cache->setPrimaryCache(true);
		$this->assertTrue($this->_cache->getPrimaryCache());
	}

	public function testMultipleConnectionsSameKey()
	{
		$this->initCache();

		$key = 'shared_key';
		$value1 = 'cache1_value';
		$value2 = 'cache2_value';

		$cache2 = new TDbCache();
		$cache2->setPrimaryCache(false);
		$this->_dbFiles[] = $this->_dbFile;
		$cache2->setConnectionString('sqlite:' . $this->_dbFile);
		$cache2->init(null);

		$this->_cache->set($key, $value1);

		$result = $cache2->get($key);
		$this->assertEquals($value1, $result);

		$cache2->set($key, $value2);

		$result = $this->_cache->get($key);
		$this->assertEquals($value2, $result);

		$cache2->deactivateDbConnection(true);
		$cache2 = null;
	}

	public function testCacheTableAutoCreationDisabled()
	{
		$disabledDbFile = $this->_dbDir . '/test_disabled.db';

		$this->_dbFiles[] = $disabledDbFile;
		$this->_cache->setConnectionString('sqlite:' . $disabledDbFile);
		$this->_cache->setAutoCreateCacheTable(false);
		$this->_cache->init(null);

		$key = 'no_auto_create_key';
		$value = 'no_auto_create_value';

		$this->expectException(\Prado\Exceptions\TDbException::class);
		$this->_cache->set($key, $value);
	}

	public function testCustomCacheTableName()
	{
		$customTable = 'my_custom_cache';

		$this->_dbFiles[] = $this->_dbFile;
		$this->_cache->setConnectionString('sqlite:' . $this->_dbFile);
		$this->_cache->setCacheTableName($customTable);
		$this->_cache->setAutoCreateCacheTable(true);
		$this->_cache->init(null);

		$key = 'custom_table_key';
		$value = 'custom_table_value';

		$result = $this->_cache->set($key, $value);
		$this->assertTrue($result);

		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testSqliteDatabaseName()
	{
		$reflection = new ReflectionClass($this->_cache);
		$method = $reflection->getMethod('getSqliteDatabaseName');
		$method->setAccessible(true);

		$dbName = $method->invoke($this->_cache);
		$this->assertEquals('sqlite3.cache', $dbName);
	}

	public function testReplaceIntoBehavior()
	{
		$this->initCache();

		$key = 'replace_key';

		$this->_cache->set($key, 'first');
		$this->assertEquals('first', $this->_cache->get($key));

		$this->_cache->set($key, 'second');
		$this->assertEquals('second', $this->_cache->get($key));
	}

	public function testDestructor()
	{
		$this->initCache();
		$this->_cache->set('destruct_key', 'destruct_value');

		$db = $this->_cache->getDbConnection();
		$this->assertTrue($db->getActive());

		$dbFileExists = file_exists($this->_dbFile);

		unset($this->_cache);
		$this->_cache = null;

		$this->assertTrue($dbFileExists);
	}

	public function testCacheInitializeWithForce()
	{
		$this->initCache();

		$this->_cache->set('force_key', 'force_value');

		$reflection = new ReflectionClass($this->_cache);
		$property = $reflection->getProperty('_cacheInitialized');
		$property->setAccessible(true);
		$property->setValue($this->_cache, false);

		$method = $reflection->getMethod('initializeCache');
		$method->setAccessible(true);
		$method->invoke($this->_cache, true);

		$this->assertEquals('force_value', $this->_cache->get('force_key'));
	}

	public function testEmptyStringKey()
	{
		$this->initCache();

		$key = '';
		$value = 'empty_key_value';

		$result = $this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testSpecialCharactersInValue()
	{
		$this->initCache();

		$key = 'special_key';
		$value = "Special chars: '\"\\\n\t\r\0" . chr(0) . " and unicode: \xC2\xA9\xE2\x88\x82\xC2\xA3";

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testVeryLongValue()
	{
		$this->initCache();

		$key = 'long_key';
		$value = str_repeat('A very long string. ', 10000);

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testDoFlushCacheExpired()
	{
		$this->_cache->setFlushInterval(1);
		$this->initCache();

		$this->_cache->set('do_expire1', 'value1', 1);
		$this->_cache->set('do_expire2', 'value2', 10);

		sleep(2);

		$this->_cache->doFlushCacheExpired();

		$this->assertFalse($this->_cache->get('do_expire1'));
		$this->assertEquals('value2', $this->_cache->get('do_expire2'));
	}

	public function testDoInitializeCache()
	{
		$this->initCache();

		$key = 'init_key';
		$value = 'init_value';

		$reflection = new ReflectionClass($this->_cache);
		$property = $reflection->getProperty('_cacheInitialized');
		$property->setAccessible(true);
		$property->setValue($this->_cache, false);

		$this->_cache->doInitializeCache();

		$this->_cache->set($key, $value);
		$this->assertEquals($value, $this->_cache->get($key));
	}

	public function testFxGetCronTaskInfos()
	{
		$this->initCache();

		$cronInfos = $this->_cache->fxGetCronTaskInfos($this->_cache, null);

		$this->assertInstanceOf(\Prado\Util\Cron\TCronTaskInfo::class, $cronInfos);
		$this->assertEquals('dbcacheflushexpired', $cronInfos->getName());
	}

	public function testGetTraitDbConnection()
	{
		$this->initCache();

		$method = new ReflectionMethod($this->_cache, 'getTraitDbConnection');
		$method->setAccessible(true);

		$db = $method->invoke($this->_cache);
		$this->assertInstanceOf(TDbConnection::class, $db);
		$this->assertTrue($db->getActive());
	}

	public function testConnectionWithUsernamePassword()
	{
		$dbFileWithCreds = $this->_dbDir . '/test_creds.db';
		
		$this->_dbFiles[] = $dbFileWithCreds;

		$this->_cache->setConnectionString('sqlite:' . $dbFileWithCreds);
		$this->_cache->setUsername('testuser');
		$this->_cache->setPassword('testpassword');
		$this->_cache->init(null);

		$key = 'cred_key';
		$value = 'cred_value';

		$this->_cache->set($key, $value);
		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);
	}

	public function testVeryShortExpiration()
	{
		$this->initCache();

		$key = 'short_expire';
		$value = 'short_expire_value';

		$this->_cache->set($key, $value, 1);

		$result = $this->_cache->get($key);
		$this->assertEquals($value, $result);

		sleep(2);

		$result = $this->_cache->get($key);
		$this->assertFalse($result);
	}

	public function testUpdateExistingKeyViaAdd()
	{
		$this->initCache();

		$key = 'update_add_key';

		$this->_cache->add($key, 'initial');
		$result = $this->_cache->get($key);
		$this->assertEquals('initial', $result);

		$this->_cache->delete($key);
		$result = $this->_cache->get($key);
		$this->assertFalse($result);

		$this->_cache->add($key, 'after_delete');
		$result = $this->_cache->get($key);
		$this->assertEquals('after_delete', $result);
	}
}
