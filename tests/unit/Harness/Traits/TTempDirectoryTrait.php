<?php

/**
 * TTempDirectoryTrait file.
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

/**
 * TTempDirectoryTrait creates temporary directories for a test and removes them.
 *
 * {@see makeTempDirectory()} creates a directory in the system temp directory and
 * records it. {@see removeTempDirectories()}, called from `tearDown()`, removes every
 * recorded directory with its contents, restoring the permissions a test removed.
 *
 * ```php
 * use TTempDirectoryTrait;
 *
 * protected function tearDown(): void
 * {
 *     $this->removeTempDirectories();
 * }
 * ```
 */
trait TTempDirectoryTrait
{
	/** @var string[] the directories created by {@see makeTempDirectory()}. */
	private array $_tempDirectories = [];

	/**
	 * Creates an empty directory in the system temp directory.
	 * @param string $prefix the prefix of the directory name.
	 * @return string the real path of the directory.
	 */
	protected function makeTempDirectory(string $prefix = 'prado_test_'): string
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(6));
		mkdir($dir, 0o777, true);
		$dir = realpath($dir);
		$this->_tempDirectories[] = $dir;
		return $dir;
	}

	/**
	 * Removes the directories created by {@see makeTempDirectory()} and their contents.
	 */
	protected function removeTempDirectories(): void
	{
		foreach (array_reverse($this->_tempDirectories) as $dir) {
			self::removeTempTree($dir);
		}
		$this->_tempDirectories = [];
	}

	/**
	 * Removes a file, a link, or a directory and its contents.
	 * @param string $path the path to remove.
	 */
	private static function removeTempTree(string $path): void
	{
		if (is_link($path) || is_file($path)) {
			@unlink($path);
			return;
		}
		if (!is_dir($path)) {
			return;
		}
		@chmod($path, 0o777);
		foreach (scandir($path) ?: [] as $entry) {
			if ($entry !== '.' && $entry !== '..') {
				self::removeTempTree($path . DIRECTORY_SEPARATOR . $entry);
			}
		}
		@rmdir($path);
	}
}
