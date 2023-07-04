<?php
/**
 * TArraySubscription classes
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Util\Helpers\TArrayHelper;

use ArrayAccess;
use WeakReference;

/**
 * TArraySubscription class.
 *
 * Given an array reference or ArrayAccess object, this class adds an item to an
 * array on {@see self::subscribe()} and removes the element of the array when the
 * instance is dereferenced or {@see self::unsubscribe()} is called.
 *
 * These are specific types that can be subscribed: PHP Array, Object implementing
 * ArrayAccess, TList and subclasses, and TMap and subclasses.  A priority can be
 * specified for {@see \Prado\Collections\IPriorityCollection} instances.
 *
 * When an array is associative, the original element is saved and restored (with
 * its original priority).  IWeakCollections retain their elements in their weakened
 * "storage" state.
 *
 * ```php
 *		$map = new TPriorityMap(['key' => 'original value']);
 *
 *		$subscription = new TArraySubscription($map, 'key', 'new value', priority: 5);
 *
 *		$subscription->getIsSubscribed() === true;
 *		$map['key'] === 'new value';	// @ priority = 5
 *
 *		$subscription->unsubscribe();
 *
 *		$subscription->getIsSubscribed() === false;
 *		$map['key'] === 'original value';	// @ priority = default (10)
 * ```
 *
 * When the TArraySubscription is dereferenced, the item is also automatically unsubscribed.
 * ```php
 *	{
 *		$list = [];
 *		{
 *			$subscription = new TArraySubscription($list, null, 'subscribed item', isAssociative: false);
 *			$list[0] === 'subscribed item';
 *			array_splice($list, 0, 0, ['first item']);
 *			$list[] = 'last item'; // ['first item', 'subscribed item', 'last item']
 *			...
 *		}	// $subscription unsubscribes here, out of scope
 *		$list[0] === 'first item';
 *		$list[1] === 'last item'; // count = 2
 *	}
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 * @todo TComponent is too complex, TBaseObject for getters/setters.
 */
class TArraySubscription
{
	use TPriorityPropertyTrait;

	/**
	 * @var null|array|ArrayAccess The array reference or ArrayAccess being subscribed to.
	 */
	private mixed $_array = null;

	/**
	 * @var null|int|string The key that the item is added to the array.
	 */
	private null|int|string $_key = null;

	/**
	 * @var mixed The item subscribing to the array.
	 */
	protected mixed $_item = null;

	/**
	 * @var ?string The class filtering the item for storage.  This is populated when
	 * the {@see self::getArray()} is an instanceOf {@see \Prado\Collections\ICollectionFilter}.
	 */
	protected ?string $_filterClass = null;

	/**
	 * @var null|bool|int Is the array an associative array.  False for a "list" style array.
	 *   null for discovery of the style of array on subscribe.  default true.
	 */
	protected null|bool|int $_isAssoc = true;

	/**
	 * @var bool Is the item inserted into the collection.
	 */
	private bool $_isSubscribed = false;

	/**
	 * @var ?bool Is the subscription replacing an item.
	 */
	private ?bool $_isReplacing = null;

	/**
	 * @var mixed The original item.
	 */
	private mixed $_originalItem = null;

	/**
	 * @var ?float The priority of the original item.
	 */
	private ?float $_originalPriority = null;

	/**
	 * Constructs the TArraySubscription.  If there is an key or item then the item is
	 * automatically subscribed to the array when $autoSubscribe is the default null.
	 * When $autoSubscribe = true, the item is added regardless of a key and/or item
	 * has a value.
	 * @param mixed &$array The array to subscribe to, passed by reference.
	 *   Default null.
	 * @param mixed $key The key of the subscribed item, default null for append the
	 *   item. Default null.
	 * @param mixed $item The item to insert into the array at $key with $priority.
	 *   Default null.
	 * @param null|float|int $priority The priority of the item for IPriorityCollection.
	 *   Default null.
	 * @param null|bool|int $isAssociative Is the array an associative array.  false is
	 *   the list from (0...n-1).  When false, this will use `array_splice` to insert
	 *   the item.  Default 1 for true except for TList.
	 * @param bool $autoSubscribe Should the
	 */
	public function __construct(mixed &$array = null, mixed $key = null, mixed $item = null, null|int|float $priority = null, null|bool|int $isAssociative = 1, ?bool $autoSubscribe = null)
	{
		$this->setArray($array);
		$this->setKey($key);
		$this->setItem($item);
		$this->setPriority($priority);
		$this->setIsAssociative($isAssociative);

		//parent::__construct();

		if (($autoSubscribe === null && ($key !== null || $item !== null) || $autoSubscribe === true) && $this->getArray() !== null) {
			$this->subscribe();
		}
	}

