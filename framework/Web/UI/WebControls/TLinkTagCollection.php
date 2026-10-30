<?php

/**
 * TLinkTagCollection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TLinkTagCollection class
 *
 * TLinkTagCollection represents the collection of {@see TLinkTag} link tags
 * contained in a {@see \Prado\Web\UI\WebControls\THead} control.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLinkTagCollection extends \Prado\Collections\TList
{
	/**
	 * Inserts an item at the specified position, after checking that it is a link tag.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a {@see TLinkTag}
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TLinkTag) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('linktagcollection_linktag_invalid');
		}
	}

	/**
	 * Finds the lowest index of the link tag with the ID.
	 * @param string $id the ID of the link tag to be looked for
	 * @return int the index of the link tag found, -1 if not found.
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
	 * Finds the link tag with the ID.
	 * @param string $id the ID of the link tag to be looked for
	 * @return ?TLinkTag the link tag found, null if not found.
	 */
	public function findLinkTagByID($id)
	{
		if (($index = $this->findIndexByID($id)) >= 0) {
			return $this->itemAt($index);
		}
		return null;
	}
}
