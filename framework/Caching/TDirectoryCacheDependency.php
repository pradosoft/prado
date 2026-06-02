<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;
use Prado\TPropertyValue;

/**
 * TDirectoryCacheDependency class
 *
 * TDirectoryCacheDependency reports a cache-dependency change when the
 * modification time of any file under the directory specified via
 * {@see setDirectory Directory} differs from the snapshot taken when the
 * dependency was created, or when the number of files in the directory has
 * changed.
 *
 * By default all files under the specified directory and its subdirectories
 * are checked. Set {@see setRecursiveCheck RecursiveCheck} to `false` to
 * limit checking to the top level, or set {@see setRecursiveLevel RecursiveLevel}
 * to cap the depth of subdirectory traversal.
 *
 * Override {@see validateFile()} or {@see validateDirectory()} in a subclass
 * to restrict which files or subdirectories are included in the check.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TDirectoryCacheDependency extends TCacheDependency
{
	/** @var ?string resolved absolute path of the tracked directory */
	private ?string $_directory = null;
	/** @var array<string,int> map of absolute file path to last recorded mtime */
	private array $_timestamps = [];
	/** @var bool whether subdirectories are included in the dependency check */
	private bool $_recursiveCheck = true;
	/** @var int maximum subdirectory depth to traverse; -1 means unlimited */
	private int $_recursiveLevel = -1;

	/**
	 * @param string $directory path to the directory to be tracked.
	 */
	public function __construct(string $directory)
	{
		$this->setDirectory($directory);
		parent::__construct();
	}

	/**
	 * Returns the stored directory path without validation.
	 * @return ?string the resolved directory path, or `null` before initialization.
	 * @since 4.4.0
	 */
	protected function getDirectoryDirect(): ?string
	{
		return $this->_directory;
	}

	/**
	 * Stores the resolved directory path without re-scanning.
	 * @param ?string $value the resolved directory path.
	 * @since 4.4.0
	 */
	protected function setDirectoryDirect(?string $value): void
	{
		$this->_directory = $value;
	}

	/**
	 * @return string the resolved absolute path of the tracked directory.
	 */
	public function getDirectory(): string
	{
		return $this->_directory ?? '';
	}

	/**
	 * Sets the directory to track and records a fresh snapshot of file mtimes.
	 * @param string $directory path to the directory.
	 * @throws TInvalidDataValueException if the path does not exist or is not a directory.
	 */
	public function setDirectory(string $directory): void
	{
		if (($path = realpath($directory)) === false || !is_dir($path)) {
			throw new TInvalidDataValueException('directorycachedependency_directory_invalid', $directory);
		}
		$this->setDirectoryDirect($path);
		$this->_timestamps = $this->generateTimestamps($path);
	}

	/**
	 * @return bool whether subdirectories are included in the dependency check.
	 *   Defaults to `true`.
	 */
	public function getRecursiveCheck(): bool
	{
		return $this->_recursiveCheck;
	}

	/**
	 * @param bool $value whether subdirectories are included in the dependency check.
	 */
	public function setRecursiveCheck($value): void
	{
		$this->_recursiveCheck = TPropertyValue::ensureBoolean($value);
		if ($this->getDirectoryDirect() !== null) {
			$this->_timestamps = $this->generateTimestamps($this->getDirectoryDirect());
		}
	}

	/**
	 * @return int the maximum subdirectory depth to traverse.
	 *   `-1` means unlimited depth; `0` means only the top-level directory.
	 *   Defaults to `-1`.
	 */
	public function getRecursiveLevel(): int
	{
		return $this->_recursiveLevel;
	}

	/**
	 * Sets the maximum subdirectory depth to traverse when
	 * {@see getRecursiveCheck RecursiveCheck} is `true`.
	 * Values less than `0` mean unlimited depth; `0` checks only files directly
	 * under the tracked directory.
	 * @param int $value the depth limit.
	 */
	public function setRecursiveLevel($value): void
	{
		$this->_recursiveLevel = TPropertyValue::ensureInteger($value);
		if ($this->getDirectoryDirect() !== null) {
			$this->_timestamps = $this->generateTimestamps($this->getDirectoryDirect());
		}
	}

	/**
	 * @return bool whether any tracked file's mtime or the file count has changed.
	 */
	public function getHasChanged(): bool
	{
		return $this->generateTimestamps($this->getDirectoryDirect()) !== $this->_timestamps;
	}

	/**
	 * Returns whether the given file should be included in the dependency check.
	 * Called for each file encountered during the directory scan. Override in a
	 * subclass to restrict which files are tracked.
	 * @param string $fileName absolute path to the file.
	 * @return bool `true` to include the file; `false` to skip it.
	 */
	protected function validateFile(string $fileName): bool
	{
		return true;
	}

	/**
	 * Returns whether the given subdirectory should be descended into.
	 * Called for each subdirectory encountered during the scan. Override in a
	 * subclass to restrict which subdirectories are traversed.
	 * @param string $directory absolute path to the subdirectory.
	 * @return bool `true` to traverse the subdirectory; `false` to skip it.
	 */
	protected function validateDirectory(string $directory): bool
	{
		return true;
	}

	/**
	 * Builds a map of absolute file paths to their modification times for all
	 * tracked files under `$directory`.
	 * Recurses into subdirectories when {@see getRecursiveCheck RecursiveCheck}
	 * is `true` and the current depth is within {@see getRecursiveLevel RecursiveLevel}.
	 * @param string $directory the directory to scan.
	 * @param int $level the current recursion depth (0 = top level).
	 * @throws TIOException if the directory cannot be opened.
	 * @return array<string, int> map of file path to mtime.
	 */
	protected function generateTimestamps(string $directory, int $level = 0): array
	{
		if (($dir = opendir($directory)) === false) {
			throw new TIOException('directorycachedependency_directory_invalid', $directory);
		}
		$recursiveLevel = $this->getRecursiveLevel();
		$timestamps = [];
		while (($file = readdir($dir)) !== false) {
			$path = $directory . DIRECTORY_SEPARATOR . $file;
			if ($file === '.' || $file === '..') {
				continue;
			} elseif (is_dir($path)) {
				if ($this->getRecursiveCheck() && ($recursiveLevel < 0 || $level < $recursiveLevel) && $this->validateDirectory($path)) {
					$timestamps = array_merge($timestamps, $this->generateTimestamps($path, $level + 1));
				}
			} elseif ($this->validateFile($path)) {
				$timestamps[$path] = filemtime($path);
			}
		}
		closedir($dir);
		return $timestamps;
	}
}
