<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Collections\TList;
use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TCacheDependencyList class.
 *
 * TCacheDependencyList represents a list of cache dependency objects.
 * Only objects implementing {@link ICacheDependency} can be added into this list.
 *
 * TCacheDependencyList can be used like an array. See {@link TList}
 * for more details.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.1.0
 */
class TCacheDependencyList extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional type checking
	 * for each newly added item.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a dependency instance
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof ICacheDependency) {
			parent::insertAt($index, $item);
		} else {
			throw new TInvalidDataTypeException('cachedependencylist_cachedependency_required');
		}
	}
}
