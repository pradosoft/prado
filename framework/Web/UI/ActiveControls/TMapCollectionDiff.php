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
 * TMapCollectionDiff class.
 *
 * Calculate the changes to attributes collection.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TMapCollectionDiff extends TViewStateDiff
{
	/**
	 * @return array updates to the attributes collection.
	 */
	public function getDifference()
	{
		if ($this->_old === null) {
			return ($this->_new !== null) ? $this->_new->toArray() : $this->_null;
		} else {
			$new = $this->_new->toArray();
			$old = $this->_old->toArray();
			$diff = array_diff_assoc($new, $old);
			return count($diff) > 0 ? $diff : $this->_null;
		}
	}
}
