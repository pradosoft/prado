<?php
/**
 * TXmlElement, TXmlDocument, TXmlElementList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Xml
 */

namespace Prado\Xml;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TXmlElementList class.
 *
 * TXmlElementList represents a collection of {@link TXmlElement}.
 * You may manipulate the collection with the operations defined in {@link TList}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Xml
 * @since 3.0
 */
class TXmlElementList extends \Prado\Collections\TList
{
	/**
	 * @var TXmlElement owner of this list
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param TXmlElement $owner owner of this list
	 */
	public function __construct(TXmlElement $owner)
	{
		$this->_o = $owner;
	}

	/**
	 * @return TXmlElement owner of this list
	 */
	protected function getOwner()
	{
		return $this->_o;
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added TXmlElement object.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TXmlElement object.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof TXmlElement) {
			parent::insertAt($index, $item);
			if ($item->getParent() !== null) {
				$item->getParent()->getElements()->remove($item);
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
	 * @param int $index the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item = parent::removeAt($index);
		if ($item instanceof TXmlElement) {
			$item->setParent(null);
		}
		return $item;
	}
}
