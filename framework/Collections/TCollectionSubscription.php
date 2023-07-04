<?php
/**
 * TCollectionSubscription classes
 *
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use ArrayAccess;
use WeakReference;

/**
 * TCollectionSubscription class.
 *
 * This class does the same thing as TArraySubscription except it does not pass
 * by reference.  This will work with ArrayAccess objects but will not work with
 * PHP arrays.
 *
 * This has the property Collection that provides Array access without passing by
 * reference.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TCollectionSubscription extends TArraySubscription
{
	/**
	 * Constructor.
	 * @param ?ArrayAccess $collection The collection object being subscribed.
	 *   Default null.
	 * @param mixed $key The key of the subscription item.  Default null.
	 * @param mixed $item The item being subscribed to the collection.
	 *   Default null.
	 * @param null|float|int $priority The priority of the item. Default null.
	 * @param null|bool|int $isAssociative Is the collection associative.  this is
	 *   automatically set for TList and TMap.
	 * @param ?bool $autoSubscribe Default null for autoSubscribing when there is a
	 *   key or item.
	 */
	public function __construct(?ArrayAccess $collection = null, mixed $key = null, mixed $item = null, null|int|float $priority = null, null|bool|int $isAssociative = 1, ?bool $autoSubscribe = null)
	{
		parent::__construct($collection, $key, $item, $priority, $isAssociative, $autoSubscribe);
	}

	/**
	 * The ArrayAccess collection for getting the array without pass by reference.
	 * @param bool $weak Return the collection in as a WeakReference.
	 */
	public function getCollection(bool $weak = false): null|ArrayAccess|WeakReference
	{
		return $this->getArray($weak);
	}

	/**
	 * The ArrayAccess collection for setting the array without pass by reference.
	 * @param ?ArrayAccess $value
	 * @return static The current object.
	 */
	public function setCollection(?ArrayAccess $value): static
	{
		return $this->setArray($value);
	}
}
