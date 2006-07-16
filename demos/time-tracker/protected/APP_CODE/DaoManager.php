<?php
/**
 * DaoManager class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $16/07/2006: $
 * @package Demos
 */
 
/**
 * DaoManager class.
 * 
 * A Registry for Dao and an implementation of that type.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $16/07/2006: $
 * @package Demos
 * @since 3.1
 */
class DaoManager extends TModule
{
	/**
	 * @var TSqlMapper sqlmap client
	 */
	private $_connection;
	/**
	 * @var boolean if the module has been initialized
	 */
	private $_initialized=false;	
	/**
	 * @var array registered list of dao
	 */
	private $_dao=array();
	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It loads dao information from the module configuration.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{ 	
		if($this->_connection === null)
			throw new TimeTrackerException('daomanager_connection_required');
		$app = $this->getApplication();
		if(is_string($this->_connection))
		{
			if(($conn=$app->getModule($this->_connection)->getClient())===null)
				throw new TimeTrackerException('daomanager_undefined_connection',$this->_connection);
			if(!($conn instanceof TSqlMapper))
				throw new TimeTrackerException('daomanager_invalid_connection',	$this->_connection);
			$this->_connection = $conn;
		}
		$this->includeDaoImplementation($config->getElementsByTagName('dao'));
		$this->_initialized = true;
	}
	
	/**
	 * Register the dao type and implementation class names.
	 * @param array list of TXmlDocument nodes.
	 */
	protected function includeDaoImplementation($nodes)
	{
		foreach($nodes as $node)
		{
			$id = $node->getAttribute('id');
			$class = $node->getAttribute('class');
			$this->_dao[$id] = array('class' => $class);
		}
	}
	
	/**
	 * @return array list of registered Daos
	 */
	public function getDaos()
	{
		return $this->_dao;
	}
	
	/**
	 * Returns an implementation of a Dao type, implements the Registery
	 * pattern. Multiple calls returns the same Dao instance.
	 * @param string Dao type to find.
	 * @return object instance of the Dao implementation.
	 */
	public function getDao($class)
	{
		if(isset($this->_dao[$class]))
		{
			if(!isset($this->_dao[$class]['instance']))
			{
				$dao = Prado::createComponent($this->_dao[$class]['class']);
				$dao->setConnection($this->getConnection());
				$this->_dao[$class]['instance'] = $dao;	
			}
			return $this->_dao[$class]['instance'];
		}
		else
			throw TimeTrackerException('daomanager_undefined_dao', $class);
	}
	
	/**
	 * @return TSqlMapper sqlmap client instance
	 */
	public function getConnection()
	{
		return $this->_connection;
	}
	
	/**
	 * Sets the connection for all Daos registered.
	 * @param string|TSqlMapper sqlmap client module id or TSqlMapper instance.
	 */
	public function setConnection($client)
	{
		if($this->_initialized)
			throw new TimeTrackerException('daomanager_unchangeable');
		if(!is_string($client) && !($client instanceof TSqlMapper))
			throw new TConfigurationException('daomanager_invalid_connection',$client);
		$this->_connection = $client;
	}
}

?>