	/**
	 * Cleans up the instance on destruction.
	 * If the item is subscribed to the array, the item is removed.
	 */
	public function __destruct()
	{
		$this->unsubscribe();
		//parent::__destruct();
	}

	/**
	 * Returns the collection to which the item is subscribed.
	 * Be very careful with the returnad array as it is passed by reference and could change
	 * the original variable to something other than an array.
	 * ```php
	 *		$array = & $subscription->getArray();
	 *		... //use $array
	 *		// $array = null;  // This will destroy the array being used originally.  Avoid this.
	 *		unset($array);  // This dereferences.
	 *
	 *		// or, use a non pass-by-reference
	 *		$array = $subscription->getArray();
	 *		$array = null;  // ok.
	 * ```
	 * @param bool $weak
	 * @return array|ArrayAccess The subscribed array-collection, passed by reference.
	 */
	public function &getArray(bool $weak = false): array|ArrayAccess|WeakReference|null
	{
		if ($this->_array instanceof WeakReference) {
			if ($weak) {
				$collection = $this->_array;
			} else {
				$collection = $this->_array->get();
			}
		} elseif (is_array($this->_array)) {
			$collection = &$this->_array;
		} elseif ($this->_array instanceof ArrayAccess) {
			$collection = $this->_array;
		} else {
			$collection = null;
		}
		return $collection;
	}

	/**
	 * Sets the array or ArrayAccess object.
	 * @param null|array|ArrayAccess $value The array, passed by reference.
	 * @throws TInvalidOperationException When setting during a subscription.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 * @return static The current object.
	 */
	public function setArray(null|array|ArrayAccess &$value): static
	{
		if ($this->_isSubscribed) {
			throw new TInvalidOperationException('arraysubscription_no_change', 'Array');
		}

		unset($this->_array);
		if (is_object($value)) {
			$this->_array = WeakReference::create($value);
		} else {
			$this->_array = &$value;
		}

		return $this;
	}

	/**
	 * If the item is subscribed and the key is null, the item key will be discovered
	 * with the TList (by {@see \Prado\Collections\TList::indexOf()}) or array (by array_search).
	 * @return null|int|string The key for the item subscription to the array.
	 */
	public function getKey(): null|int|string
	{
		$collection = &$this->getArray();
		if ($this->_isSubscribed && $this->_key === null) {
			if ($collection instanceof TList) {
				$args = [$this->getItem()];
				if ($collection instanceof IPriorityCollection) {
					$args[] = $this->getPriority();
				}
				return ($index = $collection->indexOf(...$args)) === -1 ? null : $index;
			} elseif (is_array($collection)) {
				if (($key = array_search($this->getItem(), $collection, true)) === false) {
					return null;
				}
				return $key;
			}
		}
		return $this->_key;
	}

	/**
	 * @param mixed $value The key for the item subscription to the array.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 */
	public function setKey(mixed $value): static
	{
		if ($this->_isSubscribed) {
			throw new TInvalidOperationException('arraysubscription_no_change', 'Key');
		}

		if (is_bool($value) || is_float($value)) {
			$value = (int) $value;
		}
		$this->_key = $value;

		return $this;
	}

