<?php

/**
 * IMutex interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

/**
 * IMutex interface.
 *
 * IMutex is a named lock that excludes other holders of the same name.  Only one
 * holder at a time acquires a name; a holder releases the names it acquired.  The
 * holders are the processes, servers, or connections that the implementation's
 * backend separates.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IMutex
{
	/**
	 * Acquires the lock of a name.
	 * @param string $name the lock name.
	 * @param float|int $timeout the seconds to wait for the lock; 0 tries once.
	 * @return bool whether the lock is acquired.
	 */
	public function acquire(string $name, float|int $timeout = 0): bool;

	/**
	 * Releases the lock of a name that this mutex acquired.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released; false when this mutex does not hold it.
	 */
	public function release(string $name): bool;

	/**
	 * @param string $name the lock name.
	 * @return bool whether this mutex holds the lock of the name.
	 */
	public function isAcquired(string $name): bool;
}
