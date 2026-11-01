<?php

/**
 * TDbTableMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;

/**
 * TDbTableMutex class.
 *
 * TDbTableMutex locks a name by inserting its row into a lock table, whose primary
 * key admits one row per name, and releases it by deleting the row that holds this
 * mutex's token.  It works with every PDO driver, SQLite included, and excludes the
 * connections to the database.
 *
 * Without a {@see setConnectionID ConnectionID}, the lock table is in the SQLite
 * database `sqlite3.mutex` of the application runtime path, which excludes the
 * processes of one host.
 *
 * The lock is a lease of {@see getExpire Expire} seconds.  An attempt deletes the
 * expired row of its name before it inserts, so a holder that crashes keeps the
 * lock until it expires, and a holder whose work outlasts it loses it.
 *
 * With {@see getAutoCreateMutexTable AutoCreateMutexTable} true, the default, the
 * table is created on first use:
 * ```sql
 * CREATE TABLE pradomutex (lockname VARCHAR(191) NOT NULL PRIMARY KEY, locktoken CHAR(32) NOT NULL, expire INTEGER NOT NULL)
 * ```
 * A name with the {@see getKeyPrefix KeyPrefix} longer than 191 characters becomes
 * its SHA-1 hash.
 *
 * A failed insert ends the open transaction on PostgreSQL.  Give the mutex a
 * connection of its own when the application uses transactions.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TDbTableMutex" ConnectionID="db" MutexTableName="app_mutex" Expire="60" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDbTableMutex extends TBaseDbMutex
{
	use TApplicationClockAwareTrait;

	/** The default seconds a lock lasts. */
	public const DEFAULT_EXPIRE = 30;

	/** The longest name the lock table stores. */
	public const MAX_NAME_LENGTH = 191;

	/** @var string the name of the lock table. */
	private string $_tableName = 'pradomutex';

	/** @var bool whether the lock table is created when it does not exist. */
	private bool $_autoCreate = true;

	/** @var int the seconds a lock lasts. */
	private int $_expire = self::DEFAULT_EXPIRE;

	/** @var bool whether the lock table is known to exist. */
	private bool $_tableChecked = false;

	/** @var array<string, string> the token of each held name. */
	private array $_tokens = [];

	/**
	 * @return string[] an empty list, as the lock table works with every driver.
	 */
	public static function getSupportedDrivers(): array
	{
		return [];
	}

	/**
	 * @return string the name of the lock table. Default 'pradomutex'.
	 */
	public function getMutexTableName(): string
	{
		return $this->_tableName;
	}

	/**
	 * @param string $value the name of the lock table.
	 * @throws TInvalidDataValueException when the value is not an SQL identifier.
	 * @return static the current object.
	 */
	public function setMutexTableName($value): static
	{
		$this->_tableName = $this->ensureSqlIdentifier(TPropertyValue::ensureString($value), 'MutexTableName');
		$this->_tableChecked = false;
		return $this;
	}

	/**
	 * @return bool whether the lock table is created when it does not exist. Default true.
	 */
	public function getAutoCreateMutexTable(): bool
	{
		return $this->_autoCreate;
	}

	/**
	 * @param bool|string $value whether the lock table is created when it does not exist.
	 * @return static the current object.
	 */
	public function setAutoCreateMutexTable($value): static
	{
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return int the seconds a lock lasts. Default 30.
	 */
	public function getExpire(): int
	{
		return $this->_expire;
	}

	/**
	 * @param int|string $value the seconds a lock lasts.
	 * @throws TInvalidDataValueException when the value is less than 1.
	 * @return static the current object.
	 */
	public function setExpire($value): static
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('mutex_expire_invalid', static::class, $value);
		}
		$this->_expire = $value;
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return string the lock table's name of the name.
	 */
	public function getServerLockName(string $name): string
	{
		return $this->getLimitedLockKey($name, self::MAX_NAME_LENGTH);
	}

	/**
	 * Deletes the expired row of the name, then inserts the name's row with a new token.
	 * @param string $name the lock name.
	 * @return bool whether the insert succeeded.
	 */
	protected function acquireLock(string $name): bool
	{
		$this->ensureMutexTable();
		$connection = $this->getLockConnection();
		$table = $this->getMutexTableName();
		$lockName = $this->getServerLockName($name);
		$now = $this->getClock()->time();

		$command = $connection->createCommand("DELETE FROM {$table} WHERE lockname=:name AND expire<:now");
		$command->bindValue(':name', $lockName);
		$command->bindValue(':now', $now, \PDO::PARAM_INT);
		$command->execute();

		$token = bin2hex(random_bytes(16));
		$command = $connection->createCommand("INSERT INTO {$table} (lockname, locktoken, expire) VALUES (:name, :token, :expire)");
		$command->bindValue(':name', $lockName);
		$command->bindValue(':token', $token);
		$command->bindValue(':expire', $now + $this->getExpire(), \PDO::PARAM_INT);
		try {
			$command->execute();
		} catch (\Exception $e) {
			return false;
		}
		$this->_tokens[$name] = $token;
		return true;
	}

	/**
	 * Deletes the row of the name that holds this mutex's token.
	 * @param string $name the lock name.
	 * @return bool whether the row was deleted; false when the lease expired and another holder took it.
	 */
	protected function releaseLock(string $name): bool
	{
		$token = $this->_tokens[$name] ?? null;
		unset($this->_tokens[$name]);
		if ($token === null) {
			return false;
		}
		$command = $this->getLockConnection()->createCommand("DELETE FROM {$this->getMutexTableName()} WHERE lockname=:name AND locktoken=:token");
		$command->bindValue(':name', $this->getServerLockName($name));
		$command->bindValue(':token', $token);
		return $command->execute() > 0;
	}

	/**
	 * Drops the token in a forked child, leaving the parent's row.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		unset($this->_tokens[$name]);
	}

	/**
	 * Checks that the lock table exists, creating it when
	 * {@see getAutoCreateMutexTable AutoCreateMutexTable} is true.
	 * @throws \Exception when the table does not exist and is not created.
	 */
	protected function ensureMutexTable(): void
	{
		if ($this->_tableChecked) {
			return;
		}
		$connection = $this->getLockConnection();
		$table = $this->getMutexTableName();
		try {
			$connection->createCommand("SELECT 1 FROM {$table} WHERE 0=1")->queryScalar();
		} catch (\Exception $e) {
			if (!$this->getAutoCreateMutexTable()) {
				throw $e;
			}
			Prado::trace('Autocreate: ' . $table, static::class);
			try {
				$connection->createCommand("CREATE TABLE {$table} (lockname VARCHAR(191) NOT NULL PRIMARY KEY, locktoken CHAR(32) NOT NULL, expire INTEGER NOT NULL)")->execute();
			} catch (\Exception $createError) {
				$connection->createCommand("SELECT 1 FROM {$table} WHERE 0=1")->queryScalar();
			}
		}
		$this->_tableChecked = true;
	}

	/**
	 * @return string the SQLite database in the runtime path without a ConnectionID.
	 */
	protected function getSqliteDatabaseName(): ?string
	{
		return 'sqlite3.mutex';
	}
}
