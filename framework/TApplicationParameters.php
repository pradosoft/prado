<?php

/**
 * TApplicationParameters class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\Behaviors\IMapRouteIndex;
use Prado\Util\Behaviors\TMapRouteBehavior;

/**
 * TApplicationParameters class
 *
 * TApplicationParameters is the map of application parameters returned by
 * {@see TApplication::getParameters()}. A parameter marked final keeps its value
 * for the rest of the request. Every later add, set, or removal of a final key
 * throws a {@see TInvalidOperationException}.
 *
 * A configuration finalizes a parameter with the `final` attribute:
 * ```xml
 * <parameter id="DeveloperMode" value="0" final="true" />
 * ```
 * The PHP configuration equivalent is
 * `'DeveloperMode' => ['value' => '0', 'final' => true]`.
 * Code finalizes a parameter with {@see finalize()}.
 *
 * A final parameter in `application.xml` locks the value against folder
 * `config.xml` files, service configurations, included configurations, modules,
 * and parameter loaders such as {@see \Prado\Util\TParameterModule}.
 *
 * | Operation on a final key | Result |
 * |---|---|
 * | {@see itemAt()}, `$params[$key]` | returns the value; null for an absent key, without `dyNoItem` |
 * | {@see add()}, `$params[$key] = $v`, {@see mergeWith()} | throws |
 * | `$params[] = $v` when the next integer key is final | throws |
 * | {@see remove()}, `unset($params[$key])`, {@see clear()}, {@see copyFrom()} | throws |
 * | {@see finalize()} | no change |
 *
 * TApplicationParameters indexes its {@see TMapRouteBehavior}s by key
 * ({@see IMapRouteIndex}). A change calls the routes for its key, then the routes for
 * every key, each in attach order, after the `dyAddItem` or `dyRemoveItem` chain.
 * Indexed routes are left out of that chain, so a change costs one call per route for
 * its key, not one call per route on the map.
 *
 * Behaviors on the map follow the final keys:
 * - `dyAddItem` and `dyRemoveItem` are not raised for a rejected change, and the
 *   indexed routes are not called, so {@see TMapRouteBehavior} handlers receive no message.
 * - `dyNoItem` is not raised for a final absent key, so
 *   {@see \Prado\Util\Behaviors\TMapLazyLoadBehavior} loaders are not consulted.
 * - A property set from a final parameter by
 *   {@see \Prado\Util\Behaviors\TParameterizeBehavior} holds a copy; code may still set
 *   that property directly.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TApplicationParameters extends TMap implements IMapRouteIndex
{
	/**
	 * @var array<int|string, true> the final parameter keys
	 */
	private array $_final = [];

	/**
	 * @var array<string, array<int, TMapRouteBehavior>> routes for one key, by key string and object id
	 */
	private array $_keyRoutes = [];

	/**
	 * @var array<int, TMapRouteBehavior> routes for every key, by object id
	 */
	private array $_allRoutes = [];

	/**
	 * @var array<int, ?string> the indexed key of each route, by object id; null routes every key
	 */
	private array $_routeKeys = [];

	/**
	 * @var array<string, array> `dyAddItem` and `dyRemoveItem` behaviors without the indexed routes
	 */
	private array $_routeChains = [];

	/**
	 * Finalizes a parameter so its key cannot be added, set, or removed again.
	 * A key without a value may be finalized; the key then stays absent.
	 * @param mixed $key the parameter key; null finalizes the empty-string key.
	 */
	public function finalize($key): void
	{
		$this->_final[$key ?? ''] = true;
	}

	/**
	 * @param mixed $key the parameter key; null checks the empty-string key.
	 * @return bool whether the parameter key is final.
	 */
	public function getIsFinal($key): bool
	{
		return isset($this->_final[$key ?? '']);
	}

	/**
	 * @return array<int|string> the final parameter keys, in finalization order.
	 */
	public function getFinalKeys(): array
	{
		return array_keys($this->_final);
	}

	/**
	 * Returns a parameter.
	 * A final absent key returns null without raising `dyNoItem`, so lazy loaders
	 * cannot supply it.
	 * @param mixed $key the parameter key; null looks up the empty-string key.
	 * @return mixed the parameter value, null if no value is found.
	 */
	public function itemAt($key)
	{
		if ($this->getIsFinal($key) && !$this->contains($key)) {
			return null;
		}
		return parent::itemAt($key);
	}

	/**
	 * Adds a parameter.  An append checks the next integer key PHP assigns.
	 * @param mixed $key the parameter key; null appends with the next integer key.
	 * @param mixed $value the parameter value.
	 * @throws TInvalidOperationException if the key is final or the map is read-only.
	 * @return mixed the key of the parameter.
	 */
	public function add($key, $value): mixed
	{
		if ($key === null && $this->_final !== []) {
			$key = $this->getNextIntegerKey();
		}
		if ($key !== null) {
			$this->ensureNotFinal($key);
		}
		$key = parent::add($key, $value);
		$this->routeKeyChange($key, $value);
		return $key;
	}

	/**
	 * Returns the integer key PHP assigns to the next append.  A copy of the data keeps
	 * PHP's append counter, which a removal does not lower.
	 * @return int the next integer key.
	 */
	protected function getNextIntegerKey(): int
	{
		$data = $this->toArray();
		$data[] = null;
		return array_key_last($data);
	}

	/**
	 * Removes a parameter.
	 * @param mixed $key the parameter key; null removes the empty-string key.
	 * @throws TInvalidOperationException if the key is final or the map is read-only.
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key)
	{
		$key ??= '';
		$this->ensureNotFinal($key);
		if (!$this->contains($key)) {
			return parent::remove($key);
		}
		$value = parent::remove($key);
		$this->routeKeyChange($key, null);
		return $value;
	}

	/**
	 * Indexes a route by its Parameter; a null Parameter routes every key.
	 * Registering a route again moves it to its current Parameter.
	 * @param TMapRouteBehavior $route the route to index.
	 */
	public function attachKeyRoute(TMapRouteBehavior $route): void
	{
		$this->detachKeyRoute($route);
		$id = spl_object_id($route);
		$key = $route->getParameter();
		if ($key === null) {
			$this->_allRoutes[$id] = $route;
		} else {
			$key = (string) $key;
			$this->_keyRoutes[$key][$id] = $route;
		}
		$this->_routeKeys[$id] = $key;
		$this->_routeChains = [];
	}

	/**
	 * Removes a route from the index.
	 * @param TMapRouteBehavior $route the route to remove.
	 */
	public function detachKeyRoute(TMapRouteBehavior $route): void
	{
		$id = spl_object_id($route);
		if (!array_key_exists($id, $this->_routeKeys)) {
			return;
		}
		$key = $this->_routeKeys[$id];
		unset($this->_routeKeys[$id]);
		if ($key === null) {
			unset($this->_allRoutes[$id]);
		} else {
			unset($this->_keyRoutes[$key][$id]);
			if (empty($this->_keyRoutes[$key])) {
				unset($this->_keyRoutes[$key]);
			}
		}
		$this->_routeChains = [];
	}

	/**
	 * Calls the enabled routes for a changed key, then the enabled routes for every key.
	 * @param int|string $key the changed key.
	 * @param mixed $value the new value; null for a removal.
	 */
	protected function routeKeyChange($key, $value): void
	{
		if (!$this->getBehaviorsEnabled()) {
			return;
		}
		$routes = $this->_keyRoutes[(string) $key] ?? [];
		foreach ($this->_allRoutes as $id => $route) {
			$routes[$id] = $route;
		}
		foreach ($routes as $route) {
			if ($route->getEnabled()) {
				$route->routeChange($key, $value);
			}
		}
	}

	/**
	 * Leaves the indexed routes out of the `dyAddItem` and `dyRemoveItem` chains.
	 * @param string $method the dynamic event.
	 * @return array the behaviors with the method, in priority order.
	 */
	protected function getBehaviorsWithMethod(string $method): array
	{
		$behaviors = parent::getBehaviorsWithMethod($method);
		if (empty($this->_routeKeys)) {
			return $behaviors;
		}
		$lmethod = strtolower($method);
		if ($lmethod !== 'dyadditem' && $lmethod !== 'dyremoveitem') {
			return $behaviors;
		}
		return $this->_routeChains[$lmethod] ??= array_values(array_filter(
			$behaviors,
			fn ($behavior) => !array_key_exists(spl_object_id($behavior), $this->_routeKeys)
		));
	}

	/**
	 * Clears the cached `dy` chains along with the behavior method cache.
	 */
	protected function flushBehaviorMethodCache(): void
	{
		parent::flushBehaviorMethodCache();
		$this->_routeChains = [];
	}

	/**
	 * Resets the route index; the cloned behaviors register again as they attach.
	 */
	public function __clone()
	{
		$this->resetKeyRoutes();
		parent::__clone();
	}

	/**
	 * Resets the route index; the restored behaviors register again as they attach.
	 */
	public function __wakeup()
	{
		$this->resetKeyRoutes();
		parent::__wakeup();
	}

	/**
	 * Empties the route index.
	 */
	protected function resetKeyRoutes(): void
	{
		$this->_keyRoutes = [];
		$this->_allRoutes = [];
		$this->_routeKeys = [];
		$this->_routeChains = [];
	}

	/**
	 * Guards a key against change.
	 * @param int|string $key the parameter key.
	 * @throws TInvalidOperationException if the key is final.
	 */
	protected function ensureNotFinal($key): void
	{
		if ($this->getIsFinal($key)) {
			throw new TInvalidOperationException('appparameters_final', $key);
		}
	}

	/**
	 * Omits the empty final-key list and the route index from serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if (empty($this->_final)) {
			$exprops[] = "\0" . __CLASS__ . "\0_final";
		}
		$exprops[] = "\0" . __CLASS__ . "\0_keyRoutes";
		$exprops[] = "\0" . __CLASS__ . "\0_allRoutes";
		$exprops[] = "\0" . __CLASS__ . "\0_routeKeys";
		$exprops[] = "\0" . __CLASS__ . "\0_routeChains";
	}
}
