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
use Prado\TModule;

/**
 * TDataSourceConfig class
 *
 * TDataSourceConfig module class provides <module> configuration for database connections.
 *
 * XML configuration style, for example with a MySQL connection:
 * ```xml
 * <modules>
 *   <module id="db1" class="Prado\Data\TDataSourceConfig">
 *     <database ConnectionString="mysql:host=localhost;dbname=mydb"
 *       Username="dbuser" Password="dbpass" />
 *   </module>
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'db1' => [
 *             'class' => 'Prado\Data\TDataSourceConfig',
 *             'database' => [
 *                 'ConnectionString' => 'mysql:host=localhost;dbname=mydb',
 *                 'Username' => 'dbuser',
 *                 'Password' => 'dbpass',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * The configured connection may then be retrieved in page or component code:
 * ```php
 * class Home extends TPage
 * {
 *   public function onLoad($param)
 *   {
 *     $db = $this->Application->Modules['db1']->DbConnection;
 *     $db->createCommand('SELECT ...')->query();
 *   }
 * }
 * ```
 *
 * The properties of the {@see \Prado\Data\TDbConnection} are set via attributes on the
 * nested `<database ... />` element, routed through {@see init}.
 * Set the {@see setConnectionClass ConnectionClass} attribute to use a custom
 * database connection class that extends {@see \Prado\Data\TDbConnection}.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TDataSourceConfig extends TModule
{
	use TDbPropertiesTrait;

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
		} elseif ($config !== null) {
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
	 * Gets the TDbConnection from another module if {@see setConnectionID ConnectionID}
	 * is supplied and valid. Otherwise, a connection of type given by
	 * {@see setConnectionClass ConnectionClass} is created.
	 * @return \Prado\Data\TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		if ($this->_conn === null) {
			$connID = $this->getConnectionID();
			if ($connID !== '') {
				$this->_conn = $this->findConnectionByID($connID);
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
	 * The database connection class name to be created when {@see getDbConnection}
	 * method is called <b>and</b> {@see setConnectionID ConnectionID} is null. The
	 * {@see setConnectionClass ConnectionClass} property must be set before
	 * calling {@see getDbConnection} if you wish to create the connection using the
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
