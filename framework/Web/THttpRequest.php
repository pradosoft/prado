<?php
/**
 * THttpRequest, THttpCookie, THttpCookieCollection, TUri class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web
 */

namespace Prado\Web;

use Prado\Caching\TFileCacheDependency;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TPhpErrorException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\TApplicationMode;

/**
 * THttpRequest class
 *
 * THttpRequest provides storage and access scheme for user request sent via HTTP.
 * It also encapsulates a uniform way to parse and construct URLs.
 *
 * User post data can be retrieved from THttpRequest by using it like an associative array.
 * For example, to test if a user supplies a variable named 'param1', you can use,
 * <code>
 *   if(isset($request['param1'])) ...
 *   // equivalent to:
 *   // if($request->contains('param1')) ...
 * </code>
 * To get the value of 'param1', use,
 * <code>
 *   $value=$request['param1'];
 *   // equivalent to:
 *   //   $value=$request->itemAt('param1');
 * </code>
 * To traverse the user post data, use
 * <code>
 *   foreach($request as $name=>$value) ...
 * </code>
 * Note, POST and GET variables are merged together in THttpRequest.
 * If a variable name appears in both POST and GET data, then POST data
 * takes precedence.
 *
 * To construct a URL that can be recognized by Prado, use {@link constructUrl()}.
 * The format of the recognizable URLs is determined according to
 * {@link setUrlManager UrlManager}. By default, the following two formats
 * are recognized:
 * <code>
 * /index.php?ServiceID=ServiceParameter&Name1=Value1&Name2=Value2
 * /index.php/ServiceID,ServiceParameter/Name1,Value1/Name2,Value2
 * </code>
 * The first format is called 'Get' while the second 'Path', which is specified
 * via {@link setUrlFormat UrlFormat}. For advanced users who want to use
 * their own URL formats, they can write customized URL management modules
 * and install the managers as application modules and set {@link setUrlManager UrlManager}.
 *
 * The ServiceID in the above URLs is as defined in the application configuration
 * (e.g. the default page service's service ID is 'page').
 * As a consequence, your GET variable names should not conflict with the service
 * IDs that your application supports.
 *
 * THttpRequest also provides the cookies sent by the user, user information such
 * as his browser capabilities, accepted languages, etc.
 *
 * By default, THttpRequest is registered with {@link TApplication} as the
 * request module. It can be accessed via {@link TApplication::getRequest()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web
 * @since 3.0
 */
class THttpRequest extends \Prado\TApplicationComponent implements \IteratorAggregate, \ArrayAccess, \Countable, \Prado\IModule
{
	const CGIFIX__PATH_INFO = 1;
	const CGIFIX__SCRIPT_NAME = 2;
	/**
	 * @var TUrlManager the URL manager module
	 */
	private $_urlManager;
	/**
	 * @var string the ID of the URL manager module
	 */
	private $_urlManagerID = '';
	/**
	 * @var string Separator used to separate GET variable name and value when URL format is Path.
	 */
	private $_separator = ',';
	/**
	 * @var string requested service ID
	 */
	private $_serviceID;
	/**
	 * @var string requested service parameter
	 */
	private $_serviceParam;
	/**
	 * @var THttpCookieCollection cookies sent from user
	 */
	private $_cookies;
	/**
	 * @var string requested URI (URL w/o host info)
	 */
	private $_requestUri;
	/**
	 * @var string path info of URL
	 */
	private $_pathInfo;
	/**
	 * @var bool whether the session ID should be kept in cookie only
	 */
	private $_cookieOnly;
	private $_urlFormat = THttpRequestUrlFormat::Get;
	private $_services;
	private $_requestResolved = false;
	private $_enableCookieValidation = false;
	private $_cgiFix = 0;
	/**
	 * @var bool whether to cache the TUrlManager class (useful with a lot of TUrlMappings)
	 */
	private $_enableCache = false;
	/**
	 * @var string request URL
	 */
	private $_url;

	/**
	 * @var string module id
	 */
	private $_id;

