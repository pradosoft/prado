<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado
 */

namespace Prado;

/**
 * ICacheDependency interface.
 *
 * This interface must be implemented by classes meant to be used as
 * cache dependencies.
 *
 * Classes implementing this interface must support serialization and unserialization.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
interface ICacheDependency
{
	/**
	 * @return boolean whether the dependency has changed. Defaults to false.
	 */
	public function getHasChanged();
}