<?php

/**
 * TDbPluginModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */
	
namespace Prado\Util;

use Prado\Util\XTPluginModule;
use Prado\Data\TDataSourceConfig;
use Prado\Exceptions\TConfigurationException;

/**
 * TDbPluginModule class.
 *
 * TDbPluginModule adds database connectivity to the plugin modules. This standardizes
 * the Database Connectivity for Plugins. Also TParameterizeBehavior can be used to set
 * all TDbPluginModule::ConnectionID with one setting.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado
 * @since 4.2.0
 */

class TDbPluginModule extends TPluginModule
{
	/**
	 * @var string the ID of TDataSourceConfig module
	 */
	private $_connID = '';
	/**
	 * @var TDbConnection the DB connection instance
	 */
	private $_conn;

	/**
	 * @return string the ID of a TDataSourceConfig module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection
	 * that will be used by the plugin.
	 * @param string $value module ID.
	 */
	public function setConnectionID($value)
	{
		$this->_connID = $value;
	}

	/**
	 * @return TDbConnection the database connection that may be used to retrieve user data.
	 */
	public function getDbConnection()
	{
		if ($this->_conn === null) {
			$this->_conn = $this->createDbConnection($this->_connID);
			$this->_conn->setActive(true);
		}
		return $this->_conn;
	}

	/**
	 * Creates the DB connection.  Override this method to create a sqlite
	 * database when no connection ID is available.
	 * @param string $connectionID the module ID for TDataSourceConfig
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return TDbConnection the created DB connection
	 */
	protected function createDbConnection($connectionID)
	{
		if ($connectionID !== '') {
			$conn = $this->getApplication()->getModule($connectionID);
			if ($conn instanceof TDataSourceConfig) {
				return $conn->getDbConnection();
			} else {
				throw new TConfigurationException('dbusermanager_connectionid_invalid', $connectionID);
			}
		} else {
			throw new TConfigurationException('dbusermanager_connectionid_required');
		}
	}
}
