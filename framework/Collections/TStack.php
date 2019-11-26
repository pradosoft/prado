<?php
/**
 * TStack classes
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
 * TStack class
 *
 * TStack implements a stack.
 *
 * The typical stack operations are implemented, which include
 * {@link push()}, {@link pop()} and {@link peek()}. In addition,
 * {@link contains()} can be used to check if an item is contained
 * in the stack. To obtain the number of the items in the stack,
 * check the {@link getCount Count} property.
 *
 * Items in the stack may be traversed using foreach as follows,
 * <code>
 * foreach($stack as $item) ...
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TStack extends \Prado\TComponent implements \IteratorAggregate, \Countable
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
	 * Initializes the stack with an array or an iterable object.
	 * @param null|array|Iterator $data the initial data. Default is null, meaning no initialization.
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data = null)
	{
		if ($data !== null) {
			$this->copyFrom($data);
		}
	}

	/**
	 * @return array the list of items in stack
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Copies iterable data into the stack.
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
			throw new TInvalidDataTypeException('stack_data_not_iterable');
		}
	}

	/**
	 * Removes all items in the stack.
	 */
	public function clear()
	{
		$this->_c = 0;
		$this->_d = [];
	}

	/**
	 * @param mixed $item the item
	 * @return bool whether the stack contains the item
	 */
	public function contains($item)
	{
		return array_search($item, $this->_d, true) !== false;
	}

	/**
	 * Returns the item at the top of the stack.
	 * Unlike {@link pop()}, this method does not remove the item from the stack.
	 * @throws TInvalidOperationException if the stack is empty
	 * @return mixed item at the top of the stack
	 */
	public function peek()
	{
		if ($this->_c === 0) {
			throw new TInvalidOperationException('stack_empty');
		} else {
			return $this->_d[$this->_c - 1];
		}
	}

	/**
	 * Pops up the item at the top of the stack.
	 * @throws TInvalidOperationException if the stack is empty
	 * @return mixed the item at the top of the stack
	 */
	public function pop()
	{
		if ($this->_c === 0) {
			throw new TInvalidOperationException('stack_empty');
		} else {
			--$this->_c;
			return array_pop($this->_d);
		}
	}

	/**
	 * Pushes an item into the stack.
	 * @param mixed $item the item to be pushed into the stack
	 */
	public function push($item)
	{
		++$this->_c;
		$this->_d[] = $item;
	}

	/**
	 * Returns an iterator for traversing the items in the stack.
	 * This method is required by the interface \IteratorAggregate.
	 * @return \Iterator an iterator for traversing the items in the stack.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_d);
	}

	/**
	 * @return int the number of items in the stack
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * Returns the number of items in the stack.
	 * This method is required by \Countable interface.
	 * @return int number of items in the stack.
	 */
	public function count()
	{
		return $this->getCount();
	}
}
