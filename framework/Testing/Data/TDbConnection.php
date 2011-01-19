<?php
/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2009 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

prado::using ('System.Testing.Data.TDbCommand');
prado::using ('System.Testing.Data.TDbTransaction');

/**
 * TDbConnection represents a connection to a database.
 *
 * This is a port of {@link http://www.yiiframework.com Yii} {@link http://www.yiiframework.com/ CDbConnection}
 *
 * TDbConnection works together with {@link TDbCommand}, {@link TDbDataReader}
 * and {@link TDbTransaction} to provide data access to various DBMS
 * in a common set of APIs. They are a thin wrapper of the {@link http://www.php.net/manual/en/ref.pdo.php PDO}
 * PHP extension.
 *
 * To establish a connection, set {@link setActive active} to true after
 * specifying {@link connectionString}, {@link username} and {@link password}.
 *
 * The following example shows how to create a TDbConnection instance and establish
 * the actual connection:
 * <pre>
 * $connection=new TDbConnection($dsn,$username,$password);
 * $connection->active=true;
 * </pre>
 *
 * After the DB connection is established, one can execute an SQL statement like the following:
 * <pre>
 * $command=$connection->createCommand($sqlStatement);
 * $command->execute();   // a non-query SQL statement execution
 * // or execute an SQL query and fetch the result set
 * $reader=$command->query();
 *
 * // each $row is an array representing a row of data
 * foreach($reader as $row) ...
 * </pre>
 *
 * One can do prepared SQL execution and bind parameters to the prepared SQL:
 * <pre>
 * $command=$connection->createCommand($sqlStatement);
 * $command->bindParam($name1,$value1);
 * $command->bindParam($name2,$value2);
 * $command->execute();
 * </pre>
 *
 * To use transaction, do like the following:
 * <pre>
 * $transaction=$connection->beginTransaction();
 * try
 * {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... other SQL executions
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollBack();
 * }
 * </pre>
 *
 * TDbConnection also provides a set of methods to support setting and querying
 * of certain DBMS attributes, such as {@link getNullConversion nullConversion}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe.Boulain <Christophe.Boulain@gmail.com>
 * @version $Id$
 * @package System.Testing.Data
 * @since 3.2
 */
class TDbConnection extends TComponent
{
	/**
	 * @var string The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 */
	public $connectionString;
	/**
	 * @var string the username for establishing DB connection. Defaults to empty string.
	 */
	public $username='';
	/**
	 * @var string the password for establishing DB connection. Defaults to empty string.
	 */
	public $password='';
	/**
	 * @var integer number of seconds that table metadata can remain valid in cache.
	 * Use 0 or negative value to indicate not caching schema.
	 * If greater than 0 and the primary cache is enabled, the table metadata will be cached.
	 * @see schemaCachingExclude
	 */
	public $schemaCachingDuration=0;
	/**
	 * @var array list of tables whose metadata should NOT be cached. Defaults to empty array.
	 * @see schemaCachingDuration
	 */
	public $schemaCachingExclude=array();
	/**
	 * @var boolean whether the database connection should be automatically established
	 * the component is being initialized. Defaults to true. Note, this property is only
	 * effective when the TDbConnection object is used as an application component.
	 */
	public $autoConnect=true;
	/**
	 * @var string the charset used for database connection. The property is only used
	 * for MySQL and PostgreSQL databases. Defaults to null, meaning using default charset
	 * as specified by the database.
	 */
	public $charset;
	/**
	 * @var boolean whether to turn on prepare emulation. Defaults to false, meaning PDO
	 * will use the native prepare support if available. For some databases (such as MySQL),
	 * this may need to be set true so that PDO can emulate the prepare support to bypass
	 * the buggy native prepare support. Note, this property is only effective for PHP 5.1.3 or above.
	 */
	public $emulatePrepare=false;
	/**
	 * @var boolean whether to log the values that are bound to a prepare SQL statement.
	 * Defaults to false. During development, you may consider setting this property to true
	 * so that parameter values bound to SQL statements are logged for debugging purpose.
	 * You should be aware that logging parameter values could be expensive and have significant
	 * impact on the performance of your application.
	 */
	public $enableParamLogging=false;
	/**
	 * @var boolean whether to enable profiling the SQL statements being executed.
	 * Defaults to false. This should be mainly enabled and used during development
	 * to find out the bottleneck of SQL executions.
	 */
	public $enableProfiling=false;

