<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

/**
 * TFileCacheDependency class
 *
 * TFileCacheDependency performs dependency checking based on the
 * last modification time of the file specified via {@see setFileName FileName}.
 * The dependency is reported as unchanged if and only if the file's
 * last modification time remains unchanged.
 *
 * Each read of the modification time clears PHP's stat cache for the file, so a
 * long-lived process sees a change made by another process.
 *
 * The constructor argument is optional. A dependency created without it, as from
 * configuration, is given its file through {@see setFileName FileName} and
 * reports unchanged until then.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TFileCacheDependency extends TCacheDependency
{
	/** @var ?string absolute path to the tracked file, or `null` before it is set */
	private $_fileName;
	/** @var false|int last recorded modification time of the file */
	private $_timestamp = false;

	/**
	 * Constructor.
	 * @param ?string $fileName name of the file whose change is to be checked, or `null`
	 *   to set it later through {@see setFileName FileName}.
	 */
	public function __construct($fileName = null)
	{
		if ($fileName !== null) {
			$this->setFileName($fileName);
		}
		parent::__construct();
	}

	/**
	 * @return ?string the name of the file whose change is to be checked, or `null`
	 *   before it is set.
	 */
	public function getFileName()
	{
		return $this->_fileName;
	}

	/**
	 * @param string $value the name of the file whose change is to be checked
	 * @since 4.4.0
	 */
	protected function setFileNameDirect($value)
	{
		$this->_fileName = $value;
	}

	/**
	 * @param string $value the name of the file whose change is to be checked
	 */
	public function setFileName($value)
	{
		$this->setFileNameDirect($value);
		$this->updateTimestamp();
	}

	/**
	 * Re-captures the tracked file's current modification time as the baseline. Call this
	 * after intentionally modifying the file so the next {@see getHasChanged()} compares
	 * against the refreshed timestamp.
	 * @since 4.4.0
	 */
	public function updateTimestamp()
	{
		$fileName = $this->getFileName();
		$this->setTimestamp($fileName === null ? false : $this->readFileTime($fileName));
	}

	/**
	 * Reads a file's modification time from the file system. PHP's stat cache is cleared
	 * for the file first, so the read reflects a change made by another process.
	 * @param string $fileName path of the file.
	 * @return false|int the modification time, or `false` when the file cannot be read.
	 * @since 4.4.0
	 */
	protected function readFileTime(string $fileName): false|int
	{
		clearstatcache(true, $fileName);
		return @filemtime($fileName);
	}

	/**
	 * @return false|int the last recorded modification time of the file, or `false` when
	 *   the file did not exist or no file name is set.
	 */
	public function getTimestamp()
	{
		return $this->_timestamp;
	}

	/**
	 * @since 4.4.0
	 * @param mixed $value
	 */
	protected function setTimestamp($value): void
	{
		$this->_timestamp = $value;
	}

	/**
	 * @return bool true if the file's last modification time has changed; false before
	 *   the file name is set.
	 */
	public function getHasChanged(): bool
	{
		$fileName = $this->getFileName();
		if ($fileName === null) {
			return false;
		}
		return $this->readFileTime($fileName) !== $this->getTimestamp();
	}
}
