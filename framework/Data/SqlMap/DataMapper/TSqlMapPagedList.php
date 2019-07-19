<?php
/**
 * TSqlMapPagedList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Collections\TList;
use Prado\Collections\TPagedList;
use Prado\Data\SqlMap\Statements\IMappedStatement;
use Prado\Prado;

/**
 * TSqlMapPagedList implements a list with paging functionality that retrieves
 * data from a SqlMap statement.
 *
 * The maximum number of records fetched is 3 times the page size. It fetches
 * the current, the previous and the next page at a time. This allows the paged
 * list to determine if the page is a the begin, the middle or the end of the list.
 *
 * The paged list does not need to know about the total number of records.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
class TSqlMapPagedList extends TPagedList
{
	private $_statement;
	private $_parameter;
	private $_prevPageList;
	private $_nextPageList;
	private $_delegate;

	/**
	 * Create a new SqlMap paged list.
	 * @param IMappedStatement $statement SqlMap statement.
	 * @param mixed $parameter query parameters
	 * @param int $pageSize page size
	 * @param null|mixed $delegate delegate for each data row retrieved.
	 * @param int $page number of page to fetch on initialization
	 */
	public function __construct(IMappedStatement $statement, $parameter, $pageSize, $delegate = null, $page = 0)
	{
		parent::__construct();
		parent::setCustomPaging(true);
		$this->initialize($statement, $parameter, $pageSize, $page);
		$this->_delegate = $delegate;
	}

	/**
	 * Initialize the paged list.
	 * @param IMappedStatement $statement SqlMap statement.
	 * @param mixed $parameter query parameters
	 * @param int $pageSize page size.
	 * @param int $page number of page.
	 */
	protected function initialize($statement, $parameter, $pageSize, $page)
	{
		$this->_statement = $statement;
		$this->_parameter = $parameter;
		$this->setPageSize($pageSize);
		$this->attachEventHandler('OnFetchData', [$this, 'fetchDataFromStatement']);
		$this->gotoPage($page);
	}

	/**
	 * @param mixed $value
	 * @throws TSqlMapException custom paging must be enabled.
	 */
	public function setCustomPaging($value)
	{
		throw new TSqlMapException('sqlmap_must_enable_custom_paging');
	}

	/**
	 * Fetch data by executing the SqlMap statement.
	 * @param TPageList $sender current object.
	 * @param TPagedListFetchDataEventParameter $param fetch parameters
	 */
	protected function fetchDataFromStatement($sender, $param)
	{
		$limit = $this->getOffsetAndLimit($param);
		$connection = $this->_statement->getManager()->getDbConnection();
		$data = $this->_statement->executeQueryForList(
			$connection,
			$this->_parameter,
			null,
			$limit[0],
			$limit[1],
			$this->_delegate
		);
		$this->populateData($param, $data);
	}

	/**
	 * Switches to the next page.
	 * @return bool|int the new page index, false if next page is not availabe.
	 */
	public function nextPage()
	{
		return $this->getIsNextPageAvailable() ? parent::nextPage() : false;
	}

	/**
	 * Switches to the previous page.
	 * @return bool|int the new page index, false if previous page is not availabe.
	 */
	public function previousPage()
	{
		return $this->getIsPreviousPageAvailable() ? parent::previousPage() : false;
	}

	/**
	 * Populate the list with the fetched data.
	 * @param TPagedListFetchDataEventParameter $param fetch parameters
	 * @param array $data fetched data.
	 */
	protected function populateData($param, $data)
	{
		$total = $data instanceof TList ? $data->getCount() : count($data);
		$pageSize = $this->getPageSize();
		if ($total < 1) {
			$param->setData($data);
			$this->_prevPageList = null;
			$this->_nextPageList = null;
			return;
		}

		if ($param->getNewPageIndex() < 1) {
			$this->_prevPageList = null;
			if ($total <= $pageSize) {
				$param->setData($data);
				$this->_nextPageList = null;
			} else {
				$param->setData(array_slice($data, 0, $pageSize));
				$this->_nextPageList = array_slice($data, $pageSize - 1, $total);
			}
		} else {
			if ($total <= $pageSize) {
				$this->_prevPageList = array_slice($data, 0, $total);
				$param->setData([]);
				$this->_nextPageList = null;
			} elseif ($total <= $pageSize * 2) {
				$this->_prevPageList = array_slice($data, 0, $pageSize);
				$param->setData(array_slice($data, $pageSize, $total));
				$this->_nextPageList = null;
			} else {
				$this->_prevPageList = array_slice($data, 0, $pageSize);
				$param->setData(array_slice($data, $pageSize, $pageSize));
				$this->_nextPageList = array_slice($data, $pageSize * 2, $total - $pageSize * 2);
			}
		}
	}

	/**
	 * Calculate the data fetch offsets and limits.
	 * @param TPagedListFetchDataEventParameter $param fetch parameters
	 * @return array 1st element is the offset, 2nd element is the limit.
	 */
	protected function getOffsetAndLimit($param)
	{
		$index = $param->getNewPageIndex();
		$pageSize = $this->getPageSize();
		return $index < 1 ? [$index, $pageSize * 2] : [($index - 1) * $pageSize, $pageSize * 3];
	}

	/**
	 * @return bool true if the next page is available, false otherwise.
	 */
	public function getIsNextPageAvailable()
	{
		return $this->_nextPageList !== null;
	}

	/**
	 * @return bool true if the previous page is available, false otherwise.
	 */
	public function getIsPreviousPageAvailable()
	{
		return $this->_prevPageList !== null;
	}

	/**
	 * @return bool true if is the very last page, false otherwise.
	 */
	public function getIsLastPage()
	{
		return ($this->_nextPageList === null) || $this->_nextPageList->getCount() < 1;
	}

	/**
	 * @return bool true if is not first nor last page, false otherwise.
	 */
	public function getIsMiddlePage()
	{
		return !($this->getIsFirstPage() || $this->getIsLastPage());
	}
}
