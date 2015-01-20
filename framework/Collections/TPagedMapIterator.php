<?php
/**
 * TPagedDataSource, TPagedListIterator, TPagedMapIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Collections
 */

/**
 * TPagedMapIterator class
 *
 * TPagedMapIterator implements Iterator interface.
 *
 * TPagedMapIterator is used by {@link TPagedDataSource}. It allows TPagedDataSource
 * to return a new iterator for traversing the items in a {@link TMap} object.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Collections
 * @since 3.0
 */
class TPagedMapIterator implements Iterator
{
	private $_map;
	private $_startIndex;
	private $_count;
	private $_index;
	private $_iterator;

	/**
	 * Constructor.
	 * @param array the data to be iterated through
	 */
	public function __construct(TMap $map,$startIndex,$count)
	{
		$this->_map=$map;
		$this->_index=0;
		$this->_startIndex=$startIndex;
		if($startIndex+$count>$map->getCount())
			$this->_count=$map->getCount()-$startIndex;
		else
			$this->_count=$count;
		$this->_iterator=$map->getIterator();
	}

	/**
	 * Rewinds internal array pointer.
	 * This method is required by the interface Iterator.
	 */
	public function rewind()
	{
		$this->_iterator->rewind();
		for($i=0;$i<$this->_startIndex;++$i)
			$this->_iterator->next();
		$this->_index=0;
	}

	/**
	 * Returns the key of the current array item.
	 * This method is required by the interface Iterator.
	 * @return integer the key of the current array item
	 */
	public function key()
	{
		return $this->_iterator->key();
	}

	/**
	 * Returns the current array item.
	 * This method is required by the interface Iterator.
	 * @return mixed the current array item
	 */
	public function current()
	{
		return $this->_iterator->current();
	}

	/**
	 * Moves the internal pointer to the next array item.
	 * This method is required by the interface Iterator.
	 */
	public function next()
	{
		$this->_index++;
		$this->_iterator->next();
	}

	/**
	 * Returns whether there is an item at current position.
	 * This method is required by the interface Iterator.
	 * @return boolean
	 */
	public function valid()
	{
		return $this->_index<$this->_count;
	}
}