<?php
/**
 * TDataSourceConfig class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;

/**
 * TDataSourceConfig module class provides <module> configuration for database connections.
 *
 * Example usage: mysql connection
 * <code>
 * <modules>
 * 	<module id="db1">
 * 		<database ConnectionString="mysqli:host=localhost;dbname=test"
 * 			username="dbuser" password="dbpass" />
 * 	</module>
 * </modules>
 * </code>
 *
 * Usage in php:
 * <code>
 * class Home extends TPage
 * {
 * 		function onLoad($param)
 * 		{
 * 			$db = $this->Application->Modules['db1']->DbConnection;
 * 			$db->createCommand('...'); //...
 * 		}
 * }
 * </code>
 *
 * The properties of <connection> are those of the class TDbConnection.
 * Set {@link setConnectionClass} attribute for a custom database connection class
 * that extends the TDbConnection class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TDataSourceConfig extends \Prado\TModule
{
	private $_connID = '';
	private $_conn;
	private $_connClass = \Prado\Data\TDbConnection::class;

	/**
	 * Initalize the database connection properties from attributes in <database> tag.
	 * @param \Prado\Xml\TXmlDocument $config xml or php configuration.
	 */
	public function init($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			if (isset($config['database']) && is_array($config['database'])) {
				$db = $this->getDbConnection();
				foreach ($config['database'] as $name => $value) {
					$db->setSubProperty($name, $value);
				}
			}
		} else {
			if ($prop = $config->getElementByTagName('database')) {
				$db = $this->getDbConnection();
				foreach ($prop->getAttributes() as $name => $value) {
					$db->setSubproperty($name, $value);
				}
			}
		}
		parent::init($config);
	}

	/**
	 * The module ID of another TDataSourceConfig. The {@link getDbConnection DbConnection}
	 * property of this configuration will equal to {@link getDbConnection DbConnection}
	 * of the given TDataSourceConfig module.
	 * @param string $value module ID.
	 */
	public function setConnectionID($value)
	{
		$this->_connID = $value;
	}

	/**
	 * @return string connection module ID.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Gets the TDbConnection from another module if {@link setConnectionID ConnectionID}
	 * is supplied and valid. Otherwise, a connection of type given by
	 * {@link setConnectionClass ConnectionClass} is created.
	 * @return \Prado\Data\TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		if ($this->_conn === null) {
			if ($this->_connID !== '') {
				$this->_conn = $this->findConnectionByID($this->getConnectionID());
			} else {
				$this->_conn = Prado::createComponent($this->getConnectionClass());
			}
		}
		return $this->_conn;
	}

	/**
	 * Alias for getDbConnection().
	 * @return \Prado\Data\TDbConnection database connection.
	 */
	public function getDatabase()
	{
		return $this->getDbConnection();
	}

	/**
	 * @return string Database connection class name to be created.
	 */
	public function getConnectionClass()
	{
		return $this->_connClass;
	}

	/**
	 * The database connection class name to be created when {@link getDbConnection}
	 * method is called <b>and</b> {@link setConnectionID ConnectionID} is null. The
	 * {@link setConnectionClass ConnectionClass} property must be set before
	 * calling {@link getDbConnection} if you wish to create the connection using the
	 * given class name.
	 * @param string $value Database connection class name.
	 * @throws TConfigurationException when database connection is already established.
	 */
	public function setConnectionClass($value)
	{
		if ($this->_conn !== null) {
			throw new TConfigurationException('datasource_dbconnection_exists', $value);
		}
		$this->_connClass = $value;
	}

	/**
	 * Finds the database connection instance from the Application modules.
	 * @param string $id Database connection module ID.
	 * @throws TConfigurationException when module is not of TDbConnection or TDataSourceConfig.
	 * @return \Prado\Data\TDbConnection database connection.
	 */
	protected function findConnectionByID($id)
	{
		$conn = $this->getApplication()->getModule($id);
		if ($conn instanceof TDbConnection) {
			return $conn;
		} elseif ($conn instanceof TDataSourceConfig) {
			return $conn->getDbConnection();
		} else {
			throw new TConfigurationException('datasource_dbconnection_invalid', $id);
		}
	}
}
