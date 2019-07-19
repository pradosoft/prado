<?php
/**
 * TUrlMapping, TUrlMappingPattern and TUrlMappingPatternSecureConnection class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web
 */

namespace Prado\Web;

use Prado\Collections\TAttributeCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TUrlMappingPattern class.
 *
 * TUrlMappingPattern represents a pattern used to parse and construct URLs.
 * If the currently requested URL matches the pattern, it will alter
 * the THttpRequest parameters. If a constructUrl() call matches the pattern
 * parameters, the pattern will generate a valid URL. In both case, only the PATH_INFO
 * part of a URL is parsed/constructed using the pattern.
 *
 * To specify the pattern, set the {@link setPattern Pattern} property.
 * {@link setPattern Pattern} takes a string expression with
 * parameter names enclosed between a left brace '{' and a right brace '}'.
 * The patterns for each parameter can be set using {@link getParameters Parameters}
 * attribute collection. For example
 * <code>
 * <url ... pattern="articles/{year}/{month}/{day}"
 *          parameters.year="\d{4}" parameters.month="\d{2}" parameters.day="\d+" />
 * </code>
 *
 * In the above example, the pattern contains 3 parameters named "year",
 * "month" and "day". The pattern for these parameters are, respectively,
 * "\d{4}" (4 digits), "\d{2}" (2 digits) and "\d+" (1 or more digits).
 * Essentially, the <tt>Parameters</tt> attribute name and values are used
 * as substrings in replacing the placeholders in the <tt>Pattern</tt> string
 * to form a complete regular expression string.
 *
 * For more complicated patterns, one may specify the pattern using a regular expression
 * by {@link setRegularExpression RegularExpression}. For example, the above pattern
 * is equivalent to the following regular expression-based pattern:
 * <code>
 * #^articles/(?P<year>\d{4})/(?P<month>\d{2})\/(?P<day>\d+)$#u
 * </code>
 * The above regular expression used the "named group" feature available in PHP.
 * If you intended to use the <tt>RegularExpression</tt> property or
 * regular expressions in CDATA sections, notice that you need to escape the slash,
 * if you are using the slash as regular expressions delimiter.
 *
 * Thus, only an url that matches the pattern will be valid. For example,
 * a URL <tt>http://example.com/index.php/articles/2006/07/21</tt> will match the above pattern,
 * while <tt>http://example.com/index.php/articles/2006/07/hello</tt> will not
 * since the "day" parameter pattern is not satisfied.
 *
 * The parameter values are available through the <tt>THttpRequest</tt> instance (e.g.
 * <tt>$this->Request['year']</tt>).
 *
 * The {@link setServiceParameter ServiceParameter} and {@link setServiceID ServiceID}
 * (the default ID is 'page') set the service parameter and service id respectively.
 *
 * Since 3.1.4 you can also use simplyfied wildcard patterns to match multiple
 * ServiceParameters with a single rule. The pattern must contain the placeholder
 * {*} for the ServiceParameter. For example
 *
 * <url ServiceParameter="adminpages.*" pattern="admin/{*}" />
 *
 * This rule will match an URL like <tt>http://example.com/index.php/admin/edituser</tt>
 * and resolve it to the page Application.pages.admin.edituser. The wildcard matching
 * is non-recursive. That means you have to add a rule for every subdirectory you
 * want to access pages in:
 *
 * <url ServiceParameter="adminpages.users.*" pattern="useradmin/{*}" />
 *
 * It is still possible to define an explicit rule for a page in the wildcard path.
 * This rule has to preceed the wildcard rule.
 *
 * You can also use parameters with wildcard patterns. The parameters are then
 * available with every matching page:
 *
 * <url ServiceParameter="adminpages.*" pattern="admin/{*}/{id}" parameters.id="\d+" />
 *
 * To enable automatic parameter encoding in a path format from wildcard patterns you can set
 * {@setUrlFormat UrlFormat} to 'Path':
 *
 * <url ServiceParameter="adminpages.*" pattern="admin/{*}" UrlFormat="Path" />
 *
 * This will create and parse URLs of the form
 * <tt>.../index.php/admin/listuser/param1/value1/param2/value2</tt>.
 *
 * Use {@setUrlParamSeparator} to define another separator character between parameter
 * name and value. Parameter/value pairs are always separated by a '/'.
 *
 * <url ServiceParameter="adminpages.*" pattern="admin/{*}" UrlFormat="Path" UrlParamSeparator="-" />
 *
 * <tt>.../index.php/admin/listuser/param1-value1/param2-value2</tt>.
 *
 * Since 3.2.2 you can also add a list of "constants" parameters that can be used just
 * like the original "parameters" parameters, except that the supplied value will be treated
 * as a simple string constant instead of a regular expression. For example
 *
 * <url ServiceParameter="MyPage" pattern="/mypage/mypath/list/detail/{pageidx}" parameters.pageidx="\d+" constants.listtype="detailed"/>
 * <url ServiceParameter="MyPage" pattern="/mypage/mypath/list/summary/{pageidx}" parameters.pageidx="\d+" constants.listtype="summarized"/>
 *
 * These rules, when matched by the actual request, will make the application see a "lisstype" parameter present
 * (even through not supplied in the request) and equal to "detailed" or "summarized", depending on the friendly url matched.
 * The constants is practically a table-based validation and translation of specified, fixed-set parameter values.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web
 * @since 3.0.5
 */
