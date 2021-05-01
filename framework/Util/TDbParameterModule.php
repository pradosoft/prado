<?php
/**
 * TDbParameterModule class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

use PDO;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Util\Behaviors\TMapLazyLoadBehavior;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TConfigurationException;

/**
 * TDbParameterModule class
 *
 * This loads application parameters from a database.  It adds the
 * {@link TMapLazyLoadBehavior} to Application Parameters when the
 * {@link setAutoLoadField} is set.  The key and name fields, table,
 * autoload field, and autoload values (both true and false values)
 * are parameterized.  Set them to your application specific values.
 *
 * The following will read the options from a WordPress Database:
 * <code>
 *		<module id="dbparams" class="Prado\Util\TDbParameterModule"
 * ConnectionID="DB" KeyField="option_name" ValueField="option_value" TableName="wp_options" Serializer="php"
 * autoLoadField="autoload" autoLoadValue="'yes'" autoLoadValueFalse="'no'"/>
 * </code>
 *
 * This allows for setting and removal of application parameters
 * into and from the database through {@link set} and
 * {@link remove}, respectively. Arrays and Objects are
 * serialized.  The specific serializer can be chose to be 'php',
 * 'json', or provide your own function or callable.  Default to 'php'.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util
 * @since 4.2.0
 */
class TDbParameterModule extends TModule
{
	const SERIALIZE_PHP = 'php';
	const SERIALIZE_JSON = 'json';
	/**
	 * The name of the Application Parameter Lazy Load Behavior
	 */
	const APP_PARAMETER_LAZY_BEHAVIOR = 'lazyDbParameter';
	/**
	 * @var string the ID of TDataSourceConfig module
	 */
	private $_connID = '';
	
	/**
	 * @var TDbConnection the DB connection instance
	 */
	private $_conn;
	
	/**
	 * @var bool whether or not the database parameters have been loaded.
	 * when true none of the variables can be changed
	 */
	private $_initialized = false;
	
	/**
	 * @var string The key field for the parameter from the database
	 */
	private $_keyField = 'option_key';
	
	/**
	 * @var string The value field for the parameter from the database
	 */
	private $_valueField = 'option_value';
	
	/**
	 * @var string The table name for the parameters from the database
	 */
	private $_tableName = 'options';
	
	/**
	 * @var string autoload Field. default "", meaning no autoload field
	 */
	private $_autoLoadField = '';
	
	/**
	 * @var string autoload True value. default sql "true"
	 */
	private $_autoLoadValue = 'true';
	
	/**
	 * @var string autoload False value. default sql "false"
	 */
	private $_autoLoadValueFalse = 'false';
	
	/**
	 * @var callable|string which serialize function to use,
	 */
	private $_serializer = self::SERIALIZE_PHP;

	
	/**
	 * Initializes the module by loading parameters.
	 * @param mixed $config content enclosed within the module tag
	 */
	public function init($config)
	{
		$this->loadDbParameters();
		$this->_initialized = true;

		if ($this->getConnectionID() && $this->_autoLoadField) {
			$this->getApplication()->getParameters()->attachBehavior(self::APP_PARAMETER_LAZY_BEHAVIOR, new TMapLazyLoadBehavior([$this, 'getParameter']));
		}
		parent::init($config);
	}
	
	/**
	 * Loads parameters from the database into the application.
	 * @throws TDbException if the Fields and table is not correct
	 */
	protected function loadDbParameters()
	{
		if (!$this->getConnectionID()) {
			return;
		}
		
		$connection = $this->getDbConnection();
		$where = ($this->_autoLoadField ? " WHERE {$this->_autoLoadField}={$this->_autoLoadValue}" : '');
		$cmd = $connection->createCommand(
			"SELECT {$this->_keyField}, {$this->_valueField} FROM {$this->_tableName}{$where}"
		);
		$results = $cmd->query();
		
		$appParameters = $this->getApplication()->getParameters();
		$serializer = $this->getSerializer();
		foreach ($results->readAll() as $row) {
			$value = $row[$this->_valueField];
			if ($serializer == self::SERIALIZE_PHP) {
				if (($avalue = @unserialize($value)) !== false) {
					$value = $avalue;
				}
			} elseif ($serializer == self::SERIALIZE_JSON) {
				if (($avalue = json_decode($value, true)) !== null) {
					$value = $avalue;
				}
			} elseif ($serializer) {
				if (($avalue = call_user_func($serializer, $value, false)) !== null) {
					$value = $avalue;
				}
			}
			$appParameters[$row[$this->_keyField]] = $value;
		}
	}
	
