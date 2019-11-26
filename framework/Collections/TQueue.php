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

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;

/**
 * TQueue class
 *
 * TQueue implements a queue.
 *
 * The typical queue operations are implemented, which include
 * {@link enqueue()}, {@link dequeue()} and {@link peek()}. In addition,
 * {@link contains()} can be used to check if an item is contained
 * in the queue. To obtain the number of the items in the queue,
 * check the {@link getCount Count} property.
 *
 * Items in the queue may be traversed using foreach as follows,
 * <code>
 * foreach($queue as $item) ...
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package Prado\Collections
 * @since 3.1
 */
class TQueue extends \Prado\TComponent implements \IteratorAggregate, \Countable
{
	/**
	 * internal data storage
	 * @var array
	 */
	private $_d = [];
	/**
	 * number of items
	 * @var int
	 */
	private $_c = 0;

	/**
	 * Constructor.
	 * Initializes the queue with an array or an iterable object.
	 * @param null|array|Iterator $data the intial data. Default is null, meaning no initialization.
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data = null)
	{
		if ($data !== null) {
			$this->copyFrom($data);
		}
	}

	/**
	 * @return array the list of items in queue
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Copies iterable data into the queue.
	 * Note, existing data in the list will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor a Traversable.
	 */
	public function copyFrom($data)
	{
		if (is_array($data) || ($data instanceof \Traversable)) {
			$this->clear();
			foreach ($data as $item) {
				$this->_d[] = $item;
				++$this->_c;
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('queue_data_not_iterable');
		}
	}

	/**
	 * Removes all items in the queue.
	 */
	public function clear()
	{
		$this->_c = 0;
		$this->_d = [];
	}

	/**
	 * @param mixed $item the item
	 * @return bool whether the queue contains the item
	 */
	public function contains($item)
	{
		return array_search($item, $this->_d, true) !== false;
	}

	/**
	 * Returns the first item at the front of the queue.
	 * Unlike {@link dequeue()}, this method does not remove the item from the queue.
	 * @throws TInvalidOperationException if the queue is empty
	 * @return mixed item at the top of the queue
	 */
	public function peek()
	{
		if ($this->_c === 0) {
			throw new TInvalidOperationException('queue_empty');
		} else {
			return $this->_d[0];
		}
	}

	/**
	 * Removes and returns the object at the beginning of the queue.
	 * @throws TInvalidOperationException if the queue is empty
	 * @return mixed the item at the beginning of the queue
	 */
	public function dequeue()
	{
		if ($this->_c === 0) {
			throw new TInvalidOperationException('queue_empty');
		} else {
			--$this->_c;
			return array_shift($this->_d);
		}
	}

	/**
	 * Adds an object to the end of the queue.
	 * @param mixed $item the item to be appended into the queue
	 */
	public function enqueue($item)
	{
		++$this->_c;
		$this->_d[] = $item;
	}

	/**
	 * Returns an iterator for traversing the items in the queue.
	 * This method is required by the interface \IteratorAggregate.
	 * @return \Iterator an iterator for traversing the items in the queue.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_d);
	}

	/**
	 * @return int the number of items in the queue
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * Returns the number of items in the queue.
	 * This method is required by \Countable interface.
	 * @return int number of items in the queue.
	 */
	public function count()
	{
		return $this->getCount();
	}
}
