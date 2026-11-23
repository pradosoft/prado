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
use Prado\Data\TDbDriverCapabilities;
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
 * CREATE TABLE pradocache (itemkey CHAR(128) NOT NULL PRIMARY KEY, value BLOB, expire INTEGER)
 * CREATE INDEX IX_pradocache_expire ON pradocache (expire)
 * ```
 *
 * The value column is `LONGBLOB` on MySQL, `BYTEA` on PostgreSQL, `VARBINARY(MAX)` on SQL
 * Server, and `BLOB(1G)` on IBM DB2. The key is `VARCHAR2(128)` on Oracle and `VARCHAR(128)`
 * on IBM DB2 ({@see getColumnTypes()}). Firebird reserves `VALUE`, so its value column is the
 * quoted `"VALUE"` ({@see getValueColumn()}).
 *
 * `itemkey` is the primary key; the upsert of {@see set()} replaces the row with the same key
 * ({@see getUpsertSql()}). The `expire` index is named after the table.
 *
 * On IBM DB2, pdo_ibm fetches a value of about 64 KiB or more as `NULL`, which reads as a miss.
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

	/** @var string the PL/SQL variable that holds the value on Oracle ({@see getOracleValueBlock()}). */
	public const ORACLE_VALUE_VARIABLE = 'cachevalue';
	/** @var int the bytes of the value in each `HEXTORAW` literal on Oracle ({@see getOracleValueBlock()}). */
	public const ORACLE_CHUNK_SIZE = 16000;

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
		$app = $this->getApplication();
		if ($app) {
			$app->attachEventHandler('OnLoadStateComplete', [$this, 'doInitializeCache']);
			$app->attachEventHandler('OnSaveState', [$this, 'doFlushCacheExpired']);
		}
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
		$autoCreateCacheTable = $this->getAutoCreateCacheTable();
		$key = $this->getTableStateKey('created');
		try {
			if ($force) {
				$this->_createCheck = false;
			} else {
				$this->_createCheck = $this->getApplication()->getGlobalState($key, 0);
			}

			if ($autoCreateCacheTable && !$this->_createCheck) {
				Prado::trace(($force ? 'Force initializing: ' : 'Initializing: ') . $this->getConnectionID() . ', ' . $cacheTable, TDbCache::class);

				$sql = 'SELECT 1 FROM ' . $cacheTable . ' WHERE 0=1';
				$db->createCommand($sql)->queryScalar();

				$this->_createCheck = true;
				$this->getApplication()->setGlobalState($key, $this->getClock()->time());
			}
		} catch (\Exception $e) {
			// DB table not exists
			Prado::trace('Autocreate: ' . $cacheTable, TDbCache::class);

			$driver = TDbDriverCapabilities::normalizeDriver($db->getDriverName());
			[$keyType, $valueType] = $this->getColumnTypes($driver);
			$sql = 'CREATE TABLE ' . $cacheTable . " (itemkey $keyType NOT NULL PRIMARY KEY, {$this->getValueColumn($driver)} $valueType, expire INTEGER)";
			$db->createCommand($sql)->execute();

			$sql = 'CREATE INDEX ' . $this->getExpireIndexName() . ' ON ' . $cacheTable . ' (expire)';
			$db->createCommand($sql)->execute();

			$this->_createCheck = true;
			$this->getApplication()->setGlobalState($key, $this->getClock()->time());
		}
		$this->setIsCacheInitialized(true);
	}

	/**
	 * Returns the name of the index on the `expire` column, derived from the table name.
	 * SQLite and PostgreSQL scope index names to the schema, so each cache table has its own.
	 * @return string `IX_`, the table name with each run of non-word characters as `_`, and `_expire`.
	 * @since 4.4.0
	 */
	protected function getExpireIndexName(): string
	{
		return 'IX_' . preg_replace('/\W+/', '_', $this->getCacheTableName()) . '_expire';
	}

	/**
	 * Returns the global state key that records an event of the cache table: `created` or `flushed`.
	 * The key names the connection and the table, so caches with one table name in different
	 * databases keep their own state. The connection is its {@see getConnectionID ConnectionID},
	 * its {@see getConnectionString ConnectionString}, or the default SQLite database, in that
	 * order; it is hashed, so a connection string is not written to the global state.
	 * @param string $event the event the key records.
	 * @return string the global state key.
	 * @since 4.4.0
	 */
	protected function getTableStateKey(string $event): string
	{
		if (($connection = $this->getConnectionID()) !== '') {
			$connection = 'id:' . $connection;
		} elseif (($connection = $this->getConnectionString()) !== '') {
			$connection = 'dsn:' . $connection;
		} else {
			$connection = 'sqlite:' . $this->getSqliteDatabaseName();
		}
		return 'TDbCache:' . sha1($connection) . ':' . $this->getCacheTableName() . ':' . $event;
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
		$key = $this->getTableStateKey('flushed');
		$now = $this->getClock()->time();
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
		$this->_flushInterval = TPropertyValue::ensureInteger($value);
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
	 * CREATE TABLE pradocache (itemkey CHAR(128) NOT NULL PRIMARY KEY, value BLOB, expire INTEGER)
	 * CREATE INDEX IX_pradocache_expire ON pradocache (expire)
	 * ```
	 *
	 * The value column is `LONGBLOB` on MySQL, `BYTEA` on PostgreSQL, `VARBINARY(MAX)` on SQL
	 * Server, and `BLOB(1G)` on IBM DB2. The key is `VARCHAR2(128)` on Oracle and `VARCHAR(128)`
	 * on IBM DB2 ({@see getColumnTypes()}).
	 *
	 * `itemkey` is the primary key; the upsert of {@see set()} replaces the row with the same key.
	 *
	 * @param string $value the name of the DB table to store cache content, an SQL identifier
	 * @throws \Prado\Exceptions\TInvalidDataValueException if the name is not an SQL identifier
	 * @see setAutoCreateCacheTable
	 */
	public function setCacheTableName($value)
	{
		$this->assertUninitialized('CacheTableName');
		$this->_cacheTable = $this->ensureSqlIdentifier(TPropertyValue::ensureString($value), 'CacheTableName');
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
	 * Returns the serialized value of a key. IBM DB2 (pdo_ibm) fetches a `BLOB` of about
	 * 64 KiB or more as `NULL`; a `NULL` value is a miss.
	 * @param string $key a unique key identifying the cached value
	 * @return false|string the serialized value on a hit, or `false` on a miss or expiry.
	 * @since 4.4.0
	 */
	protected function getSerializedValue(string $key): false|string
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}

		$column = $this->getValueColumn(TDbDriverCapabilities::normalizeDriver($this->getDbConnection()->getDriverName()));
		$sql = 'SELECT ' . $column . ' FROM ' . $this->getCacheTableName() . ' WHERE itemkey=:key AND (expire=0 OR expire>=' . $this->getClock()->time() . ') ORDER BY expire DESC';
		return $this->runCacheCommand($sql, [':key' => [$key, \PDO::PARAM_STR]], 'queryScalar') ?? false;
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
		$driver = TDbDriverCapabilities::normalizeDriver($this->getDbConnection()->getDriverName());
		$expire = ($expire <= 0) ? 0 : $this->getClock()->time() + $expire;
		if (($sql = $this->getUpsertSql($driver, $expire)) !== null) {
			return $this->storeValue($driver, $sql, $key, $value);
		}
		$isCurrentTransaction = $this->getDbConnection()->getCurrentTransaction();
		$transaction = $isCurrentTransaction ?? $this->getDbConnection()->beginTransaction();

		$this->deleteValue($key);
		$return = $this->storeValue($driver, $this->getInsertSql($driver, $expire), $key, $value);

		if (!$isCurrentTransaction) {
			$transaction->commit();
		}

		return $return;
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
		$driver = TDbDriverCapabilities::normalizeDriver($this->getDbConnection()->getDriverName());
		$expire = ($expire <= 0) ? 0 : $this->getClock()->time() + $expire;
		return $this->storeValue($driver, $this->getInsertSql($driver, $expire), $key, $value);
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

		$db = $this->getDbConnection();
		$this->runCacheCommand("DELETE FROM {$this->getCacheTableName()} WHERE itemkey=:key", [':key' => [$key, $db::PARAM_STR]]);
		return true;
	}

	/**
	 * Deletes all values from cache.
	 */
	public function flush()
	{
		if (!$this->getIsCacheInitialized()) {
			$this->initializeCache();
		}
		try {
			$this->runCacheCommand("DELETE FROM {$this->getCacheTableName()}");
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Runs a command on the cache table, and runs it once more after initializing the
	 * table again when it fails. Each attempt creates and binds its own command, since a
	 * driver that prepares a statement as it is bound, such as SQLite, fails there for a
	 * missing table.
	 * @param string $sql the SQL statement.
	 * @param array<string, array{0: mixed, 1: int}> $params the value and PDO type of each parameter, by name.
	 * @param string $method the {@see \Prado\Data\TDbCommand} method that runs the command: `execute` or `queryScalar`.
	 * @throws \Exception if the second attempt fails.
	 * @return mixed the result of $method.
	 * @since 4.4.0
	 */
	protected function runCacheCommand(string $sql, array $params = [], string $method = 'execute'): mixed
	{
		return $this->retryAfterInitializing(function () use ($sql, $params, $method): mixed {
			$command = $this->getDbConnection()->createCommand($sql);
			foreach ($params as $name => [$value, $type]) {
				$command->bindValue($name, $value, $type);
			}
			return $command->$method();
		});
	}

	/**
	 * Runs an operation on the cache table, and runs it once more after initializing the
	 * table again when it fails.
	 * @param callable(): mixed $attempt the operation.
	 * @throws \Exception if the second attempt fails.
	 * @return mixed the result of the operation.
	 * @since 4.4.0
	 */
	protected function retryAfterInitializing(callable $attempt): mixed
	{
		try {
			return $attempt();
		} catch (\Exception $e) {
			$this->initializeCache(true);
			return $attempt();
		}
	}

	/**
	 * Returns the statement that inserts or replaces an entry, or `null` for a driver
	 * without an upsert, which deletes and inserts. The statement binds `:key` and holds
	 * the value expression of {@see getValueSql()}.
	 *
	 * | Driver | Upsert |
	 * |---|---|
	 * | MySQL, SQLite | `REPLACE INTO` |
	 * | PostgreSQL | `INSERT ... ON CONFLICT` |
	 * | SQL Server, IBM DB2, Oracle | `MERGE` |
	 * | Firebird | `UPDATE OR INSERT ... MATCHING` |
	 * @param string $driver the normalized PDO driver name.
	 * @param int $expire the Unix time the entry expires at; 0 means never.
	 * @return ?string the upsert statement, or `null`.
	 * @since 4.4.0
	 */
	protected function getUpsertSql(string $driver, int $expire): ?string
	{
		$table = $this->getCacheTableName();
		$value = $this->getValueSql($driver);
		$actions = "ON (t.itemkey = s.itemkey) WHEN MATCHED THEN UPDATE SET t.value = s.value, t.expire = $expire " .
			"WHEN NOT MATCHED THEN INSERT (itemkey, value, expire) VALUES (s.itemkey, s.value, $expire)";
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL, TDbDriver::DRIVER_SQLITE => "REPLACE INTO {$table} (itemkey,value,expire) VALUES (:key,$value,$expire)",
			TDbDriver::DRIVER_PGSQL => "INSERT INTO {$table} (itemkey, value, expire) VALUES (:key, $value, $expire) " .
				'ON CONFLICT (itemkey) DO UPDATE SET value = EXCLUDED.value, expire = EXCLUDED.expire',
			TDbDriver::DRIVER_SQLSRV, TDbDriver::DRIVER_DBLIB => "MERGE INTO {$table} AS t USING (SELECT :key AS itemkey, $value AS value) AS s $actions;",
			TDbDriver::DRIVER_IBM => "MERGE INTO {$table} AS t USING (SELECT CAST(:key AS VARCHAR(128)) AS itemkey, $value AS value " .
				"FROM SYSIBM.SYSDUMMY1) AS s $actions",
			TDbDriver::DRIVER_OCI => "MERGE INTO {$table} t USING (SELECT :key AS itemkey, $value AS value FROM DUAL) s $actions",
			TDbDriver::DRIVER_FIREBIRD => "UPDATE OR INSERT INTO {$table} (itemkey, {$this->getValueColumn($driver)}, expire) VALUES (:key, $value, $expire) MATCHING (itemkey)",
			default => null,
		};
	}

	/**
	 * Returns the statement that inserts an entry. It binds `:key` and holds the value
	 * expression of {@see getValueSql()}.
	 * @param string $driver the normalized PDO driver name.
	 * @param int $expire the Unix time the entry expires at; 0 means never.
	 * @return string the insert statement.
	 * @since 4.4.0
	 */
	protected function getInsertSql(string $driver, int $expire): string
	{
		return "INSERT INTO {$this->getCacheTableName()} (itemkey,{$this->getValueColumn($driver)},expire) VALUES(:key,{$this->getValueSql($driver)},$expire)";
	}

	/**
	 * Returns the name of the value column in SQL. `VALUE` is a reserved word in Firebird,
	 * so Firebird quotes it as `"VALUE"`, the name Firebird gives an unquoted `value`.
	 * @param string $driver the normalized PDO driver name.
	 * @return string the value column.
	 * @since 4.4.0
	 */
	protected function getValueColumn(string $driver): string
	{
		return $driver === TDbDriver::DRIVER_FIREBIRD ? '"VALUE"' : 'value';
	}

	/**
	 * Returns the SQL expression of the value in a statement that stores an entry.
	 *
	 * | Driver | Expression | Reason |
	 * |---|---|---|
	 * | SQL Server | `CONVERT(VARBINARY(MAX), :value, 2)` | pdo_sqlsrv binds a string as `nvarchar`, which does not convert to `VARBINARY`; the value is bound as hex. |
	 * | IBM DB2 | `CAST(:value AS BLOB(1G))` | DB2 needs the type of a parameter in a `SELECT`. |
	 * | Oracle | `cachevalue` | The PL/SQL variable of {@see getOracleValueBlock()}. |
	 * | Others | `:value` | The value is bound as a LOB. |
	 * @param string $driver the normalized PDO driver name.
	 * @return string the value expression.
	 * @since 4.4.0
	 */
	protected function getValueSql(string $driver): string
	{
		return match ($driver) {
			TDbDriver::DRIVER_SQLSRV, TDbDriver::DRIVER_DBLIB => 'CONVERT(VARBINARY(MAX), :value, 2)',
			TDbDriver::DRIVER_IBM => 'CAST(:value AS BLOB(1G))',
			TDbDriver::DRIVER_OCI => self::ORACLE_VALUE_VARIABLE,
			default => ':value',
		};
	}

	/**
	 * Runs a statement of {@see getUpsertSql()} or {@see getInsertSql()} with the key and
	 * the value. Oracle runs the statement in the PL/SQL block of {@see getOracleValueBlock()}.
	 * @param string $driver the normalized PDO driver name.
	 * @param string $sql the statement.
	 * @param string $key the item key.
	 * @param string $value the serialized value.
	 * @return bool whether the value is stored.
	 * @since 4.4.0
	 */
	protected function storeValue(string $driver, string $sql, string $key, string $value): bool
	{
		$params = [':key' => [$key, \PDO::PARAM_STR]];
		if ($driver === TDbDriver::DRIVER_OCI) {
			$sql = $this->getOracleValueBlock($sql, $value);
		} elseif ($driver === TDbDriver::DRIVER_SQLSRV || $driver === TDbDriver::DRIVER_DBLIB) {
			$params[':value'] = [bin2hex($value), \PDO::PARAM_STR];
		} else {
			$params[':value'] = [$value, \PDO::PARAM_LOB];
		}
		try {
			$this->runCacheCommand($sql, $params);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Returns a PL/SQL block that builds the value in a temporary `BLOB` and runs the
	 * statement with it. {@see \Prado\Data\Common\Oracle\TOracleDbCommand} writes each
	 * bound value into the SQL as a quoted string, which Oracle reads as hex for a `BLOB`.
	 * The block appends the value as `HEXTORAW` literals of {@see ORACLE_CHUNK_SIZE} bytes,
	 * within the 32,767-byte limit of a PL/SQL string.
	 * @param string $sql the statement, which reads the value from {@see ORACLE_VALUE_VARIABLE}.
	 * @param string $value the serialized value.
	 * @return string the PL/SQL block.
	 * @since 4.4.0
	 */
	protected function getOracleValueBlock(string $sql, string $value): string
	{
		$lob = self::ORACLE_VALUE_VARIABLE;
		$writes = '';
		foreach (str_split($value, self::ORACLE_CHUNK_SIZE) as $chunk) {
			$writes .= "DBMS_LOB.WRITEAPPEND($lob, " . strlen($chunk) . ", HEXTORAW('" . bin2hex($chunk) . "')); ";
		}
		return "DECLARE $lob BLOB; BEGIN DBMS_LOB.CREATETEMPORARY($lob, TRUE); {$writes}{$sql}; DBMS_LOB.FREETEMPORARY($lob); END;";
	}

	/**
	 * Returns the column types of the cache table for a driver: the item key, then the value.
	 * The value is a binary column; the key is `VARCHAR` where a `CHAR` key would compare
	 * with its padding, as on Oracle.
	 * @param string $driver the normalized PDO driver name.
	 * @return array{0: string, 1: string} the key type and the value type.
	 * @since 4.4.0
	 */
	protected function getColumnTypes(string $driver): array
	{
		return match ($driver) {
			TDbDriver::DRIVER_MYSQL => ['CHAR(128)', 'LONGBLOB'],
			TDbDriver::DRIVER_PGSQL => ['CHAR(128)', 'BYTEA'],
			TDbDriver::DRIVER_SQLSRV, TDbDriver::DRIVER_DBLIB => ['CHAR(128)', 'VARBINARY(MAX)'],
			TDbDriver::DRIVER_OCI => ['VARCHAR2(128)', 'BLOB'],
			TDbDriver::DRIVER_IBM => ['VARCHAR(128)', 'BLOB(1G)'],
			default => ['CHAR(128)', 'BLOB'],
		};
	}
}
