<?php
/**
 * Base DAO class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Demos
 */

/**
 * Base DAO class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Demos
 * @since 3.1
 */
class BaseDao
{
	/**
	 * @var TSqlMapGateway sqlmap client.
	 */
	private $_sqlmap;

	/**
	 * @param TSqlMapGateway sqlmap client.
	 */
	public function setSqlMap($sqlmap)
	{
		$this->_sqlmap = $sqlmap;
	}

	/**
	 * @return TSqlMapGateway sqlmap client.
	 */
	protected function getSqlMap()
	{
		return $this->_sqlmap;
	}
}

