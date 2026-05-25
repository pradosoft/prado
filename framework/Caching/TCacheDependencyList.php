<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Collections\TList;
use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TCacheDependencyList class
 *
 * TCacheDependencyList is a typed list that only accepts objects implementing
 * {@see \Prado\Caching\ICacheDependency}. It is used by
 * {@see \Prado\Caching\TChainedCacheDependency} to hold a chain of dependencies.
 *
 * The list supports all standard {@see \Prado\Collections\TList} operations
 * (add, remove, count, iterate, array-access).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TCacheDependencyList extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * Overrides the parent to enforce that every item implements
	 * {@see \Prado\Caching\ICacheDependency}.
	 * @param int $index the zero-based position at which to insert.
	 * @param mixed $item the dependency to insert.
	 * @throws TInvalidDataTypeException if `$item` does not implement {@see \Prado\Caching\ICacheDependency}.
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
