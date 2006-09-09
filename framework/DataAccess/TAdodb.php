<?php
/**
 * TAdodb and TAdodbConnection class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.DataAccess
 */

/**
 * Include the database provider base class.
 */
Prado::using('System.DataAccess.TDatabaseProvider');

/**
 * TAdodb database connection module. 
 * 
 * The TAdodb module class allows the database connection details to be
 * specified in the application.xml or config.xml, the later are directory level
 * configurations. 
 * <code> 
 * ...
 * <modules>
 * 	...
 *  <module id="my_db1"
 *          class="TAdodb"
 *          ConnectionString="mysql://username:password@localhost/mydatabase" /> 
 *  ...
 * </modules>
 * ...
 * </code>
 * Where <tt>mysql</tt> is the driver name, <tt>username</tt> and
 * <tt>password</tt> are the required credentials to connection to the database,
 * <tt>localhost</tt> is the database resource and <tt>mydatabase</tt> is the
 * name of database to connect to.
 * 
 * The Adodb library supports many database drivers. The drivers included are
 *  # <tt>mysql</tt> MySQL without transactions.
 *  # <tt>mysqlt</tt> MySQL 3.23 or later with transaction support.
 *  # <tt>mysqli</tt> MySQLi extension, does not support transactions.
 *  # <tt>pdo_mysql</tt> PDO driver for MysSQL.
 *
 *  # <tt>oracle</tt> Oracle 7.
 *  # <tt>oci8po</tt> Portable version of oci8 driver.
 *  # <tt>oci8</tt> Oracle (oci8).
 *  # <tt>oci805</tt> Oracle 8.0.5 driver.
 *  # <tt>pdo_oci</tt> PDO driver for Oracle.
 *  # <tt>odbc_oracle</tt> Oracle support via ODBC.
 *
 *  # <tt>postgres7</tt> Postgres 7, 8.
 *  # <tt>pdo_pgsql</tt> PDO driver for postgres.
 *  # <tt>postgres64</tt> Postgress 6.4.
 *
 *  # <tt>pdo_mssql</tt> PDO driver for MSSQL.
 *  # <tt>odbc_mssql</tt> MSSQL support via ODBC.
 *  # <tt>mssqlpo</tt> Portable MSSQL Driver that supports || instead of +.
 *  # <tt>ado_mssql</tt> Microsoft SQL Server ADO data driver.
 *  # <tt>mssql</tt> Native mssql driver.
 *
 *  # <tt>ldap</tt> LDAP.
 *  # <tt>sqlite</tt> SQLite database.
 * 
 * For other database drivers and detail documentation regarding indiviual
 * drivers visit {@link http://adodb.sourceforge. net/}
 *
 * When using an sqlite database it is easier to specify the {@link setDriver
 * Driver} as "sqlite" and {@link setHost Host} as the path to the sqlite
 * database file. For example:
 * <code>
 *  <module id="my_db1"
 *          class="TAdodb"
 *          Driver="sqlite"
 *          Host="Application.pages.my_db" /> 
 * </code>
 * Note that the database file should not contain <b>no dots</b>. The path can
 * be use namespace or a fullpath (but no dots).
 *
 * To access the database from a TPage or other TApplicationComponent classes
 * use the {@link TApplication::getModule getModule} method of TApplication.
 * <code>  
 *  $db  = $this->getApplication()->getModule('my_db1');  
 *  //similarly   
 *  $db  = $this->Application->Modules['my_db1'];
 * </code>
 * 
 * For classes that are not instance of TApplicationComponent (such as
 * TUserManager) use the static {@link PradoBase::getApplication getApplication}
 * method first. 
 * <code> 
 *  $db  = Prado::getApplication()->getModule('my_db1');
 * </code>
 *
 * If you wish to use a Adodb connections without module configuration, see the
 * TAdodbConnection class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.DataAccess
 * @since 3.0
 */
class TAdodb extends TDatabaseProvider
{
	/**
	 * @var string Adodb associative fetch mode.
	 */
	const FETCH_ASSOCIATIVE='associative';
	/**
	 * @var string Adodb numeric fetch mode.
	 */
	const FETCH_NUMERIC='numeric';
	/**
	 * @var string Adodb fetch mode using both associative and numeric.
	 */
	const FETCH_BOTH='both';
	/**
	 * @var string Adodb default fetch mode.
	 */
	const FETCH_DEFAULT='default';

	/**
	 * @var TAdodbConnection database connection.
	 */
	private $_connection = null;
	/**
	 * @var string Adodb record set cache directory. 
	 */
	private $_cachedir='';
	/**
	 * @var string current fetch mode.
	 */
	private $_fetchMode = 'associative';
	/**
	 * @var boolean whether to enable the active recors.
	 */
	private $_enableActiveRecords = false;

	/**
	 * @return TAdodbConnection connects to the database and returns the
	 * connection resource.
	 */
	public function getConnection()
	{
		$this->init(null);
		return $this->_connection;
	}
	
