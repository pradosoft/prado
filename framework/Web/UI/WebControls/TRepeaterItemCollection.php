<?php
/**
 * TRepeater class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TRepeaterItemCollection class.
 *
 * TRepeaterItemCollection represents a collection of repeater items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TRepeaterItemCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only objects that are descendant of {@see TControl}.
	 * @param int $index the specified position.
	 * @param \Prado\Web\UI\TControl $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a control.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof \Prado\Web\UI\TControl) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('repeateritemcollection_item_invalid');
		}
	}
}