	/**
	 * Loads parameters into application.
	 * @param string $key key to get the value
	 * @param bool $checkParameter checks the Application Parameters first
	 * @throws TInvalidOperationException if the $key is blank
	 * @throws TDbException if the Fields and table is not correct
	 */
	public function get($key, $checkParameter = true)
	{
		if ($key == '') {
			throw new TInvalidOperationException('dbparametermodule_getparameter_blank_key');
		}
		
		if ($checkParameter) {
			$appParams = $this->getApplication()->getParameters();
			if (isset($appParams[$key])) {
				return $appParams[$key];
			}
		}
		$connection = $this->getDbConnection();
		$cmd = $connection->createCommand(
			"SELECT {$this->_valueField} FROM {$this->_tableName} WHERE {$this->_keyField}=:key LIMIT 1"
		);
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$results = $cmd->queryRow();
		$serializer = $this->getSerializer();
		if (is_array($results) && ($value = $results[$this->_valueField])) {
			$appParams = $this->getApplication()->getParameters();
			if ($serializer == self::SERIALIZE_PHP) {
				if (($avalue = @unserialize($value)) !== false) {
					$value = $avalue;
				}
			} elseif ($serializer == self::SERIALIZE_JSON) {
				if (($avalue = json_decode($value, true)) !== null) {
					$value = $avalue;
				}
			} elseif ($serializer && ($avalue = call_user_func($serializer, $value, false)) !== null) {
				$value = $avalue;
			}
			$appParams[$key] = $value;
			return $value;
		}
		return null;
	}
	
	/**
	 * Sets a parameter in the database and the Application Parameter.
	 * @param string $key the key of the parameter
	 * @param mixed $value the key of the parameter
	 * @param bool $autoLoad should the key be autoloaded at init
	 * @throws TInvalidOperationException if the $key is blank
	 * @throws TDbException if the Fields and table is not correct
	 * @throws
	 */
	public function set($key, $value, $autoLoad = true)
	{
		if (empty($key)) {
			throw new TInvalidOperationException('dbparametermodule_setparameter_blank_key');
		}
			
		if (($serializer = $this->getSerializer()) && (is_array($value) || is_object($value))) {
			if ($serializer == self::SERIALIZE_PHP) {
				$value = @serialize($value);
			} elseif ($serializer == self::SERIALIZE_JSON) {
				$value = json_encode($value, JSON_UNESCAPED_UNICODE);
			} else {
				$value = call_user_func($serializer, $value, true);
			}
		}
		$connection = $this->getDbConnection();
		$field = ($this->_autoLoadField ? ", {$this->_autoLoadField}" : '');
		$values = ($this->_autoLoadField ? ", :auto" : '');
		$dupl = ($this->_autoLoadField ? ", {$this->_autoLoadField}=values({$this->_autoLoadField})" : '');
		$cmd = $connection->createCommand("INSERT INTO {$this->_tableName} ({$this->_keyField}, {$this->_valueField}{$field}) " .
					"VALUES (:key, :value{$values}) ON DUPLICATE KEY UPDATE {$this->_valueField}=values({$this->_valueField}){$dupl}");
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$cmd->bindParameter(":value", $value, PDO::PARAM_STR);
		if ($this->_autoLoadField) {
			$alv = $autoLoad ? $this->_autoLoadValue : $this->_autoLoadValueFalse;
			$cmd->bindParameter(":auto", $alv, PDO::PARAM_STR);
		}
		$cmd->execute();
		
		$appParameters = $this->getApplication()->getParameters();
		$appParameters[$key] = $value;
	}
	
