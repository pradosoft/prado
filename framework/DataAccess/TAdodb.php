<?php

Prado::using('System.DataAccess.TDatabaseProvider');

/**
 * Adbodb data access module.
 *
 * Usage:
 * <code>
 * $provider = new TAdodb; 
 * $provider->setConnectionString($dsn); $
 * connection = $provider->getConnection(); 
 * $resultSet = $connection->execute('....');
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess
 * @since 3.0
 */
class TAdodb extends TDatabaseProvider
{
	const FETCH_ASSOCIATIVE='associative';
	const FETCH_NUMERIC='numeric';
	const FETCH_BOTH='both';
	const FETCH_DEFAULT='default';

	private $_connection = null;
	private $_cachedir='';
	private $_fetchMode = 'associative';

	public function getConnection()
	{
		$this->initialize();	
		return $this->_connection;
	}

	public function initialize()
	{
		if(!class_exists('ADOConnection', false))
			$this->importAdodbLibrary();
		if(is_null($this->_connection))
			$this->_connection = new TAdodbConnection($this);
	}

	public function enableActiveRecords()
	{
		$conn = $this->getConnection();
		if(is_null($conn->getInternalConnection()) && $conn->open())
		{
			Prado::using('System.DataAccess.TActiveRecord');
			TActiveRecord::setDatabaseAdapter($conn->getInternalConnection());
		}
	}

	protected function getAdodbLibrary()
	{
		return Prado::getPathOfNamespace('System.3rdParty.adodb');
	}

	protected function importAdodbLibrary()
	{
		$path = $this->getAdodbLibrary();
		require($path.'/adodb-exceptions.inc.php');
		require($path.'/adodb.inc.php');
	}

	/**
	 * @return string the cache directory for adodb module
	 */
	public function getCacheDir()
	{
		return $this->_cachedir;
	}
	
	/**
	 * Sets the cache directory for ADODB (in adodb it is
	 * called to $ADODB_CACHE_DIR)
	 * @param string the cache directory for adodb module
	 */
	public function setCacheDir($value)
	{
		$this->_cachedir=$value;
	}

	/**
	 * @return string fetch mode of query data
	 */
	public function getFetchMode()
	{
		return $this->_fetchMode;
	}

	/**
	 * Sets the fetch mode of query data: Associative, Numeric, Both, Default (default)
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
 * TAdodbConnection is a wrapper class of the ADODB ADOConnection class.
 * For more information about the ADODB library, see {@link http://adodb.sourceforge.net/}.
 *
 * You can call any method implemented in ADOConnection class via TAdodbConnection,
 * such as TAdodbConnection::FetchRow(), and so on. The method calls
 * will be passed to ADOConnection class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess
 * @since 3.0
 */
class TAdodbConnection extends TDbConnection
{
	private $_connection;

	/**
	 * Gets the internal connection. 
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
		parent::__construct($provider);
		if(is_string($provider))
			$this->initProvider($provider);
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
		return array_keys(get_object_vars($this));
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

	public function getIsClosed()
	{
		return is_null($this->_connection) || !$this->_connection->IsConnected();
	}

	public function prepare($statement)
	{
		return $this->_connection->prepare($statement);
	}

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
	 * Finish and cleanup transactions.
	 */
	public function completeTransaction()
	{
		return $this->connection->CompleteTrans();
	}


	public function commit()
	{
		return $this->connection->CommitTrans();
	}


	public function rollback()
	{
		return $this->connection->RollbackTrans();
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
			$provider->initialize();
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
	 * Complete the database connection.
	 */
	protected function initConnection()
	{
		$provider = $this->getProvider();

		if($provider->getUsePersistentConnection())
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