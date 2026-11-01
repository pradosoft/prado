<?php

/**
 * TSyncMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TSyncMutex class.
 *
 * TSyncMutex locks a name with a named `SyncSemaphore` of one permit from the PECL
 * `sync` extension.  It excludes the processes of one host on Windows and on POSIX
 * systems; {@see TSemaphoreMutex} has no Windows build.
 *
 * The semaphore is created with auto-unlock off.  A `SyncMutex` unlocks when its
 * object is freed, so a forked child would release its parent's lock at exit; the
 * semaphore does not.  {@see getAutoRelease AutoRelease} releases the held names at
 * shutdown, after a fatal error included.  A process that is killed while it holds
 * a lock, without PHP's shutdown, leaves the permit taken: on POSIX until the
 * `/dev/shm/Sync_Semaphore*` object is removed or the host restarts, and on Windows
 * while any process keeps the semaphore open.  {@see TFileMutex} and
 * {@see TSemaphoreMutex} recover from a killed holder.
 *
 * The semaphore name of a lock is `Prado_Mutex_` and the MD5 hash of the name with
 * the {@see getKeyPrefix KeyPrefix}.  With {@see getGlobalNamespace GlobalNamespace}
 * true, a Windows name is in the `Global\` namespace, which spans the sessions of
 * services and users.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TSyncMutex" GlobalNamespace="true" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSyncMutex extends TMutex
{
	/** The prefix of every semaphore name. */
	public const NAME_PREFIX = 'Prado_Mutex_';

	/** @var bool whether a Windows semaphore name is in the Global namespace. */
	private bool $_globalNamespace = false;

	/** @var array<string, object> the held `SyncSemaphore`s by name. */
	private array $_semaphores = [];

	/**
	 * @return bool whether the `sync` extension is loaded.
	 */
	public static function getIsAvailable(): bool
	{
		return class_exists('\SyncSemaphore', false);
	}

	/**
	 * Initializes the module.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration.
	 * @throws TConfigurationException when the `sync` extension is not loaded.
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
			throw new TConfigurationException('syncmutex_extension_required');
		}
		parent::init($config);
	}

	/**
	 * @return bool whether a Windows semaphore name is in the `Global\` namespace. Default false.
	 */
	public function getGlobalNamespace(): bool
	{
		return $this->_globalNamespace;
	}

	/**
	 * @param bool|string $value whether a Windows semaphore name is in the `Global\` namespace.
	 * @return static the current object.
	 */
	public function setGlobalNamespace($value): static
	{
		$this->_globalNamespace = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return string the semaphore name of the lock.
	 */
	public function getSemaphoreName(string $name): string
	{
		$semaphoreName = self::NAME_PREFIX . md5($this->getLockKey($name));
		if ($this->getGlobalNamespace() && TProcessHelper::isSystemWindows()) {
			$semaphoreName = 'Global\\' . $semaphoreName;
		}
		return $semaphoreName;
	}

	/**
	 * Takes the permit of the name's semaphore without waiting.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$semaphore = $this->newSemaphore($this->getSemaphoreName($name));
		if (!$semaphore->lock(0)) {
			return false;
		}
		$this->_semaphores[$name] = $semaphore;
		return true;
	}

	/**
	 * Gives back the permit of the name's semaphore.
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
		return $semaphore->unlock();
	}

	/**
	 * Drops the inherited semaphore in a forked child.  Without auto-unlock, freeing it
	 * leaves the parent's permit taken.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		unset($this->_semaphores[$name]);
	}

	/**
	 * Opens the named semaphore of one permit, without auto-unlock.  Override in a
	 * subclass to substitute another implementation.
	 * @param string $semaphoreName the semaphore name.
	 * @return object the `SyncSemaphore`.
	 */
	protected function newSemaphore(string $semaphoreName): object
	{
		return new \SyncSemaphore($semaphoreName, 1, false);
	}
}
