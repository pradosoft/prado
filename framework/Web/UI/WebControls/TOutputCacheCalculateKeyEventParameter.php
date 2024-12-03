<?php

/**
 * TOutputCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TOutputCacheCalculateKeyEventParameter class
 *
 * TOutputCacheCalculateKeyEventParameter encapsulates the parameter data for
 * <b>OnCalculateKey</b> event of {@see \Prado\Web\UI\WebControls\TOutputCache} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TOutputCacheCalculateKeyEventParameter extends \Prado\TEventParameter
{
	/**
	 * @var string cache key to be appended to the default calculation scheme.
	 */
	private $_cacheKey = '';

	/**
	 * @return string cache key to be appended to the default calculation scheme.
	 */
	public function getCacheKey()
	{
		return $this->_cacheKey;
	}

	/**
	 * @param string $value cache key to be appended to the default calculation scheme
	 */
	public function setCacheKey($value)
	{
		$this->_cacheKey = TPropertyValue::ensureString($value);
	}
}
