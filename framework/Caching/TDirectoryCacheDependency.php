<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;
use Prado\TPropertyValue;

/**
 * TDirectoryCacheDependency class.
 *
 * TDirectoryCacheDependency performs dependency checking based on the
 * modification time of the files contained in the specified directory.
 * The directory being checked is specified via {@link setDirectory Directory}.
 *
 * By default, all files under the specified directory and subdirectories
 * will be checked. If the last modification time of any of them is changed
 * or if different number of files are contained in a directory, the dependency
 * is reported as changed. By specifying {@link setRecursiveCheck RecursiveCheck}
 * and {@link setRecursiveLevel RecursiveLevel}, one can limit the checking
 * to a certain depth of the subdirectories.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.1.0
 */
class TDirectoryCacheDependency extends TCacheDependency
{
	private $_recursiveCheck = true;
	private $_recursiveLevel = -1;
	private $_timestamps;
	private $_directory;

	/**
	 * Constructor.
	 * @param string $directory the directory to be checked
	 */
	public function __construct($directory)
	{
		$this->setDirectory($directory);
	}

	/**
	 * @return string the directory to be checked
	 */
	public function getDirectory()
	{
		return $this->_directory;
	}

	/**
	 * @param string $directory the directory to be checked
	 * @throws TInvalidDataValueException if the directory does not exist
	 */
	public function setDirectory($directory)
	{
		if (($path = realpath($directory)) === false || !is_dir($path)) {
			throw new TInvalidDataValueException('directorycachedependency_directory_invalid', $directory);
		}
		$this->_directory = $path;
		$this->_timestamps = $this->generateTimestamps($path);
	}

	/**
	 * @return bool whether the subdirectories of the directory will also be checked.
	 * It defaults to true.
	 */
	public function getRecursiveCheck()
	{
		return $this->_recursiveCheck;
	}

	/**
	 * @param bool $value whether the subdirectories of the directory will also be checked.
	 */
	public function setRecursiveCheck($value)
	{
		$this->_recursiveCheck = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int the depth of the subdirectories to be checked.
	 * It defaults to -1, meaning unlimited depth.
	 */
	public function getRecursiveLevel()
	{
		return $this->_recursiveLevel;
	}

	/**
	 * Sets a value indicating the depth of the subdirectories to be checked.
	 * This is meaningful only when {@link getRecursiveCheck RecursiveCheck}
	 * is true.
	 * @param int $value the depth of the subdirectories to be checked.
	 * If the value is less than 0, it means unlimited depth.
	 * If the value is 0, it means checking the files directly under the specified directory.
	 */
	public function setRecursiveLevel($value)
	{
		$this->_recursiveLevel = TPropertyValue::ensureInteger($value);
	}

	/**
	 * Performs the actual dependency checking.
	 * This method returns true if the directory is changed.
	 * @return bool whether the dependency is changed or not.
	 */
	public function getHasChanged()
	{
		return $this->generateTimestamps($this->_directory) != $this->_timestamps;
	}

	/**
	 * Checks to see if the file should be checked for dependency.
	 * This method is invoked when dependency of the whole directory is being checked.
	 * By default, it always returns true, meaning the file should be checked.
	 * You may override this method to check only certain files.
	 * @param string $fileName the name of the file that may be checked for dependency.
	 * @return bool whether this file should be checked.
	 */
	protected function validateFile($fileName)
	{
		return true;
	}

	/**
	 * Checks to see if the specified subdirectory should be checked for dependency.
	 * This method is invoked when dependency of the whole directory is being checked.
	 * By default, it always returns true, meaning the subdirectory should be checked.
	 * You may override this method to check only certain subdirectories.
	 * @param string $directory the name of the subdirectory that may be checked for dependency.
	 * @return bool whether this subdirectory should be checked.
	 */
	protected function validateDirectory($directory)
	{
		return true;
	}

	/**
	 * Determines the last modification time for files under the directory.
	 * This method may go recursively into subdirectories if
	 * {@link setRecursiveCheck RecursiveCheck} is set true.
	 * @param string $directory the directory name
	 * @param int $level level of the recursion
	 * @return array list of file modification time indexed by the file path
	 */
	protected function generateTimestamps($directory, $level = 0)
	{
		if (($dir = opendir($directory)) === false) {
			throw new TIOException('directorycachedependency_directory_invalid', $directory);
		}
		$timestamps = [];
		while (($file = readdir($dir)) !== false) {
			$path = $directory . DIRECTORY_SEPARATOR . $file;
			if ($file === '.' || $file === '..') {
				continue;
			} elseif (is_dir($path)) {
				if (($this->_recursiveLevel < 0 || $level < $this->_recursiveLevel) && $this->validateDirectory($path)) {
					$timestamps = array_merge($this->generateTimestamps($path, $level + 1));
				}
			} elseif ($this->validateFile($path)) {
				$timestamps[$path] = filemtime($path);
			}
		}
		closedir($dir);
		return $timestamps;
	}
}
