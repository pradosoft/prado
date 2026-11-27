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
 * Calculates the changes to an attributes collection: the added and changed items,
 * and each removed key with a null value.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TMapCollectionDiff extends TViewStateDiff
{
	/**
	 * Returns the items that are new or changed, and each removed key with a null
	 * value; a map that is null has no items. Items compare as strings; an array
	 * item, such as a jQuery UI option, compares by its elements and type.
	 * @return array|object updates to the attributes collection, the null value if
	 *   there is no difference.
	 */
	public function getDifference()
	{
		if ($this->_old === null) {
			return ($this->_new !== null) ? $this->_new->toArray() : $this->_null;
		}
		$new = $this->_new !== null ? $this->_new->toArray() : [];
		$old = $this->_old->toArray();
		$diff = array_udiff_assoc(
			$new,
			$old,
			fn ($a, $b) => (is_array($a) || is_array($b)) ? (int) ($a !== $b) : strcmp((string) $a, (string) $b)
		) + array_fill_keys(array_keys(array_diff_key($old, $new)), null);
		return count($diff) > 0 ? $diff : $this->_null;
	}
}
