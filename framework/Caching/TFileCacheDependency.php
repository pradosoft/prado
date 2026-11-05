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
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TFileCacheDependency extends TCacheDependency
{
	/** @var string absolute path to the tracked file */
	private $_fileName;
	/** @var false|int last recorded modification time of the file */
	private $_timestamp;

	/**
	 * Constructor.
	 * @param string $fileName name of the file whose change is to be checked.
	 */
	public function __construct($fileName)
	{
		$this->setFileName($fileName);
		parent::__construct();
	}

	/**
	 * @return string the name of the file whose change is to be checked
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
		$this->setTimestamp(@filemtime($this->getFileName()));
	}

	/**
	 * @return int the last modification time of the file
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
	 * @return bool true if the file's last modification time has changed.
	 */
	public function getHasChanged(): bool
	{
		return @filemtime($this->getFileName()) !== $this->getTimestamp();
	}
}
