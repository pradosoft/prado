<?php
/**
 * TSqliteCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Data
 */

/**
 * TSqliteCache class
 *
 * TSqliteCache implements a cache application module based on SQLite database.
 *
 * The database file is specified by the DbFile property. This property must
 * be set before {@link init} is invoked. If the specified database file does not
 * exist, it will be created automatically. Make sure the database file is writable.
 *
 * The following basic cache operations are implemented:
 * - {@link get} : retrieve the value with a key (if any) from cache
 * - {@link set} : store the value with a key into cache
 * - {@link add} : store the value only if cache does not have this key
 * - {@link replace} : store the value only if cache has this key
 * - {@link delete} : delete the value with the specified key from cache
 * - {@link flush} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@link get} operation
 * ensures that any expired value will not be returned. The expiration time can
 * be specified by the number of seconds (maximum 60*60*24*30)
 * or a UNIX timestamp. A expiration time 0 represents never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * Do not use the same database file for multiple applications using TSqliteCache.
 * Also note, cache is shared by all user sessions of an application.
 *
 * To use this module, the sqlite PHP extension must be loaded. Sqlite extension
 * is no longer loaded by default since PHP 5.1.
 *
 * Some usage examples of TSqliteCache are as follows,
 * <code>
 * $cache=new TSqliteCache;  // TSqliteCache may also be loaded as a Prado application module
 * $cache->setDbFile($dbFilePath);
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Data
 * @since 3.0
 */
class TSqliteCache extends TComponent implements IModule, ICache
{
	/**
	 * name of the table storing cache data
	 */
	const CACHE_TABLE='cache';
	/**
	 * extension of the db file name
	 */
	const DB_FILE_EXT='.db';
	/**
	 * maximum number of seconds specified as expire
	 */
	const EXPIRE_LIMIT=2592000;  // 30 days

	/**
	 * @var boolean if the module has been initialized
	 */
	private $_initialized=false;
	/**
	 * @var SQLiteDatabase the sqlite database instance
	 */
	private $_db=null;
	/**
	 * @var string the database file name
	 */
	private $_file=null;
	/**
	 * @var string id of this module
	 */
	private $_id='';

	/**
	 * Destructor.
	 * Disconnect the db connection.
	 */
	public function __destruct()
	{
		$this->_db=null;
		parent::__destruct();
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It checks if the DbFile
	 * property is set, and creates a SQLiteDatabase instance for it.
	 * The database or the cache table does not exist, they will be created.
	 * Expired values are also deleted.
	 * @param IApplication Prado application, can be null
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if sqlite extension is not installed,
	 *         DbFile is set invalid, or any error happens during creating database or cache table.
	 */
	public function init($application,$config)
	{
		if(!function_exists('sqlite_open'))
			throw new TConfigurationException('sqlitecache_extension_required');
		if($this->_file===null)
			throw new TConfigurationException('sqlitecache_filename_required');
		$error='';
		if(($fname=Prado::getPathOfNamespace($this->_file,self::DB_FILE_EXT))===null)
			throw new TConfigurationException('sqlitecache_dbfile_invalid',$this->_file);
		if(($this->_db=new SQLiteDatabase($fname,0666,$error))===false)
			throw new TConfigurationException('sqlitecache_connection_failed',$error);
		if(($res=$this->_db->query('SELECT * FROM sqlite_master WHERE tbl_name=\''.self::CACHE_TABLE.'\' AND type=\'table\''))!=false)
		{
			if($res->numRows()===0)
			{
				if($this->_db->query('CREATE TABLE '.self::CACHE_TABLE.' (key CHAR(128) PRIMARY KEY, value BLOB, serialized INT, expire INT)')===false)
					throw new TConfigurationException('sqlitecache_table_creation_failed',sqlite_error_string(sqlite_last_error()));
			}
		}
		else
			throw new TConfigurationException('sqlitecache_table_creation_failed',sqlite_error_string(sqlite_last_error()));
		$this->_initialized=true;
		$this->_db->query('DELETE FROM '.self::CACHE_TABLE.' WHERE expire<>0 AND expire<'.time());
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return string database file path (in namespace form)
	 */
	public function getDbFile()
	{
		return $this->_file;
	}

	/**
	 * @param string database file path (in namespace form)
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setDbFile($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('sqlitecache_dbfile_unchangeable');
		else
			$this->_file=$value;
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($key)
	{
		$sql='SELECT serialized,value FROM '.self::CACHE_TABLE.' WHERE key=\''.md5($key).'\' AND (expire=0 OR expire>'.time().')';
		if(($ret=$this->_db->query($sql))!=false && ($row=$ret->fetch(SQLITE_ASSOC))!==false)
			return $row['serialized']?Prado::unserialize($row['value']):$row['value'];
		else
			return false;
	}

	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * Note, avoid using this method whenever possible. Database insertion is
	 * very expensive. Try using {@link add} instead, which will not store the value
	 * if the key is already in cache.
	 *
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($key,$value,$expire=0)
	{
		$serialized=is_string($value)?0:1;
		$value1=sqlite_escape_string($serialized?Prado::serialize($value):$value);
		if($expire && $expire<=self::EXPIRE_LIMIT)
			$expire=time()+$expire;
		$sql='REPLACE INTO '.self::CACHE_TABLE.' VALUES(\''.md5($key).'\',\''.$value1.'\','.$serialized.','.$expire.')';
		return $this->_db->query($sql)!==false;
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($key,$value,$expire=0)
	{
		$serialized=is_string($value)?0:1;
		$value1=sqlite_escape_string($serialized?Prado::serialize($value):$value);
		if($expire && $expire<=self::EXPIRE_LIMIT)
			$expire=time()+$expire;
		$sql='INSERT INTO '.self::CACHE_TABLE.' VALUES(\''.md5($key).'\',\''.$value1.'\','.$serialized.','.$expire.')';
		return @$this->_db->query($sql)!==false;
	}

	/**
	 * Stores a value identified by a key into cache only if the cache contains this key.
	 * The existing value and expiration time will be overwritten with the new ones.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function replace($key,$value,$expire=0)
	{
		$serialized=is_string($value)?0:1;
		$value1=sqlite_escape_string($serialized?Prado::serialize($value):$value);
		if($expire && $expire<=self::EXPIRE_LIMIT)
			$expire=time()+$expire;
		$sql='UPDATE '.self::CACHE_TABLE.' SET value=\''.$value1.'\', serialized='.$serialized.',expire='.$expire.' WHERE key=\''.md5($key).'\'';
		$this->_db->query($sql);
		$ret=$this->_db->query('SELECT serialized FROM '.self::CACHE_TABLE.' WHERE key=\''.md5($key).'\'');
		return ($ret!=false && $ret->numRows()>0);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($key)
	{
		$sql='DELETE FROM '.self::CACHE_TABLE.' WHERE key=\''.md5($key).'\'';
		return $this->_db->query($sql)!==false;
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush()
	{
		return $this->_db->query('DELETE FROM '.self::CACHE_TABLE)!==false;
	}
}

?>