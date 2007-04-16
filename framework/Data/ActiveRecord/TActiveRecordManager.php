<?php
/**
 * TActiveRecordManager class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

Prado::using('System.Data.TDbConnection');
Prado::using('System.Data.ActiveRecord.TActiveRecord');
Prado::using('System.Data.ActiveRecord.Exceptions.TActiveRecordException');
Prado::using('System.Data.ActiveRecord.TActiveRecordGateway');
Prado::using('System.Data.ActiveRecord.TActiveRecordStateRegistry');

/**
 * TActiveRecordManager provides the default DB connection, default object state
 * registry, default active record gateway, and table meta data inspector.
 *
 * The default connection can be set as follows:
 * <code>
 * TActiveRecordManager::getInstance()->setDbConnection($conn);
 * </code>
 * All new active record created after setting the
 * {@link DbConnection setDbConnection()} will use that connection unless
 * the custom ActiveRecord class overrides the ActiveRecord::getDbConnection().
 *
 * Set the {@link setCache Cache} property to an ICache object to allow
 * the active record gateway to cache the table meta data information.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordManager extends TComponent
{
	private $_objectRegistry;
	private $_gateway;
	private $_meta=array();
	private $_connection;

	private $_cache;

	/**
	 * @return ICache application cache.
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * @param ICache application cache
	 */
	public function setCache($value)
	{
		$this->_cache=$value;
	}

	/**
	 * @param TDbConnection default database connection
	 */
	public function setDbConnection($conn)
	{
		$this->_connection=$conn;
	}

	/**
	 * @return TDbConnection default database connection
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return TActiveRecordManager static instance of record manager.
	 */
	public static function getInstance($self=null)
	{
		static $instance;
		if($self!==null)
			$instance=$self;
		else if($instance===null)
			$instance = new self;
		return $instance;
	}

	/**
	 * @return TActiveRecordStateRegistry record object registry.
	 */
	public function getObjectStateRegistry()
	{
		if(is_null($this->_objectRegistry))
			$this->_objectRegistry = $this->createObjectStateRegistry();
		return $this->_objectRegistry;
	}

	/**
	 * @return TActiveRecordStateRegistry default object registry.
	 */
	protected function createObjectStateRegistry()
	{
		return new TActiveRecordStateRegistry();
	}

	/**
	 * @return TActiveRecordGateway record gateway.
	 */
	public function getRecordGateway()
	{
		if(is_null($this->_gateway))
			$this->_gateway = $this->createRecordGateway();
		return $this->_gateway;
	}

	/**
	 * @return TActiveRecordGateway default record gateway.
	 */
	protected function createRecordGateway()
	{
		return new TActiveRecordGateway($this);
	}
}


?>