class TUrlMappingPattern extends \Prado\TComponent
{
	/**
	 * @var string service parameter such as Page class name.
	 */
	private $_serviceParameter;
	/**
	 * @var string service ID, default is 'page'.
	 */
	private $_serviceID = 'page';
	/**
	 * @var string url pattern to match.
	 */
	private $_pattern;
	/**
	 * @var TAttributeCollection parameter regular expressions.
	 */
	private $_parameters;
	/**
	 * @var TAttributeCollection of constant parameters.
	 */
	protected $_constants;
	/**
	 * @var string regular expression pattern.
	 */
	private $_regexp = '';

	private $_customUrl = true;

	private $_manager;

	private $_caseSensitive = true;

	private $_isWildCardPattern = false;

	private $_urlFormat = THttpRequestUrlFormat::Get;

	private $_separator = '/';

	/**
	 * @var TUrlMappingPatternSecureConnection
	 * @since 3.2
	 */
	private $_secureConnection = TUrlMappingPatternSecureConnection::Automatic;

	/**
	 * Constructor.
	 * @param TUrlManager $manager the URL manager instance
	 */
	public function __construct(TUrlManager $manager)
	{
		$this->_manager = $manager;
	}

	/**
	 * @return TUrlManager the URL manager instance
	 */
	public function getManager()
	{
		return $this->_manager;
	}

	/**
	 * Initializes the pattern.
	 * @param TXmlElement $config configuration for this module.
	 * @throws TConfigurationException if service parameter is not specified
	 */
	public function init($config)
	{
		if ($this->_serviceParameter === null) {
			throw new TConfigurationException('urlmappingpattern_serviceparameter_required', $this->getPattern());
		}
		if (strpos($this->_serviceParameter, '*') !== false) {
			$this->_isWildCardPattern = true;
		}
	}

	/**
	 * Substitute the parameter key value pairs as named groupings
	 * in the regular expression matching pattern.
	 * @return string regular expression pattern with parameter subsitution
	 */
	protected function getParameterizedPattern()
	{
		$params = [];
		$values = [];
		if ($this->_parameters) {
			foreach ($this->_parameters as $key => $value) {
				$params[] = '{' . $key . '}';
				$values[] = '(?P<' . $key . '>' . $value . ')';
			}
		}
		if ($this->getIsWildCardPattern()) {
			$params[] = '{*}';
			// service parameter must not contain '=' and '/'
			$values[] = '(?P<' . $this->getServiceID() . '>[^=/]+)';
		}
		$params[] = '/';
		$values[] = '\\/';
		$regexp = str_replace($params, $values, trim($this->getPattern(), '/') . '/');
		if ($this->_urlFormat === THttpRequestUrlFormat::Get) {
			$regexp = '/^' . $regexp . '$/u';
		} else {
			$regexp = '/^' . $regexp . '(?P<urlparams>.*)$/u';
		}

		if (!$this->getCaseSensitive()) {
			$regexp .= 'i';
		}
		return $regexp;
	}

	/**
	 * @return string full regular expression mapping pattern
	 */
	public function getRegularExpression()
	{
		return $this->_regexp;
	}

	/**
	 * @param string $value full regular expression mapping pattern.
	 */
	public function setRegularExpression($value)
	{
		$this->_regexp = $value;
	}

	/**
	 * @return bool whether the {@link getPattern Pattern} should be treated as case sensititve. Defaults to true.
	 */
	public function getCaseSensitive()
	{
		return $this->_caseSensitive;
	}

