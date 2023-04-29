<?php
/**
 * TPriorityMap, TPriorityMapIterator classes
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TPropertyValue;

/**
 * TPriorityMap class
 *
 * TPriorityMap implements a collection that takes key-value pairs with
 * a priority to allow key-value pairs to be ordered.  This ordering is
 * important when flattening the map. When flattening the map, if some
 * key-value pairs are required to be before or after others, use this
 * class to keep order to your map.
 *
 * You can access, add or remove an item with a key by using
 * {@link itemAt}, {@link add}, and {@link remove}.  These functions
 * can optionally take a priority parameter to allow access to specific
 * priorities.  TPriorityMap is functionally backward compatible
 * with {@link TMap}.
 *
 * To get the number of the items in the map, use {@link getCount}.
 * TPriorityMap can also be used like a regular array as follows,
 * <code>
 * $map[$key]=$value; // add a key-value pair
 * unset($map[$key]); // remove the value with the specified key
 * if(isset($map[$key])) // if the map contains the key
 * foreach($map as $key=>$value) // traverse the items in the map
 * $n=count($map);  // returns the number of items in the map
 * </code>
 * Using standard array access method like these will always use
 * the default priority.
 *
 * An item that doesn't specify a priority will receive the default
 * priority.  The default priority is set during the instantiation
 * of a new TPriorityMap. If no custom default priority is specified,
 * the standard default priority of 10 is used.
 *
 * Priorities with significant digits below precision will be rounded.
 *
 * A priority may also be a numeric with decimals.  This is set
 * during the instantiation of a new TPriorityMap.
 * The default is 8 decimal places for a priority.  If a negative number
 * is used, rounding occurs into the integer space rather than in
 * the decimal space.  See {@link round}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.2a
 * @method void dyAddItem(mixed $key, mixed $value)
 * @method void dyRemoveItem(mixed $key, mixed $value)
 * @method mixed dyNoItem(mixed $returnValue, mixed $key)
 */
class TPriorityMap extends TMap
{
	use TPriorityCollectionTrait;

	/**
	 * @var int number of items contain within the map
	 */
	protected int $_c = 0;

	/** @var int The next highest integer key at which we can add an item. */
	protected int $_ic = 0;

	/**
	 * Constructor.
	 * Initializes the array with an array or an iterable object.
	 * @param null|array|TPriorityList|TPriorityMap|\Traversable $data the initial data. Default is null, meaning no initialization.
	 * @param ?bool $readOnly whether the list is read-only
	 * @param numeric $defaultPriority the default priority of items without specified
	 *   priorities.  Default null for 10.
	 * @param int $precision the precision of the numeric priorities.  Default null for 8.
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = null, $defaultPriority = null, $precision = null)
	{
		$this->setPrecision($precision);
		$this->setDefaultPriority($defaultPriority);
		parent::__construct($data, $readOnly);
	}

	/**
	 * This is required for TPriorityCollectionTrait to determine the style of combining
	 * arrays.
	 * @return bool This returns false for array_replace (map style).  true would be
	 *   array_merge (list style).
	 */
	private function getPriorityCombineStyle(): bool
	{
		return false;
	}

	/**
	 * @return int This is the key for the next appended item that doesn't have its
	 *   own key.
	 */
	public function getNextIntegerKey(): int
	{
		return $this->_ic;
	}

	/**
	 * @return int the number of items in the map
	 */
	public function getCount(): int
	{
		return $this->_c;
	}

	/**
	 * Returns the keys within the map ordered through the priority of each key-value pair
	 * @return array the key list
	 */
	public function getKeys(): array
	{
		$this->sortPriorities();
		return array_merge(...array_map('array_keys', $this->_d));
	}

