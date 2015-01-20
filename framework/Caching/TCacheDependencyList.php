<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Caching
 */

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
 * @package System.Caching
 * @since 3.1.0
 */
class TCacheDependencyList extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional type checking
	 * for each newly added item.
	 * @param integer the specified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a dependency instance
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof ICacheDependency)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('cachedependencylist_cachedependency_required');
	}
}