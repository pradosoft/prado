<?php
/**
 * TMappedStatement and related classes.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

/**
 * TPostSelectBinding class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
class TPostSelectBinding
{
	private $_statement=null;
	private $_property=null;
	private $_resultObject=null;
	private $_keys=null;
	private $_method=TMappedStatement::QUERY_FOR_LIST;

	public function getStatement(){ return $this->_statement; }
	public function setStatement($value){ $this->_statement = $value; }

	public function getResultProperty(){ return $this->_property; }
	public function setResultProperty($value){ $this->_property = $value; }

	public function getResultObject(){ return $this->_resultObject; }
	public function setResultObject($value){ $this->_resultObject = $value; }

	public function getKeys(){ return $this->_keys; }
	public function setKeys($value){ $this->_keys = $value; }

	public function getMethod(){ return $this->_method; }
	public function setMethod($value){ $this->_method = $value; }
}