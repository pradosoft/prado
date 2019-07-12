<?php
/**
 * TQueue, TQueueIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

/**
 * TQueueIterator class
 *
 * TQueueIterator implements \Iterator interface.
 *
 * TQueueIterator is used by TQueue. It allows TQueue to return a new iterator
 * for traversing the items in the queue.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.1
 */
class TQueueIterator implements \Iterator
{
	/**
	 * @var array the data to be iterated through
	 */
	private $_d;
	/**
	 * @var int index of the current item
	 */
	private $_i;
	/**
	 * @var int count of the data items
	 */
	private $_c;

	/**
	 * Constructor.
	 * @param array &$data the data to be iterated through
	 */
	public function __construct(&$data)
	{
		$this->_d = &$data;
		$this->_i = 0;
		$this->_c = count($this->_d);
	}

	/**
	 * Rewinds internal array pointer.
	 * This method is required by the interface Iterator.
	 */
	public function rewind()
	{
		$this->_i = 0;
	}

	/**
	 * Returns the key of the current array item.
	 * This method is required by the interface Iterator.
	 * @return int the key of the current array item
	 */
	public function key()
	{
		return $this->_i;
	}

	/**
	 * Returns the current array item.
	 * This method is required by the interface Iterator.
	 * @return mixed the current array item
	 */
	public function current()
	{
		return $this->_d[$this->_i];
	}

	/**
	 * Moves the internal pointer to the next array item.
	 * This method is required by the interface Iterator.
	 */
	public function next()
	{
		$this->_i++;
	}

	/**
	 * Returns whether there is an item at current position.
	 * This method is required by the interface Iterator.
	 * @return bool
	 */
	public function valid()
	{
		return $this->_i < $this->_c;
	}
}
