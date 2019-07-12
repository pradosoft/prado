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

/**
 * TFileCacheDependency class.
 *
 * TFileCacheDependency performs dependency checking based on the
 * last modification time of the file specified via {@link setFileName FileName}.
 * The dependency is reported as unchanged if and only if the file's
 * last modification time remains unchanged.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.1.0
 */
class TFileCacheDependency extends TCacheDependency
{
	private $_fileName;
	private $_timestamp;

	/**
	 * Constructor.
	 * @param string $fileName name of the file whose change is to be checked.
	 */
	public function __construct($fileName)
	{
		$this->setFileName($fileName);
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
	 */
	public function setFileName($value)
	{
		$this->_fileName = $value;
		$this->_timestamp = @filemtime($value);
	}

	/**
	 * @return int the last modification time of the file
	 */
	public function getTimestamp()
	{
		return $this->_timestamp;
	}

	/**
	 * Performs the actual dependency checking.
	 * This method returns true if the last modification time of the file is changed.
	 * @return bool whether the dependency is changed or not.
	 */
	public function getHasChanged()
	{
		return @filemtime($this->_fileName) !== $this->_timestamp;
	}
}
