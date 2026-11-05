<?php

/**
 * TDbMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Data\IDataConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TDbMutex class.
 *
 * TDbMutex locks in the database of its connection with the driver's server lock.
 * On first use it creates the mutex of {@see getServerMutexClass()} for the
 * connection's driver and routes every attempt and release to it.  A driver without
 * a server lock uses {@see TDbTableMutex} while {@see getTableFallback TableFallback}
 * is true, the default.
 *
 * | Driver | Mutex |
 * |---|---|
 * | `mysql` | {@see TMysqlMutex} |
 * | `pgsql` | {@see TPgsqlMutex} |
 * | `oci` | {@see TOracleMutex} |
 * | `sqlsrv`, `dblib`, `mssql` | {@see TMssqlMutex} |
 * | any other | {@see TDbTableMutex}, or an exception without TableFallback |
 *
 * {@see getServerMutexClass()} and {@see getIsServerLockAvailable()} answer for a
 * connection, a DSN, or a driver name before a mutex is configured.  The server lock
 * also needs the database user's privileges, such as `EXECUTE` on `DBMS_LOCK`,
 * which the driver does not show.
 *
 * Without a {@see setConnectionID ConnectionID}, the connection is the SQLite
 * database `sqlite3.mutex` of the application runtime path, with the lock table.
 * {@see getKeyPrefix KeyPrefix} and the lock table properties pass to the routed
 * mutex; {@see getDriverMutex()} returns it for further configuration.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TDbMutex" ConnectionID="db" />
 * ```
 *
 * ```php
 * if (TDbMutex::getIsServerLockAvailable('pgsql:host=localhost;dbname=app')) {
 *     // pg_try_advisory_lock
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDbMutex extends TBaseDbMutex
{
	/** The server lock mutexes, in the order {@see getServerMutexClass()} checks them. */
	public const SERVER_MUTEX_CLASSES = [
		TMysqlMutex::class,
		TPgsqlMutex::class,
		TOracleMutex::class,
		TMssqlMutex::class,
	];

	/** @var bool whether a driver without a server lock uses the lock table. */
	private bool $_tableFallback = true;

	/** @var ?int the lease seconds of the lock table; null keeps its default. */
	private ?int $_expire = null;

	/** @var ?string the name of the lock table; null keeps its default. */
	private ?string $_tableName = null;

	/** @var ?bool whether the lock table is created; null keeps its default. */
	private ?bool $_autoCreate = null;

	/** @var ?TBaseDbMutex the mutex of the connection's driver. */
	private ?TBaseDbMutex $_driverMutex = null;

	/**
	 * @return string[] an empty list, as every driver routes to a server lock or the lock table.
	 */
	public static function getSupportedDrivers(): array
	{
		return [];
	}

	/**
	 * @param IDataConnection|string $source a connection, a DSN such as 'mysql:host=localhost', or a driver name.
	 * @return string the lowercase PDO driver name of the source.
	 */
	public static function getDriverName(IDataConnection|string $source): string
	{
		if ($source instanceof IDataConnection) {
			return strtolower((string) $source->getDriverName());
		}
		$position = strpos($source, ':');
		return strtolower(trim($position === false ? $source : substr($source, 0, $position)));
	}

	/**
	 * @param IDataConnection|string $source a connection, a DSN, or a driver name.
	 * @return ?string the class of {@see SERVER_MUTEX_CLASSES} that supports the source's driver, or null.
	 */
	public static function getServerMutexClass(IDataConnection|string $source): ?string
	{
		$driver = static::getDriverName($source);
		foreach (static::SERVER_MUTEX_CLASSES as $class) {
			if ($class::isDriverSupported($driver)) {
				return $class;
			}
		}
		return null;
	}

	/**
	 * @param IDataConnection|string $source a connection, a DSN, or a driver name.
	 * @return bool whether the source's driver has a server lock mutex.
	 */
	public static function getIsServerLockAvailable(IDataConnection|string $source): bool
	{
		return static::getServerMutexClass($source) !== null;
	}

	/**
	 * Returns the mutex of the connection's driver, creating it on first use.  It shares
	 * this mutex's connection and KeyPrefix and does not release on its own.
	 * @throws TConfigurationException when the driver has no server lock and
	 *   {@see getTableFallback TableFallback} is false.
	 * @return TBaseDbMutex the routed mutex.
	 */
	public function getDriverMutex(): TBaseDbMutex
	{
		if ($this->_driverMutex !== null) {
			return $this->_driverMutex;
		}
		$connection = $this->getLockConnection();
		$class = static::getServerMutexClass($connection);
		if ($class === null) {
			if (!$this->getTableFallback()) {
				$drivers = array_merge(...array_map(fn ($c) => $c::getSupportedDrivers(), static::SERVER_MUTEX_CLASSES));
				throw new TConfigurationException('dbmutex_driver_unsupported', static::class, static::getDriverName($connection), implode(', ', $drivers));
			}
			$class = TDbTableMutex::class;
		}
		$mutex = new $class();
		$mutex->setDbConnection($connection);
		$mutex->setAutoRelease(false);
		$this->_driverMutex = $mutex;
		$this->configureDriverMutex();
		return $mutex;
	}

	/**
	 * @return bool whether a driver without a server lock uses {@see TDbTableMutex}. Default true.
	 */
	public function getTableFallback(): bool
	{
		return $this->_tableFallback;
	}

	/**
	 * @param bool|string $value whether a driver without a server lock uses {@see TDbTableMutex}.
	 * @return static the current object.
	 */
	public function setTableFallback($value): static
	{
		$this->_tableFallback = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return ?int the lease seconds of the lock table. Default null, the {@see TDbTableMutex} default.
	 */
	public function getExpire(): ?int
	{
		return $this->_expire;
	}

	/**
	 * @param null|int|string $value the lease seconds of the lock table.
	 * @return static the current object.
	 */
	public function setExpire($value): static
	{
		$this->_expire = ($value === null || $value === '') ? null : TPropertyValue::ensureInteger($value);
		$this->configureDriverMutex();
		return $this;
	}

	/**
	 * @return ?string the name of the lock table. Default null, the {@see TDbTableMutex} default.
	 */
	public function getMutexTableName(): ?string
	{
		return $this->_tableName;
	}

	/**
	 * @param ?string $value the name of the lock table.
	 * @return static the current object.
	 */
	public function setMutexTableName($value): static
	{
		$this->_tableName = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		$this->configureDriverMutex();
		return $this;
	}

	/**
	 * @return ?bool whether the lock table is created on first use. Default null, the {@see TDbTableMutex} default.
	 */
	public function getAutoCreateMutexTable(): ?bool
	{
		return $this->_autoCreate;
	}

	/**
	 * @param null|bool|string $value whether the lock table is created on first use.
	 * @return static the current object.
	 */
	public function setAutoCreateMutexTable($value): static
	{
		$this->_autoCreate = ($value === null || $value === '') ? null : TPropertyValue::ensureBoolean($value);
		$this->configureDriverMutex();
		return $this;
	}

	/**
	 * @param string $value the prefix of every name, passed to the routed mutex.
	 * @return static the current object.
	 */
	public function setKeyPrefix($value): static
	{
		parent::setKeyPrefix($value);
		$this->configureDriverMutex();
		return $this;
	}

	/**
	 * @param ?IDataConnection $value the connection of the locks; the routed mutex is chosen again.
	 * @return static the current object.
	 */
	public function setDbConnection(?IDataConnection $value): static
	{
		parent::setDbConnection($value);
		$this->_driverMutex = null;
		return $this;
	}

	/**
	 * @param string $value the ID of the TDataSourceConfig module; the routed mutex is chosen again.
	 */
	public function setConnectionID($value)
	{
		parent::setConnectionID($value);
		$this->_driverMutex = null;
	}

	/**
	 * Routes one attempt to the driver's mutex.  The name passes without the
	 * KeyPrefix, which the routed mutex applies.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		return $this->getDriverMutex()->acquire($name);
	}

	/**
	 * Routes the release to the driver's mutex.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		return $this->getDriverMutex()->release($name);
	}

	/**
	 * Passes the KeyPrefix and the lock table properties to the routed mutex.
	 */
	protected function configureDriverMutex(): void
	{
		$mutex = $this->_driverMutex;
		if ($mutex === null) {
			return;
		}
		$mutex->setKeyPrefix($this->getKeyPrefix());
		if ($mutex instanceof TDbTableMutex) {
			if ($this->_expire !== null) {
				$mutex->setExpire($this->_expire);
			}
			if ($this->_tableName !== null) {
				$mutex->setMutexTableName($this->_tableName);
			}
			if ($this->_autoCreate !== null) {
				$mutex->setAutoCreateMutexTable($this->_autoCreate);
			}
		}
	}

	/**
	 * @return string the SQLite database in the runtime path without a ConnectionID.
	 */
	protected function getSqliteDatabaseName(): ?string
	{
		return 'sqlite3.mutex';
	}
}
