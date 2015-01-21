<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado
 */

namespace Prado;

/**
 * ICache interface.
 *
 * This interface must be implemented by cache managers.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
interface ICache
{
	/**
	 * Retrieves a value from cache with a specified key.
	 * @param string a key identifying the cached value
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
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ICacheDependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id,$value,$expire=0,$dependency=null);
	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ICacheDependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id,$value,$expire=0,$dependency=null);
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