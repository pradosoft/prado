<?php
/**
 * TAPCCache class file
 *
 * @author Alban Hanry <compte_messagerie@hotmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Data
 */

/**
 * TAPCCache class
 *
 * TAPCCache implements a cache application module based on {@link http://www.php.net/apc APC}.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * To use this module, the APC PHP extension must be loaded.
 *
 * Some usage examples of TAPCCache are as follows,
 * <code>
 * $cache=new TAPCCache;  // TAPCCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * </code>
 *
 * If loaded, TAPCCache will register itself with {@link TApplication} as the
 * cache module. It can be accessed via {@link TApplication::getCache()}.
 *
 * TAPCCache may be configured in application configuration file as follows
 * <module id="cache" type="System.Data.TAPCCache" Prefix="apc_cache_prefix_key_"/>
 *
 **************
 * NOTE: not backward compatible with Prado ICache, you cannot specify a timestamp for expire.
 * It could be implemented, but what the point to check for a cache wich is meant to be as quick as possible
 ***************
 * @author Alban Hanry <compte_messagerie@hotmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Data
 * @since 3.0b
 */
class TAPCCache extends TModule implements ICache
{
   /**
    * @var boolean if the module is initialized
    */
   private $_initialized=false;

   /**
    * @var string a unique prefix used to identify this cache instance from the others
    */
   protected $_prefix=null;

   /**
    * Initializes this module.
    * This method is required by the IModule interface.
    * @param TXmlElement configuration for this module, can be null
    * @throws TConfigurationException if apc extension is not installed or not started, check your php.ini
    */
   public function init($config)
   {
      $application=$this->getApplication();
      if($application->getMode()!==TApplication::STATE_PERFORMANCE)
      {
         if(!extension_loaded('apc'))
            throw new TConfigurationException('apccache_extension_required');
      }
      if(!$this->_prefix)
         $this->_prefix=$this->getApplication()->getUniqueID();
      $this->_initialized=true;
      $application->setCache($this);
   }

   /**
    * @return string prefix used to cache key
    */
   public function getPrefix()
   {
      return $this->_prefix;
   }

   /**
    * @param string prefix to be used for cache key
    * @throws TInvalidOperationException if the module is already initialized
    */
   public function setPrefix($value)
   {
      if($this->_initialized)
         throw new TInvalidOperationException('apccache_prefix_unchangeable');
      else
         $this->_prefix=$value;
   }

   /**
    * Retrieves a value from cache with a specified key.
    * @return mixed the value stored in cache, false if the value is not in the cache or expired.
    */
   public function get($key)
   {
      return apc_fetch($this->_prefix.$key);
   }

   /**
    * Stores a value identified by a key into cache.
    * If the cache already contains such a key, the existing value and
    * expiration time will be replaced with the new ones.
    *
    * @param string the key identifying the value to be cached
    * @param mixed the value to be cached
    * @param integer the expiration time of the value,
    *        0 means never expire,
    * @return boolean true if the value is successfully stored into cache, false otherwise
    */
   public function set($key,$value,$expire=0)
   {
      return apc_store($this->_prefix.$key,$value,$expire);
   }

   /**
    * Stores a value identified by a key into cache if the cache does not contain this key.
    * Nothing will be done if the cache already contains the key.
    * @param string the key identifying the value to be cached
    * @param mixed the value to be cached
    * @param integer the expiration time of the value,
    *        0 means never expire,
    * @return boolean true if the value is successfully stored into cache, false otherwise
    */
   public function add($key,$value,$expiry=0)
   {
      if(!apc_fetch($this->_prefix.$key))
         apc_store($this->_prefix.$key,$value,$expire);
   }

   /**
    * Stores a value identified by a key into cache only if the cache contains this key.
    * The existing value and expiration time will be overwritten with the new ones.
    * @param string the key identifying the value to be cached
    * @param mixed the value to be cached
    * @param integer the expiration time of the value,
    *        0 means never expire,
    * @return boolean true if the value is successfully stored into cache, false otherwise
    */
   public function replace($key,$value,$expiry=0)
   {
      return apc_store($this->_prefix.$key,$value,$expire);
   }

   /**
    * Deletes a value with the specified key from cache
    * @param string the key of the value to be deleted
    * @return boolean if no error happens during deletion
    */
   public function delete($key)
   {
      return apc_delete($this->_prefix.$key);
   }

   /**
    * Deletes all values from cache.
    */
   public function flush()
   {
      return apc_clear_cache('user');
   }
}

?>