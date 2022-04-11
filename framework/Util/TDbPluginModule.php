<?php

/**
 * TDbPluginModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;

/**
 * TDbPluginModule class.
 *
 * TDbPluginModule adds database connectivity to the plugin modules. This standardizes
 * the Database Connectivity for Plugins. Also TParameterizeBehavior can be used to set
 * all TDbPluginModule::ConnectionID with one setting.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TDbPluginModule extends TPluginModule implements \Prado\Util\IDbModule
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
	 * Creates the DB connection.  If no ConnectionId is available, this
	 * will try to start a sqlite database if the subclass has a name via
	 * {@link getSqliteDatabaseName}.
	 * @param string $connectionID the module ID for TDataSourceConfig
	 * @throws TConfigurationException if module ID is invalid or empty
	 * without a Sqlite database.
	 * @return TDbConnection the created DB connection
	 */
	protected function createDbConnection($connectionID)
	{
		if ($connectionID !== '') {
			$conn = $this->getApplication()->getModule($connectionID);
			if ($conn instanceof TDataSourceConfig) {
				return $conn->getDbConnection();
			} else {
				throw new TConfigurationException('dbpluginmodule_connectionid_invalid', $connectionID);
			}
		} else {
			if ($file = $this->getSqliteDatabaseName()) {
				$db = new TDbConnection();
				// default to SQLite3 database
				$dbFile = $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . $file;
				$db->setConnectionString('sqlite:' . $dbFile);
				return $db;
			} else {
				throw new TConfigurationException('dbpluginmodule_connectionid_required');
			}
		}
	}

	/**
	 * @return null|string if the sub-class wants a sqlite db then return the name.
	 */
	protected function getSqliteDatabaseName()
	{
		return null;
	}
}
