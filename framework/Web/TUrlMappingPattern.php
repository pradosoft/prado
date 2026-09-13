<?php

/**
 * TUrlMapping, TUrlMappingPattern and TUrlMappingPatternSecureConnection class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
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
 * parameters, the pattern will generate a valid URL. In both case, the PATH_INFO
 * part of a URL is parsed/constructed using the pattern, and, in the
 * {@see \Prado\Web\TUrlMappingPatternUrlMatchMode::Full Full} match mode, the query string as well.
 *
 * To specify the pattern, set the {@see setPattern Pattern} property.
 * {@see setPattern Pattern} takes a string expression with
 * parameter names enclosed between a left brace '{' and a right brace '}'.
 * The patterns for each parameter can be set using {@see getParameters Parameters}
 * attribute collection. For example
 * ```php
 * <url ... pattern="articles/{year}/{month}/{day}"
 *          parameters.year="\d{4}" parameters.month="\d{2}" parameters.day="\d+" />
 * ```
 *
 * In the above example, the pattern contains 3 parameters named "year",
 * "month" and "day". The pattern for these parameters are, respectively,
 * "\d{4}" (4 digits), "\d{2}" (2 digits) and "\d+" (1 or more digits).
 * Essentially, the <tt>Parameters</tt> attribute name and values are used
 * as substrings in replacing the placeholders in the <tt>Pattern</tt> string
 * to form a complete regular expression string.
 *
 * For more complicated patterns, one may specify the pattern using a regular expression
 * by {@see setRegularExpression RegularExpression}. For example, the above pattern
 * is equivalent to the following regular expression-based pattern:
 * ```
 * #^articles/(?P<year>\d{4})/(?P<month>\d{2})\/(?P<day>\d+)$#u
 * ```
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
 * The {@see setServiceParameter ServiceParameter} and {@see setServiceID ServiceID}
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
 * These rules, when matched by the actual request, will make the application see a "listtype" parameter present
 * (even through not supplied in the request) and equal to "detailed" or "summarized", depending on the friendly url matched.
 * The constants is practically a table-based validation and translation of specified, fixed-set parameter values.
 *
 * Since 4.4.0 the pattern can match the query string of the URL as well. Set
 * {@see setUrlMatchMode UrlMatchMode} to 'Full' and write the query string of the pattern
 * after a question mark. For example
 *
 * <url ServiceParameter="Posts.EditPost" pattern="post/{id}?mode=edit" parameters.id="\d+" UrlMatchMode="Full" />
 *
 * This rule matches <tt>http://example.com/index.php/post/123?mode=edit</tt> and no other
 * query string, since the whole query string must match the pattern. The query string of a
 * request is matched in the order the variables are written in the URL.
 *
 * Since 4.4.0 you can also constrain single GET variables with the {@see getQuery Query}
 * attribute collection, in the same way that {@see getParameters Parameters} constrains the
 * parameters of the pattern. The order of the GET variables in the URL does not matter, and
 * the variables stay out of the pattern. For example
 *
 * <url ServiceParameter="Posts.EditPost" pattern="post/{id}" parameters.id="\d+" query.mode="edit|preview" />
 *
 * This rule matches <tt>http://example.com/index.php/post/123?mode=edit</tt> and
 * <tt>http://example.com/index.php/post/123?ref=list&mode=preview</tt>. It does not match a
 * request without a "mode" GET variable, or one whose "mode" is not "edit" or "preview".
 * The whole value of the GET variable must match the constraint.
 *
 * The two work together. A pattern in the 'Full' match mode with {@see getQuery Query}
 * constraints matches a request only when both are satisfied. Since the pattern of a 'Full'
 * match already pins the whole query string, the constraints only narrow the match further.
 *
 * Since 4.3.3 you can also use HTTP verb matching. The Verbs property restricts a pattern to match only
 * specific HTTP methods (GET, POST, PUT, DELETE, etc.). Use a comma-separated list for multiple verbs.
 * Use the prefix '~' or '!' to negate a verb. For example:
 * - verbs="GET" - only matches GET requests
 * - verbs="POST,PUT" - matches POST or PUT requests
 * - verbs="!DELETE" or verbs="~DELETE" - matches any request except DELETE requests
 * - verbs="!PUT, ~DELETE" - matches any request except PUT and DELETE
 * - verbs="GET,!POST" - matches GET, the negation excludes POST from an inclusion list
 *
 * A list of negated verbs alone matches every method it does not exclude. A list holding
 * both forms matches a method that is included and not excluded. The comparison ignores case.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
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
	 * @var null|array list of allowed HTTP verbs for this pattern. Null means any verb is allowed.
	 * @since 4.3.3
	 */
	private $_verbs;

	/**
	 * @var TUrlMappingPatternUrlMatchMode the part of the URL matched by the pattern.
	 * @since 4.4.0
	 */
	private $_urlMatchMode = TUrlMappingPatternUrlMatchMode::PathInfo;

	/**
	 * @var ?TAttributeCollection query string parameter regular expressions.
	 * @since 4.4.0
	 */
	private $_query;

	/**
	 * Constructor.
	 * @param TUrlManager $manager the URL manager instance
	 */
	public function __construct(TUrlManager $manager)
	{
		$this->_manager = $manager;
		parent::__construct();
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
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module.
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
	 * Splits the {@see getPattern Pattern} into its path part and its query string part.
	 * The query string part is the text after the first question mark, and exists only
	 * in the {@see \Prado\Web\TUrlMappingPatternUrlMatchMode::Full Full} match mode.
	 * @return array the path part and the query string part, the latter null when absent
	 * @since 4.4.0
	 */
	protected function getPatternParts(): array
	{
		$pattern = (string) $this->getPattern();
		if ($this->_urlMatchMode !== TUrlMappingPatternUrlMatchMode::Full) {
			return [$pattern, null];
		}
		if (($pos = strpos($pattern, '?')) === false) {
			return [$pattern, null];
		}
		return [substr($pattern, 0, $pos), substr($pattern, $pos + 1)];
	}

	/**
	 * Substitutes the parameter key value pairs as named groupings in the given
	 * part of the matching pattern, and escapes the slashes of the regular expression.
	 * @param string $pattern part of the pattern to substitute
	 * @return string regular expression fragment with parameter substitution
	 * @since 4.4.0
	 */
	protected function substituteParameters(string $pattern): string
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
		return str_replace($params, $values, $pattern);
	}

	/**
	 * Substitute the parameter key value pairs as named groupings
	 * in the regular expression matching pattern.
	 * @return string regular expression pattern with parameter subsitution
	 */
	protected function getParameterizedPattern()
	{
		[$path, $query] = $this->getPatternParts();
		$regexp = $this->substituteParameters(trim($path, '/') . '/');
		if ($this->_urlFormat !== THttpRequestUrlFormat::Get) {
			// in the Full match mode the extra url parameters stop at the query string
			$regexp .= '(?P<urlparams>' . ($this->_urlMatchMode === TUrlMappingPatternUrlMatchMode::Full ? '[^?]*' : '.*') . ')';
		}
		if ($query !== null) {
			$regexp .= '\\?' . $this->substituteParameters($query);
		}
		$regexp = '/^' . $regexp . '$/u';

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
	 * @return bool whether the {@see getPattern Pattern} should be treated as case sensititve. Defaults to true.
	 */
	public function getCaseSensitive()
	{
		return $this->_caseSensitive;
	}

	/**
	 * @param bool $value whether the {@see getPattern Pattern} should be treated as case sensititve.
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
			$this->_parameters = new TAttributeCollection();
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
			$this->_constants = new TAttributeCollection();
			$this->_constants->setCaseSensitive(true);
		}
		return $this->_constants;
	}

	/**
	 * The query string constraints are regular expressions, keyed by GET variable name,
	 * that the request GET variables must match for the pattern to match.
	 * @return TAttributeCollection query string parameter key value pairs.
	 * @since 4.4.0
	 */
	public function getQuery()
	{
		if (!$this->_query) {
			$this->_query = new TAttributeCollection();
			$this->_query->setCaseSensitive(true);
		}
		return $this->_query;
	}

	/**
	 * @param TAttributeCollection $value new query string parameter key value pairs.
	 * @since 4.4.0
	 */
	public function setQuery($value)
	{
		$this->_query = $value;
	}

	/**
	 * Matches an HTTP method against the {@see getVerbs Verbs} of the pattern. A verb
	 * prefixed with '!' or '~' excludes that method. The comparison ignores case.
	 *
	 * | Verbs | Matches |
	 * |---|---|
	 * | null | every method |
	 * | only inclusions | a method in the list |
	 * | only exclusions | a method that is not excluded |
	 * | both | a method that is included and not excluded |
	 *
	 * @param string $verb the HTTP method of the request
	 * @return bool whether the pattern matches the HTTP method
	 * @since 4.4.0
	 */
	protected function matchesVerb(string $verb): bool
	{
		$verbs = $this->getVerbs();
		if ($verbs === null) {
			return true;
		}
		$included = [];
		foreach ($verbs as $item) {
			if (str_starts_with($item, '!') || str_starts_with($item, '~')) {
				if (strcasecmp(substr($item, 1), $verb) === 0) {
					return false;
				}
			} else {
				$included[] = $item;
			}
		}
		if (!$included) {
			return true;
		}
		foreach ($included as $item) {
			if (strcasecmp($item, $verb) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the part of the URL that the pattern matches against. In the
	 * {@see \Prado\Web\TUrlMappingPatternUrlMatchMode::Full Full} match mode the query
	 * string is appended to the path, separated by a question mark.
	 * @param THttpRequest $request the request module
	 * @param string $path the path info of the request, normalized for the matching in use
	 * @return string the URL part to match the pattern against
	 * @since 4.4.0
	 */
	protected function getMatchSubject($request, string $path): string
	{
		if ($this->_urlMatchMode !== TUrlMappingPatternUrlMatchMode::Full) {
			return $path;
		}
		$query = (string) $request->getQueryString();
		return ($query === '') ? $path : $path . '?' . $query;
	}

	/**
	 * Returns the GET variables that the {@see getQuery Query} constraints apply to.
	 * @return array the GET variables of the request
	 * @since 4.4.0
	 */
	protected function getQueryItems(): array
	{
		return $_GET;
	}

	/**
	 * Builds the regular expression of a {@see getQuery Query} constraint. The whole
	 * value of the GET variable must match the constraint.
	 * @param string $constraint the regular expression of the constraint
	 * @return string the anchored regular expression
	 * @since 4.4.0
	 */
	protected function getConstraintRegularExpression(string $constraint): string
	{
		$regexp = '/^(?:' . str_replace('/', '\\/', $constraint) . ')$/u';
		if (!$this->getCaseSensitive()) {
			$regexp .= 'i';
		}
		return $regexp;
	}

	/**
	 * Matches the given GET variables against the {@see getQuery Query} constraints.
	 * A missing variable, a variable that is not a scalar, and a variable whose value
	 * does not match its constraint each fail the match.
	 * @param array|\ArrayAccess $items the GET variables to match
	 * @return bool whether every query constraint is satisfied
	 * @since 4.4.0
	 */
	protected function matchesQuery($items): bool
	{
		foreach ($this->getQuery()->toArray() as $key => $value) {
			if (!isset($items[$key]) || !is_scalar($items[$key])) {
				return false;
			}
			if (!preg_match($this->getConstraintRegularExpression($value), (string) $items[$key])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Uses URL pattern (or full regular expression if available) to
	 * match the given url path.
	 * @param THttpRequest $request the request module
	 * @return array matched parameters, empty if no matches.
	 */
	public function getPatternMatches($request)
	{
		if (!$this->matchesVerb((string) $request->getRequestType())) {
			return [];
		}

		if ($this->_query && !$this->matchesQuery($this->getQueryItems())) {
			return [];
		}

		$matches = [];
		if (($pattern = $this->getRegularExpression()) !== '') {
			preg_match($pattern, $this->getMatchSubject($request, $request->getPathInfo()), $matches);
		} else {
			preg_match($this->getParameterizedPattern(), $this->getMatchSubject($request, trim($request->getPathInfo(), '/') . '/'), $matches);
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
	 * {@see setUrlParamSeparator} and defaults to '/'.
	 * Changing the UrlFormat will affect {@see constructUrl} and how GET variables
	 * are parsed.
	 * @param THttpRequestUrlFormat $value the format of URLs.
	 * @since 3.1.4
	 */
	public function setUrlFormat($value)
	{
		$this->_urlFormat = TPropertyValue::ensureEnum($value, THttpRequestUrlFormat::class);
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
	 * @return TUrlMappingPatternSecureConnection the SecureConnection behavior. Defaults to {@see \Prado\Web\TUrlMappingPatternSecureConnection::Automatic Automatic}
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
		$this->_secureConnection = TPropertyValue::ensureEnum($value, TUrlMappingPatternSecureConnection::class);
	}

	/**
	 * @return null|array list of allowed HTTP verbs for this pattern, null means any verb is allowed.
	 *                       Use prefixes '~' or '!' to negate a verb (e.g., '~GET' means NOT GET).
	 * @since 4.3.3
	 */
	public function getVerbs()
	{
		return $this->_verbs;
	}

	/**
	 * Sets the list of allowed HTTP verbs for this pattern.
	 * @param null|array|string $value list of verbs (e.g., 'GET,POST'), null for any verb.
	 *                          Use prefix '~' or '!' to negate (e.g., '~GET' or '!GET' excludes GET).
	 * @since 4.3.3
	 */
	public function setVerbs($value)
	{
		if (is_string($value)) {
			$value = explode(',', $value);
		}
		if (is_array($value)) {
			$value = array_filter(array_map('trim', $value));
		}
		if (empty($value)) {
			$value = null;
		}
		$this->_verbs = $value;
	}

	/**
	 * @return TUrlMappingPatternUrlMatchMode the part of the URL matched by the pattern.
	 *   Defaults to {@see \Prado\Web\TUrlMappingPatternUrlMatchMode::PathInfo PathInfo}.
	 * @since 4.4.0
	 */
	public function getUrlMatchMode()
	{
		return $this->_urlMatchMode;
	}

	/**
	 * Sets the part of the URL matched by the pattern. In the
	 * {@see \Prado\Web\TUrlMappingPatternUrlMatchMode::Full Full} mode the text of the
	 * pattern after the first question mark matches the query string of the request.
	 * @param TUrlMappingPatternUrlMatchMode $value the part of the URL matched by the pattern.
	 * @since 4.4.0
	 */
	public function setUrlMatchMode($value)
	{
		$this->_urlMatchMode = TPropertyValue::ensureEnum($value, TUrlMappingPatternUrlMatchMode::class);
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
		if ($this->_query && !$this->matchesQuery($getItems)) {
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
			$url = $url . (strpos($url, '?') === false ? '?' : $amp) . substr($url2, strlen($amp));
		}
		return $this -> applySecureConnectionPrefix($url);
	}

	/**
	 * Apply behavior of {@see SecureConnection} property by conditionaly prefixing
	 * URL with {@see \Prado\Web\THttpRequest::getBaseUrl()}
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
