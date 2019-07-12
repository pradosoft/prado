<?php
/**
 * TOutputCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TOutputCacheCheckDependencyEventParameter class
 *
 * TOutputCacheCheckDependencyEventParameter encapsulates the parameter data for
 * <b>OnCheckDependency</b> event of {@link TOutputCache} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TOutputCacheCheckDependencyEventParameter extends \Prado\TEventParameter
{
	private $_isValid = true;
	private $_cacheTime = 0;

	/**
	 * @return bool whether the dependency remains valid. Defaults to true.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * @param bool $value whether the dependency remains valid
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int the timestamp of the cached result. You may use this to help determine any dependency is changed.
	 * @since 3.1.1
	 */
	public function getCacheTime()
	{
		return $this->_cacheTime;
	}

	/**
	 * @param int $value the timestamp of the cached result. This is used internally.
	 * @since 3.1.1
	 */
	public function setCacheTime($value)
	{
		$this->_cacheTime = TPropertyValue::ensureInteger($value);
	}
}
