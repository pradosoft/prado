<?php

/**
 * IMapRouteIndex interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

/**
 * IMapRouteIndex interface
 *
 * A map that implements IMapRouteIndex indexes its {@see TMapRouteBehavior}s by
 * key. A change to a key calls the routes for that key and the routes for every key,
 * through {@see TMapRouteBehavior::routeChange()}. Indexed routes are left out of the
 * map's `dyAddItem` and `dyRemoveItem` call chains, so the cost of a change does not
 * grow with the routes for other keys.
 *
 * {@see TMapRouteBehavior} registers on attach, re-registers when its Parameter
 * changes, and unregisters on detach.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IMapRouteIndex
{
	/**
	 * Indexes a route by its Parameter; a null Parameter routes every key.
	 * Registering a route again moves it to its current Parameter.
	 * @param TMapRouteBehavior $route the route to index.
	 */
	public function attachKeyRoute(TMapRouteBehavior $route): void;

	/**
	 * Removes a route from the index.
	 * @param TMapRouteBehavior $route the route to remove.
	 */
	public function detachKeyRoute(TMapRouteBehavior $route): void;
}
