<?php

/**
 * TListItemCollection class file
 *
 * @author Robin J. Rogge <rojaro@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TListItem;

/**
 * TListItemCollection class.
 *
 * TListItemCollection maintains a list of {@see \Prado\Web\UI\WebControls\TListItem} for {@see \Prado\Web\UI\WebControls\TListControl}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TListItemCollection extends TList
{
	/**
	 * Creates a list item object.
	 * This method may be overriden to provide a customized list item object.
	 * @param int $index index where the newly created item is to be inserted at.
	 * If -1, the item will be appended to the end.
	 * @return TListItem list item object
	 */
	public function createListItem($index = -1)
	{
		$item = $this->createNewListItem();
		if ($index < 0) {
			$this->add($item);
		} else {
			$this->insertAt($index, $item);
		}
		return $item;
	}

	/**
	 * @param null|mixed $text
	 * @return TListItem new item.
	 */
	protected function createNewListItem($text = null)
	{
		$item = new TListItem();
		if ($text !== null) {
			$item->setText($text);
		}
		return $item;
	}

	/**
	 * Inserts an item into the collection.
	 * @param int $index the location where the item will be inserted.
	 * The current item at the place and the following ones will be moved backward.
	 * @param TListItem $item the item to be inserted.
	 * @throws TInvalidDataTypeException if the item being inserted is neither a string nor TListItem
	 */
	public function insertAt($index, $item)
	{
		if (is_string($item)) {
			$item = $this->createNewListItem($item);
		}
		if (!($item instanceof TListItem)) {
			throw new TInvalidDataTypeException('listitemcollection_item_invalid', $this::class);
		}
		parent::insertAt($index, $item);
	}

	/**
	 * Finds the lowest cardinal index of the item whose value is the one being looked for.
	 * @param string $value the value to be looked for
	 * @param bool $includeDisabled whether to look for disabled items also
	 * @return int the index of the item found, -1 if not found.
	 */
	public function findIndexByValue($value, $includeDisabled = true)
	{
		$value = TPropertyValue::ensureString($value);
		$index = 0;
		foreach ($this as $item) {
			if ($item->getValue() === $value && ($includeDisabled || $item->getEnabled())) {
				return $index;
			}
			$index++;
		}
		return -1;
	}

	/**
	 * Finds the lowest cardinal index of the item whose text is the one being looked for.
	 * @param string $text the text to be looked for
	 * @param bool $includeDisabled whether to look for disabled items also
	 * @return int the index of the item found, -1 if not found.
	 */
	public function findIndexByText($text, $includeDisabled = true)
	{
		$text = TPropertyValue::ensureString($text);
		$index = 0;
		foreach ($this as $item) {
			if ($item->getText() === $text && ($includeDisabled || $item->getEnabled())) {
				return $index;
			}
			$index++;
		}
		return -1;
	}

	/**
	 * Finds the item whose value is the one being looked for.
	 * @param string $value the value to be looked for
	 * @param bool $includeDisabled whether to look for disabled items also
	 * @return null|TListItem the item found, null if not found.
	 */
	public function findItemByValue($value, $includeDisabled = true)
	{
		if (($index = $this->findIndexByValue($value, $includeDisabled)) >= 0) {
			return $this->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * Finds the item whose text is the one being looked for.
	 * @param string $text the text to be looked for
	 * @param bool $includeDisabled whether to look for disabled items also
	 * @return null|TListItem the item found, null if not found.
	 */
	public function findItemByText($text, $includeDisabled = true)
	{
		if (($index = $this->findIndexByText($text, $includeDisabled)) >= 0) {
			return $this->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * Loads state into every item in the collection.
	 * This method should only be used by framework and control developers.
	 * @param null|array $state state to be loaded.
	 */
	public function loadState($state)
	{
		$this->clear();
		if ($state !== null) {
			$this->copyFrom($state);
		}
	}

	/**
	 * Saves state of items.
	 * This method should only be used by framework and control developers.
	 * @return null|array the saved state
	 */
	public function saveState()
	{
		return ($this->getCount() > 0) ? $this->toArray() : null;
	}
}