	/**
	 * Returns the item with the specified key.  If a priority is specified, only items
	 * within that specific priority will be selected.
	 * @param mixed $key the key
	 * @param null|false|numeric $priority the priority.  null is the default priority, false is any priority,
	 *    and numeric is a specific priority.  default: false, any priority.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAt($key, $priority = false)
	{
		if ($priority === false) {
			$this->flattenPriorities();
			return array_key_exists($key, $this->_fd) ? $this->_fd[$key] : $this->dyNoItem(null, $key);
		} else {
			$priority = $this->ensurePriority($priority);
			return (isset($this->_d[$priority]) && array_key_exists($key, $this->_d[$priority])) ? $this->_d[$priority][$key] : $this->dyNoItem(null, $key);
		}
	}

	/**
	 * This changes an item's priority.  Specify the item and the new priority.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed $key the key
	 * @param null|numeric $priority the priority.  default: null, filled in with the default priority numeric.
	 * @return numeric old priority of the item
	 */
	public function setPriorityAt($key, $priority = null)
	{
		$priority = $this->ensurePriority($priority);
		$oldpriority = $this->priorityAt($key);
		if ($oldpriority !== false && $oldpriority != $priority) {
			$value = $this->remove($key, $oldpriority);
			$this->add($key, $value, $priority);
		}
		return $oldpriority;
	}

	/**
	 * Returns the priority of a particular item within the map.  This searches the map for the item.
	 * @param mixed $item item to look for within the map
	 * @return false|numeric priority of the item in the map.  False if not found.
	 */
	public function priorityOf($item)
	{
		$this->sortPriorities();
		foreach (array_keys($this->_d) as $priority) {
			if (($index = array_search($item, $this->_d[$priority], true)) !== false) {
				return $priority;
			}
		}
		return false;
	}

	/**
	 * Returns the priority of an item at a particular key.  This searches the map for the item.
	 * @param mixed $key index of the item within the map
	 * @return false|numeric priority of the item in the map. False if not found.
	 */
	public function priorityAt($key)
	{
		$this->sortPriorities();
		foreach (array_keys($this->_d) as $priority) {
			if (array_key_exists($key, $this->_d[$priority])) {
				return $priority;
			}
		}
		return false;
	}

	/**
	 * Adds an item into the map.  A third parameter may be used to set the priority
	 * of the item within the map.  Priority is primarily used during when flattening
	 * the map into an array where order may be and important factor of the key-value
	 * pairs within the array.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * No duplicate keys are allowed regardless of priority.
	 * @param mixed $key
	 * @param mixed $value
	 * @param null|numeric $priority default: null, filled in with default priority
	 * @throws TInvalidOperationException if the map is read-only
	 * @return numeric priority at which the key-value pair was added
	 */
	public function add($key, $value, $priority = null)
	{
		$itemPriority = null;
		if (($isPriorityItem = ($value instanceof IPriorityItem)) && ($priority === null || !is_numeric($priority))) {
			$itemPriority = $priority = $value->getPriority();
		}
		$priority = $this->ensurePriority($priority);
		if (($value instanceof IPriorityCapture) && (!$isPriorityItem || $itemPriority !== $priority)) {
			$value->setPriority($priority);
		}

		if (!$this->getReadOnly()) {
			$this->ensureReadOnly();
			if ($key === null) {
				$key = $this->_ic++;
			} elseif (is_numeric($key)) {
				$this->_ic = (int) max($this->_ic, floor($key) + 1);
			}
			foreach (array_keys($this->_d) as $innerpriority) {
				if (array_key_exists($key, $this->_d[$innerpriority])) {
					unset($this->_d[$innerpriority][$key]);
					$this->_c--;
					if (count($this->_d[$innerpriority]) === 0) {
						unset($this->_d[$innerpriority]);
					}
					break;
				}
			}
			if (!isset($this->_d[$priority])) {
				$this->_d[$priority] = [$key => $value];
				$this->_o = false;
			} else {
				$this->_d[$priority][$key] = $value;
			}
			$this->_c++;
			$this->_fd = null;
			$this->dyAddItem($key, $value);
		} else {
			throw new TInvalidOperationException('map_readonly', $this::class);
		}
		return $priority;
	}

