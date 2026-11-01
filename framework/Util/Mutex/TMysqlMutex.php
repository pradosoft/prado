<?php

/**
 * TMysqlMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Data\TDbDriver;

/**
 * TMysqlMutex class.
 *
 * TMysqlMutex locks a name with MySQL or MariaDB `GET_LOCK()` and releases it with
 * `RELEASE_LOCK()`.  The lock belongs to the connection's session, as
 * {@see TDbMutex} describes.  A name with the {@see getKeyPrefix KeyPrefix} longer
 * than 64 characters, the server's limit, becomes its SHA-1 hash.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TMysqlMutex" ConnectionID="db" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMysqlMutex extends TDbMutex
{
	/** The longest lock name the server accepts. */
	public const MAX_NAME_LENGTH = 64;

	/**
	 * @return string[] the MySQL driver.
	 */
	public function getSupportedDrivers(): array
	{
		return [TDbDriver::DRIVER_MYSQL];
	}

	/**
	 * @param string $name the lock name.
	 * @return string the server lock name of the name.
	 */
	public function getServerLockName(string $name): string
	{
		return $this->getLimitedLockKey($name, self::MAX_NAME_LENGTH);
	}

	/**
	 * Requests the server lock with a zero timeout.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$command = $this->getLockConnection()->createCommand('SELECT GET_LOCK(:name, 0)');
		$command->bindValue(':name', $this->getServerLockName($name));
		return (int) $command->queryScalar() === 1;
	}

	/**
	 * Releases the server lock.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		$command = $this->getLockConnection()->createCommand('SELECT RELEASE_LOCK(:name)');
		$command->bindValue(':name', $this->getServerLockName($name));
		return (int) $command->queryScalar() === 1;
	}
}