	/**
	 * exists checks for a parameter in the database
	 * @param $key string parameter to check in the database
	 * @throws TDbException if the Fields and table is not correct
	 * @return mixed the value of the parameter, one last time
	 */
	public function exists($key)
	{
		$connection = $this->getDbConnection();
		$cmd = $connection->createCommand(
			"SELECT COUNT(*) AS count FROM {$this->_tableName} WHERE {$this->_keyField}=:key"
		);
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$result = $cmd->queryRow();
		return $result['count'];
	}
	
	/**
	 * remove removes a parameter from the database
	 * @param $key string parameter to remove from the database
	 * @throws TDbException if the Fields and table is not correct
	 * @return mixed the value of the parameter, one last time
	 */
	public function remove($key)
	{
		$value = $this->get($key);
		$connection = $this->getDbConnection();
		$cmd = $connection->createCommand("DELETE FROM {$this->_tableName} WHERE {$this->_keyField}=:key LIMIT 1");
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$cmd->execute();
		return $value;
	}
	
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
	 * that will be used by the user manager.
	 * @param string $value module ID.
	 */
	public function setConnectionID($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_connectionid_unchangeable');
		}
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
	 * Creates the DB connection.
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
				throw new TConfigurationException('dbparametermodule_connectionid_invalid', $connectionID);
			}
		} else {
			throw new TConfigurationException('dbparametermodule_connectionid_required');
		}
	}

	/**
	 * @return string the database parameter key field
	 */
	public function getKeyField()
	{
		return $this->_keyField;
	}

	/**
	 * @param string $value database parameter key field
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setKeyField($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_keyfield_unchangeable');
		}
		$this->_keyField = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the database parameter key value
	 */
	public function getValueField()
	{
		return $this->_valueField;
	}

	/**
	 * @param string $value database parameter key value
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setValueField($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_valuefield_unchangeable');
		}
		$this->_valueField = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the database parameter key value
	 */
	public function getTableName()
	{
		return $this->_tableName;
	}

	/**
	 * @param string $value database parameter key value
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setTableName($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_tablename_unchangeable');
		}
		$this->_tableName = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the database parameter key value
	 */
	public function getAutoLoadField()
	{
		return $this->_autoLoadField;
	}

	/**
	 * @param $value string database parameter key value
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setAutoLoadField($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_autoloadfield_unchangeable');
		}
		$this->_autoLoadField = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the database parameter key value
	 */
	public function getAutoLoadValue()
	{
		return $this->_autoLoadValue;
	}

	/**
	 * @param string $value database parameter key value
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setAutoLoadValue($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_autoloadvalue_unchangeable');
		}
		$this->_autoLoadValue = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the database parameter key value
	 */
	public function getAutoLoadValueFalse()
	{
		return $this->_autoLoadValueFalse;
	}

	/**
	 * @param string $value database parameter key value
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setAutoLoadValueFalse($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_autoloadvaluefalse_unchangeable');
		}
		$this->_autoLoadValueFalse = TPropertyValue::ensureString($value);
	}
	
	/**
	 * @return null|callable|string
	 */
	public function getSerializer()
	{
		return $this->_serializer;
	}

	/**
	 * Serializer sets the type of serialization of objects and arrays in parameters
	 * to and from the database.  'php' uses serialze and unserialize. 'json' uses
	 * json_encode and json_decade. or you can provide your own callable to serialized
	 * and unserialize objects and arrays.
	 * @param callable|string $value the type of un/serialization.
	 * @throws TInvalidOperationException if the module is initialized
	 */
	public function setSerializer($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbparametermodule_serializer_unchangeable');
		}
		$this->_serializer = $value;
	}
}
