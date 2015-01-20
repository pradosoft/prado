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
 * TOutputCacheCalculateKeyEventParameter class
 *
 * TOutputCacheCalculateKeyEventParameter encapsulates the parameter data for
 * <b>OnCalculateKey</b> event of {@link TOutputCache} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TOutputCacheCalculateKeyEventParameter extends TEventParameter
{
	/**
	 * @var string cache key to be appended to the default calculation scheme.
	 */
	private $_cacheKey='';

	/**
	 * @return string cache key to be appended to the default calculation scheme.
	 */
	public function getCacheKey()
	{
		return $this->_cacheKey;
	}

	/**
	 * @param string cache key to be appended to the default calculation scheme
	 */
	public function setCacheKey($value)
	{
		$this->_cacheKey=TPropertyValue::ensureString($value);
	}
}