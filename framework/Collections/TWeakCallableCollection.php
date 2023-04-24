<?php
/**
 * TWeakCallableCollection class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TPropertyValue;

use Closure;
use Traversable;
use WeakReference;

/**
 * TWeakCallableCollection class
 *
 * TWeakCallableCollection implements a priority ordered list collection of callables.
 * This extends {@link TPriorityList}.  This holds the callables for object event handlers
 * and global event handlers by converting all callable objects into a WeakReference.
 * TWeakCallableCollection prevents circular references in global events that would
 * otherwise block object destruction, and thus removal of the callable in __destruct.
 * All data out has the callable objects converted back to the regular object reference
 * in a callable.
 *
 * This uses PHP 8 WeakMap to track any system changes to the weak references of
 * objects the list is using -when {@link getDiscardInvalid DiscardInvalid} is true.
 * By default, when the map is read only then the items are not scrubbed, but the
 * scrubbing behavior can be enabled for read only lists. In this instance, no new
 * items can be added but only a list of valid  callables is kept.
 *
 * By default, lists that are mutable (aka. not read only) will discard invalid callables
 * automatically, but the scrubbing behavior can be disabled for mutable lists if needed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TWeakCallableCollection extends TPriorityList
{
	use TWeakCollectionTrait;

	/** @var bool Should invalid WeakReferences automatically be deleted from the list */
	private bool $_discardInvalid = true;

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param null|array|\Iterator|TPriorityList|TPriorityMap $data The initial data.
	 *   Default is null, meaning no initial data.
	 * @param ?bool $readOnly Whether the list is read-only
	 * @param ?numeric $defaultPriority The default priority of items without specified
	 *   priorities. Default null for 10.
	 * @paraum ?int $precision The precision of the numeric priorities.  Default null
	 *   for 8.
	 * @param ?bool $discardInvalid Whether or not to discard invalid WeakReferences.
	 *   Default null for the opposite of Read-Only.  Mutable Lists expunge invalid
	 *   WeakReferences and Read only lists do not.  Set this bool to override the default
	 *   behavior.
	 * @param null|mixed $precision
	 * @throws \Prado\Exceptions\TInvalidDataTypeException If data is not null and
	 *   is neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = null, $defaultPriority = null, $precision = null, $discardInvalid = null)
	{
		if ($readOnly === null) {
			$readOnly = false;
		}
		if ($discardInvalid === null) {
			$discardInvalid = !$readOnly;
		}
		$this->_discardInvalid = $discardInvalid;
		if ($discardInvalid) {
			$this->weakStart();
		}
		if ($defaultPriority === null) {
			$defaultPriority = 10;
		}
		if ($precision === null) {
			$precision = 8;
		}
		parent::__construct($data, $readOnly, $defaultPriority, $precision);
	}

	/**
	 * Cloning a TWeakCallableCollection requires cloning the WeakMap
	 * @since 4.2.3
	 */
	public function __clone()
	{
		$this->weakClone();
		parent::__clone();
	}

	/**
	 * Waking up a TWeakCallableCollection requires creating the WeakMap.  No items
	 * are saved in TWeakList so only initialization of the WeakMap is required.
	 * @since 4.2.3
	 */
	public function __wakeup()
	{
		if ($this->_discardInvalid) {
			$this->weakStart();
		}
		parent::__wakeup();
	}


	/**
	 * TWeakCallableCollection cannot auto listen to global events or there will be
	 * catastrophic recursion.
	 * @return bool returns false
	 */
	public function getAutoGlobalListen()
	{
		return false;
	}

	/**
	 * This converts the $items array of callable with WeakReferences back into the
	 * actual callable.
	 * @param array &$items an array of callable where objects are WeakReference
	 */
	protected function filterItemsForOutput(&$items)
	{
		if (!is_array($items)) {
			return;
		}
		for ($i = 0, $c = count($items); $i < $c; $i++) {
			$this->filterItemForOutput($items[$i]);
		}
	}


	/**
	 * This converts the $items array of callable with WeakReferences back into the
	 * actual callable.
	 * @param callable &$handler the $handler or $handler[0] may be a WeakReference
	 */
	protected function filterItemForOutput(&$handler)
	{
		if (is_array($handler) && is_object($handler[0]) && ($handler[0] instanceof WeakReference)) {
			if ($obj = $handler[0]->get()) {
				$handler[0] = $obj;
			} else {
				$handler = null;
			}
		} elseif (is_object($handler) && ($handler instanceof WeakReference)) {
			$handler = $handler->get();
		}
	}


	/**
	 * Converts the $handler callable into a WeakReference version for storage
	 * @param callable &$handler callable to convert into a WeakReference version
	 * @param bool $validate whether or not to validate the input as a callable
	 */
	protected function filterItemForInput(&$handler, $validate = false)
	{
		if ($validate && !is_callable($handler)) {
			throw new TInvalidDataValueException('weakcallablecollection_callable_required');
		}
		if (is_array($handler) && is_object($handler[0])) {
			$handler[0] = WeakReference::create($handler[0]);
		} elseif (is_object($handler) && !($handler instanceof Closure)) {
			$handler = WeakReference::create($handler);
		}
	}

	/**
	 * When a change in the WeakMap is detected, scrub the list of WeakReference that
	 * have lost their object.
	 * All invalid WeakReference[s] are optionally removed from the list when {@link
	 * getDiscardInvalid} is true.
	 * @since 4.2.3
	 */
	protected function scrubWeakReferences()
	{
		if (!$this->_discardInvalid || !$this->weakChanged()) {
			return;
		}
		foreach (array_keys($this->_d) as $priority) {
			for ($c = $i = count($this->_d[$priority]), $i--; $i >= 0; $i--) {
				$a = is_array($this->_d[$priority][$i]);
				if ($a && is_object($this->_d[$priority][$i][0]) && ($this->_d[$priority][$i][0] instanceof WeakReference) && $this->_d[$priority][$i][0]->get() === null ||
				!$a && is_object($this->_d[$priority][$i]) && ($this->_d[$priority][$i] instanceof WeakReference) && $this->_d[$priority][$i]->get() === null) {
					$c--;
					$this->_c--;
					if ($i === $c) {
						array_pop($this->_d[$priority]);
					} else {
						array_splice($this->_d[$priority], $i, 1);
					}
				}
			}
			if (!$c) {
				unset($this->_d[$priority]);
			}
		}
		$this->_fd = null;
		$this->weakResetCount();
	}

	/**
	 * @return bool Does the TWeakList scrub invalid WeakReferences.
	 * @since 4.2.3
	 */
	public function getDiscardInvalid(): bool
	{
		return $this->_discardInvalid;
	}

	/**
	 * All invalid WeakReference[s] are optionally removed from the list on $value
	 *  being "true".
	 * @param bool $value Sets the TWeakList scrubbing of invalid WeakReferences.
	 * @since 4.2.3
	 */
	protected function setDiscardInvalid($value): void
	{
		$value = TPropertyValue::ensureBoolean($value);
		if ($value && !$this->_discardInvalid) {
			$this->weakStart();
			foreach (array_keys($this->_d) as $priority) {
				for ($i = count($this->_d[$priority]) - 1; $i >= 0; $i--) {
					$a = is_array($this->_d[$priority][$i]);
					if ($a && is_object($this->_d[$priority][$i][0]) || !$a && is_object($this->_d[$priority][$i])) {
						$obj = $a ? $this->_d[$priority][$i][0] : $this->_d[$priority][$i];
						if ($obj instanceof WeakReference) {
							if($obj = $obj->get()) {
								$this->weakAdd($obj);
							} else {
								parent::removeAtIndexInPriority($i, $priority);
							}
						} else {
							$this->weakAdd($obj);
						}
					}
				}
			}
		} elseif (!$value && $this->_discardInvalid) {
			$this->weakStop();
		}
		$this->_discardInvalid = $value;
	}


	/**
	 * This flattens the priority list into a flat array [0,...,n-1]. This is needed to
	 * filter the output.
	 * All invalid WeakReference[s] are optionally removed from the list before flattening.
	 */
	protected function flattenPriorities(): void
	{
		$this->scrubWeakReferences();
		parent::flattenPriorities();
	}

	/**
	 * This returns a list of the priorities within this list, ordered lowest (first)
	 * to highest (last).
	 * All invalid WeakReference[s] are optionally removed from the list before getting
	 * the priorities.
	 * @return array the array of priority numerics in increasing priority number
	 * @since 4.2.3
	 */
	public function getPriorities(): array
	{
		$this->scrubWeakReferences();
		return parent::getPriorities();
	}

	/**
	 * Gets the number of items at a priority within the list.
	 * All invalid WeakReference[s] are optionally removed from the list before counting.
	 * @param null|numeric $priority optional priority at which to count items.  if no
	 *    parameter, it will be set to the default {@link getDefaultPriority}
	 * @return int the number of items in the list at the specified priority
	 * @since 4.2.3
	 */
	public function getPriorityCount($priority = null)
	{
		$this->scrubWeakReferences();
		return parent::getPriorityCount($priority);
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * This method is required by the interface \IteratorAggregate.
	 * @return \Iterator an iterator for traversing the items in the list.
	 * @since 4.2.3
	 */
	public function getIterator(): \Iterator
	{
		$this->flattenPriorities();
		$items = $this->_fd;
		$this->filterItemsForOutput($items);
		return new \ArrayIterator($items);
	}

	/**
	 * Returns the total number of items in the list
	 * All invalid WeakReference[s] are optionally removed from the list before counting.
	 * @return int the number of items in the list
	 * @since 4.2.3
	 */
	public function getCount(): int
	{
		$this->scrubWeakReferences();
		return parent::getCount();
	}

	/**
	 * Returns the item at the index of a flattened priority list. This is needed to
	 *  filter the output.  {@link offsetGet} calls this method.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index the index of the item to get
	 * @throws TInvalidDataValueException Issued when the index is invalid
	 * @return mixed the element at the offset
	 */
	public function itemAt($index)
	{
		$this->scrubWeakReferences();
		if ($index >= 0 && $index < $this->_c) {
			parent::flattenPriorities();
			$item = $this->_fd[$index];
			$this->filterItemForOutput($item);
			return $item;
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $index);
		}
	}

	/**
	 * Gets all the items at a specific priority. This is needed to filter the output.
	 * All invalid WeakReference[s] are optionally removed from the list before retrieving.
	 * @param null|numeric $priority priority of the items to get.  Defaults to null,
	 *    filled in with the default priority, if left blank.
	 * @return ?array all items at priority in index order, null if there are no items
	 *    at that priority
	 */
	public function itemsAtPriority($priority = null): ?array
	{
		$this->scrubWeakReferences();
		$items = parent::itemsAtPriority($priority);
		$this->filterItemsForOutput($items);
		return $items;
	}

	/**
	 * Returns the item at an index within a priority. This is needed to filter the
	 * output.
	 * All invalid WeakReference[s] are optionally removed from the list before retrieving.
	 * @param int $index the index into the list of items at priority
	 * @param null|numeric $priority the priority which to index.  no parameter or null
	 *   will result in the default priority
	 * @return mixed the element at the offset, false if no element is found at the offset
	 */
	public function itemAtIndexInPriority($index, $priority = null)
	{
		$this->scrubWeakReferences();
		$item = parent::itemAtIndexInPriority($index, $priority);
		$this->filterItemForOutput($item);
		return $item;
	}

	/**
	 * Inserts an item at an index.  It reads the priority of the item at index within the
	 * flattened list and then inserts the item at that priority-index.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index the specified position in the flattened list.
	 * @param mixed $item new item to add
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 * @since 4.2.3
	 */
	public function insertAt($index, $item)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityAt($index, true)) !== false) {
			$this->internalInsertAtIndexInPriority($item, $priority[1], $priority[0]);
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $index);
		}
	}

	/**
	 * Inserts an item at the specified index within a priority.  This scrubs the list and
	 * calls {@link internalInsertAtIndexInPriority}.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $item item to add within the list.
	 * @param null|false|int $index index within the priority to add the item, defaults to null
	 *    which appends the item at the priority
	 * @param null|numeric $priority priority of the item.  defaults to null, which sets it
	 *   to the default priority
	 * @param bool $preserveCache preserveCache specifies if this is a special quick function
	 *   or not. This defaults to false.
	 * @throws \Prado\Exceptions\TInvalidDataValueException If the index specified exceeds
	 *   the bound
	 * @throws \Prado\Exceptions\TInvalidOperationException if the list is read-only
	 */
	public function insertAtIndexInPriority($item, $index = null, $priority = null, $preserveCache = false)
	{
		$this->scrubWeakReferences();
		return $this->internalInsertAtIndexInPriority($item, $index, $priority, $preserveCache);
	}

	/**
	 * Inserts an item at the specified index within a priority.  This does not scrub the
	 * list of WeakReference.  This converts the item into a WeakReference if it is an object
	 * or contains an object in its callable.  This does not convert Closure into WeakReference.
	 * @param mixed $item item to add within the list.
	 * @param null|false|int $index index within the priority to add the item, defaults to null
	 *    which appends the item at the priority
	 * @param null|numeric $priority priority of the item.  defaults to null, which sets it
	 *    to the default priority
	 * @param bool $preserveCache preserveCache specifies if this is a special quick function
	 *    or not. This defaults to false.
	 * @throws \Prado\Exceptions\TInvalidDataValueException If the index specified exceeds the
	 *    bound
	 * @throws \Prado\Exceptions\TInvalidOperationException if the list is read-only
	 * @since 4.2.3
	 */
	protected function internalInsertAtIndexInPriority($item, $index = null, $priority = null, $preserveCache = false)
	{
		$itemPriority = null;
		if (($isPriorityItem = ($item instanceof IPriorityItem)) && ($priority === null || !is_numeric($priority))) {
			$itemPriority = $priority = $item->getPriority();
		}
		$priority = $this->ensurePriority($priority);
		if (($item instanceof IPriorityCapture) && (!$isPriorityItem || $itemPriority !== $priority)) {
			$item->setPriority($priority);
		}
		if (($isObj = is_object($item)) || is_array($item) && is_object($item[0])) {
			$this->weakAdd($isObj ? $item : $item[0]);
		}
		$this->filterItemForInput($item, true);
		return parent::insertAtIndexInPriority($item, $index, $priority, $preserveCache);
	}

	/**
	 * Removes an item from the priority list.
	 * The list will search for the item.  The first matching item found will be removed from
	 * the list.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $item item the item to be removed.
	 * @param null|bool|float $priority priority of item to remove. without this parameter it
	 *   defaults to false.  A value of false means any priority. null will be filled in with
	 *   the default priority.
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return int index within the flattened list at which the item is being removed
	 * @since 4.2.3
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
			$this->internalRemoveAtIndexInPriority($p[1], $p[0]);
			return $p[2];
		} else {
			throw new TInvalidDataValueException('list_item_inexistent');
		}
	}

	/**
	 * Removes an item at the specified index in the flattened list.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index index of the item to be removed.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 * @return mixed the removed item.
	 * @since 4.2.3
	 */
	public function removeAt($index)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityAt($index, true)) !== false) {
			return $this->internalRemoveAtIndexInPriority($priority[1], $priority[0]);
		}
		throw new TInvalidDataValueException('list_index_invalid', $index);
	}

	/**
	 * Removes the item at a specific index within a priority.  This is needed to filter
	 * the output.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index index of item to remove within the priority.
	 * @param null|numeric $priority priority of the item to remove, defaults to null,
	 *    or left blank, it is then set to the default priority
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return mixed the removed item.
	 */
	public function removeAtIndexInPriority($index, $priority = null)
	{
		$this->scrubWeakReferences();
		return $this->internalRemoveAtIndexInPriority($index, $priority);
	}

	/**
	 * Removes the item at a specific index within a priority.  This is needed to filter
	 * the output.
	 * @param int $index index of item to remove within the priority.
	 * @param null|numeric $priority priority of the item to remove, defaults to null, or
	 *    left blank, it is then set to the default priority
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return mixed the removed item.
	 * @since 4.2.3
	 */
	protected function internalRemoveAtIndexInPriority($index, $priority = null)
	{
		$item = parent::removeAtIndexInPriority($index, $priority);
		$this->filterItemForOutput($item);
		if (($isObj = is_object($item)) || is_array($item) && is_object($item[0])) {
			$this->weakRemove($obj = $isObj ? $item : $item[0]);
		}
		return $item;
	}

	/**
	 * Removes all items in the priority list by calling removeAtIndexInPriority from the
	 * last item to the first.
	 * @since 4.2.3
	 */
	public function clear(): void
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}

		$c = $this->_c;
		foreach (array_keys($this->_d) as $priority) {
			for ($index = count($this->_d[$priority]) - 1; $index >= 0; $index--) {
				parent::removeAtIndexInPriority($index, $priority);
			}
		}

		if ($c) {
			$this->weakRestart();
		}
	}

	/**
	 * @param mixed $item the item
	 * @return bool whether the list contains the item
	 * @since 4.2.3
	 */
	public function contains($item): bool
	{
		$this->filterItemForInput($item);
		if ($this->_fd !== null) {
			return array_search($item, $this->_fd, true) !== false;
		} else {
			foreach (array_keys($this->_d) as $priority) {
				if (array_search($item, $this->_d[$priority], true) !== false) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $item item being indexed.
	 * @return int the index of the item in the flattened list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		$this->flattenPriorities();
		$this->filterItemForInput($item);
		if (($index = array_search($item, $this->_fd, true)) === false) {
			return -1;
		} else {
			return $index;
		}
	}

	/**
	 * Returns the priority of a particular item.  This is needed to filter the input.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing
	 * when $withindex is "true" due to the more complex processing.
	 * @param mixed $item the item to look for within the list.
	 * @param bool $withindex this specifies if the full positional data of the item
	 *   within the list is returned.  This defaults to false, if no parameter is provided,
	 *   so only provides the priority number of the item by default.
	 * @return array|false|numeric the priority of the item in the list, false if not found.
	 *   if withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex,
	 *    2 => flattenedIndex, 'priority' => $priority, 'index' => $priorityIndex,
	 *   'absindex' => flattenedIndex]
	 */
	public function priorityOf($item, $withindex = false)
	{
		if ($withindex) {
			$this->scrubWeakReferences();
		}
		$this->filterItemForInput($item);
		return parent::priorityOf($item, $withindex);
	}

	/**
	 * Returns the priority of an item at a particular flattened index.  The index after
	 * the last item does not exist but receives a priority from the last item so that
	 * priority information about any new items being appended is available.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index index of the item within the list
	 * @param bool $withindex this specifies if the full positional data of the item
	 *   within the list is returned.  This defaults to false, if no parameter is provided,
	 *   so only provides the priority number of the item by default.
	 * @return array|false|numeric the priority of the item in the list, false if not found.
	 *   if $withindex is true, an array is returned of [0 => $priority, 1 => $priorityIndex,
	 *   2 => flattenedIndex, 'priority' => $priority, 'index' => $priorityIndex, 'absindex'
	 *   => flattenedIndex]
	 * @since 4.2.3
	 */
	public function priorityAt($index, $withindex = false)
	{
		$this->scrubWeakReferences();
		return parent::priorityAt($index, $withindex);
	}

	/**
	 * This inserts an item before another item within the list.  It uses the same priority
	 * as the found index item and places the new item before it.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $indexitem the item to index
	 * @param mixed $item the item to add before indexitem
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return int where the item has been inserted in the flattened list
	 * @since 4.2.3
	 */
	public function insertBefore($indexitem, $item)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityOf($indexitem, true)) === false) {
			throw new TInvalidDataValueException('list_item_inexistent');
		}

		$this->internalInsertAtIndexInPriority($item, $priority[1], $priority[0]);

		return $priority[2];
	}

	/**
	 * This inserts an item after another item within the list.  It uses the same priority
	 * as the found index item and places the new item after it.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $indexitem the item to index
	 * @param mixed $item the item to add after indexitem
	 * @throws TInvalidDataValueException If the item does not exist
	 * @return int where the item has been inserted in the flattened list
	 * @since 4.2.3
	 */
	public function insertAfter($indexitem, $item)
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if (($priority = $this->priorityOf($indexitem, true)) === false) {
			throw new TInvalidDataValueException('list_item_inexistent');
		}

		$this->internalInsertAtIndexInPriority($item, $priority[1] + 1, $priority[0]);

		return $priority[2] + 1;
	}

	/**
	 * All invalid WeakReference[s] are optionally removed from the list before returning.
	 * @return array the priority list of items in array
	 * @since 4.2.3
	 */
	public function toArray(): array
	{
		$this->flattenPriorities();
		$items = $this->_fd;
		$this->filterItemsForOutput($items);
		return $items;
	}


	/**
	 * All invalid WeakReference[s] are optionally removed from the list before returning.
	 * @return array the array of priorities keys with values of arrays of callables.
	 *   The priorities are sorted so important priorities, lower numerics, are first.
	 */
	public function toPriorityArray(): array
	{
		$this->scrubWeakReferences();
		$result = parent::toPriorityArray();
		foreach (array_keys($result) as $key) {
			$this->filterItemsForOutput($result[$key]);
		}
		return $result;
	}


	/**
	 * All invalid WeakReference[s] are optionally removed from the list before returning.
	 * @return array the array of priorities keys with values of arrays of callables with
	 *   WeakReference rather than objects.  The priorities are sorted so important priorities,
	 *   lower numerics, are first.
	 */
	public function toPriorityArrayWeak()
	{
		$this->scrubWeakReferences();
		return parent::toPriorityArray();
	}

	/**
	 * Combines the map elements which have a priority below the parameter value.  This
	 * is needed to filter the output.
	 * All invalid WeakReference[s] are optionally removed from the list before returning.
	 * @param numeric $priority the cut-off priority.  All items of priority less than
	 *   this are returned.
	 * @param bool $inclusive whether or not the input cut-off priority is inclusive.
	 *   Default: false, not inclusive.
	 * @return array the array of priorities keys with values of arrays of items that
	 *   are below a specified priority.  The priorities are sorted so important priorities,
	 *   lower numerics, are first.
	 * @since 4.2.3
	 */
	public function toArrayBelowPriority($priority, bool $inclusive = false): array
	{
		$this->scrubWeakReferences();
		$items = parent::toArrayBelowPriority($priority, $inclusive);
		$this->filterItemsForOutput($items);
		return $items;
	}

	/**
	 * Combines the map elements which have a priority above the parameter value. This
	 * is needed to filter the output.
	 * All invalid WeakReference[s] are optionally removed from the list before returning.
	 * @param numeric $priority the cut-off priority.  All items of priority greater
	 *   than this are returned.
	 * @param bool $inclusive whether or not the input cut-off priority is inclusive.
	 *   Default: true, inclusive.
	 * @return array the array of priorities keys with values of arrays of items that
	 *   are above a specified priority.  The priorities are sorted so important priorities,
	 *   lower numerics, are first.
	 * @since 4.2.3
	 */
	public function toArrayAbovePriority($priority, bool $inclusive = true): array
	{
		$this->scrubWeakReferences();
		$items = parent::toArrayAbovePriority($priority, $inclusive);
		$this->filterItemsForOutput($items);
		return $items;
	}

	/**
	 * Copies iterable data into the list.
	 * Note, existing data in the list will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing
	 *   Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor a Traversable.
	 * @since 4.2.3
	 */
	public function copyFrom($data): void
	{
		if ($data instanceof TPriorityList) {
			if ($this->_c > 0) {
				$this->clear();
			}
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				for ($i = 0, $c = count($array[$priority]); $i < $c; $i++) {
					$this->internalInsertAtIndexInPriority($array[$priority][$i], null, $priority);
				}
			}
		} elseif ($data instanceof TPriorityMap) {
			if ($this->_c > 0) {
				$this->clear();
			}
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				foreach ($array[$priority] as $item) {
					$this->internalInsertAtIndexInPriority($item, null, $priority);
				}
			}
		} elseif (is_array($data) || ($data instanceof Traversable)) {
			if ($this->_c > 0) {
				$this->clear();
			}
			foreach ($data as $item) {
				$this->internalInsertAtIndexInPriority($item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('list_data_not_iterable');
		}
	}

	/**
	 * Merges iterable data into the priority list.
	 * New data will be appended to the end of the existing data.  If another TPriorityList
	 * is merged, the incoming parameter items will be appended at the priorities they are
	 * present.  These items will be added to the end of the existing items with equal
	 * priorities, if there are any.
	 * @param mixed $data the data to be merged with, must be an array or object implementing
	 *   Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 * @since 4.2.3
	 */
	public function mergeWith($data): void
	{
		if ($data instanceof TPriorityList) {
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				for ($i = 0, $c = count($array[$priority]); $i < $c; $i++) {
					$this->internalInsertAtIndexInPriority($array[$priority][$i], null, $priority);
				}
			}
		} elseif ($data instanceof TPriorityMap) {
			$array = $data->toPriorityArray();
			foreach (array_keys($array) as $priority) {
				foreach ($array[$priority] as $item) {
					$this->internalInsertAtIndexInPriority($item, null, $priority);
				}
			}
		} elseif (is_array($data) || ($data instanceof Traversable)) {
			foreach ($data as $item) {
				$this->internalInsertAtIndexInPriority($item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('list_data_not_iterable');
		}
	}

	/**
	 * Sets the element at the specified offset. This method is required by the interface
	 * \ArrayAccess.  Setting elements in a priority list is not straight forword when
	 * appending and setting at the end boundary.  When appending without an offset (a
	 * null offset), the item will be added at the default priority.  The item may not be
	 * the last item in the list.  When appending with an offset equal to the count of the
	 * list, the item will get be appended with the last items priority.
	 *
	 * All together, when setting the location of an item, the item stays in that location,
	 * but appending an item into a priority list doesn't mean the item is at the end of
	 * the list.
	 *
	 * All invalid WeakReference[s] are optionally removed from the list when an $offset
	 * is given.
	 * @param int $offset the offset to set element
	 * @param mixed $item the element value
	 * @since 4.2.3
	 */
	public function offsetSet($offset, $item): void
	{
		if ($this->getReadOnly()) {
			throw new TInvalidOperationException('list_readonly', $this::class);
		}

		if ($offset === null) {
			$this->internalInsertAtIndexInPriority($item, null, null, true);
			return;
		}
		if (0 <= $offset && $offset <= ($count = $this->getCount())) {
			$priority = parent::priorityAt($offset, true);
			if ($offset !== $count) {
				$this->internalRemoveAtIndexInPriority($priority[1], $priority[0]);
			}
		} else {
			throw new TInvalidDataValueException('list_index_invalid', $offset);
		}
		$this->internalInsertAtIndexInPriority($item, $priority[1], $priority[0]);
	}

	/**
	 * Returns an array with the names of all variables of this object that should
	 * NOT be serialized because their value is the default one or useless to be cached
	 * for the next page loads.  Reimplement in derived classes to add new variables,
	 * but remember to  also to call the parent implementation first.
	 * @param array $exprops by reference
	 * @since 4.2.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		$c = $this->_c;
		$this->_c = 0;
		parent::_getZappableSleepProps($exprops);
		$this->_c = $c;

		$this->_weakZappableSleepProps($exprops);
		if ($this->_discardInvalid === true) {
			$exprops[] = "\0" . __CLASS__ . "\0_discardInvalid";
		}
	}
}
