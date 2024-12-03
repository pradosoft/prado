<?php

/**
 * ISingleton interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * ISingleton interface.
 *
 * This interface is for getting specific class (application) singletons.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
interface ISingleton
{
	/**
	 * @param bool $create Should the singleton be created if it doesn't exist.
	 * @return ?object The singleton instance of the class.
	 */
	public static function singleton(bool $create = true): ?object;
}
