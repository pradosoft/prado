<?php

/**
 * TXmlElementList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Xml;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;

/**
 * TXmlElementList class.
 *
 * TXmlElementList represents a collection of {@see \Prado\Xml\TXmlElement}.
 * You may manipulate the collection with the operations defined in {@see \Prado\Collections\TList}.
 *
 * This class has been enhanced with additional functionality for managing XML
 * elements within a collection, including proper parent/child relationship
 * handling.
 *
 * Note: TXmlElementList is only instanced by TXmlElement and accessed through
 * {@see TXmlElement::getElements()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TXmlElementList extends \Prado\Collections\TList
{
	/**
	 * @var TXmlElement owner of this list
	 */
	private ?TXmlElement $_o;

	/**
	 * Constructor.
	 * Initializes a new TXmlElementList with the specified owner.
	 * @param ?TXmlElement $owner Owner of this list
	 */
	public function __construct(?TXmlElement $owner)
	{
		$this->_o = $owner;
		parent::__construct();
	}

	/**
	 * Gets the owner of this list.
	 * @return TXmlElement Owner of this list
	 */
	protected function getOwner()
	{
		return $this->_o;
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added TXmlElement object.
	 * An element that is already in a list is moved out of it.
	 * @param int $index The specified position.
	 * @param mixed $item New item to insert
	 * @throws TInvalidDataTypeException If the item to be inserted is not a TXmlElement object.
	 * @throws TInvalidOperationException If the item is the owner of this list or one of its ancestors.
	 */
	public function insertAt($index, $item)
	{
		if (!($item instanceof TXmlElement)) {
			throw new TInvalidDataTypeException('xmlelementlist_xmlelement_required');
		}
		if ($this->isOwnerOrAncestor($item)) {
			throw new TInvalidOperationException('xmlelementlist_circular_insert', $item->getTagName());
		}

		$itemParent = $item->getParent();
		$parentIndex = -1;
		if ($itemParent !== null) {
			$parentIndex = $itemParent->getElements()->indexOf($item);
			if ($itemParent === $this->_o && $parentIndex >= 0 && $index <= $parentIndex) {
				$parentIndex++;
			}
		}
		parent::insertAt($index, $item);
		if ($parentIndex >= 0) {
			$itemParent->getElements()->removeAt($parentIndex);
		}
		$item->setParent($this->_o);
	}

	/**
	 * Determines whether an element is the owner of this list or one of the owner's ancestors.
	 * Inserting such an element makes a cycle in the element tree.
	 * @param TXmlElement $element The element to check
	 * @return bool Whether the element is the owner or one of its ancestors
	 * @since 4.4.0
	 */
	protected function isOwnerOrAncestor(TXmlElement $element): bool
	{
		for ($node = $this->getOwner(); $node !== null; $node = $node->getParent()) {
			if ($node === $element) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a TXmlElement object.
	 * The list holds only TXmlElement objects, since {@see insertAt()} rejects any other item.
	 * @param int $index The index of the item to be removed.
	 * @return TXmlElement The removed item.
	 */
	public function removeAt($index)
	{
		$item = parent::removeAt($index);
		$item->setParent(null);
		return $item;
	}
}
