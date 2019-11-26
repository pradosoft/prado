<?php
/**
 * TPagedDataSource, TPagedListIterator, TPagedMapIterator classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TPagedDataSource class
 *
 * TPagedDataSource implements an integer-indexed collection class with paging functionality.
 *
 * Data items in TPagedDataSource can be traversed using <b>foreach</b>
 * PHP statement like the following,
 * <code>
 * foreach($pagedDataSource as $dataItem)
 * </code>
 * The data are fetched from {@link setDataSource DataSource}. Only the items
 * within the specified page will be returned and traversed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TPagedDataSource extends \Prado\TComponent implements \IteratorAggregate, \Countable
{
	/**
	 * @var mixed original data source
	 */
	private $_dataSource;
	/**
	 * @var int number of items in each page
	 */
	private $_pageSize = 10;
	/**
	 * @var int current page index
	 */
	private $_currentPageIndex = 0;
	/**
	 * @var bool whether to allow paging
	 */
	private $_allowPaging = false;
	/**
	 * @var bool whether to allow custom paging
	 */
	private $_allowCustomPaging = false;
	/**
	 * @var int user-assigned number of items in data source
	 */
	private $_virtualCount = 0;

	/**
	 * @return mixed original data source. Defaults to null.
	 */
	public function getDataSource()
	{
		return $this->_dataSource;
	}

	/**
	 * @param mixed $value original data source
	 */
	public function setDataSource($value)
	{
		if (!($value instanceof TMap) && !($value instanceof TList)) {
			if (is_array($value)) {
				$value = new TMap($value);
			} elseif ($value instanceof \Traversable) {
				$value = new TList($value);
			} elseif ($value !== null) {
				throw new TInvalidDataTypeException('pageddatasource_datasource_invalid');
			}
		}
		$this->_dataSource = $value;
	}

	/**
	 * @return int number of items in each page. Defaults to 10.
	 */
	public function getPageSize()
	{
		return $this->_pageSize;
	}

	/**
	 * @param int $value number of items in each page
	 */
	public function setPageSize($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) > 0) {
			$this->_pageSize = $value;
		} else {
			throw new TInvalidDataValueException('pageddatasource_pagesize_invalid');
		}
	}

	/**
	 * @return int current page index. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->_currentPageIndex;
	}

	/**
	 * @param int $value current page index
	 */
	public function setCurrentPageIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = 0;
		}
		$this->_currentPageIndex = $value;
	}

	/**
	 * @return bool whether to allow paging. Defaults to false.
	 */
	public function getAllowPaging()
	{
		return $this->_allowPaging;
	}

	/**
	 * @param bool $value whether to allow paging
	 */
	public function setAllowPaging($value)
	{
		$this->_allowPaging = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether to allow custom paging. Defaults to false.
	 */
	public function getAllowCustomPaging()
	{
		return $this->_allowCustomPaging;
	}

	/**
	 * @param bool $value whether to allow custom paging
	 */
	public function setAllowCustomPaging($value)
	{
		$this->_allowCustomPaging = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int user-assigned number of items in data source Defaults to 0.
	 */
	public function getVirtualItemCount()
	{
		return $this->_virtualCount;
	}

	/**
	 * @param int $value user-assigned number of items in data source
	 */
	public function setVirtualItemCount($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) >= 0) {
			$this->_virtualCount = $value;
		} else {
			throw new TInvalidDataValueException('pageddatasource_virtualitemcount_invalid');
		}
	}

	/**
	 * @return int number of items in current page
	 */
	public function getCount()
	{
		if ($this->_dataSource === null) {
			return 0;
		}
		if (!$this->_allowPaging) {
			return $this->getDataSourceCount();
		}
		if (!$this->_allowCustomPaging && $this->getIsLastPage()) {
			return $this->getDataSourceCount() - $this->getFirstIndexInPage();
		}
		return $this->_pageSize;
	}

	/**
	 * Returns the number of items in the current page.
	 * This method is required by \Countable interface.
	 * @return int number of items in the current page.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * @return int number of pages
	 */
	public function getPageCount()
	{
		if ($this->_dataSource === null) {
			return 0;
		}
		$count = $this->getDataSourceCount();
		if (!$this->_allowPaging || $count <= 0) {
			return 1;
		}
		return (int) (($count + $this->_pageSize - 1) / $this->_pageSize);
	}

	/**
	 * @return bool whether the current page is the first page Defaults to false.
	 */
	public function getIsFirstPage()
	{
		if ($this->_allowPaging) {
			return $this->_currentPageIndex === 0;
		} else {
			return true;
		}
	}

	/**
	 * @return bool whether the current page is the last page
	 */
	public function getIsLastPage()
	{
		if ($this->_allowPaging) {
			return $this->_currentPageIndex === $this->getPageCount() - 1;
		} else {
			return true;
		}
	}

	/**
	 * @return int the index of the item in data source, where the item is the first in
	 * current page
	 */
	public function getFirstIndexInPage()
	{
		if ($this->_dataSource !== null && $this->_allowPaging && !$this->_allowCustomPaging) {
			return $this->_currentPageIndex * $this->_pageSize;
		} else {
			return 0;
		}
	}

	/**
	 * @return int number of items in data source, if available
	 */
	public function getDataSourceCount()
	{
		if ($this->_dataSource === null) {
			return 0;
		} elseif ($this->_allowCustomPaging) {
			return $this->_virtualCount;
		} else {
			return $this->_dataSource->getCount();
		}
	}

	/**
	 * @return \Iterator iterator
	 */
	public function getIterator()
	{
		if ($this->_dataSource instanceof TList) {
			return new TPagedListIterator($this->_dataSource, $this->getFirstIndexInPage(), $this->getCount());
		} elseif ($this->_dataSource instanceof TMap) {
			return new TPagedMapIterator($this->_dataSource, $this->getFirstIndexInPage(), $this->getCount());
		} else {
			return null;
		}
	}
}
