<?php

/**
 * TDbPropertiesTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;

/**
 * TDbPropertiesTrait class.
 *
 * This trait provides database connection management functionality for classes
 * that need to connect to a database. It supports both explicit connection
 * configuration via a TDataSourceConfig module ID and automatic SQLite
 * database creation when no connection is specified.
 *
 * Classes using this trait can override:
 * - getSqliteDatabaseName() to enable automatic SQLite database creation in the runtime path
 * - getConnectionInvalidExceptionKey() to customize the exception message when ConnectionID is invalid
 * - getConnectionRequiredExceptionKey() to customize the exception message when no connection is configured
 *
 * There is a {@see getTableGateway()} method to access a database connection via {@see TTableGateway}.
 * This provides an abstraction to the databases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait TDbPropertiesTrait
{
	/**
	 * @var string the ID of TDataSourceConfig module
	 */
	private $_connID = '';

	/**
	 * @var null|TDbConnection the DB connection instance
	 */
	private $_conn;

	/**
	 * @var null|TTableGateway[] the cache of table gateways
	 */
	private $_gateways;

	/**
	 * Database class destruct.
	 *
	 * This calls deactivateDbConnection(true) and then parent::__destruct() if available.
	 * It ensures the database connection is properly closed when the object is destroyed.
	 */
	public function __destruct()
	{
		$this->deactivateDbConnection(true);
		if (is_callable([parent::class, '__destruct'])) {
			parent::__destruct();
		}
	}

	/**
	 * Gets the ID of a TDataSourceConfig module.
	 *
	 * The datasource module will be used to establish the DB connection
	 * that will be used by the class implementing this trait.
	 *
	 * @return string the ID of a TDataSourceConfig module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID(): string
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 *
	 * The datasource module will be used to establish the DB connection
	 * that will be used by the class implementing this trait.
	 *
	 * @param mixed $value ID of the TDataSourceConfig module
	 */
	public function setConnectionID($value)
	{
		$this->_connID = $value;
	}

	/**
	 * Gets the database connection.
	 *
	 * If no connection has been established, it will be created via
	 * createDbConnection() and activated.
	 *
	 * @throws TConfigurationException if the connection cannot be established
	 * @return TDbConnection the database connection that may be used to retrieve data.
	 */
	public function getDbConnection(): TDbConnection
	{
		if ($this->_conn === null) {
			$this->_conn = $this->createDbConnection();
			$this->_conn->setActive(true);
		}
		return $this->_conn;
	}

	/**
	 * Checks if a database connection has been established.
	 *
	 * @return bool true if a connection exists, false otherwise
	 */
	public function getHasDbConnection(): bool
	{
		return $this->_conn !== null;
	}

	/**
	 * Deactivates the database connection.
	 *
	 * When the Connection is present, this sets the connection active to false.
	 * Optionally dereferences the connection entirely.
	 *
	 * @param bool $clearConnection Default false. When true, dereferences and clears the Connection.
	 * @return static The current object
	 */
	public function deactivateDbConnection(bool $clearConnection = false): static
	{
		if ($this->getHasDbConnection()) {
			$this->_conn->setActive(false);
			if ($clearConnection) {
				$this->_conn = null;
			}
		}
		return $this;
	}

	/**
	 * Creates the DB connection.
	 *
	 * If no ConnectionID is available, this will try to start a sqlite database
	 * if the subclass has a name via getSqliteDatabaseName().
	 *
	 * @param null|string $connectionID the module ID for TDataSourceConfig. If null, uses getConnectionID().
	 * @throws TConfigurationException if module ID is invalid or empty without a Sqlite database.
	 * @return TDbConnection the created DB connection
	 */
	protected function createDbConnection(?string $connectionID = null): TDbConnection
	{
		if ($connectionID === null) {
			$connectionID = $this->getConnectionID();
		}
		$app = null;
		if (method_exists($this, 'getApplication')) {
			if (($v = $this->getApplication()) && $v->isa(\Prado\TApplication::class)) {
				$app = $v;
			}
		}
		if (!$app) {
			$app = Prado::getApplication();
		}
		if ($connectionID !== '') {
			$conn = $app->getModule($connectionID);
			if ($conn instanceof TDataSourceConfig) {
				return $conn->getDbConnection();
			} else {
				throw new TConfigurationException(
					$this->getConnectionInvalidExceptionKey(),
					$connectionID,
					array_slice(explode('\\', static::class), -1)[0]
				);
			}
		} else {
			if ($db = $this->getCustomDbConnection()) {
				return $db;
			}
			if ($sqliteFile = $this->getSqliteDatabaseName()) {
				$db = new TDbConnection();
				$dbFile = $app->getRuntimePath() . DIRECTORY_SEPARATOR . $sqliteFile;
				$db->setConnectionString('sqlite:' . $dbFile);
				return $db;
			} else {
				throw new TConfigurationException(
					$this->getConnectionRequiredExceptionKey(),
					'ConnectionID',
					array_slice(explode('\\', static::class), -1)[0]
				);
			}
		}
	}

	/**
	 * This is for the using class to override with their own custom connection when the
	 * ConnectionID is missing.
	 * @return ?TDbConnection the custom DB connection, or null if not implemented
	 */
	protected function getCustomDbConnection(): ?TDbConnection
	{
		return null;
	}

	/**
	 * Returns the name of the SQLite database file to create.
	 *
	 * When the class overrides this method, createDbConnection will try to
	 * start a sqlite database in the PRADO Runtime Path.
	 *
	 * @return null|string if the using class wants a sqlite db then return the name, otherwise null
	 */
	protected function getSqliteDatabaseName(): ?string
	{
		return null;
	}

	/**
	 * Returns the error message key when createDbConnection could not find the ConnectionID.
	 *
	 * Subclasses can override this to provide custom error message keys.
	 *
	 * @return string the error message key
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'dbproperties_connectionid_invalid';
	}

	/**
	 * Returns the error message key when createDbConnection has no ConnectionID and no sqlite database.
	 *
	 * Subclasses can override this to provide custom error message keys.
	 *
	 * @return string the error message key
	 */
	protected function getConnectionRequiredExceptionKey(): string
	{
		return 'dbproperties_property_required';
	}

	/**
	 * Returns a cached TTableGateway instance for the specified table.
	 *
	 * The TTableGateway provides database agnostic table access. Gateway instances
	 * are cached per table name to avoid creating duplicate gateways.
	 *
	 * @param string $tableName the name of the table for the Gateway
	 * @return TTableGateway the table gateway instance
	 */
	public function getTableGateway(string $tableName): TTableGateway
	{
		if ($this->_gateways === null) {
			$this->_gateways = [];
		}
		if (isset($this->_gateways[$tableName])) {
			return $this->_gateways[$tableName];
		}
		$gateway = new TTableGateway($tableName, $this->getDbConnection());
		$this->_gateways[$tableName] = $gateway;
		return $gateway;
	}
}
