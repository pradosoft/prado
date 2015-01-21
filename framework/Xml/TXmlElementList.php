<?php
/**
 * TXmlElement, TXmlDocument, TXmlElementList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Xml
 */

namespace Prado\Xml;

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
class TXmlElementList extends TList
{
	/**
	 * @var TXmlElement owner of this list
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param TXmlElement owner of this list
	 */
	public function __construct(TXmlElement $owner)
	{
		$this->_o=$owner;
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
	 * @param integer the specified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TXmlElement object.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TXmlElement)
		{
			parent::insertAt($index,$item);
			if($item->getParent()!==null)
				$item->getParent()->getElements()->remove($item);
			$item->setParent($this->_o);
		}
		else
			throw new TInvalidDataTypeException('xmlelementlist_xmlelement_required');
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a TXmlElement object.
	 * @param integer the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item=parent::removeAt($index);
		if($item instanceof TXmlElement)
			$item->setParent(null);
		return $item;
	}
}