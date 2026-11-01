<?php

/**
 * TPgsqlMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Data\TDbDriver;

/**
 * TPgsqlMutex class.
 *
 * TPgsqlMutex locks a name with a PostgreSQL session advisory lock,
 * `pg_try_advisory_lock(key1, key2)`, and releases it with `pg_advisory_unlock()`.
 * The lock belongs to the connection's session, as {@see TDbMutex} describes.
 *
 * The two `int4` keys of a name are the first 64 bits of the SHA-1 hash of the name
 * with the {@see getKeyPrefix KeyPrefix}.  The two-key form does not collide with
 * single `bigint` advisory locks.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TPgsqlMutex" ConnectionID="db" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPgsqlMutex extends TDbMutex
{
	/**
	 * @return string[] the PostgreSQL driver.
	 */
	public function getSupportedDrivers(): array
	{
		return [TDbDriver::DRIVER_PGSQL];
	}

	/**
	 * @param string $name the lock name.
	 * @return array{0: int, 1: int} the two signed 32-bit advisory lock keys of the name.
	 */
	public function getAdvisoryKeys(string $name): array
	{
		$words = unpack('N2', sha1($this->getLockKey($name), true));
		return [self::toSigned32($words[1]), self::toSigned32($words[2])];
	}

	/**
	 * Tries the advisory lock without waiting.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		return $this->queryLock('SELECT pg_try_advisory_lock(:key1, :key2)', $name);
	}

	/**
	 * Releases the advisory lock.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		return $this->queryLock('SELECT pg_advisory_unlock(:key1, :key2)', $name);
	}

	/**
	 * Runs a boolean advisory lock function with the keys of a name.
	 * @param string $sql the query, with `:key1` and `:key2`.
	 * @param string $name the lock name.
	 * @return bool the boolean result.
	 */
	private function queryLock(string $sql, string $name): bool
	{
		[$key1, $key2] = $this->getAdvisoryKeys($name);
		$command = $this->getLockConnection()->createCommand($sql);
		$command->bindValue(':key1', $key1, \PDO::PARAM_INT);
		$command->bindValue(':key2', $key2, \PDO::PARAM_INT);
		$result = $command->queryScalar();
		return $result === true || $result === 't' || $result === 1 || $result === '1';
	}

	/**
	 * @param int $value an unsigned 32-bit integer.
	 * @return int the value as a signed 32-bit integer.
	 */
	private static function toSigned32(int $value): int
	{
		return $value > 0x7FFFFFFF ? $value - 0x100000000 : $value;
	}
}
