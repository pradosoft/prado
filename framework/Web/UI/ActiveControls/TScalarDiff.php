<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TScalarDiff class.
 *
 * Calculate the changes to a scalar value.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TScalarDiff extends TViewStateDiff
{
	/**
	 * @return mixed update viewstate value.
	 */
	public function getDifference()
	{
		if (gettype($this->_new) === gettype($this->_old)
			&& $this->_new === $this->_old) {
			return $this->_null;
		} else {
			return $this->_new;
		}
	}
}