	/**
	 * @param bool $unfiltered Should the item be unfiltered back from the stored format.
	 *   default false.
	 * @return mixed The item subscribing to the array.
	 */
	public function getItem(bool $unfiltered = false): mixed
	{
		$item = $this->_item;

		if (!$unfiltered && $this->_filterClass !== null) {
			$this->_filterClass::filterItemForOutput($item);
		}

		return $item;
	}

	/**
	 * @param mixed $value The item subscribing to the array.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 * @return static The current object.
	 */
	public function setItem(mixed $value): static
	{
		if ($this->_isSubscribed) {
			throw new TInvalidOperationException('arraysubscription_no_change', 'Item');
		}

		$this->_item = $value;

		return $this;
	}

	/**
	 * This is on applicable to {@see \Prado\Collections\IPriorityCollection}.
	 * @param ?numeric $value The priority of the item.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 * @return static The current object.
	 */
	public function setPriority($value): static
	{
		if ($this->_isSubscribed) {
			throw new TInvalidOperationException('arraysubscription_no_change', 'Priority');
		}

		if ($value === '') {
			$value = null;
		}
		if ($value !== null) {
			$value = TPropertyValue::ensureFloat($value);
		}
		$this->_priority = $value;

		return $this;
	}

	/**
	 * Whether to add to the array by association or by splicing (for an ordered list).
	 * @return null|null|bool Is the array associative; default true.  false will treat the array
	 *   as a list from (0, ..., count() - 1).  if null, where needed, the "list"ness
	 *   of the array will be determined by {@see \Prado\Util\Helpers\TArrayHelper::array_is_list()}.
	 */
	public function getIsAssociative(): null|bool|int
	{
		return $this->_isAssoc;
	}

	/**
	 * @param null|bool|int $value Is the array associative; default null.  false will treat the array
	 *   as a list from (0, ..., count() - 1).  if null, where needed, the "list"ness
	 *   of the array will be determined by {@see \Prado\Util\Helpers\TArrayHelper::array_is_list()}.
	 * @throws TInvalidOperationException If the item is already subscribed.
	 * @return static The current object.
	 */
	public function setIsAssociative(null|bool|int $value = null): static
	{
		if ($this->_isSubscribed) {
			throw new TInvalidOperationException('arraysubscription_no_change', 'Key');
		}

		$this->_isAssoc = $value;

		return $this;
	}

	/**
	 * @return bool Is the item subscribed to the array.
	 */
	public function getIsSubscribed(): bool
	{
		return $this->_isSubscribed;
	}

