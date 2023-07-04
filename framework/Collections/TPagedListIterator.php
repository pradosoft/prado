<?php
/**
 * TPagedDataSource, TPagedListIterator, TPagedMapIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * TPagedListIterator class
 *
 * TPagedListIterator implements \Iterator interface.
 *
 * TPagedListIterator is used by {@see \Prado\Collections\TPagedDataSource}. It allows TPagedDataSource
 * to return a new iterator for traversing the items in a {@see \Prado\Collections\TList} object.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TPagedListIterator implements \Iterator
{
	private $_list;
	private $_startIndex;
	private $_count;
	private $_index;

	/**
	 * Constructor.
	 * @param TList $list the data to be iterated through
	 * @param int $startIndex start index
	 * @param int $count number of items to be iterated through
	 */
	public function __construct(TList $list, $startIndex, $count)
	{
		$this->_list = $list;
		$this->_index = 0;
		$this->_startIndex = $startIndex;
		if ($startIndex + $count > $list->getCount()) {
			$this->_count = $list->getCount() - $startIndex;
		} else {
			$this->_count = $count;
		}
	}

	/**
	 * Rewinds internal array pointer.
	 * This method is required by the interface Iterator.
	 */
	public function rewind(): void
	{
		$this->_index = 0;
	}

	/**
	 * Returns the key of the current array item.
	 * This method is required by the interface Iterator.
	 * @return int the key of the current array item
	 */
	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->_index;
	}

	/**
	 * Returns the current array item.
	 * This method is required by the interface Iterator.
	 * @return mixed the current array item
	 */
	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this->_list->itemAt($this->_startIndex + $this->_index);
	}

	/**
	 * Moves the internal pointer to the next array item.
	 * This method is required by the interface Iterator.
	 */
	public function next(): void
	{
		$this->_index++;
	}

	/**
	 * Returns whether there is an item at current position.
	 * This method is required by the interface Iterator.
	 * @return bool
	 */
	public function valid(): bool
	{
		return $this->_index < $this->_count;
	}
}
