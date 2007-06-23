<?php
/**
 * TDbCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Caching
 */

Prado::using('System.Data.TDbConnection');

/**
 * TDbCache class
 *
 * TDbCache implements a cache application module by storing cached data in a database.
 *
 * TDbCache relies on {@link http://www.php.net/manual/en/ref.pdo.php PDO} to retrieve
 * data from databases. In order to use TDbCache, you need to enable the PDO extension
 * as well as the corresponding PDO DB driver. For example, to use SQLite database
 * to store cached data, you need both php_pdo and php_pdo_sqlite extensions.
 *
 * By default, TDbCache creates and uses an SQLite database under the application
 * runtime directory. You may change this default setting by specifying the following
 * properties:
 * - {@link setConnectionString ConnectionString}
 * - {@link setUsername Username}
 * - {@link setPassword Pasword}
 *
 * The cached data is stored in a table in the specified database.
 * By default, the name of the table is called 'pradocache'. If the table does not
 * exist in the database, it will be automatically created with the following structure:
 * (key CHAR(128) PRIMARY KEY, value BLOB, expire INT)
 *
 * If you want to change the cache table name, or if you want to create the table by yourself,
 * you may set {@link setCacheTableName CacheTableName} and {@link setAutoCreateCacheTable AutoCreateCacheTableName} properties.
 *
 * The following basic cache operations are implemented:
 * - {@link get} : retrieve the value with a key (if any) from cache
 * - {@link set} : store the value with a key into cache
 * - {@link add} : store the value only if cache does not have this key
 * - {@link delete} : delete the value with the specified key from cache
 * - {@link flush} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@link get} operation
 * ensures that any expired value will not be returned. The expiration time by
 * the number of seconds. A expiration time 0 represents never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * Do not use the same database file for multiple applications using TDbCache.
 * Also note, cache is shared by all user sessions of an application.
 *
 * To use this module, the sqlite PHP extension must be loaded. Note, Sqlite extension
 * is no longer loaded by default since PHP 5.1.
 *
 * Some usage examples of TDbCache are as follows,
 * <code>
 * $cache=new TDbCache;  // TDbCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * </code>
 *
 * If loaded, TDbCache will register itself with {@link TApplication} as the
 * cache module. It can be accessed via {@link TApplication::getCache()}.
 *
 * TDbCache may be configured in application configuration file as follows
 * <code>
 * <module id="cache" class="System.Caching.TDbCache" />
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Caching
 * @since 3.1.0
 */
class TDbCache extends TCache
{
	/**
	 * @var TDbConnection the DB connection instance
	 */
	private $_db;
	/**
	 * @var string name of the DB cache table
	 */
	private $_cacheTable='pradocache';
	/**
	 * @var boolean whether the cache DB table should be created automatically
	 */
	private $_autoCreate=true;

