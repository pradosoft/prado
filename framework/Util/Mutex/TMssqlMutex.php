<?php

/**
 * TMssqlMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Data\TDbDriver;

/**
 * TMssqlMutex class.
 *
 * TMssqlMutex locks a name with SQL Server `sp_getapplock` in `Exclusive` mode,
 * owned by the `Session`, and releases it with `sp_releaseapplock`.  The lock
 * belongs to the connection's session, as {@see TBaseDbMutex} describes.  A name with
 * the {@see getKeyPrefix KeyPrefix} longer than 255 characters, the server's limit,
 * becomes its SHA-1 hash.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TMssqlMutex" ConnectionID="db" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMssqlMutex extends TBaseDbMutex
{
	/** The longest resource name the server accepts. */
	public const MAX_NAME_LENGTH = 255;

	/**
	 * @return string[] the SQL Server drivers.
	 */
	public static function getSupportedDrivers(): array
	{
		return [TDbDriver::DRIVER_SQLSRV, TDbDriver::DRIVER_DBLIB, 'mssql'];
	}

	/**
	 * @param string $name the lock name.
	 * @return string the server resource name of the name.
	 */
	public function getServerLockName(string $name): string
	{
		return $this->getLimitedLockKey($name, self::MAX_NAME_LENGTH);
	}

	/**
	 * Requests the application lock with a zero timeout.  A result of 0 or more is a grant.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$sql = 'SET NOCOUNT ON; DECLARE @result INT; '
			. "EXEC @result = sp_getapplock @Resource = :name, @LockMode = 'Exclusive', @LockOwner = 'Session', @LockTimeout = 0; "
			. 'SELECT @result';
		return $this->queryResult($sql, $name) >= 0;
	}

	/**
	 * Releases the application lock.  A result of 0 is a release.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		$sql = 'SET NOCOUNT ON; DECLARE @result INT; '
			. "EXEC @result = sp_releaseapplock @Resource = :name, @LockOwner = 'Session'; "
			. 'SELECT @result';
		return $this->queryResult($sql, $name) === 0;
	}

	/**
	 * Runs an application lock batch for a name.
	 * @param string $sql the batch, with `:name`.
	 * @param string $name the lock name.
	 * @return int the procedure's return code.
	 */
	private function queryResult(string $sql, string $name): int
	{
		$command = $this->getLockConnection()->createCommand($sql);
		$command->bindValue(':name', $this->getServerLockName($name));
		return (int) $command->queryScalar();
	}
}
