<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

/**
 * ICacheDependency interface.
 *
 * This interface must be implemented by classes meant to be used as
 * cache dependencies.
 *
 * Classes implementing this interface must support serialization and unserialization.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.0
 */
interface ICacheDependency
{
	/**
	 * @return bool whether the dependency has changed. Defaults to false.
	 */
	public function getHasChanged();
}
