<?php
/**
 * TDatabaseProvider and TDbConnection class and IDbConnection interface file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.DataAccess
 */

/**
 * Database provider or adapter base class.
 * 
 * All database providers should extend this base class to provide a uniform
 * configuration to the database. Database providers should allow the database
 * connection to be set via the {@link setConnectionString ConnectionString}
 * property using a DSN string. The DSN format is
 * <code>
 *	$driver://$username:$password@host/$database?options[=value]
 * </code>
 * Alternatively the database connections details can be set via the {@link
 * setDriver Driver}, {@link setUsername Username}, {@link setPassword
 * Password}, {@link setHost Host} and {@link setDatabase Database} properties.
 * Additional options for individual database driver may be added via the {@link
 * setConnectionOptions ConnectionOptions} property.
 * 
 * Database provider implementation must implement the {@link getConnection
 * Connection} property that returns a database connection or client. A
 * DSN connection string the represents the available connection properties can
 * be obtained from the protected method {@link buildConnectionString} method.
 * 
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.DataAccess
 * @since 3.0
 */
abstract class TDatabaseProvider extends TModule
{
	/**
	 * @var string DSN connection string.
	 */
	private $_connectionString = '';
	/**
	 * @var string database name.
	 */
	private $_database='';
	/**
	 * @var string database driver name.
	 */
	private $_driver='';
	/**
	 * @var string database host name.
	 */
	private $_host='';
	/**
	 * @var string database connection username credentail.
	 */
	private $_username='';
	/**
	 * @var string database connection password credential.
	 */
	private $_password='';
	/**
	 * @var string additional connection options.
	 */
	private $_options='';
	

	/**
	 * DSN connection string of the form 
	 * <tt>$driver://$username:$password@host/$database?options[=value]</tt>
	 * @param string DSN style connection string.
	 */
	public function	setConnectionString($value)
	{
		$this->_connectionString = $value;
	}

	/**
	 * @return string DSN connection string
	 */
	public function getConnectionString()
	{
		return $this->_connectionString;
	}

	/**
	 * @return string database driver name (mysql, sqlite, etc.)
	 */
	public function getDriver()
	{
		return $this->_driver;
	}

	/**
	 * @param string database driver name.
	 */
	public function setDriver($value)
	{
		$this->_driver=$value;
	}

	/**
	 * If the driver is <tt>sqlite</tt>, the host must be dot directory of to
	 * the sqlite file. E.g. "<tt>Application.pages.my_db</tt>". The database
	 * filename must be specified by the <tt>Database</tt> attribute.
	 * @return string database host name/IP (and port number) in the format
	 * "host[:port]"
	 */
	public function getHost()
	{
		if(strtolower($this->getDriver()) == "sqlite")
		{
			$dir = Prado::getPathOfNamespace($this->_host);
			return $dir.'/'.$this->getDatabase();
		}
		else
			return $this->_host;
	}

	/**
	 * Sets the database host name/IP or resource (and port number) in the
	 * format "host [: port]"
	 * @param string the DB host
	 */
	public function setHost($value)
	{
		$this->_host=$value;
	}

	/**
	 * @return string database connection username credential.
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * @param string database connection username credential.
	 */
	public function setUsername($value)
	{
		$this->_username=$value;
	}

	/**
	 * @return string database connection password
	 */
	public function getPassword()
	{
		return $this->_password;
	}

	/**
	 * @param string database connection password.
	 */
	public function setPassword($value)
	{
		$this->_password=$value;
	}

	/**
	 * @return string default database name to connect.
	 */
	public function getDatabase()
	{
		return $this->_database;
	}

	/**
	 * @param string default database name to connect to.
	 */
	public function setDatabase($value)
	{
		$this->_database=$value;
	}

	/**
	 * @return string additional connection options.
	 */
	public function getConnectionOptions()
	{
		return $this->_options;
	}

	/**
	 * @param string additional connection options for each individual database
	 * driver.
	 */
	public function setConnectionOptions($value)
	{
		$this->_options=$value;
	}

	/**
	 * @return string the DSN connection string build from individual connection
	 * properties.
	 */
	protected function buildConnectionString()
	{
		$driver = $this->getDriver().'://';
		$user = $this->getUsername();
		$pass = rawurlencode($this->getPassword());
		$pass = strlen($pass) > 0 ? ':'.$pass : $pass;
		$host = $this->getHost().'/';
		if(strtolower($this->getDriver()) == 'sqlite')
			$host = rawurlencode($host);
		else
			$host = '@'.$host;
		$db = $this->getDatabase();
		$db = strlen($db) > 0 ? '/'.$db : $db;
		$options = $this->getConnectionOptions();
		
		return $driver.$user.$pass.$host.$options;
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
 * @version $Id$
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
	 * Opens a database connection using settings of a TDatabaseProvider.
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
	 * Start a transaction on this connection. Not all database will support
	 * transactions.
	 */
	public function beginTransaction();

	/**
	 * Makes all changes made since the previous commit/rollback permanent and
	 * releases any database locks. Not all database will support transactions.
	 */
	public function commit();

	/**
	 * Undoes all changes made in the current transaction and releases any
	 * database locks. Not all database will support transactions.
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
 * and provides an interface for executing SQL statements and transactions.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.DataAccess
 * @since 3.0
 */
abstract class TDbConnection extends TComponent implements IDbConnection
{
	/**
	 * @string TDatabaseProvider database provider containing connection
	 * details.
	 */
	private $_provider;

	/**
	 * Creates a new database connection context.
	 */
	public function __construct(TDatabaseProvider $provider)
	{
		$this->setProvider($provider);
	}

	/**
	 * @param TDatabaseProvider sets the connection details.
	 */
	public function setProvider($provider)
	{
		$this->_provider = $provider;
	}

	/**
	 * @param TDatabaseProvider gets the database connection details.
	 */
	public function getProvider()
	{
		return $this->_provider;
	}

	/**
	 * Automatically closes the database connection.
	 */
	public function __destruct()
	{
		$this->close();
	}
}

?>