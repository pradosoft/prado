<?php
/**
 * TDummyDataSource, TDummyDataSourceIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * TDummyDataSourceIterator class
 *
 * TDummyDataSourceIterator implements \Iterator interface.
 *
 * TDummyDataSourceIterator is used by {@see \Prado\Collections\TDummyDataSource}.
 * It allows TDummyDataSource to return a new iterator
 * for traversing its dummy items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDummyDataSourceIterator implements \Iterator
{
	private $_index;
	private $_count;

	/**
	 * Constructor.
	 * @param int $count number of (virtual) items in the data source.
	 */
	public function __construct($count)
	{
		$this->_count = $count;
		$this->_index = 0;
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
		return null;
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
