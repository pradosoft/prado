<?php

/**
 * TBaseDbMutex class file
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
 * TBaseDbMutex class.
 *
 * TBaseDbMutex is the base of the mutexes that lock in a database.  The
 * {@see setConnectionID ConnectionID} names the {@see \Prado\Data\TDataSourceConfig}
 * of the connection, or {@see setDbConnection()} sets the connection directly.  The
 * connection's driver must be one of {@see getSupportedDrivers()}; an empty list
 * accepts any driver.  {@see TDbMutex} chooses the subclass for a connection.
 *
 * {@see TMysqlMutex}, {@see TPgsqlMutex}, {@see TOracleMutex}, and
 * {@see TMssqlMutex} take a lock of the server's session.  The server releases it
 * when the connection closes, so a crashed process does not keep it.  Mutexes that
 * share one connection share one session, and the server does not exclude them
 * from each other; give each holder a mutex of its own connection where that
 * matters.  Closing the connection releases every lock of its session.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseDbMutex extends TMutex implements IDbModule
{
	use TDbPropertiesTrait {
		getDbConnection as private getConfiguredDbConnection;
	}

	/** @var ?IDataConnection the connection set directly; null uses ConnectionID. */
	private ?IDataConnection $_lockConnection = null;

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
	abstract public static function getSupportedDrivers(): array;

	/**
	 * @param string $driver a PDO driver name.
	 * @return bool whether this mutex supports the driver.
	 */
	public static function isDriverSupported(string $driver): bool
	{
		$drivers = static::getSupportedDrivers();
		return $drivers === [] || in_array(strtolower($driver), $drivers, true);
	}

	/**
	 * Returns the connection set by {@see setDbConnection()}, activated, or else the
	 * connection of {@see getConnectionID ConnectionID}.
	 * @return IDataConnection the database connection.
	 */
	public function getDbConnection(): IDataConnection
	{
		if ($this->_lockConnection !== null) {
			if (!$this->_lockConnection->getActive()) {
				$this->_lockConnection->setActive(true);
			}
			return $this->_lockConnection;
		}
		return $this->getConfiguredDbConnection();
	}

	/**
	 * @param ?IDataConnection $value the connection of the locks; null uses {@see getConnectionID ConnectionID}.
	 * @return static the current object.
	 */
	public function setDbConnection(?IDataConnection $value): static
	{
		$this->_lockConnection = $value;
		$this->_driverChecked = false;
		return $this;
	}

	/**
	 * Returns the active connection, checking its driver on first use.
	 * @throws TConfigurationException when the driver is not supported.
	 * @return IDataConnection the active connection.
	 */
	protected function getLockConnection(): IDataConnection
	{
		$connection = $this->getDbConnection();
		if (!$this->_driverChecked) {
			$driver = (string) $connection->getDriverName();
			if (!static::isDriverSupported($driver)) {
				throw new TConfigurationException('dbmutex_driver_unsupported', static::class, $driver, implode(', ', static::getSupportedDrivers()));
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
