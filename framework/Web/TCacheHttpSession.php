<?php
/**
 * TCacheHttpSession class
 *
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.1.1
 */

namespace Prado\Web;

use Prado\Caching\ICache;
use Prado\Exceptions\TConfigurationException;

/**
 * TCacheHttpSession class
 *
 * TCacheHttpSession provides access for storing session data using a cache module (e.g. TMemCache, TDbCache).
 * To specify the cache module for data storage, set the {@see setCacheModuleID CacheModuleID} property
 * which should refer to a valid cache module configured in the application configuration.
 *
 * The following example shows how we configure TCacheHttpSession:
 * ```xml
 *  <modules>
 *    <module id="cache" class="Prado\Caching\TMemCache" Host="localhost" Port="11211" />
 *    <module id="session" class="Prado\Web\TCacheHttpSession"
 *         CacheModuleID="cache" SessionName="SSID"
 *         CookieMode="Allow" AutoStart="true" GCProbability="1"
 *         UseTransparentSessionID="true" TimeOut="3600" />
 *  </modules>
 * ```
 *
 * Beware, by definition cache storage are volatile, which means the data stored on them
 * may be swapped out and get lost. This may not be the case for certain cache storage,
 * such as database. So make sure you manage your cache properly to avoid loss of session data.
 *
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.1
 */
class TCacheHttpSession extends THttpSession
{
	private $_prefix = 'session';
	private $_cacheModuleID = '';
	private $_cache;

	/**
	 * Initializes the module.
	 * This method is required by IModule.
	 * It reads the CacheModule property.
	 * @param \Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		if ($this->_cacheModuleID === '') {
			throw new TConfigurationException('cachesession_cachemoduleid_required');
		} elseif (($cache = $this->getApplication()->getModule($this->_cacheModuleID)) === null) {
			throw new TConfigurationException('cachesession_cachemodule_inexistent', $this->_cacheModuleID);
		} elseif ($cache instanceof ICache) {
			$this->_cache = $cache;
		} else {
			throw new TConfigurationException('cachesession_cachemodule_invalid', $this->_cacheModuleID);
		}
		$this->setUseCustomStorage(true);
		parent::init($config);
	}

	/**
	 * @return string the ID of the cache module.
	 */
	public function getCacheModuleID()
	{
		return $this->_cacheModuleID;
	}

	/**
	 * @param string $value the ID of the cache module.
	 */
	public function setCacheModuleID($value)
	{
		$this->_cacheModuleID = $value;
	}

	/**
	 * @return ICache the cache module being used for data storage
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * Session read handler.
	 * @param string $id session ID
	 * @return string the session data
	 */
	public function _read($id)
	{
		return (string) $this->_cache->get($this->calculateKey($id));
	}

	/**
	 * Session write handler.
	 * @param string $id session ID
	 * @param string $data session data
	 * @return bool whether session write is successful
	 */
	public function _write($id, $data)
	{
		return $this->_cache->set($this->calculateKey($id), $data, $this->getTimeout());
	}

	/**
	 * Session destroy handler.
	 * This method should be overriden if {@see setUseCustomStorage UseCustomStorage} is set true.
	 * @param string $id session ID
	 * @return bool whether session is destroyed successfully
	 */
	public function _destroy($id)
	{
		return $this->_cache->delete($this->calculateKey($id));
	}

	/**
	 * @return string prefix of session variable name to avoid conflict with other cache data. Defaults to 'session'.
	 */
	public function getKeyPrefix()
	{
		return $this->_prefix;
	}

	/**
	 * @param string $value prefix of session variable name to avoid conflict with other cache data
	 */
	public function setKeyPrefix($value)
	{
		$this->_prefix = $value;
	}

	/**
	 * @param string $id session variable name
	 * @return string a safe cache key associated with the session variable name
	 */
	protected function calculateKey($id)
	{
		return $this->_prefix . ':' . $id;
	}
}
