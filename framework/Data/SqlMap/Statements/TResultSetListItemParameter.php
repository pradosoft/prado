<?php

/**
 * TMappedStatement and related classes.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Statements;

/**
 * TResultSetListItemParameter class
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TResultSetListItemParameter extends \Prado\TComponent
{
	private $_resultObject;
	private $_parameterObject;
	private $_list;

	public function __construct($result, $parameter, &$list)
	{
		$this->_resultObject = $result;
		$this->_parameterObject = $parameter;
		$this->_list = &$list;
		parent::__construct();
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
