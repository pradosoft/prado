<?php
/**
 * TLazyLoadList, TObjectProxy classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

/**
 * TLazyLoadList executes mapped statements when the proxy collection is first accessed.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
class TLazyLoadList
{
	private $_param;
	private $_target;
	private $_propertyName = '';
	private $_statement = '';
	private $_loaded = false;
	private $_innerList;
	private $_connection;

	/**
	 * Create a new proxy list that will execute the mapped statement when any
	 * of the list's method are accessed for the first time.
	 * @param TMappedStatement $mappedStatement statement to be executed to load the data.
	 * @param mixed $param parameter value for the statement.
	 * @param object $target result object that contains the lazy collection.
	 * @param string $propertyName property of the result object to set the loaded collection.
	 */
	protected function __construct($mappedStatement, $param, $target, $propertyName)
	{
		$this->_param = $param;
		$this->_target = $target;
		$this->_statement = $mappedStatement;
		$this->_connection = $mappedStatement->getManager()->getDbConnection();
		$this->_propertyName = $propertyName;
	}

	/**
	 * Create a new instance of a lazy collection.
	 * @param TMappedStatement $mappedStatement statement to be executed to load the data.
	 * @param mixed $param parameter value for the statement.
	 * @param object $target result object that contains the lazy collection.
	 * @param string $propertyName property of the result object to set the loaded collection.
	 * @return TObjectProxy proxied collection object.
	 */
	public static function newInstance($mappedStatement, $param, $target, $propertyName)
	{
		$handler = new self($mappedStatement, $param, $target, $propertyName);
		$statement = $mappedStatement->getStatement();
		$registry = $mappedStatement->getManager()->getTypeHandlers();
		$list = $statement->createInstanceOfListClass($registry);
		if (!is_object($list)) {
			throw new TSqlMapExecutionException('sqlmap_invalid_lazyload_list', $statement->getID());
		}
		return new TObjectProxy($handler, $list);
	}

	/**
	 * Relay the method call to the underlying collection.
	 * @param string $method method name.
	 * @param array $arguments method parameters.
	 */
	public function intercept($method, $arguments)
	{
		return call_user_func_array([$this->_innerList, $method], $arguments);
	}

	/**
	 * Load the data by executing the mapped statement.
	 */
	protected function fetchListData()
	{
		if ($this->_loaded == false) {
			$this->_innerList = $this->_statement->executeQueryForList($this->_connection, $this->_param);
			$this->_loaded = true;
			//replace the target property with real list
			TPropertyAccess::set($this->_target, $this->_propertyName, $this->_innerList);
		}
	}

	/**
	 * Try to fetch the data when any of the proxy collection method is called.
	 * @param string $method method name.
	 * @return bool true if the underlying collection has the corresponding method name.
	 */
	public function hasMethod($method)
	{
		$this->fetchListData();
		if (is_object($this->_innerList)) {
			return in_array($method, get_class_methods($this->_innerList));
		}
		return false;
	}
}