	/**
	 * @param bool $value whether the {@link getPattern Pattern} should be treated as case sensititve.
	 */
	public function setCaseSensitive($value)
	{
		$this->_caseSensitive = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @param string $value service parameter, such as page class name.
	 */
	public function setServiceParameter($value)
	{
		$this->_serviceParameter = $value;
	}

	/**
	 * @return string service parameter, such as page class name.
	 */
	public function getServiceParameter()
	{
		return $this->_serviceParameter;
	}

	/**
	 * @param string $value service id to handle.
	 */
	public function setServiceID($value)
	{
		$this->_serviceID = $value;
	}

	/**
	 * @return string service id.
	 */
	public function getServiceID()
	{
		return $this->_serviceID;
	}

	/**
	 * @return string url pattern to match. Defaults to ''.
	 */
	public function getPattern()
	{
		return $this->_pattern;
	}

	/**
	 * @param string $value url pattern to match.
	 */
	public function setPattern($value)
	{
		$this->_pattern = $value;
	}

	/**
	 * @return TAttributeCollection parameter key value pairs.
	 */
	public function getParameters()
	{
		if (!$this->_parameters) {
			$this->_parameters = new TAttributeCollection;
			$this->_parameters->setCaseSensitive(true);
		}
		return $this->_parameters;
	}

	/**
	 * @param TAttributeCollection $value new parameter key value pairs.
	 */
	public function setParameters($value)
	{
		$this->_parameters = $value;
	}

	/**
	 * @return TAttributeCollection constanst parameter key value pairs.
	 * @since 3.2.2
	 */
	public function getConstants()
	{
		if (!$this->_constants) {
			$this->_constants = new TAttributeCollection;
			$this->_constants->setCaseSensitive(true);
		}
		return $this->_constants;
	}

	/**
	 * Uses URL pattern (or full regular expression if available) to
	 * match the given url path.
	 * @param THttpRequest $request the request module
	 * @return array matched parameters, empty if no matches.
	 */
	public function getPatternMatches($request)
	{
		$matches = [];
		if (($pattern = $this->getRegularExpression()) !== '') {
			preg_match($pattern, $request->getPathInfo(), $matches);
		} else {
			preg_match($this->getParameterizedPattern(), trim($request->getPathInfo(), '/') . '/', $matches);
		}

		if ($this->getIsWildCardPattern() && isset($matches[$this->_serviceID])) {
			$matches[$this->_serviceID] = str_replace('*', $matches[$this->_serviceID], $this->_serviceParameter);
		}

		if (isset($matches['urlparams'])) {
			$params = explode('/', $matches['urlparams']);
			if ($this->_separator === '/') {
				while ($key = array_shift($params)) {
					$matches[$key] = ($value = array_shift($params)) ? $value : '';
				}
			} else {
				array_pop($params);
				foreach ($params as $param) {
					[$key, $value] = explode($this->_separator, $param, 2);
					$matches[$key] = $value;
				}
			}
			unset($matches['urlparams']);
		}

		if (count($matches) > 0 && $this->_constants) {
			foreach ($this->_constants->toArray() as $key => $value) {
				$matches[$key] = $value;
			}
		}

		return $matches;
	}

	/**
	 * Returns a value indicating whether to use this pattern to construct URL.
	 * @return bool whether to enable custom constructUrl. Defaults to true.
	 * @since 3.1.1
	 */
	public function getEnableCustomUrl()
	{
		return $this->_customUrl;
	}

	/**
	 * Sets a value indicating whether to enable custom constructUrl using this pattern
	 * @param bool $value whether to enable custom constructUrl.
	 */
	public function setEnableCustomUrl($value)
	{
		$this->_customUrl = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether this pattern is a wildcard pattern
	 * @since 3.1.4
	 */
	public function getIsWildCardPattern()
	{
		return $this->_isWildCardPattern;
	}

	/**
	 * @return THttpRequestUrlFormat the format of URLs. Defaults to THttpRequestUrlFormat::Get.
	 */
	public function getUrlFormat()
	{
		return $this->_urlFormat;
	}

	/**
	 * Sets the format of URLs constructed and interpreted by this pattern.
	 * A Get URL format is like index.php?name1=value1&name2=value2
	 * while a Path URL format is like index.php/name1/value1/name2/value.
	 * The separating character between name and value can be configured with
	 * {@link setUrlParamSeparator} and defaults to '/'.
	 * Changing the UrlFormat will affect {@link constructUrl} and how GET variables
	 * are parsed.
	 * @param THttpRequestUrlFormat $value the format of URLs.
	 * @since 3.1.4
	 */
	public function setUrlFormat($value)
	{
		$this->_urlFormat = TPropertyValue::ensureEnum($value, 'Prado\\Web\\THttpRequestUrlFormat');
	}

	/**
	 * @return string separator used to separate GET variable name and value when URL format is Path. Defaults to slash '/'.
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
	 * @return TUrlMappingPatternSecureConnection the SecureConnection behavior. Defaults to {@link TUrlMappingPatternSecureConnection::Automatic Automatic}
	 * @since 3.2
	 */
	public function getSecureConnection()
	{
		return $this->_secureConnection;
	}

	/**
	 * @param TUrlMappingPatternSecureConnection $value the SecureConnection behavior.
	 * @since 3.2
	 */
	public function setSecureConnection($value)
	{
		$this->_secureConnection = TPropertyValue::ensureEnum($value, 'Prado\\Web\\TUrlMappingPatternSecureConnection');
	}

	/**
	 * @param array $getItems list of GET items to be put in the constructed URL
	 * @return bool whether this pattern IS the one for constructing the URL with the specified GET items.
	 * @since 3.1.1
	 */
	public function supportCustomUrl($getItems)
	{
		if (!$this->_customUrl || $this->getPattern() === null) {
			return false;
		}
		if ($this->_parameters) {
			foreach ($this->_parameters as $key => $value) {
				if (!isset($getItems[$key])) {
					return false;
				}
			}
		}

		if ($this->_constants) {
			foreach ($this->_constants->toArray() as $key => $value) {
				if (!isset($getItems[$key])) {
					return false;
				}
				if ($getItems[$key] != $value) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Constructs a URL using this pattern.
	 * @param array $getItems list of GET variables
	 * @param bool $encodeAmpersand whether the ampersand should be encoded in the constructed URL
	 * @param bool $encodeGetItems whether the GET variables should be encoded in the constructed URL
	 * @return string the constructed URL
	 * @since 3.1.1
	 */
	public function constructUrl($getItems, $encodeAmpersand, $encodeGetItems)
	{
		if ($this->_constants) {
			foreach ($this->_constants->toArray() as $key => $value) {
				unset($getItems[$key]);
			}
		}

		$extra = [];
		$replace = [];
		// for the GET variables matching the pattern, put them in the URL path
		foreach ($getItems as $key => $value) {
			if (($this->_parameters && $this->_parameters->contains($key)) || ($key === '*' && $this->getIsWildCardPattern())) {
				$replace['{' . $key . '}'] = $encodeGetItems ? rawurlencode($value) : $value;
			} else {
				$extra[$key] = $value;
			}
		}

		$url = $this->_manager->getUrlPrefix() . '/' . ltrim(strtr($this->getPattern(), $replace), '/');

		// for the rest of the GET variables, put them in the query string
		if (count($extra) > 0) {
			if ($this->_urlFormat === THttpRequestUrlFormat::Path && $this->getIsWildCardPattern()) {
				foreach ($extra as $name => $value) {
					$url .= '/' . $name . $this->_separator . ($encodeGetItems ? rawurlencode($value) : $value);
				}
				return $url;
			}

			$url2 = '';
			$amp = $encodeAmpersand ? '&amp;' : '&';
			if ($encodeGetItems) {
				foreach ($extra as $name => $value) {
					if (is_array($value)) {
						$name = rawurlencode($name . '[]');
						foreach ($value as $v) {
							$url2 .= $amp . $name . '=' . rawurlencode($v);
						}
					} else {
						$url2 .= $amp . rawurlencode($name) . '=' . rawurlencode($value);
					}
				}
			} else {
				foreach ($extra as $name => $value) {
					if (is_array($value)) {
						foreach ($value as $v) {
							$url2 .= $amp . $name . '[]=' . $v;
						}
					} else {
						$url2 .= $amp . $name . '=' . $value;
					}
				}
			}
			$url = $url . '?' . substr($url2, strlen($amp));
		}
		return $this -> applySecureConnectionPrefix($url);
	}

	/**
	 * Apply behavior of {@link SecureConnection} property by conditionaly prefixing
	 * URL with {@link THttpRequest::getBaseUrl()}
	 *
	 * @param string $url
	 * @return string
	 * @since 3.2
	 */
	protected function applySecureConnectionPrefix($url)
	{
		static $request;
		if ($request === null) {
			$request = Prado::getApplication() -> getRequest();
		}

		static $isSecureConnection;
		if ($isSecureConnection === null) {
			$isSecureConnection = $request -> getIsSecureConnection();
		}

		switch ($this -> getSecureConnection()) {
			case TUrlMappingPatternSecureConnection::EnableIfNotSecure:
				if ($isSecureConnection) {
					return $url;
				}
				return $request -> getBaseUrl(true) . $url;
			break;
			case TUrlMappingPatternSecureConnection::DisableIfSecure:
				if (!$isSecureConnection) {
					return $url;
				}
				return $request -> getBaseUrl(false) . $url;
			break;
			case TUrlMappingPatternSecureConnection::Enable:
				return $request -> getBaseUrl(true) . $url;
			break;
			case TUrlMappingPatternSecureConnection::Disable:
				return $request -> getBaseUrl(false) . $url;
			break;
			case TUrlMappingPatternSecureConnection::Automatic:
			default:
				return $url;
			break;
		}
	}
}
