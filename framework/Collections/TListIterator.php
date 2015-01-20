<?php
/**
 * TList, TListIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Collections
 */


/**
 * TListIterator class
 *
 * TListIterator implements Iterator interface.
 *
 * TListIterator is used by TList. It allows TList to return a new iterator
 * for traversing the items in the list.
 *
 * @deprecated Issue 264 : ArrayIterator should be used instead
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Collections
 * @since 3.0
 */
class TListIterator extends ArrayIterator
{
}