	/**
	 * Initialize the module configurations.
	 */
	public function init($config)
	{
		parent::init($config);
		if(!class_exists('ADOConnection', false))
			$this->importAdodbLibrary();
		if(is_null($this->_connection))
		{
			if($config instanceof TAdodbConnection)
				$this->_connection = $config;
			else
				$this->_connection = new TAdodbConnection($this);
			if($this->getEnableActiveRecords())
				$this->initializeActiveRecords();	
		}
	}

	/**
	 * Enabling Adodb to retrieve results as active records, and active record
	 * object to save changes. Once set to true and the connection is
	 * initialized, setting <tt>EnableActiveRecords</tt> to false has no effect.
	 * @param boolean true to  allow active records.
	 */
	public function setEnableActiveRecords($value)
	{
		$this->_enableActiveRecords = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @param boolean whether to enable active records.
	 */
	public function getEnableActiveRecords()
	{
		return $this->_enableActiveRecords;
	}

	/**
	 * Initialize the active records by setting the active records database
	 * adpater to the current database connection.
	 */
	public function initializeActiveRecords()
	{
		$conn = $this->_connection;
		if(!is_null($conn->getInternalConnection()) || $conn->open())
		{
			Prado::using('System.DataAccess.TActiveRecord');
			TActiveRecord::setDatabaseAdapter($conn->getInternalConnection());
			$this->_enableActiveRecords = true;
		}
	}

	/**
	 * @return string the adodb library path.
	 */
	protected function getAdodbLibrary()
	{
		return Prado::getPathOfNamespace('System.3rdParty.adodb');
	}

	/**
	 * Import the necessary adodb library files.
	 */
	protected function importAdodbLibrary()
	{
		$path = $this->getAdodbLibrary();
		require($path.'/adodb-exceptions.inc.php');
		require($path.'/adodb.inc.php');
	}

	/**
	 * @return string the cache directory for Adodb to save cached queries.
	 */
	public function getCacheDir()
	{
		return $this->_cachedir;
	}
	
	/**
	 * The cache directory for Adodb to save cached queries. The path can be
	 * specified using a namespace or the fullpath.
	 * @param string the cache directory for adodb module
	 */
	public function setCacheDir($value)
	{
		$this->_cachedir=Prado::getPathOfNamespace($value);
	}

	/**
	 * @return string fetch mode of queried data 
	 */
	public function getFetchMode()
	{
		return $this->_fetchMode;
	}

	/**
	 * Sets the fetch mode of query data, valid modes are <tt>Associative</tt>,
	 * <tt>Numeric</tt>, <tt>Both</tt> or <tt>Default</tt>. The mode names are
	 * case insensitive.
	 * @param string the fetch mode of query data
	 */
	public function setFetchMode($value)
	{
		$value = strtolower($value);
		if($value===self::FETCH_ASSOCIATIVE || $value===self::FETCH_NUMERIC
				|| $value===self::FETCH_BOTH)
			$this->_fetchMode=$value;
		else
			$this->_fetchMode=self::FETCH_DEFAULT;
	}
}

/**
 * TAdodbConnection provides access to the ADODB ADOConnection class. For detail
 * documentation regarding indiviual drivers visit {@link http://adodb.sourceforge.net/}
 *
 * You can call any method implemented in ADOConnection class via TAdodbConnection,
 * such as TAdodbConnection::FetchRow(), and so on. The method calls
 * will be passed an ADOConnection instance.
 * 
 * To use TAdodbConnection without the TAdodb database connection provider pass
 * a DSN style connection string to the TAdodbConnection constructor.
 * <code>
 *  $dsn = "mysql://username:password@localhost/mydb"; 
 *  $db = new TAdodbConnection($dsn);
 *  $resultSet = $db->execute('...');
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.DataAccess
 * @since 3.0
 */
class TAdodbConnection extends TDbConnection
{
	/**
	 * @var ADOConnection database connection.
	 */
	private $_connection;

	/**
	 * Gets the internal connection. Should only be used by framework
	 * developers.
	 */
	public function getInternalConnection()
	{
		return $this->_connection;
	}
	
	/**
	 * Constructor, initialize a new Adodb connection.
	 * @param string|TAdodb DSN connection string or a TAdodb
	 */
	public function __construct($provider=null)
	{
		if(is_string($provider))
			$this->initProvider($provider);
		else
			parent::__construct($provider);
	}

	/**
	 * Create a new provider for this connection using the DSN string.
	 * @param string DSN connection string.
	 */
	protected function initProvider($connectionString)
	{
		$provider  = new TAdodb();
		$provider->setConnectionString($connectionString);
		$this->setProvider($provider);
	}

	/**
	 * Cleanup work before serializing.
	 * This is a PHP defined magic method.
	 * @return array the names of instance-variables to serialize.
	 */
	public function __sleep()
	{
		//close any open connections before serializing.
		$this->close();
		$this->_connection = null;
	}

	/**
	 * This method will be automatically called when unserialization happens.
	 * This is a PHP defined magic method.
	 */
	public function __wakeup()
	{
	}

