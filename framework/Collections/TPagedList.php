<?php
/**
 * TPagedList, TPagedListFetchDataEventParameter, TPagedListPageChangedEventParameter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Collections
 */

namespace Prado\Collections;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TPagedList class
 *
 * TPagedList implements a list with paging functionality.
 *
 * TPagedList works in one of two modes, managed paging or customized paging,
 * specified by {@link setCustomPaging CustomPaging}.
 * - Managed paging ({@link setCustomPaging CustomPaging}=false) :
 *   the list is assumed to contain all data and it will manage which page
 *   of data are available to user.
 * - Customized paging ({@link setCustomPaging CustomPaging}=true) :
 *   the list is assumed to contain only one page of data. An  {@link onFetchData OnFetchData}
 *   event will be raised if the list changes to a different page.
 *   Developers can attach a handler to the event and supply the needed data.
 *   The event handler can be written as follows,
 * <code>
 *  public function fetchData($sender,$param)
 *  {
 *    $offset=$param->Offset; // beginning index of the data needed
 *    $limit=$param->Limit;   // maximum number of data items needed
 *    // get data according to the above two parameters
 *    $param->Data=$data;
 *  }
 * </code>
 *
 * Data in TPagedList can be accessed like an integer-indexed array and can
 * be traversed using foreach. For example,
 * <code>
 * $count=$list->Count;
 * for($index=0;$index<$count;++$index)
 *     echo $list[$index];
 * foreach($list as $index=>$item) // traverse each item in the list
 * </code>
 *
 * The {@link setPageSize PageSize} property specifies the number of items in each page.
 * To access different page of data in the list, set {@link setCurrentPageIndex CurrentPageIndex}
 * or call {@link nextPage()}, {@link previousPage()}, or {@link gotoPage()}.
 * The total number of pages can be obtained by {@link getPageCount() PageCount}.
 *
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Collections
 * @since 3.0
 */
class TPagedList extends TList
{
	/**
	 * @var boolean whether to allow custom paging
	 */
	private $_customPaging = false;
	/**
	 * @var integer number of items in each page
	 */
	private $_pageSize = 10;
	/**
	 * @var integer current page index
	 */
	private $_currentPageIndex = -1;
	/**
	 * @var integer user-assigned number of items in data source
	 */
	private $_virtualCount = -1;

	/**
	 * Constructor.
	 * @param array|Iterator the initial data. Default is null, meaning no initialization.
	 * @param boolean whether the list is read-only. Always true for paged list.
	 */
	public function __construct($data = null, $readOnly = false)
	{
		parent::__construct($data, true);
	}

	/**
	 * @return boolean whether to use custom paging. Defaults to false.
	 */
	public function getCustomPaging()
	{
		return $this->_customPaging;
	}

