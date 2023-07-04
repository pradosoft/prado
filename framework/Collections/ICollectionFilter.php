<?php
/**
 * ICollectionFilter interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

/**
 * ICollectionFilter interface
 *
 * Collections implement this to convert their items for storage. Mainly this is
 * used by {@see \Prado\Collections\TWeakCallableCollection} and {@see \Prado\Collections\TWeakList} to convert objects
 * into {@see WeakReference}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
interface ICollectionFilter
{
	/**
	 * Converts an item into an internal storage format.  For instance, IWeakCollection
	 * will convert objects into WeakReferences.
	 * @param mixed &$item The item to convert
	 */
	public static function filterItemForInput(&$item): void;

	/**
	 * Converts an item from an internal storage format back to its normal state.
	 * For instance, IWeakCollection will convert objects from WeakReferences.
	 * @param mixed &$item The item to convert
	 */
	public static function filterItemForOutput(&$item): void;
}
