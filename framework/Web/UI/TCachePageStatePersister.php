<?php

/**
 * TCachePageStatePersister class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Caching\ICache;
use Prado\Prado;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Compression\ICompressionConfigurable;
use Prado\IO\Compression\TCompressionConfig;
use Prado\IO\Compression\TCompressionConfigTrait;
use Prado\Security\TAuthManager;
use Prado\TPropertyValue;
use Prado\Web\THttpSession;

/**
 * TCachePageStatePersister class
 *
 * TCachePageStatePersister implements a page state persistent method based on cache.
 * Page state are stored in cache (e.g. memcache, DB cache, etc.), and only a small token
 * is passed to the client side to identify the state. This greatly reduces the size of
 * the page state that needs to be transmitted between the server and the client. Of course,
 * this is at the cost of using server side resource.
 *
 * A cache module has to be loaded in order to use TCachePageStatePersister.
 * By default, TCachePageStatePersister will use the primary cache module.
 * A non-primary cache module can be used by setting {@see setCacheModuleID CacheModuleID}.
 * Any cache module, as long as it implements the interface {@see \Prado\Caching\ICache}, may be used.
 * For example, one can use {@see \Prado\Caching\TDbCache}, {@see \Prado\Caching\TMemCache}, {@see \Prado\Caching\TAPCCache}, etc.
 *
 * TCachePageStatePersister uses {@see setCacheTimeout CacheTimeout} to limit the data
 * that stores in cache.  {@see setCacheTimeoutMode CacheTimeoutMode} can take the
 * lifetime from the session or the login instead, so a page state lasts as long as its
 * page can still be posted back; see {@see TCachePageStatePersisterTimeoutMode}.
 *
 * Since server resource is often limited, be cautious if you plan to use TCachePageStatePersister
 * for high-traffic Web pages. You may consider using a small {@see setCacheTimeout CacheTimeout}.
 *
 * There are a couple of ways to use TCachePageStatePersister.
 * One can override the page's {@see \Prado\Web\UI\TPage::getStatePersister()} method and
 * create a TCachePageStatePersister instance there.
 * Or one can configure the pages to use TCachePageStatePersister in page configurations
 * as follows,
 * ```xml
 *   <pages StatePersisterClass="Prado\Web\UI\TCachePageStatePersister"
 *          StatePersister.CacheModuleID="mycache"
 *          StatePersister.CacheTimeout="3600"
 *          StatePersister.CacheTimeoutMode="Auto" />
 * ```
 * Note in the above, we use StatePersister.CacheModuleID to configure the cache module ID
 * for the TCachePageStatePersister instance.
 *
 * The above configuration will affect the pages under the directory containing
 * this configuration and all its subdirectories.
 * To configure individual pages to use TCachePageStatePersister, use
 * ```xml
 *   <pages>
 *     <page id="PageID" StatePersisterClass="Prado\Web\UI\TCachePageStatePersister" />
 *   </pages>
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.1
 */
class TCachePageStatePersister extends \Prado\TComponent implements IPageStatePersister, ICompressionConfigurable
{
	use TApplicationClockAwareTrait;
	use TCompressionConfigTrait;

	/**
	 * The {@see setCacheTimeoutMode CacheTimeoutMode} a persister starts with.  `Fixed`
	 * keeps the lifetime at {@see getCacheTimeout() CacheTimeout}.
	 * @since 4.4.0
	 */
	public const DEFAULT_CACHE_TIMEOUT_MODE = TCachePageStatePersisterTimeoutMode::Fixed;

	private $_prefix = 'statepersister';
	private $_page;
	private $_cache;
	private $_cacheModuleID = '';
	private $_timeout = 1800;
	/**
	 * @var string where the lifetime of a cached page state comes from
	 * @since 4.4.0
	 */
	private $_timeoutMode;

	/**
	 * Applies the `DEFAULT_` constants of the instantiated class, so a subclass that
	 * redeclares one starts from its own value.
	 * @since 4.4.0
	 */
	public function __construct()
	{
		$this->_timeoutMode = static::DEFAULT_CACHE_TIMEOUT_MODE;
		parent::__construct();
	}

	/**
	 * Returns the compression settings this persister starts from: the client token
	 * compresses by default, under the `deflate` coding and whatever its length.  The
	 * state kept in the cache is stored as is.
	 * @return TCompressionConfig a new compression configuration.
	 * @since 4.4.0
	 */
	protected function newCompression(): TCompressionConfig
	{
		return new TPageStateCompressionConfig();
	}

	/**
	 * @return TPage the page that this persister works for
	 */
	public function getPage()
	{
		return $this->_page;
	}

	/**
	 * @param TPage $page the page that this persister works for.
	 */
	public function setPage(TPage $page)
	{
		$this->_page = $page;
	}

	/**
	 * @return string the ID of the cache module.
	 */
	public function getCacheModuleID()
	{
		return $this->_cacheModuleID;
	}

	/**
	 * @param string $value the ID of the cache module. If not set, the primary cache module will be used.
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
		if ($this->_cache === null) {
			if ($this->_cacheModuleID !== '') {
				$cache = Prado::getApplication()->getModule($this->_cacheModuleID);
			} else {
				$cache = Prado::getApplication()->getCache();
			}
			if ($cache === null || !($cache instanceof ICache)) {
				if ($this->_cacheModuleID !== '') {
					throw new TConfigurationException('cachepagestatepersister_cachemoduleid_invalid', $this->_cacheModuleID);
				} else {
					throw new TConfigurationException('cachepagestatepersister_cache_required');
				}
			}
			$this->_cache = $cache;
		}
		return $this->_cache;
	}

	/**
	 * @return int the number of seconds in which the cached state will expire. Defaults to 1800.
	 */
	public function getCacheTimeout()
	{
		return $this->_timeout;
	}