	/**
	 * @param boolean $value whether to allow custom paging
	 */
	public function setCustomPaging($value)
	{
		$this->_customPaging = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return integer number of items in each page. Defaults to 10.
	 */
	public function getPageSize()
	{
		return $this->_pageSize;
	}

	/**
	 * @param integer $value number of items in each page
	 */
	public function setPageSize($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) > 0) {
			$this->_pageSize = $value;
		} else {
			throw new TInvalidDataValueException('pagedlist_pagesize_invalid');
		}
	}

	/**
	 * @return integer current page index. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->_currentPageIndex;
	}

	/**
	 * @param integer $value current page index
	 * @throws TInvalidDataValueException if the page index is out of range
	 */
	public function setCurrentPageIndex($value)
	{
		if ($this->gotoPage($value = TPropertyValue::ensureInteger($value)) === false) {
			throw new TInvalidDataValueException('pagedlist_currentpageindex_invalid');
		}
	}

	/**
	 * Raises <b>OnPageIndexChanged</b> event.
	 * This event is raised each time when the list changes to a different page.
	 * @param TPagedListPageChangedEventParameter $param event parameter
	 */
	public function onPageIndexChanged($param)
	{
		$this->raiseEvent('OnPageIndexChanged', $this, $param);
	}

	/**
	 * Raises <b>OnFetchData</b> event.
	 * This event is raised each time when the list changes to a different page
	 * and needs the new page of data. This event can only be raised when
	 * {@link setCustomPaging CustomPaging} is true.
	 * @param TPagedListFetchDataEventParameter $param event parameter
	 */
	public function onFetchData($param)
	{
		$this->raiseEvent('OnFetchData', $this, $param);
	}

	/**
	 * Changes to a page with the specified page index.
	 * @param integer $pageIndex page index
	 * @return integer|boolean the new page index, false if page index is out of range.
	 */
	public function gotoPage($pageIndex)
	{
		if ($pageIndex === $this->_currentPageIndex) {
			return $pageIndex;
		}
		if ($this->_customPaging) {
			if ($pageIndex >= 0 && ($this->_virtualCount < 0 || $pageIndex < $this->getPageCount())) {
				$param = new TPagedListFetchDataEventParameter($pageIndex, $this->_pageSize * $pageIndex, $this->_pageSize);
				$this->onFetchData($param);
				if (($data = $param->getData()) !== null) {
					$this->setReadOnly(false);
					$this->copyFrom($data);
					$this->setReadOnly(true);
					$oldPage = $this->_currentPageIndex;
					$this->_currentPageIndex = $pageIndex;
					$this->onPageIndexChanged(new TPagedListPageChangedEventParameter($oldPage));
					return $pageIndex;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			if ($pageIndex >= 0 && $pageIndex < $this->getPageCount()) {
				$this->_currentPageIndex = $pageIndex;
				$this->onPageIndexChanged(null);
				return $pageIndex;
			} else {
				return false;
			}
		}
	}

	/**
	 * Switches to the next page.
	 * @return integer|boolean the new page index, false if next page is not available.
	 */
	public function nextPage()
	{
		return $this->gotoPage($this->_currentPageIndex + 1);
	}

	/**
	 * Switches to the previous page.
	 * @return integer|boolean the new page index, false if previous page is not available.
	 */
	public function previousPage()
	{
		return $this->gotoPage($this->_currentPageIndex - 1);
	}

	/**
	 * @return integer user-assigned number of items in data source. Defaults to 0.
	 */
	public function getVirtualCount()
	{
		return $this->_virtualCount;
	}

	/**
	 * @param integer $value user-assigned number of items in data source
	 */
	public function setVirtualCount($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->_virtualCount = $value;
	}

	/**
	 * @return integer number of pages, -1 if under custom paging mode and {@link setVirtualCount VirtualCount} is not set.
	 */
	public function getPageCount()
	{
		if ($this->_customPaging) {
			if ($this->_virtualCount >= 0) {
				return (int) (($this->_virtualCount + $this->_pageSize - 1) / $this->_pageSize);
			} else {
				return -1;
			}
		} else {
			return (int) ((parent::getCount() + $this->_pageSize - 1) / $this->_pageSize);
		}
	}

	/**
	 * @return boolean whether the current page is the first page
	 */
	public function getIsFirstPage()
	{
		return $this->_currentPageIndex === 0;
	}

	/**
	 * @return boolean whether the current page is the last page
	 */
	public function getIsLastPage()
	{
		return $this->_currentPageIndex === $this->getPageCount() - 1;
	}

	/**
	 * @return integer the number of items in current page
	 */
	public function getCount()
	{
		if ($this->_customPaging) {
			return parent::getCount();
		} else {
			if ($this->_currentPageIndex === $this->getPageCount() - 1) {
				return parent::getCount() - $this->_pageSize * $this->_currentPageIndex;
			} else {
				return $this->_pageSize;
			}
		}
	}

	/**
	 * @return Iterator iterator
	 */
	public function getIterator()
	{
		if ($this->_customPaging) {
			return parent::getIterator();
		} else {
			$data = $this->toArray();
			return new \ArrayIterator($data);
		}
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param integer the index of the item
	 * @return mixed the item at the index
	 * @throws TInvalidDataValueException if the index is out of the range
	 */
	public function itemAt($index)
	{
		if ($this->_customPaging) {
			return parent::itemAt($index);
		} else {
			return parent::itemAt($this->_pageSize * $this->_currentPageIndex + $index);
		}
	}

	/**
	 * @param mixed $item the item
	 * @return integer the index of the item in the list (0 based), -1 if not found.
	 */
	public function indexOf($item)
	{
		$c = $this->getCount();
		for ($i = 0;$i < $c;++$i) {
			if ($this->itemAt($i) === $item) {
				return $i;
			}
		}
		return -1;
	}

	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param integer $offset the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset >= 0 && $offset < $this->getCount());
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param integer the offset to retrieve item.
	 * @return mixed the item at the offset
	 * @throws TInvalidDataValueException if the offset is invalid
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		$c = $this->getCount();
		$array = [];
		for ($i = 0;$i < $c;++$i) {
			$array[$i] = $this->itemAt($i);
		}
		return $array;
	}
}
