<?php

/**
 * TMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\Prado;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TMutex class.
 *
 * TMutex is the base of the named lock modules.  It keeps the names this mutex
 * holds, retries an attempt until the timeout, and releases the held names at the
 * end of the process.  A subclass implements one non-blocking attempt in
 * {@see acquireLock()} and the release in {@see releaseLock()}.
 *
 * | Class | Backend | Excludes |
 * |---|---|---|
 * | {@see TFileMutex} | `flock()` on a lock file | processes on one host or a shared file system |
 * | {@see TSemaphoreMutex} | System V semaphore | processes on one host |
 * | {@see TMemoryMutex} | process memory | fibers and mutexes of one process |
 * | {@see TCacheMutex} | {@see \Prado\Caching\ICache::add()} | clients of the cache |
 * | {@see TRedisMutex} | Redis `SET NX PX` | clients of the Redis server |
 * | {@see TMysqlMutex} | MySQL `GET_LOCK()` | connections to the server |
 * | {@see TPgsqlMutex} | PostgreSQL advisory lock | connections to the server |
 * | {@see TOracleMutex} | Oracle `DBMS_LOCK` | connections to the server |
 * | {@see TMssqlMutex} | SQL Server `sp_getapplock` | connections to the server |
 * | {@see TDbTableMutex} | a row in a lock table | connections to the database |
 *
 * {@see acquire()} tries once and then waits {@see getRetryDelay RetryDelay}
 * milliseconds between attempts until `$timeout` seconds elapse.  The wait is
 * {@see \Prado\IO\Async\TAsync::sleep()}, so a fiber yields to the event loop
 * while it waits.  A mutex does not re-enter: acquiring a name it holds waits
 * like any other contended attempt, so another fiber that shares the mutex
 * acquires the name after its holder releases it.
 *
 * {@see synchronize()} runs a callable while holding a name and releases it after.
 *
 * ```php
 * $mutex = Prado::getApplication()->getModule('mutex');
 * if ($mutex->acquire('report', 5)) {
 *     try {
 *         buildReport();
 *     } finally {
 *         $mutex->release('report');
 *     }
 * }
 * $total = $mutex->synchronize('ledger', fn () => postLedger(), 5);
 * ```
 *
 * With {@see getAutoRelease AutoRelease} true, the default, the held names are
 * released on destruction and at process shutdown.  An auto release that throws is
 * logged as a warning.  A child that
 * {@see \Prado\Util\Helpers\TProcessHelper::fork()} creates forgets the names
 * the parent holds on `fxRestoreAfterFork`, without releasing them, so the
 * parent keeps its locks.
 *
 * {@see getKeyPrefix KeyPrefix} prepends to every name, separating applications
 * that share one backend.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TMutex extends TModule implements IMutex
{
	/** The default milliseconds between attempts of {@see acquire()}. */
	public const DEFAULT_RETRY_DELAY = 50;

	/** @var array<string, true> the names this mutex holds. */
	private array $_locks = [];

	/** @var bool whether the held names are released on destruction and shutdown. */
	private bool $_autoRelease = true;

	/** @var int the milliseconds between attempts. */
	private int $_retryDelay = self::DEFAULT_RETRY_DELAY;

	/** @var string the prefix of every name. */
	private string $_keyPrefix = '';

	/** @var bool whether the shutdown release is registered. */
	private bool $_shutdownRegistered = false;

	/**
	 * Releases the held names when {@see getAutoRelease AutoRelease} is true.
	 */
	public function __destruct()
	{
		if ($this->getAutoRelease()) {
			$this->autoReleaseAll();
		}
		parent::__destruct();
	}

	/**
	 * Acquires the lock of a name, retrying every {@see getRetryDelay RetryDelay}
	 * milliseconds until the timeout.
	 * @param string $name the lock name.
	 * @param float|int $timeout the seconds to wait for the lock; 0 tries once.
	 * @return bool whether the lock is acquired.
	 */
	public function acquire(string $name, float|int $timeout = 0): bool
	{
		$deadline = microtime(true) + max(0, $timeout);
		while (true) {
			if (!$this->isAcquired($name) && $this->acquireLock($name)) {
				$this->_locks[$name] = true;
				$this->registerShutdownRelease();
				return true;
			}
			$remaining = $deadline - microtime(true);
			if ($remaining <= 0) {
				return false;
			}
			TAsync::sleep(min($remaining, $this->getRetryDelay() / 1000));
		}
	}

	/**
	 * Releases the lock of a name that this mutex acquired.  The name is no longer
	 * held after the call, even when the backend reports a failure.
	 * @param string $name the lock name.
	 * @return bool whether the backend released the lock; false when this mutex does not hold it.
	 */
	public function release(string $name): bool
	{
		if (!$this->isAcquired($name)) {
			return false;
		}
		unset($this->_locks[$name]);
		return $this->releaseLock($name);
	}

	/**
	 * @param string $name the lock name.
	 * @return bool whether this mutex holds the lock of the name.
	 */
	public function isAcquired(string $name): bool
	{
		return isset($this->_locks[$name]);
	}

	/**
	 * @return string[] the names this mutex holds, in the order acquired.
	 */
	public function getAcquiredLocks(): array
	{
		return array_map('strval', array_keys($this->_locks));
	}

	/**
	 * Releases every name this mutex holds.
	 */
	public function releaseAll(): void
	{
		foreach ($this->getAcquiredLocks() as $name) {
			$this->release($name);
		}
	}

	/**
	 * Runs a callable while holding the lock of a name, and releases the lock after.
	 * @param string $name the lock name.
	 * @param callable $callback the callable to run; it receives this mutex.
	 * @param float|int $timeout the seconds to wait for the lock; 0 tries once.
	 * @throws TMutexException when the lock is not acquired within the timeout.
	 * @return mixed the result of the callable.
	 */
	public function synchronize(string $name, callable $callback, float|int $timeout = 0): mixed
	{
		if (!$this->acquire($name, $timeout)) {
			throw new TMutexException('mutex_acquire_failed', static::class, $name, $timeout);
		}
		try {
			return $callback($this);
		} finally {
			$this->release($name);
		}
	}

	/**
	 * Forgets the names held before a fork, in the child, without releasing them.
	 * Raised as `fxRestoreAfterFork` by {@see \Prado\Util\Helpers\TProcessHelper::fork()}.
	 * @param mixed $sender the application.
	 * @param mixed $param the fork data, with the fork result in 'pid'.
	 */
	public function fxRestoreAfterFork($sender, $param)
	{
		if (($param['pid'] ?? null) !== 0) {
			return;
		}
		foreach ($this->getAcquiredLocks() as $name) {
			$this->forgetLock($name);
		}
		$this->_locks = [];
	}

	/**
	 * @return bool whether the held names are released on destruction and at process shutdown. Default true.
	 */
	public function getAutoRelease(): bool
	{
		return $this->_autoRelease;
	}

	/**
	 * @param bool|string $value whether the held names are released on destruction and at process shutdown.
	 * @return static the current object.
	 */
	public function setAutoRelease($value): static
	{
		$this->_autoRelease = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return int the milliseconds between attempts of {@see acquire()}. Default 50.
	 */
	public function getRetryDelay(): int
	{
		return $this->_retryDelay;
	}

	/**
	 * @param int|string $value the milliseconds between attempts of {@see acquire()}.
	 * @throws TInvalidDataValueException when the value is less than 1.
	 * @return static the current object.
	 */
	public function setRetryDelay($value): static
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('mutex_retrydelay_invalid', static::class, $value);
		}
		$this->_retryDelay = $value;
		return $this;
	}

	/**
	 * @return string the prefix of every name. Default ''.
	 */
	public function getKeyPrefix(): string
	{
		return $this->_keyPrefix;
	}

	/**
	 * @param string $value the prefix of every name.
	 * @return static the current object.
	 */
	public function setKeyPrefix($value): static
	{
		$this->_keyPrefix = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return string the name with the {@see getKeyPrefix KeyPrefix}, as the backend stores it.
	 */
	protected function getLockKey(string $name): string
	{
		return $this->getKeyPrefix() . $name;
	}

	/**
	 * Makes one attempt to acquire the backend lock of a name, without waiting.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	abstract protected function acquireLock(string $name): bool;

	/**
	 * Releases the backend lock of a name.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	abstract protected function releaseLock(string $name): bool;

	/**
	 * Drops the local state of a name held before a fork, in the child, leaving the
	 * parent's lock in place.  The base implementation does nothing.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
	}

	/**
	 * Registers the release of the held names at process shutdown, once.  The
	 * registration holds this mutex weakly, so it does not delay destruction.
	 */
	private function registerShutdownRelease(): void
	{
		if ($this->_shutdownRegistered || !$this->getAutoRelease()) {
			return;
		}
		$this->_shutdownRegistered = true;
		$ref = \WeakReference::create($this);
		register_shutdown_function(static function () use ($ref): void {
			$mutex = $ref->get();
			if ($mutex !== null && $mutex->getAutoRelease()) {
				$mutex->autoReleaseAll();
			}
		});
	}

	/**
	 * Releases every held name on destruction or at shutdown.  A release that throws
	 * is logged as a warning, and the other names are still released.
	 */
	private function autoReleaseAll(): void
	{
		foreach ($this->getAcquiredLocks() as $name) {
			try {
				$this->release($name);
			} catch (\Throwable $e) {
				Prado::warning("Auto release of lock '{$name}' failed: " . $e->getMessage(), static::class);
			}
		}
	}
}
