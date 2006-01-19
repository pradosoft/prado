<?php
/**
 * TDummyDataSource, TDummyDataSourceIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Collections
 */

/**
 * TDummyDataSource class
 *
 * TDummyDataSource implements a dummy data collection with a specified number
 * of dummy data items. You can traverse it using <b>foreach</b>
 * PHP statement like the following,
 * <code>
 * foreach($dummyDataSource as $dataItem)
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Collections
 * @since 3.0
 */
class TDummyDataSource extends TComponent implements IteratorAggregate
{
	private $_count;

	public function __construct($count)
	{
		$this->_count=$count;
	}

	public function getCount()
	{
		return $this->_count;
	}

	/**
	 * @return Iterator iterator
	 */
	public function getIterator()
	{
		return new TDummyDataSourceIterator($this->_count);
	}
}

/**
 * TDummyDataSourceIterator class
 *
 * TDummyDataSourceIterator implements Iterator interface.
 *
 * TDummyDataSourceIterator is used by {@link TDummyDataSource}.
 * It allows TDummyDataSource to return a new iterator
 * for traversing its dummy items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Collections
 * @since 3.0
 */
class TDummyDataSourceIterator implements Iterator
{
	private $_index;
	private $_count;

	/**
	 * Constructor.
	 * @param TList the data to be iterated through
	 * @param integer start index
	 * @param integer number of items to be iterated through
	 */
	public function __construct($count)
	{
		$this->_count=$count;
		$this->_index=0;
	}

	/**
	 * Rewinds internal array pointer.
	 * This method is required by the interface Iterator.
	 */
	public function rewind()
	{
		$this->_index=0;
	}

	/**
	 * Returns the key of the current array item.
	 * This method is required by the interface Iterator.
	 * @return integer the key of the current array item
	 */
	public function key()
	{
		return $this->_index;
	}

	/**
	 * Returns the current array item.
	 * This method is required by the interface Iterator.
	 * @return mixed the current array item
	 */
	public function current()
	{
		return null;
	}

	/**
	 * Moves the internal pointer to the next array item.
	 * This method is required by the interface Iterator.
	 */
	public function next()
	{
		$this->_index++;
	}

	/**
	 * Returns whether there is an item at current position.
	 * This method is required by the interface Iterator.
	 * @return boolean
	 */
	public function valid()
	{
		return $this->_index<$this->_count;
	}
}

?>