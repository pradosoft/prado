<?php
/**
 * TRepeater class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRepeaterItemCollection class.
 *
 * TRepeaterItemCollection represents a collection of repeater items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TRepeaterItemCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only objects that are descendant of {@link TControl}.
	 * @param integer the speicified position.
	 * @param TControl new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a control.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TControl)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('repeateritemcollection_item_invalid');
	}
}