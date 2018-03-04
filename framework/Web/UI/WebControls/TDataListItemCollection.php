<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\TControl;

/**
 * TDataListItemCollection class.
 *
 * TDataListItemCollection represents a collection of data list items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataListItemCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only TControl descendants.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TControl descendant.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TControl) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('datalistitemcollection_datalistitem_required');
		}
	}
}
