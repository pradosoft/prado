<?php
/**
 * TMappedStatement and related classes.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.SqlMap.Statements
 */

/**
 * TResultSetListItemParameter class
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TResultSetListItemParameter extends TComponent
{
	private $_resultObject;
	private $_parameterObject;
	private $_list;

	public function __construct($result, $parameter, &$list)
	{
		$this->_resultObject = $result;
		$this->_parameterObject = $parameter;
		$this->_list = &$list;
	}

	public function getResult()
	{
		return $this->_resultObject;
	}

	public function getParameter()
	{
		return $this->_parameterObject;
	}

	public function &getList()
	{
		return $this->_list;
	}
}