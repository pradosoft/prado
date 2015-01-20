<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */

/**
 * ICacheDependency interface.
 *
 * This interface must be implemented by classes meant to be used as
 * cache dependencies.
 *
 * Classes implementing this interface must support serialization and unserialization.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
interface ICacheDependency
{
	/**
	 * @return boolean whether the dependency has changed. Defaults to false.
	 */
	public function getHasChanged();
}