<?php

/**
 * TDbParameterModule class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Exception;
use PDO;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Util\Behaviors\TMapLazyLoadBehavior;
use Prado\Util\Behaviors\TMapRouteBehavior;
use Prado\Util\Traits\TInitializedTrait;

/**
 * TDbParameterModule class
 *
 * This loads application parameters from a database.  It adds the
 * {@see \Prado\Util\Behaviors\TMapLazyLoadBehavior} to Application Parameters when the
 * {@see setAutoLoadField} is set.  The key and name fields, table,
 * autoload field, and autoload values (both true and false values)
 * are parameterized.  Set them to your application specific values.
 *
 * The following will load the options from a WordPress Database:
 * ```xml
 *		<module id="dbparams" class="Prado\Util\TDbParameterModule"
 * ConnectionID="DB" KeyField="option_name" ValueField="option_value" TableName="wp_options" Serializer="php"
 * autoLoadField="autoload" autoLoadValue="'yes'" autoLoadValueFalse="'no'"/>
 * ```
 *
 * This allows for setting and removal of application parameters
 * into and from the database through {@see set} and
 * {@see remove}, respectively. Arrays and Objects are
 * serialized.  The specific serializer can be chose to be 'php',
 * 'json', or provide your own function or callable.  Default to 'php'.
 *
 * setting {@see setSerializer} to your own function that has the
 * following format:
 * ```php
 *		function mySerializerFunction($data, $encode) {...}
 * ```
 * If $encode is true, then encode, otherwise decode, to text.
 *
 * When {@see getCaptureParameterChanges} is true, the default,
 * then this will route any changes to the Application Parameters
 * after TPageService::onPreRunPage back to the TDbParameterModule
 * and be saved to the database.  This captures any changes when
 * done by the page or user.  These changes are restored when
 * this module is loaded again.
 *
 * When TPermissionsManager is a module in your app, there is one permission
 * to control user access to its function:
 *  - TDbParameterModule::PERM_PARAM_SHELL 'param_shell' enables the shell command to index, get and set database parameters.
 *
 * The role and rule management functions only work when the TDbParameter Module is specified.
 * The following gives user "admin" and all users with "Administrators" role the
 * permission to access permissions shell and its full functionality:
 * ```xml
 *   <permissionrule name="param_shell" action="allow" users="admin" />
 *   <permissionrule name="param_shell" action="allow" roles="Administrators" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method bool dyRegisterShellAction($returnValue)
 */
class TDbParameterModule extends TDbModule implements IPermissions
{
	use TInitializedTrait;

	public const SERIALIZE_PHP = 'php';

	public const SERIALIZE_JSON = 'json';

	/** The permission for the cron shell */
	public const PERM_PARAM_SHELL = 'param_shell';

	/**
	 * The name of the Application Parameter Lazy Load Behavior
	 */
	public const APP_PARAMETER_LAZY_BEHAVIOR = 'lazyTDbParameter';

	/**
	 * The name of the Application Parameter Lazy Load Behavior
	 */
	public const APP_PARAMETER_SET_BEHAVIOR = 'setTDbParameter';

	/**
	 * @var string The key field for the parameter from the database
	 */
	private $_keyField = 'param_key';

	/**
	 * @var string The value field for the parameter from the database
	 */
	private $_valueField = 'param_value';

	/**
	 * @var string The table name for the parameters from the database
	 */
	private $_tableName = 'parameters';

	/**
	 * @var string autoload Field. default "", meaning no autoload field
	 */
	private $_autoLoadField = 'autoload';

	/**
	 * @var string autoload True value. default sql "1"
	 */
	private $_autoLoadValue = '1';

	/**
	 * @var string autoload False value. default sql "0"
	 */
	private $_autoLoadValueFalse = '0';

	/**
	 * @var bool whether the parameter DB table should be created automatically
	 */
	private $_autoCreate = true;

	/**
	 * @var bool whether ensureTable was called
	 */
	private $_tableEnsured;

	/**
	 * @var callable|string which serialize function to use,
	 */
	private $_serializer = self::SERIALIZE_PHP;

	/**
	 * @var bool automatically capture changes to Parameters after Application Initialize
	 */
	private $_autoCapture = true;

	/**
	 * @var TMapRouteBehavior captures all the changes to the parameters to the db
	 */
	private $_setBehavior;

