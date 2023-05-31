<?php
/**
 * TMultiView and TView class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TViewCollection class.
 * TViewCollection represents a collection that only takes {@see TView} instances
 * as collection elements.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TViewCollection extends \Prado\Web\UI\TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by ensuring only {@see TView}
	 * controls be added into the collection.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is neither a string nor a TControl.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TView) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('viewcollection_view_required');
		}
	}
}
