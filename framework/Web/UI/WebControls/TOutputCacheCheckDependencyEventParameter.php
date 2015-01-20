<?php
/**
 * TOutputCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TOutputCacheCheckDependencyEventParameter class
 *
 * TOutputCacheCheckDependencyEventParameter encapsulates the parameter data for
 * <b>OnCheckDependency</b> event of {@link TOutputCache} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TOutputCacheCheckDependencyEventParameter extends TEventParameter
{
	private $_isValid=true;
	private $_cacheTime=0;

	/**
	 * @return boolean whether the dependency remains valid. Defaults to true.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * @param boolean whether the dependency remains valid
	 */
	public function setIsValid($value)
	{
		$this->_isValid=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return integer the timestamp of the cached result. You may use this to help determine any dependency is changed.
	 * @since 3.1.1
	 */
	public function getCacheTime()
	{
		return $this->_cacheTime;
	}

	/**
	 * @param integer the timestamp of the cached result. This is used internally.
	 * @since 3.1.1
	 */
	public function setCacheTime($value)
	{
		$this->_cacheTime=TPropertyValue::ensureInteger($value);
	}
}