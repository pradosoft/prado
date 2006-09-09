<?php
/**
 * Base DAO class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package Demos
 */

/**
 * Base DAO class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package Demos
 * @since 3.1
 */
class BaseDao
{
	/**
	 * @var TSqlMapper sqlmap client.
	 */
	private $_connection;
	
	/**
	 * @param TSqlMapper sqlmap client.
	 */
	public function setConnection($connection)
	{
		$this->_connection = $connection;
	}
	
	/**
	 * @return TSqlMapper sqlmap client.
	 */
	protected function getConnection()
	{
		return $this->_connection;
	}
}

?>