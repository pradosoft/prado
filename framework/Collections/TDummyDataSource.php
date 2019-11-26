<?php
/**
 * TDummyDataSource, TDummyDataSourceIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

/**
 * TDummyDataSource class
 *
 * TDummyDataSource implements a dummy data collection with a specified number
 * of dummy data items. The number of virtual items can be set via
 * {@link setCount Count} property. You can traverse it using <b>foreach</b>
 * PHP statement like the following,
 * <code>
 * foreach($dummyDataSource as $dataItem)
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TDummyDataSource extends \Prado\TComponent implements \IteratorAggregate, \Countable
{
	private $_count;

	/**
	 * Constructor.
	 * @param int $count number of (virtual) items in the data source.
	 */
	public function __construct($count)
	{
		$this->_count = $count;
	}

	/**
	 * @return int number of (virtual) items in the data source.
	 */
	public function getCount()
	{
		return $this->_count;
	}

	/**
	 * @return \Iterator iterator
	 */
	public function getIterator()
	{
		return new TDummyDataSourceIterator($this->_count);
	}

	/**
	 * Returns the number of (virtual) items in the data source.
	 * This method is required by \Countable interface.
	 * @return int number of (virtual) items in the data source.
	 */
	public function count()
	{
		return $this->getCount();
	}
}
