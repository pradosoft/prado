<?php

/**
 * Database access module.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess
 * @since 3.0
 */
abstract class TDatabaseProvider extends TModule
{
	private $_connectionString = '';
	private $_database='';
	private $_driver='';
	private $_host='';
	private $_username='';
	private $_password='';
	private $_persistent=true;

	/**
	 * @param string used to open the connection
	 */
	public function	setConnectionString($value)
	{
		$this->_connectionString = $value;
	}

	/**
	 * @return string used to open the connection
	 */
	public function getConnectionString()
	{
		return $this->_connectionString;
	}

	/**
	 * @return string the DB driver (mysql, sqlite, etc.)
	 */
	public function getDriver()
	{
		return $this->_driver;
	}

	/**
	 * Sets the DB driver (mysql, sqlite, etc.)
	 * @param string the DB driver
	 */
	public function setDriver($value)
	{
		$this->_driver=$value;
	}

	/**
	 * @return string the DB host name/IP (and port number) in the format "host[:port]"
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * Sets the DB host name/IP (and port number) in the format "host[:port]"
	 * @param string the DB host
	 */
	public function setHost($value)
	{
		$this->_host=$value;
	}

	/**
	 * @return string the DB username
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * Sets the DB username
	 * @param string the DB username
	 */
	public function setUsername($value)
	{
		$this->_username=$value;
	}

	/**
	 * @return string the DB password
	 */
	public function getPassword()
	{
		return $this->_password;
	}

	/**
	 * Sets the DB password
	 * @param string the DB password
	 */
	public function setPassword($value)
	{
		$this->_password=$value;
	}

	/**
	 * @return string the database name
	 */
	public function getDatabase()
	{
		return $this->_database;
	}

	/**
	 * Sets the database name
	 * @param string the database name
	 */
	public function setDatabase($value)
	{
		$this->_database=$value;
	}

	/**
	 * @return boolean whether the DB connection is persistent
	 */
	public function getUsePersistentConnection()
	{
		return $this->_persistent;
	}

	/**
	 * Sets whether the DB connection should be persistent
	 * @param boolean whether the DB connection should be persistent
	 */
	public function setUsePersistentConnection($value)
	{
		$this->_persistent=$value;
	}

	/**
	 * @return TDbConnection a database connection
	 */
	public abstract function getConnection();
}

/**
 * A connection (session) with a specific database. SQL statements are executed
 * and results are returned within the context of a connection.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess
 * @since 3.0
 */
interface IDbConnection
{
	/**
	 * Closes the connection to the database.
	 */
	public function close();

	/**
	 * @return boolean retrieves whether this connection has been closed.
	 */
	public function getIsClosed();

	/**
	 * Opens a database connection with settings provided in the ConnectionString.
	 */
	public function open();

	/**
	 * @return string creates a prepared statement for sending parameterized
	 * SQL statements to the database.
	 */
	public function prepare($statement);

	/**
	 * Executes the SQL statement which may be any kind of SQL statement,
	 * including prepared statements.
	 * @param string sql query statement
	 * @param array subsititution parameters
	 * @return mixed result set
	 */
	public function execute($sql, $parameters=array());

	/**
	 * Start a transaction on this connection.
	 */
	public function beginTransaction();

	/**
	 * Finish and cleanup transactions.
	 */
	public function completeTranaction();

	/**
	 * Fail the current transaction.
	 */
	public function failTransaction();

	/**
	 * @return boolean true if transaction has failed.
	 */
	public function getHasTransactionFailed();

	/**
	 * Makes all changes made since the previous commit/rollback permanent and
	 * releases any database locks.
	 */
	public function commit();

	/**
	 * Undoes all changes made in the current transaction and releases any
	 * database locks
	 */
	public function rollback();

	/**
	 * @param string quote a string to be sent to the database.
	 * @param boolean if true it ensure that the variable is not quoted twice,
	 * once by quote and once by the magic_quotes_gpc.
	 * @return string database specified quoted string
	 */
	public function quote($string, $magic_quotes=false);

}

/**
 * Performs the connection to the database using a TDatabaseProvider,
 * executes SQL statements.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess
 * @since 3.0
 */
abstract class TDbConnection extends TComponent implements IDbConnection
{
	private $_provider;

	public function __construct($provider)
	{
		if($provider instanceof TDatabaseProvider)
			$this->setProvider($provider);
	}

	public function setProvider($provider)
	{
		$this->_provider = $provider;
	}

	public function getProvider()
	{
		return $this->_provider;
	}
}

?>