	/**
	 * @param int $value the number of seconds in which the cached state will expire. 0 means never expire.
	 * @throws TInvalidDataValueException if the number is smaller than 0.
	 */
	public function setCacheTimeout($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) >= 0) {
			$this->_timeout = $value;
		} else {
			throw new TInvalidDataValueException('cachepagestatepersister_timeout_invalid');
		}
	}

	/**
	 * @return string where the lifetime of a cached page state comes from, a
	 *   {@see TCachePageStatePersisterTimeoutMode} value. Defaults to {@see DEFAULT_CACHE_TIMEOUT_MODE}.
	 * @since 4.4.0
	 */
	public function getCacheTimeoutMode()
	{
		return $this->_timeoutMode;
	}

	/**
	 * @param string $value where the lifetime of a cached page state comes from:
	 *   `Fixed`, `Session`, `Auth` or `Auto`.
	 * @throws TInvalidDataValueException if the value is not a {@see TCachePageStatePersisterTimeoutMode}.
	 * @since 4.4.0
	 */
	public function setCacheTimeoutMode($value)
	{
		$this->_timeoutMode = TPropertyValue::ensureEnum($value, TCachePageStatePersisterTimeoutMode::class);
	}

	/**
	 * Returns the lifetime, in seconds, of a page state saved now, resolved through
	 * {@see getCacheTimeoutMode() CacheTimeoutMode}.  A source that does not apply falls
	 * through to the next, and {@see getCacheTimeout() CacheTimeout} ends every chain.
	 * @return int the lifetime in seconds; 0 means the state never expires.
	 * @since 4.4.0
	 */
	public function getEffectiveCacheTimeout(): int
	{
		$mode = $this->getCacheTimeoutMode();
		if ($mode === TCachePageStatePersisterTimeoutMode::Auth || $mode === TCachePageStatePersisterTimeoutMode::Auto) {
			if (($timeout = $this->getAuthTimeout()) > 0) {
				return $timeout;
			}
		}
		if ($mode === TCachePageStatePersisterTimeoutMode::Session || $mode === TCachePageStatePersisterTimeoutMode::Auto) {
			if (($timeout = $this->getSessionTimeout()) > 0) {
				return $timeout;
			}
		}
		return $this->getCacheTimeout();
	}

	/**
	 * Returns the login lifetime of the current user: the auth manager's `AuthExpire`,
	 * when it is above 0, `AllowAutoLogin` is off, and the user is authenticated.
	 * `AuthExpire` slides forward on each request, so a state saved now lasts as long as
	 * the login does as of this request.
	 * @return int the lifetime in seconds, or 0 when no login lifetime applies.
	 * @since 4.4.0
	 */
	protected function getAuthTimeout(): int
	{
		$app = Prado::getApplication();
		if (!$app || !($user = $app->getUser()) || $user->getIsGuest()) {
			return 0;
		}
		$auth = $this->findModule(TAuthManager::class);
		if (!($auth instanceof TAuthManager) || $auth->getAllowAutoLogin()) {
			return 0;
		}
		return max(0, (int) $auth->getAuthExpire());
	}

	/**
	 * Returns the session lifetime: the session module's `Timeout`.  The module is looked
	 * up among those configured, so an application without one gains none.
	 * @return int the lifetime in seconds, or 0 when there is no session module.
	 * @since 4.4.0
	 */
	protected function getSessionTimeout(): int
	{
		$session = $this->findModule(THttpSession::class);
		return ($session instanceof THttpSession) ? max(0, (int) $session->getTimeout()) : 0;
	}

	/**
	 * Returns the first configured module of a type, loading it when it is lazy.
	 * @param string $type the module class.
	 * @return ?\Prado\IModule the module, or null when none is configured.
	 * @since 4.4.0
	 */
	protected function findModule(string $type)
	{
		$app = Prado::getApplication();
		if (!$app) {
			return null;
		}
		foreach ($app->getModulesByType($type) as $id => $module) {
			return $module ?? $app->getModule($id);
		}
		return null;
	}

	/**
	 * @return string prefix of cache variable name to avoid conflict with other cache data. Defaults to 'statepersister'.
	 */
	public function getKeyPrefix()
	{
		return $this->_prefix;
	}

	/**
	 * @param string $value prefix of cache variable name to avoid conflict with other cache data
	 */
	public function setKeyPrefix($value)
	{
		$this->_prefix = $value;
	}

	/**
	 * @param string $timestamp micro timestamp when saving state occurs
	 * @return string a key that is unique per user request
	 */
	protected function calculateKey($timestamp)
	{
		return $this->getKeyPrefix() . ':'
			. $this->_page->getRequest()->getUserHostAddress()
			. $this->_page->getPagePath()
			. $timestamp;
	}

	/**
	 * Saves state in cache.
	 * @param mixed $data state to be stored
	 */
	public function save($data)
	{
		$timestamp = (string) $this->getClock()->microtime();
		$key = $this->calculateKey($timestamp);
		$this->getCache()->add($key, $data, $this->getEffectiveCacheTimeout());
		$this->_page->setClientState(TPageStateFormatter::serialize($this->_page, $timestamp));
	}

	/**
	 * Loads page state from cache.
	 * @throws THttpException if page state is corrupted
	 * @return mixed the restored state
	 */
	public function load()
	{
		if (($timestamp = TPageStateFormatter::unserialize($this->_page, $this->_page->getRequestClientState())) !== null) {
			$key = $this->calculateKey($timestamp);
			if (($data = $this->getCache()->get($key)) !== false) {
				return $data;
			}
		}
		throw new THttpException(400, 'cachepagestatepersister_pagestate_corrupted');
	}
}
