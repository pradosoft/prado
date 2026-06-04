<?php

/**
 * TDbCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Prado;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\Data\TDbPropertiesTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Util\IDbModule;

/**
 * TDbCache class
 *
 * TDbCache implements a cache application module by storing cached data in a database.
 *
 * TDbCache relies on {@see http://www.php.net/manual/en/ref.pdo.php PDO} to retrieve
 * data from databases. In order to use TDbCache, you need to enable the PDO extension
 * as well as the corresponding PDO DB driver. For example, to use SQLite database
 * to store cached data, you need both php_pdo and php_pdo_sqlite extensions.
 *
 * By default, TDbCache creates and uses an SQLite database under the application
 * runtime directory. You may change this default setting by specifying the following
 * properties:
 * - {@see \Prado\Caching\TDbCache::setConnectionID() ConnectionID} or
 * - {@see \Prado\Caching\TDbCache::setConnectionString() ConnectionString}, {@see \Prado\Caching\TDbCache::setUsername() Username} and {@see \Prado\Caching\TDbCache::setPassword() Pasword}.
 *
 * The cached data is stored in a table in the specified database.
 * By default, the name of the table is called 'pradocache'. If the table does not
 * exist in the database, it will be automatically created with the following structure:
 * ```sql
 * CREATE TABLE pradocache (itemkey CHAR(128), value BLOB, expire INT)
 * CREATE INDEX IX_itemkey ON pradocache (itemkey)
 * CREATE INDEX IX_expire ON pradocache (expire)
 * ```
 *
 * Note, some DBMS might not support BLOB type. In this case, replace 'BLOB' with a suitable
 * binary data type (e.g. LONGBLOB in MySQL, BYTEA in PostgreSQL.)
 *
 * Important: Make sure that the indices are non-unique!
 *
 * If you want to change the cache table name, or if you want to create the table by yourself,
 * you may set {@see \Prado\Caching\TDbCache::setCacheTableName() CacheTableName} and {@see \Prado\Caching\TDbCache::setAutoCreateCacheTable() AutoCreateCacheTable} properties.
 *
 * {@see \Prado\Caching\TDbCache::setFlushInterval() FlushInterval} control how often expired items will be removed from cache.
 * If you prefer to remove expired items manualy e.g. via cronjob you can disable automatic deletion by setting FlushInterval to '0'.
 *
 * The following basic cache operations are implemented:
 * - {@see self::get()} : retrieve the value with a key (if any) from cache
 * - {@see \Prado\Caching\TDbCache::set()} : store the value with a key into cache
 * - {@see \Prado\Caching\TDbCache::add()} : store the value only if cache does not have this key
 * - {@see \Prado\Caching\TDbCache::delete()} : delete the value with the specified key from cache
 * - {@see \Prado\Caching\TDbCache::flush()} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@see \Prado\Caching\TDbCache::get()} operation
 * ensures that any expired value will not be returned. The expiration time by
 * the number of seconds. A expiration time 0 represents never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be a persistent storage.
 *
 * Do not use the same database file for multiple applications using TDbCache.
 * Also note, cache is shared by all user sessions of an application.
 *
 * Some usage examples of TDbCache are as follows,
 * ```php
 * $cache=new TDbCache;  // TDbCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * ```
 *
 * If loaded, TDbCache will register itself with {@see \Prado\TApplication} as the
 * cache module. It can be accessed via {@see \Prado\TApplication::getCache()}.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="cache" class="Prado\Caching\TDbCache"
 *       ConnectionString="sqlite:protected/runtime/cache.db"
 *       CacheTableName="pradocache" AutoCreateCacheTable="true" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TDbCache',
 *             'properties' => [
 *                 'ConnectionString' => 'sqlite:protected/runtime/cache.db',
 *                 'CacheTableName' => 'pradocache',
 *                 'AutoCreateCacheTable' => 'true',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TDbCache extends TSerializingCache implements IDbModule
{
	use TDbPropertiesTrait {
		getDbConnection as getTraitDbConnection;
	}

	/**
	 * @var string name of the DB cache table
	 */
	private $_cacheTable = 'pradocache';
	/**
	 * @var int Interval expired items will be removed from cache
	 */
	private $_flushInterval = 60;
	/**
	 * @var bool whether the cache table has been verified or created for this request
	 */
	private $_cacheInitialized = false;
	/**
	 * @var bool whether the cache table existence has already been confirmed in this request
	 */
	private $_createCheck = false;
	/**
	 * @var bool whether the cache DB table should be created automatically
	 */
	private $_autoCreate = true;
	/** @var string username for establishing the DB connection */
	private $_username = '';
	/** @var string password for establishing the DB connection */
	private $_password = '';
	/** @var string DSN connection string for the DB connection */
	private $_connectionString = '';

	/**
	 * @return bool whether the PDO extension is loaded.
	 * @since 4.4.0
	 */
	public static function getIsAvailable(): bool
	{
		return extension_loaded('pdo');
	}

	/**
	 * @return bool whether the cache table has been verified or created for this request.
	 * @since 4.4.0
	 */
	protected function getIsCacheInitialized(): bool
	{
		return $this->_cacheInitialized;
	}

	/**
	 * @param bool $value whether the cache table has been verified or created for this request.
	 * @since 4.4.0
	 */
	protected function setIsCacheInitialized(bool $value): void
	{
		$this->_cacheInitialized = $value;
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * attach {@see \Prado\Caching\TDbCache::doInitializeCache()} to TApplication.OnLoadStateComplete event
	 * attach {@see \Prado\Caching\TDbCache::doFlushCacheExpired()} to TApplication.OnSaveState event
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module, can be null
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
			throw new TConfigurationException('dbcache_unavailable');
		}
		$this->getApplication()->attachEventHandler('OnLoadStateComplete', [$this, 'doInitializeCache']);
		$this->getApplication()->attachEventHandler('OnSaveState', [$this, 'doFlushCacheExpired']);
		parent::init($config);
	}

	/**
	 * Event listener for TApplication.OnSaveState
	 * @since 3.1.5
	 * @see flushCacheExpired
	 */
	public function doFlushCacheExpired()
	{
		$this->flushCacheExpired(false);
	}

	/**
	 * Event listener for TApplication.OnLoadStateComplete
	 *
	 * @since 3.1.5
	 * @see initializeCache
	 */
	public function doInitializeCache()
	{
		$this->initializeCache();
	}

	/**
	 * Initialize TDbCache
	 *
	 * If {@see \Prado\Caching\TDbCache::setAutoCreateCacheTable() AutoCreateCacheTable} is 'true', checks the existence of the cache table
	 * and create table if does not exist.
	 *
	 * @param bool $force Force override global state check
	 * @throws TConfigurationException if any error happens during creating database or cache table.
	 * @since 3.1.5
	 */
	protected function initializeCache($force = false)
	{
		if ($this->getIsCacheInitialized() && !$force) {
			return;
		}
		$db = $this->getDbConnection();
		$cacheTable = $this->getCacheTableName();
		try {
			$key = 'TDbCache:' . $cacheTable . ':created';
			if ($force) {
				$this->_createCheck = false;
			} else {
				$this->_createCheck = $this->getApplication()->getGlobalState($key, 0);
			}

			if ($this->getAutoCreateCacheTable() && !$this->_createCheck) {
				Prado::trace(($force ? 'Force initializing: ' : 'Initializing: ') . $this->getConnectionID() . ', ' . $cacheTable, TDbCache::class);

				$sql = 'SELECT 1 FROM ' . $cacheTable . ' WHERE 0=1';
				$db->createCommand($sql)->queryScalar();

				$this->_createCheck = true;
				$this->getApplication()->setGlobalState($key, $this->time());
			}
		} catch (\Exception $e) {
			// DB table not exists
			if ($this->getAutoCreateCacheTable()) {
				Prado::trace('Autocreate: ' . $cacheTable, TDbCache::class);

				$driver = $db->getDriverName();
				if ($driver === TDbDriver::DRIVER_MYSQL) {
					$blob = 'LONGBLOB';
				} elseif ($driver === TDbDriver::DRIVER_PGSQL) {
					$blob = 'BYTEA';
				} else {
					$blob = 'BLOB';
				}

				$sql = 'CREATE TABLE ' . $cacheTable . " (itemkey CHAR(128) PRIMARY KEY, value $blob, expire INTEGER)";
				$db->createCommand($sql)->execute();

				$sql = 'CREATE INDEX IX_expire ON ' . $cacheTable . ' (expire)';
				$db->createCommand($sql)->execute();

				$this->_createCheck = true;
				$this->getApplication()->setGlobalState($key, $this->time());
			} else {
				throw new TConfigurationException('db_cachetable_inexistent', $cacheTable);
			}
		}
		$this->setIsCacheInitialized(true);
	}

	/**
	 * Flush expired values from cache depending on {@see \Prado\Caching\TDbCache::setFlushInterval() FlushInterval}
	 * @param bool $force override {@see \Prado\Caching\TDbCache::setFlushInterval() FlushInterval} and force deletion of expired items
	 * @since 3.1.5
	 */
	public function flushCacheExpired($force = false)
	{
		$interval = $this->getFlushInterval();
		if (!$force && $interval === 0) {
			return;
		}
		$cacheTable = $this->getCacheTableName();
		$key = 'TDbCache:' . $cacheTable . ':flushed';
		$now = $this->time();
		$next = $interval + (int) $this->getApplication()->getGlobalState($key, 0);

		if ($force || $next <= $now) {
			if (!$this->getIsCacheInitialized()) {
				$this->initializeCache();
			}
			Prado::trace(($force ? 'Force flush of expired items: ' : 'Flush expired items: ') . $this->getConnectionID() . ', ' . $cacheTable, TDbCache::class);
			$sql = 'DELETE FROM ' . $cacheTable . ' WHERE expire<>0 AND expire<' . $now;
			$this->getDbConnection()->createCommand($sql)->execute();
			$this->getApplication()->setGlobalState($key, $now);
		}
	}

	/**
	 * @param object $sender the object raising fxGetCronTaskInfos.
	 * @param mixed $param the parameter
	 * @since 4.2.0
	 */
	public function fxGetCronTaskInfos($sender, $param)
	{
		return Prado::createComponent(TCronTaskInfo::class, 'dbcacheflushexpired', $this->getId() . '->flushCacheExpired(true)', $this, Prado::localize('DbCache Flush Expired Keys'), Prado::localize('This manually clears out the expired keys of TDbCache.'));
	}

	/**
	 * @return int Interval in sec expired items will be removed from cache. Default to 60
	 * @since 3.1.5
	 */
	public function getFlushInterval()
	{
		return $this->_flushInterval;
	}

	/**
	 * Sets interval expired items will be removed from cache
	 *
	 * To disable automatic deletion of expired items,
	 * e.g. for external flushing via cron you can set value to '0'
	 *
	 * @param int $value Interval in sec
	 * @since 3.1.5
	 */
	public function setFlushInterval($value)
	{
		$this->_flushInterval = $value;
	}

	/**
	 * Active on every getDbConnection.
	 * @return ?bool What kind of activation for the connection on retrieving.
	 * @since 4.3.3
	 */
	protected function getDbConnectionActivationType(): ?bool
	{
		return true;
	}

	/**
	 * If there is a custom Db Connection that isn't referenced by ConnectionID.
	 * @return ?TDbConnection the custom DB connection, or null if not implemented
	 * @since 4.3.3
	 */
	protected function getCustomDbConnection(): ?TDbConnection
	{
		$connectionString = $this->getConnectionString();
		if (empty($connectionString)) {
			return null;
		}
		$db = Prado::createComponent(TDbConnection::class);
		$db->setConnectionString($connectionString);
		$username = $this->getUsername();
		if ($username !== '') {
			$db->setUsername($username);
		}
		$password = $this->getPassword();
		if ($password !== '') {
			$db->setPassword($password);
		}
		return $db;
	}

	/**
	 * @return string the SQLite database filename within the PRADO runtime path.
	 * @since 4.3.3
	 */
	protected function getSqliteDatabaseName()
	{
		return 'sqlite3.cache';
	}

	/**
	 * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
	 */
	public function getConnectionString()
	{
		return $this->_connectionString;
	}

	/**
	 * @param string $value The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @see http://www.php.net/manual/en/function.pdo-construct.php
	 */
	public function setConnectionString($value)
	{
		$this->assertUninitialized('ConnectionString');
		$this->_connectionString = $value;
	}

	/**
	 * @return string the username for establishing DB connection. Defaults to empty string.
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * @param string $value the username for establishing DB connection
	 */
	public function setUsername($value)
	{
		$this->assertUninitialized('Username');
		$this->_username = $value;
	}

	/**
	 * @return string the password for establishing DB connection. Defaults to empty string.
	 */
	public function getPassword()
	{
		return $this->_password;
	}

	/**
	 * @param string $value the password for establishing DB connection
	 */
	public function setPassword(#[\SensitiveParameter] $value)
	{
		$this->assertUninitialized('Password');
		$this->_password = $value;
	}

	/**
	 * @return string the name of the DB table to store cache content. Defaults to 'pradocache'.
	 * @see setAutoCreateCacheTable
	 */
	public function getCacheTableName()
	{
		return $this->_cacheTable;
	}

	/**
	 * Sets the name of the DB table to store cache content.
	 * Note, if {@see \Prado\Caching\TDbCache::setAutoCreateCacheTable() AutoCreateCacheTable} is false
	 * and you want to create the DB table manually by yourself,
	 * you need to make sure the DB table is of the following structure:
	 * ```sql
	 * CREATE TABLE pradocache (itemkey CHAR(128), value BLOB, expire INT)
	 * CREATE INDEX IX_itemkey ON pradocache (itemkey)
	 * CREATE INDEX IX_expire ON pradocache (expire)
	 * ```
	 *
	 * Note, some DBMS might not support BLOB type. In this case, replace 'BLOB' with a suitable
	 * binary data type (e.g. LONGBLOB in MySQL, BYTEA in PostgreSQL.)
	 *
	 * Important: Make sure that the indices are non-unique!
	 *
	 * @param string $value the name of the DB table to store cache content
	 * @see setAutoCreateCacheTable
	 */
	public function setCacheTableName($value)
	{
		$this->assertUninitialized('CacheTableName');
		$this->_cacheTable = $value;
	}

	/**
	 * @return bool whether the cache DB table should be automatically created if not exists. Defaults to true.
	 * @see setAutoCreateCacheTable
	 */
	public function getAutoCreateCacheTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param bool $value whether the cache DB table should be automatically created if not exists.
	 * @see setCacheTableName
	 */
	public function setAutoCreateCacheTable($value)
	{
		$this->assertUninitialized('AutoCreateCacheTable');
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @param string $key a unique key identifying the cached value
	 * @return false|string the serialized value on a hit, or `false` on a miss or expiry.
	 * @since 4.4.0
	 */
	protected function getSerializedValue(string $key): false|string
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}

		$sql = 'SELECT value FROM ' . $this->getCacheTableName() . ' WHERE itemkey=\'' . $key . '\' AND (expire=0 OR expire>=' . $this->time() . ') ORDER BY expire DESC';
		$command = $this->getDbConnection()->createCommand($sql);
		try {
			return $command->queryScalar();
		} catch (\Exception $e) {
			$this->initializeCache(true);
			return $command->queryScalar();
		}
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the serialized value to store
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 * @since 4.4.0
	 */
	protected function setSerializedValue(string $key, string $value, int $expire): bool
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		if (in_array($driver, [TDbDriver::DRIVER_MYSQL, TDbDriver::EXTENSION_MYSQLI, TDbDriver::DRIVER_SQLITE, TDbDriver::DRIVER_IBM, TDbDriver::DRIVER_OCI, TDbDriver::DRIVER_SQLSRV, TDbDriver::EXTENSION_MSSQL, TDbDriver::DRIVER_DBLIB, TDbDriver::DRIVER_PGSQL])) {
			$expire = ($expire <= 0) ? 0 : $this->time() + $expire;
			$cacheTable = $this->getCacheTableName();
			if (in_array($driver, [TDbDriver::DRIVER_MYSQL, TDbDriver::EXTENSION_MYSQLI, TDbDriver::DRIVER_SQLITE])) {
				$sql = "REPLACE INTO {$cacheTable} (itemkey,value,expire) VALUES (:key,:value,$expire)";
			} elseif ($driver === TDbDriver::DRIVER_PGSQL) {
				$sql = "INSERT INTO {$cacheTable} (itemkey, value, expire) VALUES (:key, :value, :expire) " .
					"ON CONFLICT (itemkey) DO UPDATE SET value = EXCLUDED.value, expire = EXCLUDED.expire";
			} else {
				$sql = "MERGE INTO {$cacheTable} AS c " .
				"USING (SELECT :key AS itemkey, :value AS value, $expire AS expire) AS data " .
				"ON c.itemkey = data.itemkey " .
				"WHEN MATCHED THEN " .
					"UPDATE SET c.value = data.value, c.expire = data.expire " .
				"WHEN NOT MATCHED THEN " .
					"INSERT (itemkey, value, expire) " .
					"VALUES (data.itemkey, data.value, data.expire)";
			}
			$command = $db->createCommand($sql);
			$command->bindValue(':key', $key, \PDO::PARAM_STR);
			$command->bindValue(':value', $value, \PDO::PARAM_LOB);

			try {
				$command->execute();
				return true;
			} catch (\Exception $e) {
				try {
					$this->initializeCache(true);
					$command->execute();
					return true;
				} catch (\Exception $e) {
					return false;
				}
			}
		} else {
			$isCurrentTransaction = $this->getDbConnection()->getCurrentTransaction();
			$transaction = $this->getDbConnection()->getCurrentTransaction() ?? $this->getDbConnection()->beginTransaction();

			$this->deleteValue($key);
			$return = $this->addSerializedValue($key, $value, $expire);

			if (!$isCurrentTransaction) {
				$transaction->commit();
			}

			return $return;
		}
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the serialized value to store
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 * @since 4.4.0
	 */
	protected function addSerializedValue(string $key, string $value, int $expire): bool
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}
		$expire = ($expire <= 0) ? 0 : $this->time() + $expire;
		$sql = "INSERT INTO {$this->getCacheTableName()} (itemkey,value,expire) VALUES(:key,:value,$expire)";
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':key', $key, \PDO::PARAM_STR);
		$command->bindValue(':value', $value, \PDO::PARAM_LOB);

		try {
			$command->execute();
			return true;
		} catch (\Exception $e) {
			try {
				$this->initializeCache(true);
				$command->execute();
				return true;
			} catch (\Exception $e) {
				return false;
			}
		}
	}

	/**
	 * @param string $key the key of the value to be deleted
	 * @return bool true if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}

		$command = $this->getDbConnection()->createCommand("DELETE FROM {$this->getCacheTableName()} WHERE itemkey=:key");
		$command->bindValue(':key', $key, \PDO::PARAM_STR);
		try {
			$command->execute();
			return true;
		} catch (\Exception $e) {
			$this->initializeCache(true);
			$command->execute();
			return true;
		}
	}

	/**
	 * Deletes all values from cache.
	 */
	public function flush()
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}
		$command = $this->getDbConnection()->createCommand("DELETE FROM {$this->getCacheTableName()}");
		try {
			$command->execute();
		} catch (\Exception $e) {
			try {
				$this->initializeCache(true);
				$command->execute();
				return true;
			} catch (\Exception $e) {
				return false;
			}
		}
		return true;
	}
}
