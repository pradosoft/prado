<?php

/**
 * TTableColumnCollection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TTableColumnCollection class.
 *
 * TTableColumnCollection holds the {@see TTableColumn} (`<col>`) children of a
 * {@see TTableColumnGroup}. A {@see TTableColumnGroup} cannot be added; the
 * HTML specification does not allow `<colgroup>` nesting.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTableColumnCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only {@see TTableColumn} objects.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TTableColumn, or is a TTableColumnGroup.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TTableColumn && !($item instanceof TTableColumnGroup)) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('tablecolumncollection_tablecolumn_required');
		}
	}
}
