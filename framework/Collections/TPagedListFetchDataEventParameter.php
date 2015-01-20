<?php
/**
 * TPagedList, TPagedListFetchDataEventParameter, TPagedListPageChangedEventParameter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Collections
 */

/**
 * TPagedListFetchDataEventParameter class.
 *
 * TPagedListFetchDataEventParameter is used as the parameter for
 * {@link TPagedList::onFetchData OnFetchData} event.
 * To obtain the new page index, use {@link getNewPageIndex NewPageIndex}.
 * The {@link getOffset Offset} property refers to the index
 * of the first item in the new page, while {@link getLimit Limit}
 * specifies how many items are requested for the page.
 * Newly fetched data should be saved in {@link setData Data} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Collections
 * @since 3.0
 */
class TPagedListFetchDataEventParameter extends TEventParameter
{
	private $_pageIndex;
	private $_offset;
	private $_limit;
	private $_data=null;

	/**
	 * Constructor.
	 * @param integer new page index
	 * @param integer offset of the first item in the new page
	 * @param integer number of items in the new page desired
	 */
	public function __construct($pageIndex,$offset,$limit)
	{
		$this->_pageIndex=$pageIndex;
		$this->_offset=$offset;
		$this->_limit=$limit;
	}

	/**
	 * @return integer the zero-based index of the new page
	 */
	public function getNewPageIndex()
	{
		return $this->_pageIndex;
	}

	/**
	 * @return integer offset of the first item in the new page
	 */
	public function getOffset()
	{
		return $this->_offset;
	}

	/**
	 * @return integer number of items in the new page
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * @return mixed new page data
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed new page data
	 */
	public function setData($value)
	{
		$this->_data=$value;
	}
}