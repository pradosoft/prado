<?php
/**
 * TActiveRecordManager and TActiveRecordEventParameter classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

Prado::using('System.Data.TDbConnection');
Prado::using('System.Data.ActiveRecord.Exceptions.TActiveRecordException');
Prado::using('System.Data.ActiveRecord.TActiveRecordGateway');
Prado::using('System.Data.ActiveRecord.TActiveRecordStateRegistry');

/**
 * TActiveRecordManager provides the default DB connection, default object state
 * registry, default active record gateway, and table meta data inspector.
 *
 * You can provide a different registry by overriding the createObjectStateRegistry() method.
 * Similarly, override createRecordGateway() for default gateway and override
 * createMetaDataInspector() for meta data inspector.
 *
 * The default connection can be set as follows:
 * <code>
 * TActiveRecordManager::getInstance()->setDbConnection($conn);
 * </code>
 * All new active record created after setting the
 * {@link DbConnection setDbConnection()} will use that connection.
 *
 * The {@link OnInsert onInsert()}, {@link OnUpdate onUpdate()},
 * {@link OnDelete onDelete()} and {@link onSelect onSelect()} events are raised
 * <b>before</b> their respective command are executed.
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
	public static function getInstance()
	{
		static $instance;
		if($instance===null)
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

	/**
	 * Get table meta data for particular database and table.
	 * @param TDbConnection database connection.
	 * @return TDbMetaDataInspector table meta inspector
	 */
	public function getTableInspector(TDbConnection $conn)
	{
		$database = $conn->getConnectionString();
		if(!isset($this->_meta[$database]))
			$this->_meta[$database] = $this->createMetaDataInspector($conn);
		return $this->_meta[$database];
	}

	/**
	 * Create an instance of a database meta inspector corresponding to the
	 * given database vendor specified by the $driver parameter.
	 * @param TDbConnection database connection
	 * @return TDbMetaDataInspector table meta inspector
	 */
	protected function createMetaDataInspector($conn)
	{
		$conn->setActive(true); //must be connected before retrieving driver name!
		$driver = $conn->getDriverName();
		switch(strtolower($driver))
		{
			case 'pgsql':
				Prado::using('System.Data.ActiveRecord.Vendor.TPgsqlMetaDataInspector');
				return new TPgsqlMetaDataInspector($conn);
			case 'mysqli':
			case 'mysql':
				Prado::using('System.Data.ActiveRecord.Vendor.TMysqlMetaDataInspector');
				return new TMysqlMetaDataInspector($conn);
			case 'sqlite': //sqlite 3
			case 'sqlite2': //sqlite 2
				Prado::using('System.Data.ActiveRecord.Vendor.TSqliteMetaDataInspector');
				return new TSqliteMetaDataInspector($conn);
			default:
				throw new TActiveRecordConfigurationException(
					'ar_invalid_database_driver',$driver);
		}
	}

	/**
	 * This method is invoked before the object is inserted into the database.
	 * The method raises 'OnInsert' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TActiveRecordEventParameter event parameter to be passed to the event handlers
	 */
	public function onInsert($param)
	{
		$this->raiseEvent('OnInsert', $this, $param);
	}

	/**
	 * This method is invoked before the object is deleted from the database.
	 * The method raises 'OnDelete' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TActiveRecordEventParameter event parameter to be passed to the event handlers
	 */
	public function onDelete($param)
	{
		$this->raiseEvent('OnDelete', $this, $param);
	}

	/**
	 * This method is invoked before the object data is updated in the database.
	 * The method raises 'OnUpdate' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TActiveRecordEventParameter event parameter to be passed to the event handlers
	 */
	public function onUpdate($param)
	{
		$this->raiseEvent('OnUpdate', $this, $param);
	}

	/**
	 * This method is invoked before any select query is executed on the database.
	 * The method raises 'OnSelect' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TActiveRecordEventParameter event parameter to be passed to the event handlers
	 */
	public function onSelect($param)
	{
		$this->raiseEvent('OnSelect', $this, $param);
	}
}

/**
 * TActiveRecordEventParameter class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordEventParameter extends TEventParameter
{

}

?>