	/**
	 * Destructor.
	 * Disconnect the db connection.
	 */
	public function __destruct()
	{
		if($this->_db!==null)
			$this->_db->setActive(false);
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It checks if the DbFile
	 * property is set, and creates a SQLiteDatabase instance for it.
	 * The database or the cache table does not exist, they will be created.
	 * Expired values are also deleted.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if sqlite extension is not installed,
	 *         DbFile is set invalid, or any error happens during creating database or cache table.
	 */
	public function init($config)
	{
		$db=$this->getDbConnection();
		if($db->getConnectionString()==='')
		{
			// default to SQLite3 database
			$dbFile=$this->getApplication()->getRuntimePath().'/sqlite3.cache';
			$db->setConnectionString('sqlite:'.$dbFile);
		}

		$db->setActive(true);

		$sql='DELETE FROM '.$this->_cacheTable.' WHERE expire<>0 AND expire<'.time();
		try
		{
			$db->createCommand($sql)->execute();
		}
		catch(Exception $e)
		{
			// DB table not exists
			if($this->_autoCreate)
			{
				$sql='CREATE TABLE '.$this->_cacheTable.' (itemkey CHAR(128) PRIMARY KEY, value BLOB, expire INT)';
				$db->createCommand($sql)->execute();
			}
			else
				throw TConfigurationException('pdocache_cachetable_inexistent',$this->_cacheTable);
		}

		parent::init($config);
	}

	/**
	 * @return TDbConnection the DB connection instance
	 */
	public function getDbConnection()
	{
		if($this->_db===null)
			$this->_db=new TDbConnection;
		return $this->_db;
	}

	/**
	 * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
	 */
	public function getConnectionString()
	{
		return $this->getDbConnection()->getConnectionString();
	}

	/**
	 * @param string The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @see http://www.php.net/manual/en/function.pdo-construct.php
	 */
	public function setConnectionString($value)
	{
		$this->getDbConnection()->setConnectionString($value);
	}

	/**
	 * @return string the username for establishing DB connection. Defaults to empty string.
	 */
	public function getUsername()
	{
		return $this->getDbConnection()->getUsername();
	}

	/**
	 * @param string the username for establishing DB connection
	 */
	public function setUsername($value)
	{
		$this->getDbConnection()->setUsername($value);
	}

	/**
	 * @return string the password for establishing DB connection. Defaults to empty string.
	 */
	public function getPassword()
	{
		return $this->getDbConnection()->getPassword();
	}

	/**
	 * @param string the password for establishing DB connection
	 */
	public function setPassword($value)
	{
		$this->getDbConnection()->setPassword($value);
	}

	/**
	 * @return string the name of the DB table to store cache content. Defaults to 'pradocache'.
	 * @see setAutoCreateCacheTable
	 */
	public function getCacheTableName()
	{
		return $this->_cacheTable;
	}

	/**
	 * Sets the name of the DB table to store cache content.
	 * Note, if {@link setAutoCreateCacheTable AutoCreateCacheTable} is false
	 * and you want to create the DB table manually by yourself,
	 * you need to make sure the DB table is of the following structure:
	 * (key CHAR(128) PRIMARY KEY, value BLOB, expire INT)
	 * @param string the name of the DB table to store cache content
	 * @see setAutoCreateCacheTable
	 */
	public function setCacheTableName($value)
	{
		$this->_cacheTable=$value;
	}

	/**
	 * @return boolean whether the cache DB table should be automatically created if not exists. Defaults to true.
	 * @see setAutoCreateCacheTable
	 */
	public function getAutoCreateCacheTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param boolean whether the cache DB table should be automatically created if not exists.
	 * @see setCacheTableName
	 */
	public function setAutoCreateCacheTable($value)
	{
		$this->_autoCreate=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		$sql='SELECT value FROM '.$this->_cacheTable.' WHERE itemkey=\''.$key.'\' AND (expire=0 OR expire>'.time().')';
		return $this->_db->createCommand($sql)->queryScalar();
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string the key identifying the value to be cached
	 * @param string the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key,$value,$expire)
	{
		$this->deleteValue($key);
		return $this->addValue($key,$value,$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string the key identifying the value to be cached
	 * @param string the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key,$value,$expire)
	{
		$sql="INSERT INTO {$this->_cacheTable} (itemkey,value,expire) VALUES(:key,:value,$expire)";
		try
		{
			$command=$this->_db->createCommand($sql);
			$command->bindValue(':key',$key,PDO::PARAM_STR);
			$command->bindValue(':value',$value,PDO::PARAM_LOB);
			$command->execute();
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$command=$this->_db->createCommand("DELETE FROM {$this->_cacheTable} WHERE itemkey=:key");
		$command->bindValue(':key',$key,PDO::PARAM_STR);
		$command->execute();
		return true;
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush()
	{
		$this->_db->createCommand("DELETE FROM {$this->_cacheTable}")->execute();
		return true;
	}
}

?>