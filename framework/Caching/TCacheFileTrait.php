<?php

/**
 * TCacheFileTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

/**
 * TCacheFileTrait trait
 *
 * Provides the shared filesystem read/write helpers used by the file-persisting
 * cache modules ({@see TFileCache} and {@see TMemoryCache}). Each method is a thin,
 * overridable seam so that subclasses and test doubles can intercept file I/O.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCacheFileTrait
{
	/**
	 * Reads and returns the entire contents of a file.
	 * Returns false when the file cannot be read.
	 *
	 * @param string $filePath the path of the file to read
	 * @return false|string the file contents, or false on failure
	 */
	protected function getContents(string $filePath): string|false
	{
		return @file_get_contents($filePath);
	}

	/**
	 * Writes data to a file, replacing its current contents.
	 *
	 * @param string $filePath the path of the file to write
	 * @param string $data the data to write
	 * @param bool $exclusive when true, the write acquires an exclusive lock (`LOCK_EX`);
	 *   when false (default), no lock is used — atomicity is expected to be provided by
	 *   the caller (e.g. a `tempnam()` + `rename()` pattern)
	 * @return false|int the number of bytes written, or false on failure
	 */
	protected function putContents(string $filePath, string $data, bool $exclusive = false): int|false
	{
		return @file_put_contents($filePath, $data, $exclusive ? LOCK_EX : 0);
	}
}