	/**
	 * Initializes the module by loading parameters.
	 * @param mixed $config content enclosed within the module tag
	 */
	public function init($config)
	{
		$this->loadDbParameters();

		if ($this->_autoLoadField) {
			$this->getApplication()->getParameters()->attachBehavior(self::APP_PARAMETER_LAZY_BEHAVIOR, new TMapLazyLoadBehavior([$this, 'getFromBehavior']));
		}
		if ($this->_autoCapture) {
			$this->getApplication()->attachEventHandler('onBeginRequest', [$this, 'attachTPageServiceHandler']);
		}
		$app = $this->getApplication();
		$app->attachEventHandler('onAuthenticationComplete', [$this, 'registerShellAction']);
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager)
	{
		return [
			new TPermissionEvent(static::PERM_PARAM_SHELL, 'Activates parameter shell commands.', 'dyRegisterShellAction'),
		];
	}

	/**
	 * Loads parameters from the database into the application.
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 */
	protected function loadDbParameters()
	{
		$db = $this->getDbConnection();

		$this->ensureTable();

		$where = ($this->_autoLoadField ? " WHERE {$this->_autoLoadField}={$this->_autoLoadValue}" : '');
		$cmd = $db->createCommand(
			"SELECT {$this->_keyField} as keyField, {$this->_valueField}  as valueField FROM {$this->_tableName}{$where}"
		);
		$results = $cmd->query();

		$appParameters = $this->getApplication()->getParameters();
		$serializer = $this->getSerializer();
		foreach ($results->readAll() as $row) {
			$value = $row['valueField'];
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
			$appParameters[$row['keyField']] = $value;
		}
	}

	/**
	 * TApplication::onBeginRequest Handler that adds {@see attachTPageBehaviors} to
	 * TPageService::onPreRunPage. In turn, this attaches {@see attachTPageBehaviors}
	 * to TPageService to then adds the page behaviors.
	 * @param object $sender the object that raised the event
	 * @param mixed $param parameter of the event
	 */
	public function attachTPageServiceHandler($sender, $param)
	{
		$service = $this->getService();
		if ($service->hasEvent('onPreRunPage')) {
			$service->attachEventHandler('onPreRunPage', [$this, 'attachParameterStorage'], 0);
		}
	}