	/**
	 * PHP magic function.
	 * This method will pass all method calls to ADOConnection class
	 * provided in the ADODB library.
	 * @param mixed method name
	 * @param mixed method call parameters
	 * @param mixed return value of the method call
	 */
	public function __call($method, $params)
	{
		if(is_null($this->_connection) || !$this->_connection->IsConnected())
			$this->open();
		return call_user_func_array(array($this->_connection,$method),$params);
	}

	/**
	 * @return boolean true if the database is connected. 
	 */
	public function getIsClosed()
	{
		return is_null($this->_connection) || !$this->_connection->IsConnected();
	}

	/**
	 * Prepares (compiles) an SQL query for repeated execution. Bind parameters
	 * are denoted by ?, except for the oci8 driver, which uses the traditional
	 * Oracle :varname convention. If there is an error, or we are emulating
	 * Prepare( ), we return the original $sql string.
	 * 
	 * Prepare( ) cannot be used with functions that use SQL query rewriting
	 * techniques, e.g. PageExecute( ) and SelectLimit( ).
	 * 
	 * @param string sql statement.
     * @return array an array containing the original sql statement in the first
     * array element; 
	 */
	public function prepare($statement)
	{
		return $this->_connection->prepare($statement);
	}

	/**
	 * Execute SQL statement $sql and return derived class of ADORecordSet if
	 * successful. Note that a record set is always returned on success, even if
	 * we are executing an insert or update statement. You can also pass in $sql
	 * a statement prepared in {@link prepare}.
	 */
	public function execute($sql, $parameters=array())
	{
		return $this->_connection->execute($sql, $parameters);
	}

	/**
	 * Start a transaction on this connection.
	 */
	public function beginTransaction()
	{
		return $this->_connection->StartTrans();
	}

	/**
	 * End a transaction successfully. 
	 * @return true if successful. If the database does not support
	 * transactions, will return true also as data is always committed.
	 */
	public function commit()
	{
		return $this->_connection->CommitTrans();
	}

	/**
	 * End a transaction, rollback all changes. 
	 * @return true if successful. If the database does not support
	 * transactions, will return false as data is never rollbacked.
	 */
	public function rollback()
	{
		return $this->_connection->RollbackTrans();
	}

	/**
	 * Establishes a DB connection.
	 * An ADOConnection instance will be created if none.
	 */
	public function open()
	{
		if($this->getIsClosed())
		{
			$provider = $this->getProvider();
			$provider->init($this);
			if(strlen($provider->getConnectionString()) < 1)
			{
				if(strlen($provider->getDriver()) < 1)
					throw new TDbConnectionException('db_driver_required');
				$this->_connection=ADONewConnection($provider->getDriver());
				$this->initConnection();
			}
			else
				$this->_connection=ADONewConnection($provider->getConnectionString());
			$this->initFetchMode();
			$this->initCacheDir();
		}
		return $this->_connection->IsConnected();
	}

	/**
	 * Creates the database connection using host, username, password and
	 * database name properties.
	 */
	protected function initConnection()
	{
		$provider = $this->getProvider();
		if(is_int(strpos($provider->getConnectionOptions(), 'persist')))
		{
			$this->_connection->PConnect($provider->getHost(),
				$provider->getUsername(),$provider->getPassword(),
					$provider->getDatabase());
		}
		else
		{
			$this->_connection->Connect($provider->getHost(),
				$provider->getUsername(),$provider->getPassword(),
					$provider->getDatabase());
		}
	}

	/**
	 * Initialize the fetch mode.
	 */
	protected function initFetchMode()
	{
		global $ADODB_FETCH_MODE;
		$provider = $this->getProvider();
		if($provider->getFetchMode()===TAdodb::FETCH_ASSOCIATIVE)
			$ADODB_FETCH_MODE=ADODB_FETCH_ASSOC;
		else if($provider->fetchMode===TAdodb::FETCH_NUMERIC)
			$ADODB_FETCH_MODE=ADODB_FETCH_NUM;
		else if($provider->fetchMode===TAdodb::FETCH_BOTH)
			$ADODB_FETCH_MODE=ADODB_FETCH_BOTH;
		else
			$ADODB_FETCH_MODE=ADODB_FETCH_DEFAULT;
	}

	/**
	 * Initialize the cache directory.
	 */
	protected function initCacheDir()
	{
		global $ADODB_CACHE_DIR;
		$provider = $this->getProvider();
		if($provider->getCacheDir()!=='')
			$ADODB_CACHE_DIR=$provider->getCacheDir();
	}

	/**
	 * Closes the DB connection.
	 * You are not required to call this method as PHP will automatically
	 * to close any DB connections when exiting a script.
	 */
	public function close()
	{
		if(!is_null($this->_connection) && $this->_connection->IsConnected())
			$this->_connection->Close();
	}

	/**
	 * @param string quote a string to be sent to the database.
	 * @param boolean if true it ensure that the variable is not quoted twice,
	 * once by quote and once by the magic_quotes_gpc.
	 * @return string database specified quoted string
	 */
	public function quote($string, $magic_quotes=false)
	{
		return $this->_connection->qstr($string, $magic_quotes);
	}
}

?>