	/**
	 * @var array contains all request variables
	 */
	private $_items = [];

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value id of this module
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TXmlElement $config module configuration
	 */
	public function init($config)
	{
		// Fill in default request info when the script is run in command line
		if (php_sapi_name() === 'cli') {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
			$_SERVER['REQUEST_METHOD'] = 'GET';
			$_SERVER['SERVER_NAME'] = 'localhost';
			$_SERVER['SERVER_PORT'] = 80;
			$_SERVER['HTTP_USER_AGENT'] = '';
		}

		// Info about server variables:
		// PHP_SELF contains real URI (w/ path info, w/o query string)
		// SCRIPT_NAME is the real URI for the requested script (w/o path info and query string)
		// QUERY_STRING is the string following the '?' in the ur (eg the a=x part in http://foo/bar?a=x)
		// REQUEST_URI contains the URI part entered in the browser address bar
		// SCRIPT_FILENAME is the file path to the executing script
		if (isset($_SERVER['REQUEST_URI'])) {
			$this->_requestUri = $_SERVER['REQUEST_URI'];
		} else {  // TBD: in this case, SCRIPT_NAME need to be escaped
			$this->_requestUri = $_SERVER['SCRIPT_NAME'] . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
		}

		if ($this->_cgiFix & self::CGIFIX__PATH_INFO && isset($_SERVER['ORIG_PATH_INFO'])) {
			$this->_pathInfo = substr($_SERVER['ORIG_PATH_INFO'], strlen($_SERVER['SCRIPT_NAME']));
		} elseif (isset($_SERVER['PATH_INFO'])) {
			$this->_pathInfo = $_SERVER['PATH_INFO'];
		} elseif (strpos($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_NAME']) === 0 && $_SERVER['PHP_SELF'] !== $_SERVER['SCRIPT_NAME']) {
			$this->_pathInfo = substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME']));
		} else {
			$this->_pathInfo = '';
		}

		$this->getApplication()->setRequest($this);
	}

	/**
	 * Strips slashes from input data.
	 * This method is applied when magic quotes is enabled.
	 * @param mixed &$data input data to be processed
	 * @return mixed processed data
	 * @deprecated useless since get_magic_quotes_gpc() is unavailable from php 5.4
	 */
	public function stripSlashes(&$data)
	{
		return is_array($data) ? array_map([$this, 'stripSlashes'], $data) : stripslashes($data);
	}

	/**
	 * @return TUri the request URL
	 */
	public function getUrl()
	{
		if ($this->_url === null) {
			$secure = $this->getIsSecureConnection();
			$url = $secure ? 'https://' : 'http://';
			if (empty($_SERVER['HTTP_HOST'])) {
				$url .= $_SERVER['SERVER_NAME'];
				$port = $_SERVER['SERVER_PORT'];
				if (($port != 80 && !$secure) || ($port != 443 && $secure)) {
					$url .= ':' . $port;
				}
			} else {
				$url .= $_SERVER['HTTP_HOST'];
			}
			$url .= $this->getRequestUri();
			$this->_url = new TUri($url);
		}
		return $this->_url;
	}

	/**
	 * Set true to cache the UrlManager instance. Consider to enable this cache
	 * when the application defines a lot of TUrlMappingPatterns
	 * @param bool $value true to cache urlmanager instance.
	 */
	public function setEnableCache($value)
	{
		$this->_enableCache = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool true if urlmanager instance should be cached, false otherwise.
	 */
	public function getEnableCache()
	{
		return $this->_enableCache;
	}

	protected function getCacheKey()
	{
		return $this->getID();
	}

	/**
	 * Saves the current UrlManager instance to cache.
	 * @param mixed $manager
	 * @return bool true if UrlManager instance was cached, false otherwise.
	 */
	protected function cacheUrlManager($manager)
	{
		if ($this->getEnableCache()) {
			$cache = $this->getApplication()->getCache();
			if ($cache !== null) {
				$dependencies = null;
				if ($this->getApplication()->getMode() !== TApplicationMode::Performance) {
					if ($manager instanceof TUrlMapping && $fn = $manager->getConfigFile()) {
						$fn = Prado::getPathOfNamespace($fn, $this->getApplication()->getConfigurationFileExt());
						$dependencies = new TFileCacheDependency($fn);
					}
				}
				return $cache->set($this->getCacheKey(), $manager, 0, $dependencies);
			}
		}
		return false;
	}

	/**
	 * Loads UrlManager instance from cache.
	 * @return TUrlManager intance if load was successful, null otherwise.
	 */
	protected function loadCachedUrlManager()
	{
		if ($this->getEnableCache()) {
			$cache = $this->getApplication()->getCache();
			if ($cache !== null) {
				$manager = $cache->get($this->getCacheKey());
				if ($manager instanceof TUrlManager) {
					return $manager;
				}
			}
		}
		return null;
	}

	/**
	 * @return string the ID of the URL manager module
	 */
	public function getUrlManager()
	{
		return $this->_urlManagerID;
	}

	/**
	 * Sets the URL manager module.
	 * By default, {@link TUrlManager} is used for managing URLs.
	 * You may specify a different module for URL managing tasks
	 * by loading it as an application module and setting this property
	 * with the module ID.
	 * @param string $value the ID of the URL manager module
	 */
	public function setUrlManager($value)
	{
		$this->_urlManagerID = $value;
	}

	/**
	 * @return TUrlManager the URL manager module
	 */
	public function getUrlManagerModule()
	{
		if ($this->_urlManager === null) {
			if (($this->_urlManager = $this->loadCachedUrlManager()) === null) {
				if (empty($this->_urlManagerID)) {
					$this->_urlManager = new TUrlManager;
					$this->_urlManager->init(null);
				} else {
					$this->_urlManager = $this->getApplication()->getModule($this->_urlManagerID);
					if ($this->_urlManager === null) {
						throw new TConfigurationException('httprequest_urlmanager_inexist', $this->_urlManagerID);
					}
					if (!($this->_urlManager instanceof TUrlManager)) {
						throw new TConfigurationException('httprequest_urlmanager_invalid', $this->_urlManagerID);
					}
				}
				$this->cacheUrlManager($this->_urlManager);
			}
		}
		return $this->_urlManager;
	}

	/**
	 * @return THttpRequestUrlFormat the format of URLs. Defaults to THttpRequestUrlFormat::Get.
	 */
	public function getUrlFormat()
	{
		return $this->_urlFormat;
	}

	/**
	 * Sets the format of URLs constructed and interpretted by the request module.
	 * A Get URL format is like index.php?name1=value1&name2=value2
	 * while a Path URL format is like index.php/name1,value1/name2,value.
	 * Changing the UrlFormat will affect {@link constructUrl} and how GET variables
	 * are parsed.
	 * @param THttpRequestUrlFormat $value the format of URLs.
	 */
	public function setUrlFormat($value)
	{
		$this->_urlFormat = TPropertyValue::ensureEnum($value, 'Prado\\Web\\THttpRequestUrlFormat');
	}

	/**
	 * @return string separator used to separate GET variable name and value when URL format is Path. Defaults to comma ','.
	 */
	public function getUrlParamSeparator()
	{
		return $this->_separator;
	}

	/**
	 * @param string $value separator used to separate GET variable name and value when URL format is Path.
	 * @throws TInvalidDataValueException if the separator is not a single character
	 */
	public function setUrlParamSeparator($value)
	{
		if (strlen($value) === 1) {
			$this->_separator = $value;
		} else {
			throw new TInvalidDataValueException('httprequest_separator_invalid');
		}
	}

	/**
	 * @return string request type, can be GET, POST, HEAD, or PUT
	 */
	public function getRequestType()
	{
		return $_SERVER['REQUEST_METHOD'] ?? null;
	}

	/**
	 * @param bool $mimetypeOnly whether to return only the mimetype (default: true)
	 * @return string content type (e.g. 'application/json' or 'text/html; encoding=gzip') or null if not specified
	 */
	public function getContentType($mimetypeOnly = true)
	{
		if (!isset($_SERVER['CONTENT_TYPE'])) {
			return null;
		}

		if ($mimetypeOnly === true && ($_pos = strpos(';', $_SERVER['CONTENT_TYPE'])) !== false) {
			return substr($_SERVER['CONTENT_TYPE'], 0, $_pos);
		}

		return $_SERVER['CONTENT_TYPE'];
	}

	/**
	 * @return bool if the request is sent via secure channel (https)
	 */
	public function getIsSecureConnection()
	{
		return isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off');
	}

	/**
	 * @return string part of the request URL after script name and before question mark.
	 */
	public function getPathInfo()
	{
		return $this->_pathInfo;
	}

	/**
	 * @return string part of that request URL after the question mark
	 */
	public function getQueryString()
	{
		return $_SERVER['QUERY_STRING'] ?? null;
	}

	/**
	 * @return string the requested http procolol. Blank string if not defined.
	 */
	public function getHttpProtocolVersion()
	{
		return $_SERVER['SERVER_PROTOCOL'] ?? null;
	}

	/**
	 * @param null|int $case Either {@link CASE_UPPER} or {@link CASE_LOWER} or as is null (default)
	 * @return array
	 */
	public function getHeaders($case = null)
	{
		static $result;

		if ($result === null && function_exists('apache_request_headers')) {
			$result = apache_request_headers();
		} elseif ($result === null) {
			$result = [];
			foreach ($_SERVER as $key => $value) {
				if (strncasecmp($key, 'HTTP_', 5) !== 0) {
					continue;
				}
				$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
				$result[$key] = $value;
			}
		}

		if ($case !== null) {
			return array_change_key_case($result, $case);
		}

		return $result;
	}

	/**
	 * @return string part of that request URL after the host info (including pathinfo and query string)
	 */
	public function getRequestUri()
	{
		return $this->_requestUri;
	}

	/**
	 * @param null|bool $forceSecureConnection whether to use HTTPS instead of HTTP even if the current request
	 * is sent via HTTP or vice versa
	 * 						null - keep current schema
	 * 						true - force https
	 * 						false - force http
	 * @return string schema and hostname of the requested URL
	 */
	public function getBaseUrl($forceSecureConnection = null)
	{
		$url = $this->getUrl();
		$scheme = ($forceSecureConnection) ? "https" : (($forceSecureConnection === null) ? $url->getScheme() : 'http');
		$host = $url->getHost();
		if (($port = $url->getPort())) {
			$host .= ':' . $port;
		}
		return $scheme . '://' . $host;
	}

	/**
	 * @return string entry script URL (w/o host part)
	 */
	public function getApplicationUrl()
	{
		if ($this->_cgiFix & self::CGIFIX__SCRIPT_NAME && isset($_SERVER['ORIG_SCRIPT_NAME'])) {
			return $_SERVER['ORIG_SCRIPT_NAME'];
		}

		return $_SERVER['SCRIPT_NAME'] ?? null;
	}

	/**
	 * @param null|bool $forceSecureConnection whether to use HTTPS instead of HTTP even if the current request
	 * is sent via HTTP or vice versa
	 * 						null - keep current schema
	 * 						true - force https
	 * 						false - force http
	 * @return string entry script URL (w/ host part)
	 */
	public function getAbsoluteApplicationUrl($forceSecureConnection = null)
	{
		return $this->getBaseUrl($forceSecureConnection) . $this->getApplicationUrl();
	}

	/**
	 * @return string application entry script file path (processed w/ realpath())
	 */
	public function getApplicationFilePath()
	{
		return realpath($_SERVER['SCRIPT_FILENAME'] ?? null);
	}

	/**
	 * @return string server name
	 */
	public function getServerName()
	{
		return $_SERVER['SERVER_NAME'] ?? null;
	}

	/**
	 * @return int server port number
	 */
	public function getServerPort()
	{
		return $_SERVER['SERVER_PORT'] ?? null;
	}

	/**
	 * @return string URL referrer, null if not present
	 */
	public function getUrlReferrer()
	{
		return $_SERVER['HTTP_REFERER'] ?? null;
	}

	/**
	 * @return string server software
	 * @since 3.3.3
	 */
	public function getServerSoftware()
	{
		return $_SERVER['SERVER_SOFTWARE'] ?? null;
	}

	/**
	 * @return array user browser capabilities
	 * @see get_browser
	 */
	public function getBrowser()
	{
		try {
			return get_browser();
		} catch (TPhpErrorException $e) {
			throw new TConfigurationException('httprequest_browscap_required');
		}
	}

	/**
	 * @return string user agent
	 */
	public function getUserAgent()
	{
		return $_SERVER['HTTP_USER_AGENT'] ?? null;
	}

	/**
	 * @return string user IP address
	 */
	public function getUserHostAddress()
	{
		return $_SERVER['REMOTE_ADDR'] ?? null;
	}

	/**
	 * @return string user host name, null if cannot be determined
	 */
	public function getUserHost()
	{
		return $_SERVER['REMOTE_HOST'] ?? null;
	}

	/**
	 * @return string user browser accept types
	 */
	public function getAcceptTypes()
	{
		// TBD: break it into array??
		return $_SERVER['HTTP_ACCEPT'] ?? null;
	}

	/**
	 * Returns a list of user preferred languages.
	 * The languages are returned as an array. Each array element
	 * represents a single language preference. The languages are ordered
	 * according to user preferences. The first language is the most preferred.
	 * @return array list of user preferred languages.
	 */
	public function getUserLanguages()
	{
		return Prado::getUserLanguages();
	}

	/**
	 * @return bool whether cookies should be validated. Defaults to false.
	 */
	public function getEnableCookieValidation()
	{
		return $this->_enableCookieValidation;
	}

	/**
	 * @param bool $value whether cookies should be validated.
	 */
	public function setEnableCookieValidation($value)
	{
		$this->_enableCookieValidation = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int whether to use ORIG_PATH_INFO and/or ORIG_SCRIPT_NAME. Defaults to 0.
	 * @see THttpRequest::CGIFIX__PATH_INFO, THttpRequest::CGIFIX__SCRIPT_NAME
	 */
	public function getCgiFix()
	{
		return $this->_cgiFix;
	}

	/**
	 * Enable this, if you're using PHP via CGI with php.ini setting "cgi.fix_pathinfo=1"
	 * and have trouble with friendly URL feature. Enable this only if you really know what you are doing!
	 * @param int $value enable bitwise to use ORIG_PATH_INFO and/or ORIG_SCRIPT_NAME.
	 * @see THttpRequest::CGIFIX__PATH_INFO, THttpRequest::CGIFIX__SCRIPT_NAME
	 */
	public function setCgiFix($value)
	{
		$this->_cgiFix = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return THttpCookieCollection list of cookies to be sent
	 */
	public function getCookies()
	{
		if ($this->_cookies === null) {
			$this->_cookies = new THttpCookieCollection;
			if ($this->getEnableCookieValidation()) {
				$sm = $this->getApplication()->getSecurityManager();
				foreach ($_COOKIE as $key => $value) {
					if (($value = $sm->validateData($value)) !== false) {
						$this->_cookies->add(new THttpCookie($key, $value));
					}
				}
			} else {
				foreach ($_COOKIE as $key => $value) {
					$this->_cookies->add(new THttpCookie($key, $value));
				}
			}
		}
		return $this->_cookies;
	}

	/**
	 * @return array list of uploaded files.
	 */
	public function getUploadedFiles()
	{
		return $_FILES;
	}

	/**
	 * @return array list of server variables.
	 */
	public function getServerVariables()
	{
		return $_SERVER;
	}

	/**
	 * @return array list of environment variables.
	 */
	public function getEnvironmentVariables()
	{
		return $_ENV;
	}

	/**
	 * Constructs a URL that can be recognized by PRADO.
	 * The actual construction work is done by the URL manager module.
	 * This method may append session information to the generated URL if needed.
	 * You may provide your own URL manager module by setting {@link setUrlManager UrlManager}
	 * to provide your own URL scheme.
	 *
	 * Note, the constructed URL does not contain the protocol and hostname part.
	 * You may obtain an absolute URL by prepending the constructed URL with {@link getBaseUrl BaseUrl}.
	 * @param string $serviceID service ID
	 * @param string $serviceParam service parameter
	 * @param null|array $getItems GET parameters, null if not needed
	 * @param bool $encodeAmpersand whether to encode the ampersand in URL, defaults to true.
	 * @param bool $encodeGetItems whether to encode the GET parameters (their names and values), defaults to false.
	 * @return string URL
	 * @see TUrlManager::constructUrl
	 */
	public function constructUrl($serviceID, $serviceParam, $getItems = null, $encodeAmpersand = true, $encodeGetItems = true)
	{
		if ($this->_cookieOnly === null) {
			$this->_cookieOnly = (int) ini_get('session.use_cookies') && (int) ini_get('session.use_only_cookies');
		}
		$url = $this->getUrlManagerModule()->constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems);
		if (defined('SID') && SID != '' && !$this->_cookieOnly) {
			return $url . (strpos($url, '?') === false ? '?' : ($encodeAmpersand ? '&amp;' : '&')) . SID;
		} else {
			return $url;
		}
	}

	/**
	 * Parses the request URL and returns an array of input parameters (excluding GET variables).
	 * You may override this method to support customized URL format.
	 * @return array list of input parameters, indexed by parameter names
	 * @see TUrlManager::parseUrl
	 */
	protected function parseUrl()
	{
		return $this->getUrlManagerModule()->parseUrl();
	}

	/**
	 * Resolves the requested service.
	 * This method implements a URL-based service resolution.
	 * A URL in the format of /index.php?sp=serviceID.serviceParameter
	 * will be resolved with the serviceID and the serviceParameter.
	 * You may override this method to provide your own way of service resolution.
	 * @param array $serviceIDs list of valid service IDs
	 * @return string the currently requested service ID, null if no service ID is found
	 * @see constructUrl
	 */
	public function resolveRequest($serviceIDs)
	{
		Prado::trace("Resolving request from " . $_SERVER['REMOTE_ADDR'], 'Prado\Web\THttpRequest');
		$getParams = $this->parseUrl();
		foreach ($getParams as $name => $value) {
			$_GET[$name] = $value;
		}
		$this->_items = array_merge($_GET, $_POST);
		$this->_requestResolved = true;
		foreach ($serviceIDs as $serviceID) {
			if ($this->contains($serviceID)) {
				$this->setServiceID($serviceID);
				$this->setServiceParameter($this->itemAt($serviceID));
				return $serviceID;
			}
		}
		return null;
	}

	/**
	 * @return bool true if request is already resolved, false otherwise.
	 */
	public function getRequestResolved()
	{
		return $this->_requestResolved;
	}

	/**
	 * @return string requested service ID
	 */
	public function getServiceID()
	{
		return $this->_serviceID;
	}

	/**
	 * Sets the requested service ID.
	 * @param string $value requested service ID
	 */
	public function setServiceID($value)
	{
		$this->_serviceID = $value;
	}

	/**
	 * @return string requested service parameter
	 */
	public function getServiceParameter()
	{
		return $this->_serviceParam;
	}

	/**
	 * Sets the requested service parameter.
	 * @param string $value requested service parameter
	 */
	public function setServiceParameter($value)
	{
		$this->_serviceParam = $value;
	}

	//------ The following methods enable THttpRequest to be TMap-like -----

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface \IteratorAggregate.
	 * @return \Iterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_items);
	}

	/**
	 * @return int the number of items in the request
	 */
	public function getCount()
	{
		return count($this->_items);
	}

	/**
	 * Returns the number of items in the request.
	 * This method is required by \Countable interface.
	 * @return int number of items in the request.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * @return array the key list
	 */
	public function getKeys()
	{
		return array_keys($this->_items);
	}

	/**
	 * Returns the item with the specified key.
	 * This method is exactly the same as {@link offsetGet}.
	 * @param mixed $key the key
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function itemAt($key)
	{
		return isset($this->_items[$key]) ? $this->_items[$key] : null;
	}

	/**
	 * Adds an item into the request.
	 * Note, if the specified key already exists, the old value will be overwritten.
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function add($key, $value)
	{
		$this->_items[$key] = $value;
	}

	/**
	 * Removes an item from the request by its key.
	 * @param mixed $key the key of the item to be removed
	 * @throws TInvalidOperationException if the item cannot be removed
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key)
	{
		if (isset($this->_items[$key]) || array_key_exists($key, $this->_items)) {
			$value = $this->_items[$key];
			unset($this->_items[$key]);
			return $value;
		} else {
			return null;
		}
	}

	/**
	 * Removes all items in the request.
	 */
	public function clear()
	{
		foreach (array_keys($this->_items) as $key) {
			$this->remove($key);
		}
	}

	/**
	 * @param mixed $key the key
	 * @return bool whether the request contains an item with the specified key
	 */
	public function contains($key)
	{
		return isset($this->_items[$key]) || array_key_exists($key, $this->_items);
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->_items;
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to check on
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->contains($offset);
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param int $offset the offset to set element
	 * @param mixed $item the element value
	 */
	public function offsetSet($offset, $item)
	{
		$this->add($offset, $item);
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface \ArrayAccess.
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}
}
