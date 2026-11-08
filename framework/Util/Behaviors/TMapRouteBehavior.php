<?php

/**
 * TMapRouteBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TComponentReflection;
use Prado\Util\TBehavior;

/**
 * TMapRouteBehavior class.
 *
 * TMapRouteBehavior routes the changes to Application Parameters to
 * actual functions to affect change.
 * ```php
 *		Prado::getApplication()->getParameters()->attachBehavior('name'
 *			new TMapRouteBehavior('parameterToHook', [$obj, 'setParam']));
 * ```
 * This code will call $obj->setParam($value) every time the parameter
 * 'parameterToHook' changes in the Application Parameters.
 *
 * ```php
 *		Prado::getApplication()->getParameters()->attachBehavior('name'
 *			new TMapRouteBehavior(null, [$obj, 'setParam']));
 * ```
 * This code will call $obj->setParam($key, $value) every time any parameter
 * changes in the Application Parameters.
 *
 * Keys match by their string form: the parameter `'10'` matches the map key `10`
 * and does not match `'1e1'`.
 *
 * On a map that implements {@see IMapRouteIndex}, such as
 * {@see \Prado\TApplicationParameters}, the route registers in the map's key index
 * on attach and is called through {@see routeChange()}. A change then calls only the
 * routes for its key. A subclass that overrides `dyAddItem` or `dyRemoveItem` stays
 * in the `dy` call chain.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TMapRouteBehavior extends TBehavior
{
	/**
	 * @var string the parameter to check for when there are changes.
	 */
	private $_parameter;

	/**
	 * @var callable the parameter to check for when there are changes
	 */
	private $_handler;

	/**
	 * @param string $parameter the name of the map key parameter to hook
	 * @param callable $handler the handler for setting the parameter
	 */
	public function __construct($parameter, $handler)
	{
		if (!is_callable($handler)) {
			throw new TInvalidDataTypeException('maproutebehavior_handler_not_callable');
		}
		$this->_parameter = $parameter;
		$this->_handler = $handler;
		parent::__construct();
	}

	/**
	 * This is the dynamic event for handling TMap dyAddItem.
	 * When there is a parameter, when the key is equal to the parameter,
	 * this calls handler($value).
	 * When parameter is null, this calls handler($key, $value).
	 * @param ?string $key the key of the item being added
	 * @param mixed$value the value of the item being added
	 * @param \Prado\Util\TCallChain $callchain of event handlers
	 * @return mixed returns the argv[0], chained to all handlers
	 */
	public function dyAddItem($key, $value, $callchain)
	{
		$this->routeChange($key, $value);
		return $callchain->dyAddItem($key, $value);
	}


	/**
	 * This is the dynamic event for handling TMap dyRemoveItem.
	 * When there is a parameter, when the key is equal to the parameter,
	 * this calls handler(null).
	 * When parameter is null, this calls handler($key, null).
	 * @param ?string $key the key of the item being added
	 * @param mixed $value the value of the item being added
	 * @param \Prado\Util\TCallChain $callchain of event handlers
	 * @return mixed returns the argv[0], chained to all handlers
	 */
	public function dyRemoveItem($key, $value, $callchain)
	{
		$this->routeChange($key, null);
		return $callchain->dyRemoveItem($key, $value);
	}

	/**
	 * Calls the handler for a change to a key.
	 * - Parameter matches the key → handler($value).
	 * - Parameter is null → handler($key, $value).
	 * - Otherwise → no call.
	 * @param mixed $key the changed key.
	 * @param mixed $value the new value; null for a removal.
	 * @since 4.4.0
	 */
	public function routeChange($key, $value): void
	{
		if ($this->isRoutedKey($key)) {
			call_user_func($this->_handler, $value);
		} elseif ($this->_parameter === null) {
			call_user_func($this->_handler, $key, $value);
		}
	}

	/**
	 * Attaches the route and registers it in the owner's key index.
	 * @param \Prado\TComponent $owner the map.
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		if ($owner instanceof IMapRouteIndex && $this->getIsIndexable()) {
			$owner->attachKeyRoute($this);
		}
	}

	/**
	 * Unregisters the route from the owner's key index and detaches it.
	 * @param \Prado\TComponent $owner the map.
	 */
	public function detach($owner)
	{
		if ($owner instanceof IMapRouteIndex) {
			$owner->detachKeyRoute($this);
		}
		parent::detach($owner);
	}

	/**
	 * A route is indexable when this class declares its `dyAddItem` and `dyRemoveItem`.
	 * A subclass that overrides either one runs in the `dy` call chain.  The methods
	 * resolve through the {@see TComponentReflection} cache.
	 * @return bool whether the route can be called through {@see routeChange()}.
	 * @since 4.4.0
	 */
	public function getIsIndexable(): bool
	{
		return TComponentReflection::getReflectionMethodByType(static::class, 'dyAddItem')?->class === self::class
			&& TComponentReflection::getReflectionMethodByType(static::class, 'dyRemoveItem')?->class === self::class;
	}

	/**
	 * Matches a map key against the parameter by string form.
	 * @param mixed $key the map key.
	 * @return bool whether the key is the routed parameter; false when the parameter is null.
	 * @since 4.4.0
	 */
	protected function isRoutedKey($key): bool
	{
		return $this->_parameter !== null && $key !== null && (string) $key === (string) $this->_parameter;
	}

	/**
	 * @return string parameter
	 */
	public function getParameter()
	{
		return $this->_parameter;
	}

	/**
	 *
	 * @param mixed $param
	 */
	public function setParameter($param)
	{
		$this->_parameter = $param;
		if (($owner = $this->getOwner()) instanceof IMapRouteIndex && $this->getIsIndexable()) {
			$owner->attachKeyRoute($this);
		}
	}
}