	private $_attributes=array();
	private $_active=false;
	private $_pdo;
	private $_transaction;
	private $_schema;


	/**
	 * Constructor.
	 * Note, the DB connection is not established when this connection
	 * instance is created. Set {@link setActive active} property to true
	 * to establish the connection.
	 * @param string The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @param string The user name for the DSN string.
	 * @param string The password for the DSN string.
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 */
	public function __construct($dsn='',$username='',$password='')
	{
		parent::__construct();
		$this->connectionString=$dsn;
		$this->username=$username;
		$this->password=$password;
	}

	/**
	 * Close the connection when serializing.
	 */
	public function __sleep()
	{
		$this->close();
		return array_keys(get_object_vars($this));
	}

	/**
	 * @return array list of available PDO drivers
	 * @see http://www.php.net/manual/en/function.PDO-getAvailableDrivers.php
	 */
	public static function getAvailableDrivers()
	{
		return PDO::getAvailableDrivers();
	}

	/**
	 * Initializes the component.
	 * This method is required by {@link IApplicationComponent} and is invoked by application
	 * when the TDbConnection is used as an application component.
	 * If you override this method, make sure to call the parent implementation
	 * so that the component can be marked as initialized.
	 */
	public function init()
	{
		if($this->autoConnect)
			$this->setActive(true);
	}

	/**
	 * @return boolean whether the DB connection is established
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * Open or close the DB connection.
	 * @param boolean whether to open or close DB connection
	 * @throws CException if connection fails
	 */
	public function setActive($value)
	{
		if($value!=$this->_active)
		{
			if($value)
				$this->open();
			else
				$this->close();
		}
	}

	/**
	 * Opens DB connection if it is currently not
	 * @throws CException if connection fails
	 */
	protected function open()
	{
		if($this->_pdo===null)
		{
			if(empty($this->connectionString))
				throw new TDbException('TDbConnection.connectionString cannot be empty.');
			try
			{
				Prado::trace('Opening DB connection','System.Testing.Data.TDbConnection');
				$this->_pdo=$this->createPdoInstance();
				$this->initConnection($this->_pdo);
				$this->_active=true;
			}
			catch(PDOException $e)
			{
				throw new TDbException('TDbConnection failed to open the DB connection: {0}',$e->getMessage());
			}
		}
	}

	/**
	 * Closes the currently active DB connection.
	 * It does nothing if the connection is already closed.
	 */
	protected function close()
	{
		Prado::trace('Closing DB connection','System.Testing.Data.TDbConnection');
		$this->_pdo=null;
		$this->_active=false;
		$this->_schema=null;
	}

	/**
	 * Creates the PDO instance.
	 * When some functionalities are missing in the pdo driver, we may use
	 * an adapter class to provides them.
	 * @return PDO the pdo instance
	 * @since 1.0.4
	 */
	protected function createPdoInstance()
	{
		$pdoClass='PDO';
		if(($pos=strpos($this->connectionString,':'))!==false)
		{
			$driver=strtolower(substr($this->connectionString,0,$pos));
			if($driver==='mssql' || $driver==='dblib')
			{
				prado::using('System.Testing.Data.Schema.mssql.TMssqlPdoAdapter');
				$pdoClass='TMssqlPdoAdapter';
			}
		}
		return new $pdoClass($this->connectionString,$this->username,
									$this->password,$this->_attributes);
	}

