<?php
/**
 * TList, TListIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

/**
 * TListIterator class
 *
 * TListIterator implements \Iterator interface.
 *
 * TListIterator is used by TList. It allows TList to return a new iterator
 * for traversing the items in the list.
 *
 * @deprecated Issue 264 : ArrayIterator should be used instead
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TListIterator extends \ArrayIterator
{
}