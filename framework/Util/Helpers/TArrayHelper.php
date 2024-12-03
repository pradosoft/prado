<?php

/**
 * TArrayHelper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Helpers;

use Traversable;

/**
 * TArrayHelper class.
 *
 * This is the class to assist in array access.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TArrayHelper
{
	/**
	 * Determines if the given array is a list. An array is considered a list if its
	 * keys consist of consecutive numbers from 0 to count($array)-1.
	 * @param array|Traversable $array The array to check.
	 * @return bool is the array a list.
	 * @link https://www.php.net/manual/en/function.array-is-list.php
	 */
	public static function array_is_list(array|Traversable $array): bool
	{
		if (function_exists('array_is_list') && !($array instanceof Traversable)) {
			return array_is_list($array);
		}
		$i = -1;
		foreach ($array as $k => $v) {
			++$i;
			if ($k !== $i) {
				return false;
			}
		}
		return true;
	}
}
