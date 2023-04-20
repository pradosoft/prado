<?php
/**
 * TPriorityList class
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;

/**
 * TPriorityList class
 *
 * TPriorityList implements a priority ordered list collection class.  It allows you to specify
 * any numeric for priorities down to a specific precision.  The lower the numeric, the high the priority of the item in the
 * list.  Thus -10 has a higher priority than -5, 0, 10 (the default), 18, 10005, etc.  Per {@link round}, precision may be negative and
 * thus rounding can go by 10, 100, 1000, etc, instead of just .1, .01, .001, etc. The default precision allows for 8 decimal
 * places. There is also a default priority of 10, if no different default priority is specified or no item specific priority is indicated.
 * If you replace TList with this class it will  work exactly the same with items inserted set to the default priority, until you start
 * using different priorities than the default priority.
 *
 * As you access the PHP array features of this class, it flattens and caches the results.  If at all possible, this
 * will keep the cache fresh even when manipulated.  If this is not possible the cache is cleared.
 * When an array of items are needed and the cache is outdated, the cache is recreated from the items and their priorities
 *
 * You can access, append, insert, remove an item by using
 * {@link itemAt}, {@link add}, {@link insertAt}, and {@link remove}.
 * To get the number of the items in the list, use {@link getCount}.
 * TPriorityList can also be used like a regular array as follows,
 * <code>
 * $list[]=$item;  // append with the default priority.  It may not be the last item if other items in the list are prioritized after the default priority
 * $list[$index]=$item; // $index must be between 0 and $list->Count-1.  This sets the element regardless of priority.  Priority stays the same.
 * $list[$index]=$item; // $index is $list->Count.  This appends the item to the end of the list with the same priority as the last item in the list.
 * unset($list[$index]); // remove the item at $index
 * if(isset($list[$index])) // if the list has an item at $index
 * foreach($list as $index=>$item) // traverse each item in the list in proper priority order and add/insert order
 * $n=count($list); // returns the number of items in the list
 * </code>
 *
 * To extend TPriorityList for doing your own operations with each addition or removal,
 * override {@link insertAtIndexInPriority()} and {@link removeAtIndexInPriority()} and then call the parent.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.2a
 */
