<?php

/**
 * TSemaphoreMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TSemaphoreMutex class.
 *
 * TSemaphoreMutex locks a name with a System V semaphore of one permit, through the
 * `sysvsem` extension.  It excludes the processes of one host and needs no file or
 * server.  The extension is not available on Windows; {@see TSyncMutex} is.
 *
 * The semaphore key of a name is 31 bits of the MD5 hash of the name with the
 * {@see getKeyPrefix KeyPrefix}.  Two names with the same key share one lock.  The
 * kernel undoes the hold of a process that exits, so a crashed holder does not keep
 * the lock.  The semaphores remain in the system after release, for reuse.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TSemaphoreMutex" Permissions="0660" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSemaphoreMutex extends TMutex
{
	/** The default permissions of a created semaphore. */
	public const DEFAULT_PERMISSIONS = 0o666;

	/** @var int the permissions of a created semaphore. */
	private int $_permissions = self::DEFAULT_PERMISSIONS;

	/** @var array<string, \SysvSemaphore> the held semaphores by name. */
	private array $_semaphores = [];

	/**
	 * @return bool whether the `sysvsem` extension is loaded.
	 */
	public static function getIsAvailable(): bool
	{
		return function_exists('sem_get');
	}

	/**
	 * Initializes the module.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration.
	 * @throws TConfigurationException when the `sysvsem` extension is not loaded.
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
			throw new TConfigurationException('semaphoremutex_extension_required');
		}
		parent::init($config);
	}

	/**
	 * @return int the permissions of a created semaphore. Default 0666.
	 */
	public function getPermissions(): int
	{
		return $this->_permissions;
	}

	/**
	 * @param int|string $value the permissions of a created semaphore, such as 0660 or '0660'.
	 * @return static the current object.
	 */
	public function setPermissions($value): static
	{
		$this->_permissions = is_string($value) ? (int) octdec($value) : TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return int the System V key of the name's semaphore, from 1 to 2147483647.
	 */
	public function getSemaphoreKey(string $name): int
	{
		$key = unpack('N', md5($this->getLockKey($name), true))[1] & 0x7FFFFFFF;
		return $key === 0 ? 1 : $key;
	}

	/**
	 * Takes the semaphore of the name without blocking.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$semaphore = @sem_get($this->getSemaphoreKey($name), 1, $this->getPermissions(), false);
		if ($semaphore === false) {
			return false;
		}
		if (!sem_acquire($semaphore, true)) {
			return false;
		}
		$this->_semaphores[$name] = $semaphore;
		return true;
	}

	/**
	 * Gives back the semaphore of the name.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		$semaphore = $this->_semaphores[$name] ?? null;
		unset($this->_semaphores[$name]);
		if ($semaphore === null) {
			return false;
		}
		return @sem_release($semaphore);
	}

	/**
	 * Drops the inherited semaphore in a forked child.  The child holds no permit,
	 * so the parent's lock remains.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		unset($this->_semaphores[$name]);
	}
}
