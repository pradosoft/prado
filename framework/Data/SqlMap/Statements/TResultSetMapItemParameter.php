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
 * TResultSetMapItemParameter class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TResultSetMapItemParameter extends TComponent
{
	private $_key;
	private $_value;
	private $_parameterObject;
	private $_map;

	public function __construct($key, $value, $parameter, &$map)
	{
		$this->_key = $key;
		$this->_value = $value;
		$this->_parameterObject = $parameter;
		$this->_map = &$map;
	}

	public function getKey()
	{
		return $this->_key;
	}

	public function getValue()
	{
		return $this->_value;
	}

	public function getParameter()
	{
		return $this->_parameterObject;
	}

	public function &getMap()
	{
		return $this->_map;
	}
}