class TPriorityList extends TList
{
	use TPriorityCollectionTrait;

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param null|array|\Iterator $data the initial data. Default is null, meaning no initial data.
	 * @param bool $readOnly whether the list is read-only
	 * @param numeric $defaultPriority the default priority of items without specified priorities.
	 * @param int $precision the precision of the numeric priorities
	 * @throws TInvalidDataTypeException If data is not null and is neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = false, $defaultPriority = 10, $precision = 8)
	{
		$this->setPrecision($precision);
		$this->setDefaultPriority($defaultPriority);
		parent::__construct($data, $readOnly);
	}

	/**
	 * Returns the item at the index of a flattened priority list.
	 * {@link offsetGet} calls this method.
	 * @param int $index the index of the item to get
	 * @throws TInvalidDataValueException Issued when the index is invalid
	 * @return mixed the element at the offset
	 */
	public function itemAt($index)
	{
		if ($index >= 0 && $index < $this->getCount()) {
			$this->flattenPriorities();
			return $this->_fd[$index];
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $index);
		}
	}

	/**
	 * Returns the item at an index within a priority
	 * @param int $index the index into the list of items at priority
	 * @param null|numeric $priority the priority which to index.  no parameter or null
	 *   will result in the default priority
	 * @throws TInvalidDataValueException if the index is out of the range at the
	 *   priority or no items at the priority.
	 * @return mixed the element at the offset, false if no element is found at the offset
	 */
	public function itemAtIndexInPriority($index, $priority = null)
	{
		$priority = $this->ensurePriority($priority);
		if (isset($this->_d[$priority]) && 0 <= $index && $index < count($this->_d[$priority])) {
			return $this->_d[$priority][$index];
		} else {
			throw new TInvalidDataValueException('prioritylist_index_invalid', $index, count($this->_d[$priority] ?? []), $priority);
		}
	}

	/**
	 * Appends an item into the list at the end of the specified priority.  The position of the added item may
	 * not be at the end of the list.
	 * @param mixed $item item to add into the list at priority
	 * @param null|numeric $priority priority blank or null for the default priority
	 * @throws TInvalidOperationException if the map is read-only
	 * @return int the index within the flattened array
	 */
	public function add($item, $priority = null)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		return $this->insertAtIndexInPriority($item, false, $priority, true);
	}

	/**
	 * Inserts an item at an index.  It reads the priority of the item at index within the flattened list
	 * and then inserts the item at that priority-index.
	 * @param int $index the specified position in the flattened list.
	 * @param mixed $item new item to add
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAt($index, $item)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityAt($index, true)) !== false) {
			$this->insertAtIndexInPriority($item, $priority[1], $priority[0]);
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $index);
		}
	}

	/**
	 * Inserts an item at the specified index within a priority.  Override and call this method to
	 * insert your own functionality.
	 * @param mixed $item item to add within the list.
	 * @param false|int $index index within the priority to add the item, defaults to false which appends the item at the priority
	 * @param null|numeric $priority priority of the item.  defaults to null, which sets it to the default priority
	 * @param bool $preserveCache preserveCache specifies if this is a special quick function or not. This defaults to false.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAtIndexInPriority($item, $index = false, $priority = null, $preserveCache = false)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		$itemPriority = null;
		if (($isPriorityItem = ($item instanceof IPriorityItem)) && ($priority === null || !is_numeric($priority))) {
			$itemPriority = $priority = $item->getPriority();
		}
		$priority = $this->ensurePriority($priority);
		if (($item instanceof IPriorityCapture) && (!$isPriorityItem || $itemPriority !== $priority)) {
			$item->setPriority($priority);
		}

		if (isset($this->_d[$priority])) {
			if ($index === false) {
				$c = count($this->_d[$priority]);
				$this->_d[$priority][] = $item;
			} elseif (0 <= $index && $index <= count($this->_d[$priority])) {
				$c = $index;
				array_splice($this->_d[$priority], $index, 0, [$item]);
			} else {
				throw new TInvalidDataValueException('prioritylist_index_invalid', $index, count($this->_d[$priority] ?? []), $priority);
			}
		} elseif ($index === 0 || $index === false) {
			$c = 0;
			$this->_o = false;
			$this->_d[$priority] = [$item];
		} else {
			throw new TInvalidDataValueException('prioritylist_index_invalid', $index, 0, $priority);
		}

		if ($preserveCache) {
			if ($this->_fd !== null) {
				$this->sortPriorities();
				foreach ($this->_d as $prioritykey => $items) {
					if ($prioritykey >= $priority) {
						break;
					} else {
						$c += count($items);
					}
				}
				array_splice($this->_fd, $c, 0, [$item]);
			}
		} else {
			if ($this->_fd !== null && count($this->_d) == 1) {
				array_splice($this->_fd, $c, 0, [$item]);
			} else {
				$this->_fd = null;
				$c = null;
			}
		}

		$this->_c++;

		return $c;
	}


	/**
	 * Removes an item from the priority list.
	 * The list will search for the item.  The first matching item found will be removed from the list.
	 * @param mixed $item item the item to be removed.
	 * @param null|bool|float $priority priority of item to remove. without this parameter it defaults to false.
	 * A value of false means any priority. null will be filled in with the default priority.
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return int index within the flattened list at which the item is being removed
	 */
	public function remove($item, $priority = false)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($p = $this->priorityOf($item, true)) !== false) {
			if ($priority !== false) {
				$priority = $this->ensurePriority($priority);
				if ($p[0] != $priority) {
					throw new TInvalidDataValueException('list_item_inexistent');
				}
			}
			$this->removeAtIndexInPriority($p[1], $p[0]);
			return $p[2];
		} else {
			throw new TInvalidDataValueException('list_item_inexistent');
		}
	}

	/**
	 * Removes an item at the specified index in the flattened list.
	 * @param int $index index of the item to be removed.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityAt($index, true)) !== false) {
			return $this->removeAtIndexInPriority($priority[1], $priority[0]);
		}
		throw new TInvalidDataValueException('list_index_invalid', $index);
	}

	/**
	 * Removes the item at a specific index within a priority.  Override
	 * and call this method to insert your own functionality.
	 * @param int $index index of item to remove within the priority.
	 * @param null|numeric $priority priority of the item to remove, defaults to null, or left blank, it is then set to the default priority
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return mixed the removed item.
	 */
	public function removeAtIndexInPriority($index, $priority = null)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		$priority = $this->ensurePriority($priority);
		if (!isset($this->_d[$priority]) || $index < 0 || $index >= ($c = count($this->_d[$priority]))) {
			throw new TInvalidDataValueException('list_item_inexistent');
		}

		if ($index === $c - 1) {
			$value = array_pop($this->_d[$priority]);
		} else {
			$value = array_splice($this->_d[$priority], $index, 1);
			$value = $value[0];
		}
		if (!count($this->_d[$priority])) {
			unset($this->_d[$priority]);
		}

		$this->_c--;
		$this->_fd = null;
		return $value;
	}

	/**
	 * Removes all items in the priority list by calling removeAtIndexInPriority from the last item to the first.
	 */
	public function clear(): void
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		foreach (array_keys($this->_d) as $priority) {
			for ($index = count($this->_d[$priority]) - 1; $index >= 0; $index--) {
				$this->removeAtIndexInPriority($index, $priority);
			}
		}
	}

	/**
	 * @param mixed $item item
	 * @return int the index of the item in the flattened list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		$this->flattenPriorities();
		if (($index = array_search($item, $this->_fd, true)) === false) {
			return -1;
		} else {
			return $index;
		}
	}

	/**
	 * Returns the priority of a particular item
	 * @param mixed $item the item to look for within the list
	 * @param bool $withindex this specifies if the full positional data of the item within the list is returned.
	 * 		This defaults to false, if no parameter is provided, so only provides the priority number of the item by default.
	 * @return array|false|numeric the priority of the item in the list, false if not found.
	 *   if $withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex, 2 => flattenedIndex,
	 * 'priority' => $priority, 'index' => $priorityIndex, 'absindex' => flattenedIndex]
	 */
	public function priorityOf($item, $withindex = false)
	{
		$this->sortPriorities();

		$absindex = 0;
		foreach (array_keys($this->_d) as $priority) {
			if (($index = array_search($item, $this->_d[$priority], true)) !== false) {
				$absindex += $index;
				return $withindex ? [$priority, $index, $absindex,
						'priority' => $priority, 'index' => $index, 'absindex' => $absindex, ] : $priority;
			} else {
				$absindex += count($this->_d[$priority]);
			}
		}

		return false;
	}

	/**
	 * Returns the priority of an item at a particular flattened index.  The index after
	 * the last item does not exist but receives a priority from the last item so that
	 * priority information about any new items being appended is available.
	 * @param int $index index of the item within the list
	 * @param bool $withindex this specifies if the full positional data of the item within the list is returned.
	 * 		This defaults to false, if no parameter is provided, so only provides the priority number of the item by default.
	 * @return array|false|numeric the priority of the item in the list, false if not found.
	 *   if $withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex, 2 => flattenedIndex,
	 * 'priority' => $priority, 'index' => $priorityIndex, 'absindex' => flattenedIndex]
	 */
	public function priorityAt($index, $withindex = false)
	{
		if (0 <= $index && $index <= $this->_c) {
			$c = $absindex = $index;
			$priority = null;
			$this->sortPriorities();
			foreach (array_keys($this->_d) as $priority) {
				if ($index >= ($c = count($this->_d[$priority]))) {
					$index -= $c;
				} else {
					return $withindex ? [$priority, $index, $absindex,
							'priority' => $priority, 'index' => $index, 'absindex' => $absindex, ] : $priority;
				}
			}
			return $withindex ? [$priority, $c, $absindex,
					'priority' => $priority, 'index' => $c, 'absindex' => $absindex, ] : $priority;
		}
		return false;
	}

	/**
	 * This inserts an item before another item within the list.  It uses the same priority as the
	 * found index item and places the new item before it.
	 * @param mixed $indexitem the item to index
	 * @param mixed $item the item to add before indexitem
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return int where the item has been inserted in the flattened list
	 */
	public function insertBefore($indexitem, $item)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityOf($indexitem, true)) === false) {
			throw new TInvalidDataValueException('list_item_inexistent');
		}

		$this->insertAtIndexInPriority($item, $priority[1], $priority[0]);

		return $priority[2];
	}

	/**
	 * This inserts an item after another item within the list.  It uses the same priority as the
	 * found index item and places the new item after it.
	 * @param mixed $indexitem the item to index
	 * @param mixed $item the item to add after indexitem
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return int where the item has been inserted in the flattened list
	 */
	public function insertAfter($indexitem, $item)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityOf($indexitem, true)) === false) {
			throw new TInvalidDataValueException('list_item_inexistent');
		}

		$this->insertAtIndexInPriority($item, $priority[1] + 1, $priority[0]);

		return $priority[2] + 1;
	}

	/**
	 * Copies iterable data into the priority list.
	 * Note, existing data in the map will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function copyFrom($data): void
	{
		if ($data instanceof TPriorityList) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				for ($i = 0, $c = count($array[$priority]); $i < $c; $i++) {
					$this->insertAtIndexInPriority($array[$priority][$i], false, $priority);
				}
			}
		} elseif ($data instanceof TPriorityMap) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				foreach ($array[$priority] as $item) {
					$this->insertAtIndexInPriority($item, false, $priority);
				}
			}
		} elseif (is_array($data) || $data instanceof \Traversable) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			foreach ($data as $item) {
				$this->insertAtIndexInPriority($item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Merges iterable data into the priority list.
	 * New data will be appended to the end of the existing data.  If another TPriorityList is merged,
	 * the incoming parameter items will be appended at the priorities they are present.  These items will be added
	 * to the end of the existing items with equal priorities, if there are any.
	 * @param mixed $data the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data): void
	{
		if ($data instanceof TPriorityList) {
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				for ($i = 0, $c = count($array[$priority]); $i < $c; $i++) {
					$this->insertAtIndexInPriority($array[$priority][$i], false, $priority);
				}
			}
		} elseif ($data instanceof TPriorityMap) {
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				foreach ($array[$priority] as $item) {
					$this->insertAtIndexInPriority($item, false, $priority);
				}
			}
		} elseif (is_array($data) || $data instanceof \Traversable) {
			foreach ($data as $item) {
				$this->insertAtIndexInPriority($item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to check on
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return ($offset >= 0 && $offset < $this->getCount());
	}

	/**
	 * Sets the element at the specified offset. This method is required by the interface \ArrayAccess.
	 * Setting elements in a priority list is not straight forword when appending and setting at the
	 * end boundary.  When appending without an offset (a null offset), the item will be added at
	 * the default priority.  The item may not be the last item in the list.  When appending with an
	 * offset equal to the count of the list, the item will get be appended with the last items priority.
	 *
	 * All together, when setting the location of an item, the item stays in that location, but appending
	 * an item into a priority list doesn't mean the item is at the end of the list.
	 * @param int $offset the offset to set element
	 * @param mixed $item the element value
	 */
	public function offsetSet($offset, $item): void
	{
		if ($offset === null) {
			$this->add($item);
			return;
		}
		if ($offset === $this->getCount()) {
			$priority = $this->priorityAt($offset, true);
		} else {
			$priority = $this->priorityAt($offset, true);
			$this->removeAtIndexInPriority($priority[1], $priority[0]);
		}
		$this->insertAtIndexInPriority($item, $priority[1], $priority[0]);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset): void
	{
		$this->removeAt($offset);
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 * @since 4.2.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_priorityZappableSleepProps($exprops);
	}
}
