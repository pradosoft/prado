<?php
/**
 * THead class file
 *
 * @author Marcus Nyeholt <tanus@users.sourceforge.net> and Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TMetaTagCollection class
 *
 * TMetaTagCollection represents a collection of meta tags
 * contained in a {@link THead} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TMetaTagCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing type
	 * check on the item being added.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@link TMetaTag}
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TMetaTag) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('metatagcollection_metatag_invalid');
		}
	}

	/**
	 * Finds the lowest cardinal index of the meta tag whose id is the one being looked for.
	 * @param string $id the ID of the meta tag to be looked for
	 * @return int the index of the meta tag found, -1 if not found.
	 */
	public function findIndexByID($id)
	{
		$index = 0;
		foreach ($this as $item) {
			if ($item->getID() === $id) {
				return $index;
			}
			$index++;
		}
		return -1;
	}

	/**
	 * Finds the item whose value is the one being looked for.
	 * @param string $id the id of the meta tag to be looked for
	 * @return TMetaTag the meta tag found, null if not found.
	 */
	public function findMetaTagByID($id)
	{
		if (($index = $this->findIndexByID($id)) >= 0) {
			return $this->itemAt($index);
		} else {
			return null;
		}
	}
}
