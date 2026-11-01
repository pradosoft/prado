<?php

/**
 * TOracleMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Data\TDbDriver;
use Prado\TPropertyValue;

/**
 * TOracleMutex class.
 *
 * TOracleMutex locks a name with Oracle `DBMS_LOCK.REQUEST` and releases it with
 * `DBMS_LOCK.RELEASE`.  The lock belongs to the connection's session, as
 * {@see TDbMutex} describes.  The user needs `EXECUTE` on `DBMS_LOCK`.
 *
 * `DBMS_LOCK.ALLOCATE_UNIQUE` maps the name to a lock handle and commits the
 * session's transaction, so an attempt or a release ends an open transaction of
 * the connection.  Give the mutex a connection of its own when the application uses
 * transactions.  A name with the {@see getKeyPrefix KeyPrefix} longer than 128
 * characters, the server's limit, becomes its SHA-1 hash.
 *
 * | Property | Default | Meaning |
 * |---|---|---|
 * | {@see setLockMode LockMode} | `X_MODE` | the `DBMS_LOCK` mode: `NL_MODE`, `SS_MODE`, `SX_MODE`, `S_MODE`, `SSX_MODE`, or `X_MODE` |
 * | {@see setReleaseOnCommit ReleaseOnCommit} | false | whether a commit releases the lock |
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TOracleMutex" ConnectionID="db" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TOracleMutex extends TDbMutex
{
	/** The longest lock name the server accepts. */
	public const MAX_NAME_LENGTH = 128;

	/** The `DBMS_LOCK` modes. */
	public const LOCK_MODES = ['NL_MODE', 'SS_MODE', 'SX_MODE', 'S_MODE', 'SSX_MODE', 'X_MODE'];

	/** @var string the `DBMS_LOCK` mode. */
	private string $_lockMode = 'X_MODE';

	/** @var bool whether a commit releases the lock. */
	private bool $_releaseOnCommit = false;

	/**
	 * @return string[] the Oracle driver.
	 */
	public function getSupportedDrivers(): array
	{
		return [TDbDriver::DRIVER_OCI];
	}

	/**
	 * @return string the `DBMS_LOCK` mode. Default 'X_MODE', exclusive.
	 */
	public function getLockMode(): string
	{
		return $this->_lockMode;
	}

	/**
	 * @param string $value the `DBMS_LOCK` mode, one of {@see LOCK_MODES}.
	 * @throws \Prado\Exceptions\TInvalidDataValueException when the mode is not one of {@see LOCK_MODES}.
	 * @return static the current object.
	 */
	public function setLockMode($value): static
	{
		$this->_lockMode = TPropertyValue::ensureEnum(strtoupper(TPropertyValue::ensureString($value)), self::LOCK_MODES);
		return $this;
	}

	/**
	 * @return bool whether a commit releases the lock. Default false.
	 */
	public function getReleaseOnCommit(): bool
	{
		return $this->_releaseOnCommit;
	}

	/**
	 * @param bool|string $value whether a commit releases the lock.
	 * @return static the current object.
	 */
	public function setReleaseOnCommit($value): static
	{
		$this->_releaseOnCommit = TPropertyValue::ensureBoolean($value);
		return $this;
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
	 * Requests the lock with a zero timeout.  A status of 0 is a grant.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$sql = 'DECLARE handle VARCHAR2(128); BEGIN DBMS_LOCK.ALLOCATE_UNIQUE(:name, handle); '
			. ':status := DBMS_LOCK.REQUEST(handle, DBMS_LOCK.' . $this->getLockMode() . ', 0, '
			. ($this->getReleaseOnCommit() ? 'TRUE' : 'FALSE') . '); END;';
		return $this->executeStatus($sql, $name) === 0;
	}

	/**
	 * Releases the lock.  A status of 0 is a release.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		$sql = 'DECLARE handle VARCHAR2(128); BEGIN DBMS_LOCK.ALLOCATE_UNIQUE(:name, handle); '
			. ':status := DBMS_LOCK.RELEASE(handle); END;';
		return $this->executeStatus($sql, $name) === 0;
	}

	/**
	 * Runs a `DBMS_LOCK` block for a name and reads its status.
	 * @param string $sql the block, with `:name` and the output `:status`.
	 * @param string $name the lock name.
	 * @return int the status.
	 */
	private function executeStatus(string $sql, string $name): int
	{
		$status = -1;
		$command = $this->getLockConnection()->createCommand($sql);
		$command->bindValue(':name', $this->getServerLockName($name));
		$command->bindParameter(':status', $status, \PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT, 10);
		$command->execute();
		return (int) $status;
	}
}
