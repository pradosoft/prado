<?php
/**
 * TTabPanel class file.
 *
 * @author Tomasz Wolny <tomasz.wolny@polecam.to.pl> and Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TTabViewCollection class.
 *
 * TTabViewCollection is used to maintain a list of views belong to a {@link TTabPanel}.
 *
 * @author Tomasz Wolny <tomasz.wolny@polecam.to.pl> and Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */
class TTabViewCollection extends \Prado\Web\UI\TControlCollection
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing sanity check on the type of new item.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@link TTabView} object.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TTabView) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('tabviewcollection_tabview_required');
		}
	}

	/**
	 * Finds the index of the tab view whose ID is the same as the one being looked for.
	 * @param string $id the explicit ID of the tab view to be looked for
	 * @return int the index of the tab view found, -1 if not found.
	 */
	public function findIndexByID($id)
	{
		foreach ($this as $index => $view) {
			if ($view->getID(false) === $id) {
				return $index;
			}
		}
		return -1;
	}
}