	/**
	 * Initializes the open db connection.
	 * This method is invoked right after the db connection is established.
	 * The default implementation is to set the charset for MySQL and PostgreSQL database connections.
	 * @param PDO the PDO instance
	 */
	protected function initConnection($pdo)
	{
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($this->emulatePrepare && constant('PDO::ATTR_EMULATE_PREPARES'))
			$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,true);
		if($this->charset!==null)
		{
			switch (($driver=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)))
			{
				case 'mysql':
					$stmt = $pdo->prepare('SET CHARACTER SET ?');
				break;
				case 'pgsql':
					$stmt = $pdo->prepare('SET client_encoding TO ?');
				break;
				case 'sqlite':
					$stmt = $pdo->prepare ('SET NAMES ?');
				break;
				default:
					throw new TDbException('dbconnection_unsupported_driver_charset', $driver);
			}
			$stmt->execute(array($this->charset));
		}
	}

	/**
	 * @return PDO the PDO instance, null if the connection is not established yet
	 */
	public function getPdoInstance()
	{
		return $this->_pdo;
	}

	/**
	 * Creates a command for execution.
	 * @param string SQL statement associated with the new command.
	 * @return TDbCommand the DB command
	 * @throws CException if the connection is not active
	 */
	public function createCommand($sql)
	{
		if($this->getActive())
			return new TDbCommand($this,$sql);
		else
			throw new TDbException('TDbConnection is inactive and cannot perform any DB operations.');
	}

	/**
	 * @return TDbTransaction the currently active transaction. Null if no active transaction.
	 */
	public function getCurrentTransaction()
	{
		if($this->_transaction!==null)
		{
			if($this->_transaction->getActive())
				return $this->_transaction;
		}
		return null;
	}

	/**
	 * Starts a transaction.
	 * @return TDbTransaction the transaction initiated
	 * @throws CException if the connection is not active
	 */
	public function beginTransaction()
	{
		if($this->getActive())
		{
			$this->_pdo->beginTransaction();
			return $this->_transaction=new TDbTransaction($this);
		}
		else
			throw new TDbException('TDbConnection is inactive and cannot perform any DB operations.');
	}

	/**
	 * @return TDbSchema the database schema for the current connection
	 * @throws CException if the connection is not active yet
	 */
	public function getSchema()
	{
		if($this->_schema!==null)
			return $this->_schema;
		else
		{
			if(!$this->getActive())
				throw new TDbException('TDbConnection is inactive and cannot perform any DB operations.');
			$driver=$this->getDriverName();
			switch(strtolower($driver))
			{
				case 'pgsql':  // PostgreSQL
					prado::using('System.Testing.Data.Schema.pgsql.TPgsqlSchema');
					return $this->_schema=new TPgsqlSchema($this);
				case 'mysqli': // MySQL
				case 'mysql':
					prado::using('System.Testing.Data.Schema.mysql.TMysqlSchema');
					return $this->_schema=new TMysqlSchema($this);
				case 'sqlite': // sqlite 3
				case 'sqlite2': // sqlite 2
					prado::using('System.Testing.Data.Schema.sqlite.TSqliteSchema');
					return $this->_schema=new TSqliteSchema($this);
				case 'mssql': // Mssql driver on windows hosts
				case 'dblib': // dblib drivers on linux (and maybe others os) hosts
					prado::using('System.Testing.Data.Schema.mssql.TMssqlSchema');
					return $this->_schema=new TMssqlSchema($this);
				case 'oci':  // Oracle driver
					prado::using('System.Testing.Data.Schema.oci.TOciSchema');
					return $this->_schema=new TOciSchema($this);
				case 'ibm':
				default:
					throw new TDbException('TDbConnection does not support reading schema for {0} database.',
						$driver);
			}
		}
	}

	/**
	 * Returns the SQL command builder for the current DB connection.
	 * @return TDbCommandBuilder the command builder
	 */
	public function getCommandBuilder()
	{
		return $this->getSchema()->getCommandBuilder();
	}

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * @param string name of the sequence object (required by some DBMS)
	 * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
	 * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
	 */
	public function getLastInsertID($sequenceName='')
	{
		if($this->getActive())
			return $this->_pdo->lastInsertId($sequenceName);
		else
			throw new TDbException('TDbConnection is inactive and cannot perform any DB operations.');
	}

	/**
	 * Quotes a string value for use in a query.
	 * @param string string to be quoted
	 * @return string the properly quoted string
	 * @see http://www.php.net/manual/en/function.PDO-quote.php
	 */
	public function quoteValue($str)
	{
		if($this->getActive())
			return $this->_pdo->quote($str);
		else
			throw new TDbException('TDbConnection is inactive and cannot perform any DB operations.');
	}

	/**
	 *
	 * Prado 3.1 compatibility method.
	 *
	 * @see {@link quoteValue}
	 *
	 * @param string $str
	 * @return string
	 */
	public function quoteString($str)
	{
		return $this->quoteValue($str);
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return $this->getSchema()->quoteTableName($name);
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return $this->getSchema()->quoteColumnName($name);
	}

	/**
	 * Determines the PDO type for the specified PHP type.
	 * @param string The PHP type (obtained by gettype() call).
	 * @return integer the corresponding PDO type
	 */
	public function getPdoType($type)
	{
		static $map=array
		(
			'boolean'=>PDO::PARAM_BOOL,
			'integer'=>PDO::PARAM_INT,
			'string'=>PDO::PARAM_STR,
			'NULL'=>PDO::PARAM_NULL,
		);
		return isset($map[$type]) ? $map[$type] : PDO::PARAM_STR;
	}

	/**
	 * @return TDbColumnCaseMode the case of the column names
	 */
	public function getColumnCase()
	{
		switch($this->getAttribute(PDO::ATTR_CASE))
		{
			case PDO::CASE_NATURAL:
				return TDbColumnCaseMode::Preserved;
			case PDO::CASE_LOWER:
				return TDbColumnCaseMode::LowerCase;
			case PDO::CASE_UPPER:
				return TDbColumnCaseMode::UpperCase;
		}
	}

	/**
	 * @param TDbColumnCaseMode the case of the column names
	 */
	public function setColumnCase($value)
	{
		switch(TPropertyValue::ensureEnum($value,'TDbColumnCaseMode'))
		{
			case TDbColumnCaseMode::Preserved:
				$value=PDO::CASE_NATURAL;
				break;
			case TDbColumnCaseMode::LowerCase:
				$value=PDO::CASE_LOWER;
				break;
			case TDbColumnCaseMode::UpperCase:
				$value=PDO::CASE_UPPER;
				break;
		}
		$this->setAttribute(PDO::ATTR_CASE,$value);
	}
	/**
	 * @return TDbNullConversionMode how the null and empty strings are converted
	 */
	public function getNullConversion()
	{
		switch($this->getAttribute(PDO::ATTR_ORACLE_NULLS))
		{
			case PDO::NULL_NATURAL:
				return TDbNullConversionMode::Preserved;
			case PDO::NULL_EMPTY_STRING:
				return TDbNullConversionMode::EmptyStringToNull;
			case PDO::NULL_TO_STRING:
				return TDbNullConversionMode::NullToEmptyString;
		}
	}

	/**
	 * @param TDbNullConversionMode how the null and empty strings are converted
	 */
	public function setNullConversion($value)
	{
		switch(TPropertyValue::ensureEnum($value,'TDbNullConversionMode'))
		{
			case TDbNullConversionMode::Preserved:
				$value=PDO::NULL_NATURAL;
				break;
			case TDbNullConversionMode::EmptyStringToNull:
				$value=PDO::NULL_EMPTY_STRING;
				break;
			case TDbNullConversionMode::NullToEmptyString:
				$value=PDO::NULL_TO_STRING;
				break;
		}
		$this->setAttribute(PDO::ATTR_ORACLE_NULLS,$value);
	}

	/**
	 * @return boolean whether creating or updating a DB record will be automatically committed.
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function getAutoCommit()
	{
		return $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
	}

	/**
	 * @param boolean whether creating or updating a DB record will be automatically committed.
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function setAutoCommit($value)
	{
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT,$value);
	}

	/**
	 * @return boolean whether the connection is persistent or not
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function getPersistent()
	{
		return $this->getAttribute(PDO::ATTR_PERSISTENT);
	}

	/**
	 * @param boolean whether the connection is persistent or not
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function setPersistent($value)
	{
		return $this->setAttribute(PDO::ATTR_PERSISTENT,$value);
	}

	/**
	 * @return string name of the DB driver
	 */
	public function getDriverName()
	{
		return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * @return string the version information of the DB driver
	 */
	public function getClientVersion()
	{
		return $this->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * @return string the status of the connection
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function getConnectionStatus()
	{
		return $this->getAttribute(PDO::ATTR_CONNECTION_STATUS);
	}

	/**
	 * @return boolean whether the connection performs data prefetching
	 */
	public function getPrefetch()
	{
		return $this->getAttribute(PDO::ATTR_PREFETCH);
	}

	/**
	 * @return string the information of DBMS server
	 */
	public function getServerInfo()
	{
		return $this->getAttribute(PDO::ATTR_SERVER_INFO);
	}

	/**
	 * @return string the version information of DBMS server
	 */
	public function getServerVersion()
	{
		return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	/**
	 * @return int timeout settings for the connection
	 */
	public function getTimeout()
	{
		return $this->getAttribute(PDO::ATTR_TIMEOUT);
	}

	/**
	 * Obtains a specific DB connection attribute information.
	 * @param int the attribute to be queried
	 * @return mixed the corresponding attribute information
	 * @see http://www.php.net/manual/en/function.PDO-getAttribute.php
	 */
	public function getAttribute($name)
	{
		if($this->getActive())
			return $this->_pdo->getAttribute($name);
		else
			throw new TDbException('TDbConnection is inactive and cannot perform any DB operations.');
	}

	/**
	 * Sets an attribute on the database connection.
	 * @param int the attribute to be set
	 * @param mixed the attribute value
	 * @see http://www.php.net/manual/en/function.PDO-setAttribute.php
	 */
	public function setAttribute($name,$value)
	{
		if($this->_pdo instanceof PDO)
			$this->_pdo->setAttribute($name,$value);
		else
			$this->_attributes[$name]=$value;
	}

	/**
	 * Returns the statistical results of SQL executions.
	 * The results returned include the number of SQL statements executed and
	 * the total time spent.
	 * In order to use this method, {@link enableProfiling} has to be set true.
	 * @return array the first element indicates the number of SQL statements executed,
	 * and the second element the total time spent in SQL execution.
	 * @since 1.0.6
	 */
	public function getStats()
	{
		/*$logger=Yii::getLogger();
		$timings=$logger->getProfilingResults(null,'System.Testing.Data.TDbCommand.query');
		$count=count($timings);
		$time=array_sum($timings);
		$timings=$logger->getProfilingResults(null,'System.Testing.Data.TDbCommand.execute');
		$count+=count($timings);
		$time+=array_sum($timings);
		return array($count,$time);*/
	}

	/**
	 * Getters & Setters to provide BC with prado-3.1
	 */
	public function getConnectionString() { return $this->connectionString;	}
	public function getUsername () { return $this->username; }
	public function getPassword () { return $this->password; }
	public function getCharset () { return $this->charset; }
	public function getSchemaCachingDuration() { return $this->schemaCachingDuration; }
	public function getSchemaCachingExclude () { return $this->schemaCachingExclude; }
	public function getAutoConnect () { return $this->autoConnect; }
	public function getEmulatePrepare () { return $this->emulatePrepare; }
	public function getEnableParamLogging () { return $this->enableParamLogging; }
	public function getEnableProfiling () { return $this->enableProfiling; }

	public function setConnectionString($value) { $this->connectionString=TPropertyValue::ensureString($value);	}
	public function setUsername ($value) { $this->username=TPropertyValue::ensureString($value); }
	public function setPassword ($value) { $this->password=TPropertyValue::ensureString($value); }
	public function setCharset ($value) { $this->charset=TPropertyValue::ensureString($value); }
	public function setSchemaCachingDuration ($value) { $this->schemaCachingDuration=TPropertyValue::ensureInteger($value); }
	public function setSchemaCachingExclude ($value) { $this->username=TPropertyValue::ensureArray($value); }
	public function setAutoConnect ($value) { $this->autoConnect = TPropertyValue::ensureBoolean($value); }
	public function setEnablePrepare ($value) { $this->emulatePrepare = TPropertyValue::ensureBoolean($value); }
	public function setEnableParamLogging ($value) { $this->enableParamLogging = TPropertyValue::ensureBoolean($value); }
	public function setEnableProfiling ($value) { $this->enableProfiling = TPropertyValue::ensureBoolean ($value) ; }
}


/**
 * TDbColumnCaseMode
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Testing.Data
 * @since 3.0
 */
class TDbColumnCaseMode extends TEnumerable
{
	/**
	 * Column name cases are kept as is from the database
	 */
	const Preserved='Preserved';
	/**
	 * Column names are converted to lower case
	 */
	const LowerCase='LowerCase';
	/**
	 * Column names are converted to upper case
	 */
	const UpperCase='UpperCase';
}

/**
 * TDbNullConversionMode
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Testing.Data
 * @since 3.0
 */
class TDbNullConversionMode extends TEnumerable
{
	/**
	 * No conversion is performed for null and empty values.
	 */
	const Preserved='Preserved';
	/**
	 * NULL is converted to empty string
	 */
	const NullToEmptyString='NullToEmptyString';
	/**
	 * Empty string is converted to NULL
	 */
	const EmptyStringToNull='EmptyStringToNull';
}
