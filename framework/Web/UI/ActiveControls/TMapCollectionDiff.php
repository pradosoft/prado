<?php

/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TMapCollectionDiff class.
 *
 * Calculate the changes to attributes collection.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TMapCollectionDiff extends TViewStateDiff
{
	/**
	 * Returns the items that are new or changed. Items compare as strings; an
	 * array item, such as a jQuery UI option, compares by its elements and type.
	 * @return array updates to the attributes collection.
	 */
	public function getDifference()
	{
		if ($this->_old === null) {
			return ($this->_new !== null) ? $this->_new->toArray() : $this->_null;
		} else {
			$new = $this->_new->toArray();
			$old = $this->_old->toArray();
			$diff = array_udiff_assoc(
				$new,
				$old,
				fn ($a, $b) => (is_array($a) || is_array($b)) ? (int) ($a !== $b) : strcmp((string) $a, (string) $b)
			);
			return count($diff) > 0 ? $diff : $this->_null;
		}
	}
}
