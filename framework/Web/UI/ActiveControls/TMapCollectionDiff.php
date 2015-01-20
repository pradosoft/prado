<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

/**
 * TMapCollectionDiff class.
 *
 * Calculate the changes to attributes collection.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.ActiveControls
 * @since 3.1
 */
class TMapCollectionDiff extends TViewStateDiff
{
	/**
	 * @return array updates to the attributes collection.
	 */
	public function getDifference()
	{
		if($this->_old===null)
		{
			return ($this->_new!==null) ? $this->_new->toArray() : $this->_null;
		}
		else
		{
			$new = $this->_new->toArray();
			$old = $this->_old->toArray();
			$diff = array_diff_assoc($new, $old);
			return count($diff) > 0 ? $diff : $this->_null;
		}
	}
}