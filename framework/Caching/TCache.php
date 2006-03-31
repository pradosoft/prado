<?php

/**
 * ICache interface.
 *
 * This interface must be implemented by cache managers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Caching
 * @since 3.0
 */
interface ICache
{
	/**
	 * Retrieves a value from cache with a specified key.
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($id);
	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60*60*24*30 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id,$value,$expire=0);
	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60*60*24*30 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id,$value,$expire=0);
	/**
	 * Stores a value identified by a key into cache only if the cache contains this key.
	 * The existing value and expiration time will be overwritten with the new ones.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60*60*24*30 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function replace($id,$value,$expire=0);
	/**
	 * Deletes a value with the specified key from cache
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($id);
	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush();
}

interface IDependency
{

}

class TTimeDependency
{
}

class TFileDependency
{
}

class TDirectoryDependency
{
}

?>