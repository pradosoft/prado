<?php

/**
 * TMemoryMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

/**
 * TMemoryMutex class.
 *
 * TMemoryMutex locks a name in the memory of the current process.  Every
 * TMemoryMutex of a process shares one table of held names, so it excludes the
 * other mutexes and fibers of the process.  It does not exclude other processes.
 *
 * TMemoryMutex serves fibers that share a resource within one process, and tests
 * of code that takes an {@see IMutex}.  A forked child starts with the names its
 * parent held released.
 *
 * ```php
 * $mutex = new TMemoryMutex();
 * $mutex->synchronize('cache-rebuild', fn () => rebuild(), 2);
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMemoryMutex extends TMutex
{
	/** @var array<string, true> the names held in this process. */
	private static array $_held = [];

	/**
	 * Marks the name held when no mutex of the process holds it.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$key = $this->getLockKey($name);
		if (isset(self::$_held[$key])) {
			return false;
		}
		self::$_held[$key] = true;
		return true;
	}

	/**
	 * Removes the name from the held names of the process.
	 * @param string $name the lock name.
	 * @return bool whether the lock was held.
	 */
	protected function releaseLock(string $name): bool
	{
		$key = $this->getLockKey($name);
		if (!isset(self::$_held[$key])) {
			return false;
		}
		unset(self::$_held[$key]);
		return true;
	}

	/**
	 * Removes the name from the child's copy of the held names.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		unset(self::$_held[$this->getLockKey($name)]);
	}
}