	/**
	 * Places the item in the array at the key (and priority, where possible).
	 * List based ArrayAccess must also implement \Countable as well.
	 * @throws TInvalidDataValueException When the Array is an ArrayAccess (but not
	 *   TMap nor TList), the Array isAssociative, and key is null.
	 * @return ?bool Was the subscription successful.  If the item is already subscribed
	 *   this will return false.  If the array is not an array, this returns null;
	 */
	public function subscribe(): ?bool
	{
		if ($this->_isSubscribed) {
			return false;
		}

		$collection = &$this->getArray();
		if (!is_array($collection) && !($collection instanceof ArrayAccess)) {
			return null;
		}

		if (is_int($this->_isAssoc)) {
			$this->_isAssoc = !($collection instanceof TList);
		}

		$this->_isReplacing = false;
		$key = $this->_key;

		// @todo, PHPStan bug https://github.com/phpstan/phpstan/issues/9519
		if ($collection instanceof TList || ($collection instanceof TMap)) {
			$this->_isAssoc = $collection instanceof TMap;
			if ($collection instanceof TList && $key !== null) {
				$priority = $collection->insertAt($key, $this->getItem());
				if ($collection instanceof IPriorityCollection) {
					$this->setPriority($priority);
				}
				$this->setKey(null);
			} else {
				$args = [];
				$item = $this->getItem();
				if ($collection instanceof TMap) {
					if ($collection->contains($key)) {
						$this->_isReplacing = true;
						$this->_originalItem = $collection[$key];

						if ($collection instanceof IPriorityCollection) {
							$this->_originalPriority = $collection->priorityAt($key);
							if ($item === null) {
								$this->setItem($item = $this->_originalItem);
							}
						}
					}
					array_push($args, $key);
				}
				array_push($args, $item);
				if ($collection instanceof IPriorityCollection) {
					if (($priority = $this->getPriority()) === null) {
						$this->setPriority($collection->getDefaultPriority());
					}
					array_push($args, $priority);
				}

				$key = $collection->add(...$args);

				if ($collection instanceof TMap) {
					$this->setKey($key);
				}
			}
		} else {
			if ($collection instanceof ArrayAccess) {
				$this->_isAssoc = true;
			} elseif ($this->_isAssoc === null) {
				$this->_isAssoc = !TArrayHelper::array_is_list($collection);
			}
			if ($key === null && $this->_isAssoc) {
				if ($collection instanceof ArrayAccess) {
					throw new TInvalidDataValueException('arraysubscription_no_null_key');
				}
				$collection[] = $this->getItem();
				$this->setKey(array_key_last($collection));
			} else {
				if ($key === null) {
					$key = count($collection);
				}
				$offsetSet = $this->_isAssoc || !is_array($collection);
				if ($offsetSet || $key === count($collection)) {
					if ($offsetSet && (isset($collection[$key]) || is_array($collection) && array_key_exists($key, $collection))) {
						$this->_isReplacing = true;
						$this->_originalItem = $collection[$key];
					}
					$collection[$key] = $this->getItem();
				} else {
					if ($key < 0 && $key > count($collection)) {
						throw new TInvalidDataValueException('arraysubscription_index_invalid', $key, count($collection));
					}
					array_splice($collection, $key, 0, [$this->getItem()]);
				}
				if (!$this->_isAssoc && is_array($collection)) {
					$this->setKey(null);
				}
			}
		}

		if ($collection instanceof ICollectionFilter) {
			$this->_filterClass = $collection::class;
			$collection::filterItemForInput($this->_item);
			if ($this->_isReplacing) {
				$collection::filterItemForInput($this->_originalItem);
			}
		}

		$this->_isSubscribed = true;

		return true;
	}

	/**
	 * Removes the item from the array at the key (and priority, where possible).
	 * @return ?bool Was the unsubscribe successful.  null when the array is not the proper
	 *   type, and false if already unsubscribe.
	 */
	public function unsubscribe(): ?bool
	{
		if (!$this->_isSubscribed) {
			return false;
		}

		$this->_isSubscribed = false;

		if ($this->_filterClass !== null) {
			$this->_filterClass::filterItemForOutput($this->_item);

			if ($this->_isReplacing) {
				$this->_filterClass::filterItemForOutput($this->_originalItem);
			}
			$this->_filterClass = null;
		}

		$collection = &$this->getArray();

		if (!is_array($collection) && !($collection instanceof ArrayAccess)) {
			return null;
		}

		if ($collection instanceof TList) {
			$args = [$this->getItem()];
			if ($collection instanceof IPriorityCollection) {
				array_push($args, $this->getPriority());
			}
			try {
				$collection->remove(...$args);
			} catch (TInvalidDataValueException $e) {
				// if not found, continue.
			}
		} else {
			if ($this->_isAssoc || !is_array($collection)) {
				$key = $this->_key;
				if (isset($collection[$key]) || is_array($collection) && array_key_exists($key, $collection)) {
					$item = $collection[$key];
					if ($item === $this->getItem()) {
						unset($collection[$key]);
						if ($this->_isReplacing && (!($collection instanceof IWeakCollection) || $this->_originalItem !== null)) {
							if ($collection instanceof TPriorityMap) {
								$collection->add($key, $this->_originalItem, $this->_originalPriority);
							} else {
								$collection[$key] = $this->_originalItem;
							}
						}
					}
				}
				unset($this->_originalItem);
				unset($this->_originalPriority);
				$this->_isReplacing = null;
			} else {
				$key = array_search($this->getItem(), $collection, true);
				if ($key !== false) {
					array_splice($collection, $key, 1);
				}
			}
		}

		return true;
	}

}
