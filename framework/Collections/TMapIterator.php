<?php
/**
 * TMap, TMapIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

/**
 * TMapIterator class
 *
 * TMapIterator implements \Iterator interface.
 *
 * TMapIterator is used by TMap. It allows TMap to return a new iterator
 * for traversing the items in the map.
 *
 * @deprecated Issue 264 : ArrayIterator should be used instead
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TMapIterator extends \ArrayIterator
{
}
