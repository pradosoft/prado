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
use Prado\Data\TDbPropertiesTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Util\IDbModule;

/**
 * TDbMutex class.
 *
 * TDbMutex is the base of the mutexes that lock in a database.  The
 * {@see setConnectionID ConnectionID} names the {@see \Prado\Data\TDataSourceConfig}
 * of the connection.  The connection's driver must be one of
 * {@see getSupportedDrivers()}; an empty list accepts any driver.
 *
 * {@see TMysqlMutex}, {@see TPgsqlMutex}, {@see TOracleMutex}, and
 * {@see TMssqlMutex} take a lock of the server's session.  The server releases it
 * when the connection closes, so a crashed process does not keep it.  Mutexes that
 * share one connection share one session, and the server does not exclude them
 * from each other; give each holder a mutex of its own connection where that
 * matters.  Closing the connection releases every lock of its session.
 *
 * ```xml
 * <module id="db" class="Prado\Data\TDataSourceConfig">
 *   <database ConnectionString="mysql:host=localhost;dbname=app" Username="app" Password="secret" />
 * </module>
 * <module id="mutex" class="Prado\Util\Mutex\TMysqlMutex" ConnectionID="db" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TDbMutex extends TMutex implements IDbModule
{
	use TDbPropertiesTrait;

	/** @var bool whether the connection's driver is checked. */
	private bool $_driverChecked = false;

	/**
	 * Releases the held names when {@see getAutoRelease AutoRelease} is true.  The
	 * connection stays as it is, since other holders may share it.
	 */
	public function __destruct()
	{
		parent::__destruct();
	}

	/**
	 * @return string[] the PDO driver names this mutex supports; empty for any driver.
	 */
	abstract public function getSupportedDrivers(): array;

	/**
	 * Returns the active connection, checking its driver on first use.
	 * @throws TConfigurationException when the driver is not supported.
	 * @return IDataConnection the active connection.
	 */
	protected function getLockConnection(): IDataConnection
	{
		$connection = $this->getDbConnection();
		if (!$this->_driverChecked) {
			$drivers = $this->getSupportedDrivers();
			$driver = $connection->getDriverName();
			if ($drivers !== [] && !in_array($driver, $drivers, true)) {
				throw new TConfigurationException('dbmutex_driver_unsupported', static::class, $driver, implode(', ', $drivers));
			}
			$this->_driverChecked = true;
		}
		return $connection;
	}

	/**
	 * Shortens a lock name to the server's limit with its SHA-1 hash.
	 * @param string $name the lock name.
	 * @param int $maxLength the longest name the server accepts.
	 * @return string the name with the {@see getKeyPrefix KeyPrefix}, or its SHA-1 hash when longer than the limit.
	 */
	protected function getLimitedLockKey(string $name, int $maxLength): string
	{
		$key = $this->getLockKey($name);
		return strlen($key) > $maxLength ? sha1($key) : $key;
	}

	/**
	 * @return ?bool activates the connection on every retrieval.
	 */
	protected function getDbConnectionActivationType(): ?bool
	{
		return true;
	}
}
