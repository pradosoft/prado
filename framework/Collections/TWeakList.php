<?php
/**
 * TWeakList class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TEventHandler;
use Prado\TPropertyValue;

use ArrayAccess;
use Closure;
use Traversable;
use WeakReference;

/**
 * TWeakList class
 *
 * TWeakList implements an integer-indexed collection class with objects kept as
 * WeakReference.  Closure are treated as function PHP types rather than as objects.
 *
 * Objects in the TWeakList are encoded into WeakReference when saved, and the objects
 * restored on retrieval.  When an object becomes unset in the application/system
 * and its WeakReference invalidated, it can be removed from the TWeakList or have
 * a null in place of the object, depending on the mode.  The mode can be set during
 * {@see __construct Construct}.  The default mode of the TWeakList is to maintain
 * a list of only valid objects -where the count and item locations can change when
 * an item is invalidated-.  The other mode is to retain the place of invalidated
 * objects and replace the object with null -maintaining the count and item locations-.
 *
 * List items do not need to be objects.  TWeakList is similar to TList except list
 * items that are objects (except Closure and IWeakRetainable) are stored as WeakReference.
 * List items that are arrays are recursively traversed for replacement of objects
 * with WeakReference before storing.  In this way, TWeakList will not retain objects
 * (incrementing their use/reference counter) that it contains.  Only primary list
 * items are tracked with the WeakMap, and objects in arrays has no effect on the whole.
 * If an object in an array is invalidated, it well be replaced by "null".  Arrays
 * in the TWeakList are kept regardless of the use/reference count of contained objects.
 *
 * When searching by a {@see \Prado\TEventHandler} object, it will only find itself and
 * will not match on its {@see \Prado\TEventHandler::getHandler}.  However, if searching
 * for a callable handler, it will first match direct callable handlers in the list,
 * and then search for matching TEventHandlers' Handler regardless of the data.
 *
 * {@see \Prado\Collections\TWeakCollectionTrait} implements a PHP 8 WeakMap used to track any changes
 * in WeakReference objects in the TWeakList and optionally scrubs the list of invalid
 * objects on any changes to the WeakMap.
 *
 * Note that any objects or objects in arrays will be lost if they are not otherwise
 * retained in other parts of the application.  The only exception is a PHP Closure.
 * Closures are stored without WeakReference so anonymous functions can be stored
 * without risk of deletion if it is the only reference.  Closures act similarly to
 * a PHP data type rather than an object.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TWeakList extends TList implements IWeakCollection, ICollectionFilter
{
	use TWeakCollectionTrait;

	/** @var ?bool Should invalid WeakReference automatically be deleted from the list.
	 *    Default True.
	 */
	private ?bool $_discardInvalid = null;

	/** @var int The number of TEventHandlers in the list */
	private int $_eventHandlerCount = 0;

	/**
	 * Constructor.
	 * Initializes the weak list with an array or an iterable object.
	 * @param null|array|\Iterator $data The initial data. Default is null, meaning no initialization.
	 * @param ?bool $readOnly Whether the list is read-only. Default is null.
	 * @param ?bool $discardInvalid Whether the list is scrubbed of invalid WeakReferences.
	 *   Default is null for the opposite of $readOnly.  Thus, Read Only lists retain
	 *   invalid WeakReference; and Mutable lists scrub invalid WeakReferences.
	 * @throws TInvalidDataTypeException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data = null, $readOnly = null, $discardInvalid = null)
	{
		parent::__construct($data, $readOnly);
		$this->setDiscardInvalid($discardInvalid);
	}

	/**
	 * Cloning a TWeakList requires cloning the WeakMap
	 */
	public function __clone()
	{
		$this->weakClone();
		parent::__clone();
	}

	/**
	 * Waking up a TWeakList requires creating the WeakMap.  No items are saved in
	 * TWeakList so only initialization of the WeakMap is required.
	 */
	public function __wakeup()
	{
		if ($this->_discardInvalid) {
			$this->weakStart();
		}
		parent::__wakeup();
	}

	/**
	 * This is a custom function for adding objects to the weak map.  Specifically,
	 * if the object being added is a TEventHandler, we use the {@see \Prado\TEventHandler::getHandlerObject}
	 * object instead of the TEventHandler itself.
	 * @param object $object The object to add to the managed weak map.
	 * @since 4.2.3
	 */
	protected function weakCustomAdd(object $object)
	{
		if($object instanceof TEventHandler) {
			$object = $object->getHandlerObject();
			$this->_eventHandlerCount++;
		}
		return $this->weakAdd($object);
	}

	/**
	 * This is a custom function for removing objects to the weak map.  Specifically,
	 * if the object being removed is a TEventHandler, we use the {@see \Prado\TEventHandler::getHandlerObject}
	 * object instead of the TEventHandler itself.
	 * @param object $object The object to remove to the managed weak map.
	 * @since 4.2.3
	 */
	protected function weakCustomRemove(object $object)
	{
		if($object instanceof TEventHandler) {
			$object = $object->getHandlerObject();
			$this->_eventHandlerCount--;
		}
		return $this->weakRemove($object);
	}

	/**
	 * Converts the $item callable that has WeakReference rather than the actual object
	 * back into a regular callable.
	 * @param mixed &$item
	 */
	public static function filterItemForOutput(&$item): void
	{
		if (is_array($item)) {
			foreach (array_keys($item) as $key) {
				static::filterItemForOutput($item[$key]);
			}
		} elseif ($item instanceof Traversable && $item instanceof ArrayAccess) {
			foreach ($item as $key => $element) {
				static::filterItemForOutput($element);
				$item[$key] = $element;
			}
		} elseif (is_object($item)) {
			if($item instanceof WeakReference) {
				$item = $item->get();
			} elseif (($item instanceof TEventHandler) && !$item->hasHandler()) {
				$item = null;
			}
		}
	}

	/**
	 * Converts the $item object and objects in an array into their WeakReference version
	 * for storage.  Closure[s] are not converted into WeakReference and so act like a
	 * basic PHP type.  Closures are added to the the WeakMap cache but has no weak
	 * effect because the TWeakList maintains references to Closure[s] preventing their
	 * invalidation.
	 * @param mixed &$item object to convert into a WeakReference where needed.
	 */
	public static function filterItemForInput(&$item): void
	{
		if (is_array($item)) {
			foreach (array_keys($item) as $key) {
				static::filterItemForInput($item[$key]);
			}
		} elseif ($item instanceof Traversable && $item instanceof ArrayAccess) {
			foreach ($item as $key => $element) {
				static::filterItemForInput($element);
				$item[$key] = $element;
			}
		} elseif (is_object($item) && !($item instanceof WeakReference) && !($item instanceof Closure) && !($item instanceof IWeakRetainable)) {
			$item = WeakReference::create($item);
		}
	}

	/**
	 * When a change in the WeakMap is detected, scrub the list of invalid WeakReference.
	 */
	protected function scrubWeakReferences(): void
	{
		if (!$this->getDiscardInvalid() || !$this->weakChanged()) {
			return;
		}
		for ($i = $this->_c - 1; $i >= 0; $i--) {
			if (is_object($this->_d[$i])) {
				$object = $this->_d[$i];
				if ($isEventHandler = ($object instanceof TEventHandler)) {
					$object = $object->getHandlerObject(true);
				}
				if(($object instanceof WeakReference) && $object->get() === null) {
					$this->_c--;
					if ($i === $this->_c) {
						array_pop($this->_d);
					} else {
						array_splice($this->_d, $i, 1);
					}
					if ($isEventHandler) {
						$this->_eventHandlerCount--;
					}
				}
			}
		}
		$this->weakResetCount();
	}

	/**
	 * @return bool Does the TWeakList scrub invalid WeakReference.
	 */
	public function getDiscardInvalid(): bool
	{
		$this->collapseDiscardInvalid();
		return $this->_discardInvalid;
	}

	/**
	 * Ensures that DiscardInvalid is set.
	 */
	protected function collapseDiscardInvalid()
	{
		if ($this->_discardInvalid === null) {
			$this->setDiscardInvalid(!$this->getReadOnly());
		}
	}

	/**
	 * @param bool $value Sets the TWeakList scrubbing of invalid WeakReference.
	 */
	public function setDiscardInvalid($value): void
	{
		if($value === $this->_discardInvalid) {
			return;
		}
		if ($this->_discardInvalid !== null && !Prado::isCallingSelf()) {
			throw new TInvalidOperationException('weak_no_set_discard_invalid', $this::class);
		}
		$value = TPropertyValue::ensureBoolean($value);
		if ($value && !$this->_discardInvalid) {
			$this->weakStart();
			for ($i = $this->_c - 1; $i >= 0; $i--) {
				if (is_object($this->_d[$i])) {
					$object = $this->_d[$i];
					if ($isEventHandler = ($object instanceof TEventHandler)) {
						$object = $object->getHandlerObject(true);
					}
					if ($object instanceof WeakReference) {
						$object = $object->get();
					}
					if ($object === null) {
						$this->_c--;	//on read only, parent::removeAt won't remove for scrub.
						if ($i === $this->_c) {
							array_pop($this->_d);
						} else {
							array_splice($this->_d, $i, 1);
						}
						if ($isEventHandler) {
							$this->_eventHandlerCount--;
						}
					} else {
						$this->weakAdd($object);
					}
				}
			}
		} elseif (!$value && $this->_discardInvalid) {
			$this->weakStop();
		}
		$this->_discardInvalid = $value;
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface \IteratorAggregate.
	 * All invalid WeakReference[s] are optionally removed from the iterated list.
	 * @return \Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->toArray());
	}

	/**
	 * All invalid WeakReference[s] are optionally removed from the list before counting.
	 * @return int the number of items in the list
	 */
	public function getCount(): int
	{
		$this->scrubWeakReferences();
		return parent::getCount();
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is exactly the same as {@see offsetGet}.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index the index of the item
	 * @throws TInvalidDataValueException if the index is out of the range
	 * @return mixed the item at the index
	 */
	public function itemAt($index)
	{
		$this->scrubWeakReferences();
		$item = parent::itemAt($index);
		$this->filterItemForOutput($item);
		return $item;
	}

	/**
	 * Appends an item at the end of the list.
	 * All invalid WeakReference[s] are optionally removed from the list before adding
	 * for proper indexing.
	 * @param mixed $item new item
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the zero-based index at which the item is added
	 */
	public function add($item)
	{
		$this->collapseDiscardInvalid();
		$this->scrubWeakReferences();
		if (is_object($item)) {
			$this->weakCustomAdd($item);
		}
		$this->filterItemForInput($item);
		parent::insertAt($this->_c, $item);
		return $this->_c - 1;
	}

	/**
	 * Inserts an item at the specified position.
	 * Original item at the position and the next items
	 * will be moved one step towards the end.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function insertAt($index, $item)
	{
		$this->collapseDiscardInvalid();
		$this->scrubWeakReferences();
		if (is_object($item)) {
			$this->weakCustomAdd($item);
		}
		$this->filterItemForInput($item);
		parent::insertAt($index, $item);
	}

	/**
	 * Removes an item from the list.
	 * The list will first search for the item.
	 * The first item found will be removed from the list.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $item the item to be removed.
	 * @throws TInvalidDataValueException If the item does not exist
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the index at which the item is being removed
	 */
	public function remove($item)
	{
		if (!$this->getReadOnly()) {
			if (($index = $this->indexOf($item)) !== -1) {
				if (is_object($item)) {
					$this->weakCustomRemove($item);
				}
				parent::removeAt($index);
				return $index;
			} else {
				throw new TInvalidDataValueException('list_item_inexistent');
			}
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * Removes an item at the specified position.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $index the index of the item to be removed.
	 * @throws TInvalidDataValueException If the index specified exceeds the bound
	 * @throws TInvalidOperationException if the list is read-only
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$this->scrubWeakReferences();
		$item = parent::removeAt($index);
		$this->filterItemForOutput($item);
		if (is_object($item)) {
			$this->weakCustomRemove($item);
		}
		return $item;
	}

	/**
	 * Removes all items in the list and resets the Weak Cache.
	 * @throws TInvalidOperationException if the list is read-only
	 */
	public function clear(): void
	{
		$c = $this->_c;
		for ($i = $this->_c - 1; $i >= 0; --$i) {
			parent::removeAt($i);
		}
		if ($c) {
			$this->weakRestart();
		}
	}

	/**
	 * @param mixed $item the item
	 * @return bool whether the list contains the item
	 */
	public function contains($item): bool
	{
		return $this->indexOf($item) !== -1;
	}

	/**
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $item the item
	 * @return int the index of the item in the list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		$this->scrubWeakReferences();
		$this->filterItemForInput($item);
		if (($index = parent::indexOf($item)) === -1 && $this->_eventHandlerCount) {
			$index = false;
			foreach($this->_d as $index => $dItem) {
				if (($dItem instanceof TEventHandler) && $dItem->isSameHandler($item, true)) {
					break;
				}
				$index = false;
			}
		}
		if ($index === false) {
			return -1;
		} else {
			return $index;
		}
	}

	/**
	 * Finds the base item.  If found, the item is inserted before it.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $baseitem the base item which will be pushed back by the second parameter
	 * @param mixed $item the item
	 * @throws TInvalidDataValueException if the base item is not within this list
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the index where the item is inserted
	 */
	public function insertBefore($baseitem, $item)
	{
		if (!$this->getReadOnly()) {
			if (($index = $this->indexOf($baseitem)) === -1) {
				throw new TInvalidDataValueException('list_item_inexistent');
			}
			if (is_object($item)) {
				$this->weakCustomAdd($item);
			}
			$this->filterItemForInput($item);
			parent::insertAt($index, $item);
			return $index;
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * Finds the base item.  If found, the item is inserted after it.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param mixed $baseitem the base item which comes before the second parameter when added to the list
	 * @param mixed $item the item
	 * @throws TInvalidDataValueException if the base item is not within this list
	 * @throws TInvalidOperationException if the list is read-only
	 * @return int the index where the item is inserted
	 */
	public function insertAfter($baseitem, $item)
	{
		if (!$this->getReadOnly()) {
			if (($index = $this->indexOf($baseitem)) === -1) {
				throw new TInvalidDataValueException('list_item_inexistent');
			}
			if (is_object($item)) {
				$this->weakCustomAdd($item);
			}
			$this->filterItemForInput($item);
			parent::insertAt($index + 1, $item);
			return $index + 1;
		} else {
			throw new TInvalidOperationException('list_readonly', get_class($this));
		}
	}

	/**
	 * All invalid WeakReference[s] are optionally removed from the list.
	 * @return array the list of items in array
	 */
	public function toArray(): array
	{
		$this->scrubWeakReferences();
		$items = $this->_d;
		$this->filterItemForOutput($items);
		return $items;
	}

	/**
	 * Copies iterable data into the list.
	 * Note, existing data in the list will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor a Traversable.
	 */
	public function copyFrom($data): void
	{
		if (is_array($data) || ($data instanceof Traversable)) {
			if ($this->_c > 0) {
				$this->clear();
			}
			foreach ($data as $item) {
				if (is_object($item)) {
					$this->weakCustomAdd($item);
				}
				$this->filterItemForInput($item);
				parent::insertAt($this->_c, $item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('list_data_not_iterable');
		}
	}

	/**
	 * Merges iterable data into the map.
	 * New data will be appended to the end of the existing data.
	 * @param mixed $data the data to be merged with, must be an array or object implementing Traversable
	 * @throws TInvalidDataTypeException If data is neither an array nor an iterator.
	 */
	public function mergeWith($data): void
	{
		if (is_array($data) || ($data instanceof Traversable)) {
			foreach ($data as $item) {
				if (is_object($item)) {
					$this->weakCustomAdd($item);
				}
				$this->filterItemForInput($item);
				parent::insertAt($this->_c, $item);
			}
		} elseif ($data !== null) {
			throw new TInvalidDataTypeException('list_data_not_iterable');
		}
	}

	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $offset the offset to check on
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return ($offset >= 0 && $offset < $this->getCount());
	}

	/**
	 * Sets the item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * All invalid WeakReference[s] are optionally removed from the list before indexing.
	 * @param int $offset the offset to set item
	 * @param mixed $item the item value
	 */
	public function offsetSet($offset, $item): void
	{
		$this->scrubWeakReferences();
		if ($offset === null || $offset === $this->_c) {
			if (is_object($item)) {
				$this->weakCustomAdd($item);
			}
			$this->filterItemForInput($item);
			parent::insertAt($this->_c, $item);
		} else {
			$removed = parent::removeAt($offset);
			$this->filterItemForOutput($removed);
			if (is_object($removed)) {
				$this->weakCustomRemove($removed);
			}
			if (is_object($item)) {
				$this->weakCustomAdd($item);
			}
			$this->filterItemForInput($item);
			parent::insertAt($offset, $item);
		}
	}

	/**
	 * Returns an array with the names of all variables of this object that should
	 * NOT be serialized because their value is the default one or useless to be cached
	 * for the next page loads.  Reimplement in derived classes to add new variables,
	 * but remember to  also to call the parent implementation first.
	 * Due to being weak, the TWeakList is not serialized.  The count is artificially
	 * made zero so the parent has no values to save.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		$c = $this->_c;
		$this->_c = 0;
		parent::_getZappableSleepProps($exprops);
		$this->_c = $c;

		$this->_weakZappableSleepProps($exprops);
		if ($this->_discardInvalid === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_discardInvalid";
		}
	}
}
