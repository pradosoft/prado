<?php

/**
 * TFileMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TFileMutex class.
 *
 * TFileMutex locks a name with an exclusive {@see flock()} on a lock file in
 * {@see getMutexPath MutexPath}.  It excludes the processes of one host, and of
 * the hosts that share a file system whose locks are reliable.  NFS and other
 * network file systems may not honor `flock()`.
 *
 * The lock file of a name is the MD5 hash of the name with the
 * {@see getKeyPrefix KeyPrefix}.  A release deletes the file.  An attempt that
 * locks a file another holder deleted since it was opened fails and retries on the
 * new file.  On Windows the file is deleted after it is unlocked.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TFileMutex" MutexPath="Application.runtime.mutex" />
 * ```
 *
 * ```php
 * return [
 *     'modules' => [
 *         'mutex' => [
 *             'class' => 'Prado\Util\Mutex\TFileMutex',
 *             'properties' => ['MutexPath' => 'Application.runtime.mutex'],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFileMutex extends TMutex
{
	/** The default mode of a created mutex directory. */
	public const DEFAULT_DIR_MODE = 0o775;

	/** @var ?string the directory of the lock files; null for the runtime `mutex` directory. */
	private ?string $_mutexPath = null;

	/** @var ?int the mode of a lock file; null leaves the umask mode. */
	private ?int $_fileMode = null;

	/** @var int the mode of a created mutex directory. */
	private int $_dirMode = self::DEFAULT_DIR_MODE;

	/** @var array<string, resource> the locked file handles by name. */
	private array $_files = [];

	/**
	 * @return string the directory of the lock files. Defaults to the `mutex` directory in the application runtime path.
	 */
	public function getMutexPath(): string
	{
		if ($this->_mutexPath === null) {
			$this->_mutexPath = $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'mutex';
		}
		return $this->_mutexPath;
	}

	/**
	 * @param string $value the directory of the lock files, as a path or a namespace.  It is created on first use.
	 * @return static the current object.
	 */
	public function setMutexPath($value): static
	{
		$value = TPropertyValue::ensureString($value);
		$this->_mutexPath = Prado::getPathOfNamespace($value) ?? $value;
		return $this;
	}

	/**
	 * @return ?int the mode of a lock file. Default null, the mode the umask leaves.
	 */
	public function getFileMode(): ?int
	{
		return $this->_fileMode;
	}

	/**
	 * @param null|int|string $value the mode of a lock file, such as 0664 or '0664'; null or '' leaves the umask mode.
	 * @return static the current object.
	 */
	public function setFileMode($value): static
	{
		$this->_fileMode = $this->ensureMode($value);
		return $this;
	}

	/**
	 * @return int the mode of a created mutex directory. Default 0775.
	 */
	public function getDirMode(): int
	{
		return $this->_dirMode;
	}

	/**
	 * @param int|string $value the mode of a created mutex directory, such as 0775 or '0775'.
	 * @return static the current object.
	 */
	public function setDirMode($value): static
	{
		$this->_dirMode = $this->ensureMode($value) ?? self::DEFAULT_DIR_MODE;
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return string the path of the lock file of the name.
	 */
	public function getLockFile(string $name): string
	{
		return $this->getMutexPath() . DIRECTORY_SEPARATOR . md5($this->getLockKey($name)) . '.lock';
	}

	/**
	 * Opens the lock file and locks it without blocking.
	 * @param string $name the lock name.
	 * @throws TConfigurationException when the mutex directory cannot be created.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$file = $this->getLockFile($name);
		$this->ensureDirectory(dirname($file));
		$handle = @fopen($file, 'c');
		if ($handle === false) {
			return false;
		}
		if (($mode = $this->getFileMode()) !== null) {
			@chmod($file, $mode);
		}
		if (!flock($handle, LOCK_EX | LOCK_NB)) {
			fclose($handle);
			return false;
		}
		if (!$this->isSameFile($handle, $file)) {
			flock($handle, LOCK_UN);
			fclose($handle);
			return false;
		}
		$this->_files[$name] = $handle;
		return true;
	}

	/**
	 * Deletes the lock file, unlocks it, and closes it.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released.
	 */
	protected function releaseLock(string $name): bool
	{
		$handle = $this->_files[$name] ?? null;
		unset($this->_files[$name]);
		if (!is_resource($handle)) {
			return false;
		}
		$file = $this->getLockFile($name);
		$windows = TProcessHelper::isSystemWindows();
		if (!$windows) {
			@unlink($file);
		}
		flock($handle, LOCK_UN);
		fclose($handle);
		if ($windows) {
			@unlink($file);
		}
		return true;
	}

	/**
	 * Closes the inherited handle in a forked child.  The parent's lock remains, as
	 * the parent's descriptor of the file stays open.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		$handle = $this->_files[$name] ?? null;
		unset($this->_files[$name]);
		if (is_resource($handle)) {
			fclose($handle);
		}
	}

	/**
	 * Checks that a locked handle is still the file at its path.  A holder that
	 * releases deletes the file, so a handle opened before the deletion locks a file
	 * no one else opens.  Windows does not delete an open file, so it always passes.
	 * @param resource $handle the locked handle.
	 * @param string $file the path of the lock file.
	 * @return bool whether the handle is the file at the path.
	 */
	protected function isSameFile($handle, string $file): bool
	{
		if (TProcessHelper::isSystemWindows()) {
			return true;
		}
		clearstatcache(true, $file);
		$stat = fstat($handle);
		return $stat !== false && @fileinode($file) === $stat['ino'];
	}

	/**
	 * Creates the mutex directory when it does not exist.
	 * @param string $dir the mutex directory.
	 * @throws TConfigurationException when the directory cannot be created.
	 */
	protected function ensureDirectory(string $dir): void
	{
		if (is_dir($dir)) {
			return;
		}
		if (!@mkdir($dir, $this->getDirMode(), true) && !is_dir($dir)) {
			throw new TConfigurationException('filemutex_mutexpath_invalid', $dir);
		}
		@chmod($dir, $this->getDirMode());
	}

	/**
	 * Converts an octal mode string or an integer to a mode.
	 * @param mixed $value the mode, such as 0775 or '0775'; null or '' for none.
	 * @return ?int the mode, or null.
	 */
	private function ensureMode(mixed $value): ?int
	{
		if ($value === null || $value === '') {
			return null;
		}
		if (is_string($value)) {
			return (int) octdec($value);
		}
		return TPropertyValue::ensureInteger($value);
	}
}
