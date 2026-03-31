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
	 * @param TXmlElement $owner Owner of this list
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
	protected function getOwner(): ?TXmlElement
	{
		return $this->_o;
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added TXmlElement object.
	 * @param int $index The specified position.
	 * @param mixed $item New item to insert
	 * @throws TInvalidDataTypeException If the item to be inserted is not a TXmlElement object.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TXmlElement) {

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
		} else {
			throw new TInvalidDataTypeException('xmlelementlist_xmlelement_required');
		}
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a TXmlElement object.
	 * @param int $index The index of the item to be removed.
	 * @return mixed The removed item.
	 */
	public function removeAt($index): mixed
	{
		$item = parent::removeAt($index);
		if ($item instanceof TXmlElement) {
			$item->setParent(null);
		}
		return $item;
	}
}
