<?php
/**
 * TSqlMapCacheModel, TSqlMapCacheTypes and TSqlMapCacheKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TSqlMapCacheModel corresponds to the <cacheModel> sql mapping configuration tag.
 *
 * The results from a query Mapped Statement can be cached simply by specifying
 * the {@link CacheModel TSqlMapStatement::setCacheModel()} property in <statement> tag.
 * A cache model is a configured cache that is defined within the sql map
 * configuration file. Cache models are configured using the <cacheModel> element.
 *
 * The cache model uses a pluggable framework for supporting different types of
 * caches. The choice of cache is specified by the {@link Implementation setImplementation()}
 * property. The class name specified must be one of {@link TSqlMapCacheTypes}.
 *
 * The cache implementations, LRU and FIFO cache below do not persist across
 * requests. That is, once the request is complete, all cache data is lost.
 * These caches are useful queries that results in the same repeated data during
 * the current request.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSqlMapCacheModel extends \Prado\TComponent
{
	private $_cache;
	private $_hits = 0;
	private $_requests = 0;
	private $_id;
	private $_implementation = TSqlMapCacheTypes::Basic;
	private $_properties = [];
	private $_flushInterval = 0;

	private static $_cacheTypes = [];

	public static function registerCacheType($type, $className)
	{
		self::$_cacheTypes[$type] = $className;
	}

	/**
	 * @return string unique cache model identifier.
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value unique cache model identifier.
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}

	/**
	 * @return string cache implements of TSqlMapCacheTypes, either 'Basic', 'LRU' or 'FIFO'.
	 */
	public function getImplementation()
	{
		return $this->_implementation;
	}

	/**
	 * @param string $value cache implements of TSqlMapCacheTypes, either 'Basic', 'LRU' or 'FIFO'.
	 */
	public function setImplementation($value)
	{
		if (isset(self::$_cacheTypes[$value])) {
			$this->_implementation = $value;
		} else {
			$this->_implementation = TPropertyValue::ensureEnum($value, 'Prado\\Data\\SqlMap\\Configuration\\TSqlMapCacheTypes');
		}
	}

	/**
	 * @param int $value the number of seconds in which the cached value will expire. 0 means never expire.
	 */
	public function setFlushInterval($value)
	{
		$this->_flushInterval = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return int cache duration.
	 */
	public function getFlushInterval()
	{
		return $this->_flushInterval;
	}

	/**
	 * Initialize the cache implementation, sets the actual cache contain if supplied.
	 * @param ISqLMapCache $cache cache implementation instance.
	 */
	public function initialize($cache = null)
	{
		if ($cache === null) {
			$this->_cache = Prado::createComponent($this->getImplementationClass(), $this);
		} else {
			$this->_cache = $cache;
		}
	}

	/**
	 * @return string cache implementation class name.
	 */
	public function getImplementationClass()
	{
		$implementation = $this->_implementation;
		if (isset(self::$_cacheTypes[$implementation])) {
			return self::$_cacheTypes[$implementation];
		}

		switch (TPropertyValue::ensureEnum($implementation, 'Prado\\Data\\SqlMap\\Configuration\\TSqlMapCacheTypes')) {
			case TSqlMapCacheTypes::FIFO: return '\\Prado\\Data\\SqlMap\\DataMapper\\TSqlMapFifoCache';
			case TSqlMapCacheTypes::LRU: return '\\Prado\\Data\\SqlMap\\DataMapper\\TSqlMapLruCache';
			case TSqlMapCacheTypes::Basic: return '\\Prado\\Data\\SqlMap\\DataMapper\\TSqlMapApplicationCache';
		}
	}

	/**
	 * Register a mapped statement that will trigger a cache flush.
	 * @param TMappedStatement $mappedStatement mapped statement that may flush the cache.
	 */
	public function registerTriggerStatement($mappedStatement)
	{
		$mappedStatement->attachEventHandler('OnExecuteQuery', [$this, 'flush']);
	}

	/**
	 * Clears the cache.
	 */
	public function flush()
	{
		$this->_cache->flush();
	}

	/**
	 * @param string|TSqlMapCacheKey $key cache key
	 * @return mixed cached value.
	 */
	public function get($key)
	{
		if ($key instanceof TSqlMapCacheKey) {
			$key = $key->getHash();
		}

		//if flush ?
		$value = $this->_cache->get($key);
		$this->_requests++;
		if ($value !== null) {
			$this->_hits++;
		}
		return $value;
	}

	/**
	 * @param string|TSqlMapCacheKey $key cache key
	 * @param mixed $value value to be cached.
	 */
	public function set($key, $value)
	{
		if ($key instanceof TSqlMapCacheKey) {
			$key = $key->getHash();
		}

		if ($value !== null) {
			$this->_cache->set($key, $value, $this->_flushInterval);
		}
	}

	/**
	 * @return float cache hit ratio.
	 */
	public function getHitRatio()
	{
		if ($this->_requests != 0) {
			return $this->_hits / $this->_requests;
		} else {
			return 0;
		}
	}
}