	/**
	 * Removes an item from the map by its key. If no priority, or false, is specified
	 * then priority is irrelevant. If null is used as a parameter for priority, then
	 * the priority will be the default priority.  If a priority is specified, or
	 * the default priority is specified, only key-value pairs in that priority
	 * will be affected.
	 * @param mixed $key the key of the item to be removed
	 * @param null|false|numeric $priority priority.  False is any priority, null is the
	 * default priority, and numeric is a specific priority
	 * @throws TInvalidOperationException if the map is read-only
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key, $priority = false)
	{
		if (!$this->getReadOnly()) {
			if ($priority === false) {
				$this->sortPriorities();
				foreach (array_keys($this->_d) as $priority) {
					if (array_key_exists($key, $this->_d[$priority])) {
						$value = $this->_d[$priority][$key];
						unset($this->_d[$priority][$key]);
						$this->_c--;
						if (count($this->_d[$priority]) === 0) {
							unset($this->_d[$priority]);
							$this->_o = false;
						}
						$this->_fd = null;
						$this->dyRemoveItem($key, $value);
						return $value;
					}
				}
				return null;
			} else {
				$priority = $this->ensurePriority($priority);
				if (isset($this->_d[$priority]) && (isset($this->_d[$priority][$key]) || array_key_exists($key, $this->_d[$priority]))) {
					$value = $this->_d[$priority][$key];
					unset($this->_d[$priority][$key]);
					$this->_c--;
					if (count($this->_d[$priority]) === 0) {
						unset($this->_d[$priority]);
						$this->_o = false;
					}
					$this->_fd = null;
					$this->dyRemoveItem($key, $value);
					return $value;
				} else {
					return null;
				}
			}
		} else {
			throw new TInvalidOperationException('map_readonly', $this::class);
		}
	}

	/**
	 * Removes all items in the map.  {@link remove} is called on all items.
	 */
	public function clear(): void
	{
		foreach (array_keys($this->_d) as $priority) {
			foreach (array_keys($this->_d[$priority]) as $key) {
				$this->remove($key);
			}
		}
		$this->_ic = 0;
	}

	/**
	 * @param mixed $key the key
	 * @return bool whether the map contains an item with the specified key
	 */
	public function contains($key): bool
	{
		$this->flattenPriorities();
		return isset($this->_fd[$key]) || array_key_exists($key, $this->_fd);
	}

	/**
	 * Copies iterable data into the map.
	 * Note, existing data in the map will be cleared first.
	 * @param array|TPriorityList|TPriorityMap|\Traversable $data the data to be copied from, must be an array, object implementing
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function copyFrom($data): void
	{
		if ($data instanceof TPriorityMap) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			foreach ($data->getPriorities() as $priority) {
				foreach ($data->itemsAtPriority($priority) as $key => $value) {
					$this->add($key, $value, $priority);
				}
			}
		} elseif ($data instanceof TPriorityList) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			$index = 0;
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				for ($i = 0, $c = count($array[$priority]); $i < $c; $i++) {
					$this->add(null, $array[$priority][$i], $priority);
				}
			}
		} elseif (is_array($data) || $data instanceof \Traversable) {
			if ($this->getCount() > 0) {
				$this->clear();
			}
			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
	}

	/**
	 * Merges iterable data into the map.
	 * Existing data in the map will be kept and overwritten if the keys are the same.
	 * @param array|TPriorityList|TPriorityMap|\Traversable $data the data to be merged with, must be an array,
	 * object implementing Traversable, or a TPriorityMap
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data): void
	{
		if ($data instanceof TPriorityMap) {
			foreach ($data->getPriorities() as $priority) {
				foreach ($data->itemsAtPriority($priority) as $key => $value) {
					$this->add($key, $value, $priority);
				}
			}
		} elseif ($data instanceof TPriorityList) {
			$index = 0;
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				for ($i = 0, $c = count($array[$priority]); $i < $c; $i++) {
					$this->add(null, $array[$priority][$i], $priority);
				}
			}
		} elseif (is_array($data) || $data instanceof \Traversable) {
			foreach ($data as $key => $value) {
				$this->add($key, $value);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('map_data_not_iterable');
		}
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
		if ($this->_c === 0) {
			$exprops[] = "\0*\0_c";
		}
		if ($this->_ic === 0) {
			$exprops[] = "\0*\0_ic";
		}
		$this->_priorityZappableSleepProps($exprops);
	}
}
