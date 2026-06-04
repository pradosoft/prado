<?php

/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

/**
 * ICacheDependency interface
 *
 * Implemented by all cache dependency classes. A dependency determines whether
 * a cached item has become stale since it was stored.
 *
 * Implementations must support serialization so that dependency state can persist
 * across requests.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface ICacheDependency
{
	/**
	 * @return bool whether the cached item's dependency has changed since it was stored.
	 */
	public function getHasChanged(): bool;
}
