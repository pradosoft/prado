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
 * TResultSetMapItemParameter class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TResultSetMapItemParameter extends \Prado\TComponent
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
		parent::__construct();
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
