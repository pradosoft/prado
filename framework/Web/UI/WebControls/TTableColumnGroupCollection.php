<?php

/**
 * TTableColumnGroupCollection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TTableColumnGroupCollection class.
 *
 * TTableColumnGroupCollection holds the {@see TTableColumnGroup} (`<colgroup>`)
 * elements of a {@see TTable}. Bare {@see TTableColumn} (`<col>`) items cannot
 * be added; the HTML specification requires `<col>` to reside inside a
 * `<colgroup>`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTableColumnGroupCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only {@see TTableColumnGroup} objects.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TTableColumnGroup.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TTableColumnGroup) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('tablecolumngroupcollection_group_required');
		}
	}
}
