<?php

Prado::using('System.Collections.TPagedList');

/**
 * TSQLMapPagedList
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TSqlMapPagedList extends TPagedList
{
	private $_statement;
	private $_parameter;
	private $_prevPageList;
	private $_nextPageList;
	private $_delegate=null;

	public function __construct(IMappedStatement $statement,
									$parameter, $pageSize, $delegate=null)
	{
		parent::__construct();
		parent::setCustomPaging(true);
		$this->initialize($statement,$parameter, $pageSize);
		$this->_delegate=$delegate;
	}

	protected function initialize($statement, $parameter, $pageSize)
	{
		$this->_statement = $statement;
		$this->_parameter = $parameter;
		$this->setPageSize($pageSize);
		$this->attachEventHandler('OnFetchData', array($this, 'fetchDataFromStatement'));
		$this->gotoPage(0);
	}

	public function setCustomPaging($value)
	{
		throw new TDataMapperException('sqlmap_must_enable_custom_paging');
	}

	protected function fetchDataFromStatement($sender, $param)
	{
		$limit = $this->getOffsetAndLimit($param);
		$connection = $this->_statement->getManager()->getDbConnection();
		$data = $this->_statement->executeQueryForList($connection,
						$this->_parameter, null, $limit[0], $limit[1], $this->_delegate);
		$this->populateData($param, $data);
	}

	public function nextPage()
	{
		if($this->getIsNextPageAvailable())
			return parent::nextPage();
		else
			return false;
	}

	public function previousPage()
	{
		if($this->getIsPreviousPageAvailable())
			return parent::previousPage();
		else
			return false;
	}

	protected function populateData($param, $data)
	{
		$total = $data instanceof TList ? $data->getCount() : count($data);
		$pageSize = $this->getPageSize();
		if($total < 1)
		{
			$param->setData($data);
			$this->_prevPageList = null;
			$this->_nextPageList = null;
			return;
		}

		if($param->getNewPageIndex() < 1)
		{
			$this->_prevPageList = null;
			if($total <= $pageSize)
			{
				$param->setData($data);
				$this->_nextPageList = null;
			}
			else
			{
				$param->setData($this->sublist($data, 0, $pageSize));
				$this->_nextPageList = $this->sublist($data, $pageSize,$total);
			}
		}
		else
		{
			if($total <= $pageSize)
			{
				$this->_prevPageList = $this->sublist($data, 0, $total);
				$param->setData(array());
				$this->_nextPageList = null;
			}
			else if($total <= $pageSize*2)
			{
				$this->_prevPageList = $this->sublist($data, 0, $pageSize);
				$param->setData($this->sublist($data, $pageSize, $total));
				$this->_nextPageList = null;
			}
			else
			{
				$this->_prevPageList = $this->sublist($data, 0, $pageSize);
				$param->setData($this->sublist($data, $pageSize, $pageSize*2));
				$this->_nextPageList = $this->sublist($data, $pageSize*2, $total);
			}
		}
	}

	protected function sublist($data, $from, $to)
	{
		$array = array();
		for($i = $from; $i<$to; $i++)
			$array[] = $data[$i];
		return $array;
	}

	protected function getOffsetAndLimit($param)
	{
		$index = $param->getNewPageIndex();
		$pageSize = $this->getPageSize();
		if($index < 1)
			return array($index, $pageSize*2);
		else
			return array(($index-1)*$pageSize, $pageSize*3);
	}

	public function getIsNextPageAvailable()
	{
		return !is_null($this->_nextPageList);
	}

	public function getIsPreviousPageAvailable()
	{
		return !is_null($this->_prevPageList);
	}

	public function getIsLastPage()
	{
		return is_null($this->_nextPageList)
				|| $this->_nextPageList->getCount() < 1;
	}

	public function getIsMiddlePage()
	{
		return !($this->getIsFirstPage() || $this->getIsLastPage());
	}
}

?>