	/**
	 * @param object $sender sender of this event handler
	 * @param null|mixed $param parameter for the event
	 */
	public function registerShellAction($sender, $param)
	{
		if ($this->dyRegisterShellAction(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => \Prado\Shell\Actions\TDbParameterAction::class, 'DbParameterModule' => $this]);
		}
	}

	/**
	 * This attaches the TMapRouteBehavior on the Parameters.
	 * @param object $sender
	 * @param null|mixed $param
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 */
	public function attachParameterStorage($sender, $param)
	{
		$this->_setBehavior = new TMapRouteBehavior(null, [$this, 'setFromBehavior']);
		$this->getApplication()->getParameters()->attachBehavior(self::APP_PARAMETER_SET_BEHAVIOR, $this->_setBehavior);
	}

	/**
	 * Creates the DB table for storing log messages.
	 * @todo create sequence for PostgreSQL, and other db
	 */
	protected function createDbTable()
	{
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		$autoidAttributes = '';
		$autotype = 'INTEGER';
		$postIndices = '; CREATE UNIQUE INDEX tkey ON ' . $this->_tableName . '(' . $this->_keyField . ');' .
		($this->_autoLoadField ? ' CREATE INDEX tauto ON ' . $this->_tableName . '(' . $this->_autoLoadField . ');' : '');

		switch ($driver) {
			case 'sqlite':
				$autoidAttributes = ' AUTOINCREMENT';
				break;
			case 'postgresql':
				$autotype = 'SERIAL';
				break;
			default:	// mysql
				$autoidAttributes = ' AUTO_INCREMENT';
				break;
		}

		$sql = 'CREATE TABLE ' . $this->_tableName . ' (
			param_id ' . $autotype . ' PRIMARY KEY ' . $autoidAttributes . ', ' .
			$this->_keyField . ' VARCHAR(128) NOT NULL,' .
			$this->_valueField . ' MEDIUMTEXT' .
			($this->_autoLoadField ? ', ' . $this->_autoLoadField . ' BOOLEAN NOT NULL DEFAULT 1' : '') .
			')' . $postIndices;
		$db->createCommand($sql)->execute();
	}

	/**
	 * checks for the table, and if not there and autoCreate, then creates the table else throw error.
	 * @throws \Prado\Exceptions\TConfigurationException if the table does not exist and cannot autoCreate
	 */
	protected function ensureTable()
	{
		if ($this->_tableEnsured) {
			return;
		}
		$this->_tableEnsured = true;
		$db = $this->getDbConnection();
		$sql = 'SELECT * FROM ' . $this->_tableName . ' WHERE 0=1';
		try {
			$db->createCommand($sql)->query()->close();
		} catch (Exception $e) {
			// DB table not exists
			if ($this->_autoCreate) {
				$this->createDbTable();
			} else {
				throw new TConfigurationException('dbparametermodule_table_nonexistent', $this->_tableName);
			}
		}
	}


	/**
	 * Gets a specific parameter parameters into application.
	 * @param string $key key to get the value
	 * @param bool $checkParameter checks the Application Parameters first
	 * @param bool $setParameter should the method set the application parameters
	 * @throws \Prado\Exceptions\TInvalidOperationException if the $key is blank
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return mixed the value of the key
	 */
	public function get($key, $checkParameter = true, $setParameter = true)
	{
		if ($key == '') {
			throw new TInvalidOperationException('dbparametermodule_get_no_blank_key');
		}

		if ($checkParameter) {
			$appParams = $this->getApplication()->getParameters();
			if (isset($appParams[$key])) {
				return $appParams[$key];
			}
		}
		$this->ensureTable();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT {$this->_valueField} as valueField FROM {$this->_tableName} WHERE {$this->_keyField}=:key LIMIT 1"
		);
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$results = $cmd->queryRow();
		$serializer = $this->getSerializer();
		if (is_array($results) && ($value = $results['valueField']) !== null) {
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
			if ($setParameter) {
				$appParams = $this->getApplication()->getParameters();
				$appParams[$key] = $value;
			}
			return $value;
		}
		return null;
	}

	/**
	 * Loads parameters into application.
	 * @param string $key key to get the value
	 * @throws \Prado\Exceptions\TInvalidOperationException if the $key is blank
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return mixed the value
	 */
	public function getFromBehavior($key)
	{
		return $this->get($key, false, $this->_setBehavior === null);
	}

	/**
	 * Sets a parameter in the database and the Application Parameter.
	 * @param string $key the key of the parameter
	 * @param mixed $value the key of the parameter
	 * @param bool $autoLoad should the key be autoloaded at init
	 * @param mixed $setParameter
	 * @throws \Prado\Exceptions\TInvalidOperationException if the $key is blank
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 */
	public function set($key, $value, $autoLoad = true, $setParameter = true)
	{
		if (empty($key)) {
			throw new TInvalidOperationException('dbparametermodule_set_no_blank_key');
		}

		$_value = $value;
		if (($serializer = $this->getSerializer()) && (is_array($value) || is_object($value))) {
			if ($serializer == self::SERIALIZE_PHP) {
				$_value = @serialize($value);
			} elseif ($serializer == self::SERIALIZE_JSON) {
				$_value = json_encode($value, JSON_UNESCAPED_UNICODE);
			} else {
				$_value = call_user_func($serializer, $value, true);
			}
		}
		$this->ensureTable();
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		$appendix = '';
		if ($driver === TDbConnection::DRIVER_MYSQL) {
			$dupl = ($this->_autoLoadField ? ", {$this->_autoLoadField}=values({$this->_autoLoadField})" : '');
			$appendix = " ON DUPLICATE KEY UPDATE {$this->_valueField}=values({$this->_valueField}){$dupl}";
		} else {
			$this->remove($key);
		}
		$field = ($this->_autoLoadField ? ", {$this->_autoLoadField}" : '');
		$values = ($this->_autoLoadField ? ", :auto" : '');
		$cmd = $db->createCommand("INSERT INTO {$this->_tableName} ({$this->_keyField}, {$this->_valueField}{$field}) " .
					"VALUES (:key, :value{$values})" . $appendix);
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$cmd->bindParameter(":value", $_value, PDO::PARAM_STR);
		if ($this->_autoLoadField) {
			$alv = $autoLoad ? $this->_autoLoadValue : $this->_autoLoadValueFalse;
			$cmd->bindParameter(":auto", $alv, PDO::PARAM_STR);
		}
		$cmd->execute();

		if ($setParameter) {
			$appParameters = $this->getApplication()->getParameters();
			$appParameters[$key] = $value;
		}
	}

	/**
	 * Sets a parameter in the database and the Application Parameter.
	 * from changes to the Parameter through a TMapRouteBehavior.
	 * @param string $key the key of the parameter
	 * @param mixed $value the key of the parameter
	 * @throws \Prado\Exceptions\TInvalidOperationException if the $key is blank
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 */
	public function setFromBehavior($key, $value)
	{
		if ($value !== null) {
			$this->set($key, $value, true, false);
		} else {
			$this->remove($key);
		}
	}

	/**
	 * exists checks for a parameter in the database
	 * @param string $key parameter to check in the database
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return bool whether the key exists in the database table
	 */
	public function exists($key)
	{
		$this->ensureTable();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT COUNT(*) AS count FROM {$this->_tableName} WHERE {$this->_keyField}=:key"
		);
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$result = $cmd->queryRow();
		return $result['count'] > 0;
	}

	/**
	 * remove removes a parameter from the database
	 * @param string $key parameter to remove from the database
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return mixed the value of the key removed
	 */
	public function remove($key)
	{
		$value = $this->get($key, false, false);

		$this->ensureTable();
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		$appendix = '';
		if ($driver === TDbConnection::DRIVER_MYSQL) {
			$appendix = ' LIMIT 1';
		}
		$cmd = $db->createCommand("DELETE FROM {$this->_tableName} WHERE {$this->_keyField}=:key" . $appendix);
		$cmd->bindParameter(":key", $key, PDO::PARAM_STR);
		$cmd->execute();
		return $value;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection
	 * that will be used by the user manager.
	 * @param string $value module ID.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setConnectionID($value)
	{
		$this->assertUninitialized('ConnectionID');
		parent::setConnectionID($value);
	}

	/**
	 * @return string the error message key when createDbConnection could not find the ConnectionID.
	 * @since 4.3.3
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'dbparametermodule_connectionid_invalid';
	}

	/**
	 * @return string the error message key when createDbConnection has no ConnectionID and no sqlite database.
	 * @since 4.3.3
	 */
	protected function getSqliteDatabaseName(): string
	{
		return 'app.params';
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setKeyField($value)
	{
		$this->assertUninitialized('KeyField');
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setValueField($value)
	{
		$this->assertUninitialized('ValueField');
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setTableName($value)
	{
		$this->assertUninitialized('TableName');
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
	 * @param string $value database parameter key value
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setAutoLoadField($value)
	{
		$this->assertUninitialized('AutoLoadField');
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setAutoLoadValue($value)
	{
		$this->assertUninitialized('AutoLoadValue');
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 */
	public function setAutoLoadValueFalse($value)
	{
		$this->assertUninitialized('AutoLoadValueFalse');
		$this->_autoLoadValueFalse = TPropertyValue::ensureString($value);
	}

	/**
	 * @return bool whether the paramter DB table should be automatically created if not exists. Defaults to true.
	 * @see setAutoCreateParamTable
	 */
	public function getAutoCreateParamTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param bool $value whether the parameter DB table should be automatically created if not exists.
	 * @see setTableName
	 */
	public function setAutoCreateParamTable($value)
	{
		$this->assertUninitialized('AutoCreateParamTable');
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
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
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is initialized
	 * @throws \Prado\Exceptions\TInvalidDataTypeException if the $value is not 'php', 'json', or a callable
	 */
	public function setSerializer($value)
	{
		$this->assertUninitialized('Serializer');
		if ($value !== self::SERIALIZE_PHP && $value !== self::SERIALIZE_JSON && !is_callable($value)) {
			throw new TInvalidDataTypeException('dbparametermodule_serializer_not_callable');
		}
		$this->_serializer = $value;
	}

	/**
	 * @return bool whether the parameter DB table should be automatically created if not exists. Defaults to true.
	 */
	public function getCaptureParameterChanges()
	{
		return $this->_autoCapture;
	}

	/**
	 * @param bool $value whether the parameter DB table should be automatically created if not exists.
	 */
	public function setCaptureParameterChanges($value)
	{
		$this->_autoCapture = TPropertyValue::ensureBoolean($value);
	}
}
