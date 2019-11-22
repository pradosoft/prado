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

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TUrlMapping Class
 *
 * The TUrlMapping module allows PRADO to construct and recognize URLs
 * based on specific patterns.
 *
 * TUrlMapping consists of a list of URL patterns which are used to match
 * against the currently requested URL. The first matching pattern will then
 * be used to decompose the URL into request parameters (accessible through
 * <code>$this->Request['paramname']</code>).
 *
 * The patterns can also be used to construct customized URLs. In this case,
 * the parameters in an applied pattern will be replaced with the corresponding
 * GET variable values.
 *
 * Since it is derived from {@link TUrlManager}, it should be configured globally
 * in the application configuration like the following,
 * <code>
 *  <module id="request" class="THttpRequest" UrlManager="friendly-url" />
 *  <module id="friendly-url" class="Prado\Web.TUrlMapping" EnableCustomUrl="true">
 *    <url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+" />
 *    <url ServiceParameter="Posts.ListPost" pattern="archive/{time}/" parameters.time="\d{6}" />
 *    <url ServiceParameter="Posts.ListPost" pattern="category/{cat}/" parameters.cat="\d+" />
 *  </module>
 * </code>
 *
 * In the above, each <tt>&lt;url&gt;</tt> element specifies a URL pattern represented
 * as a {@link TUrlMappingPattern} internally. You may create your own pattern classes
 * by extending {@link TUrlMappingPattern} and specifying the <tt>&lt;class&gt;</tt> attribute
 * in the element.
 *
 * The patterns can be also be specified in an external file using the {@link setConfigFile ConfigFile} property.
 *
 * The URL mapping are evaluated in order, only the first mapping that matches
 * the URL will be used. Cascaded mapping can be achieved by placing the URL mappings
 * in particular order. For example, placing the most specific mappings first.
 *
 * Only the PATH_INFO part of the URL is used to match the available patterns. The matching
 * is strict in the sense that the whole pattern must match the whole PATH_INFO of the URL.
 *
 * From PRADO v3.1.1, TUrlMapping also provides support for constructing URLs according to
 * the specified pattern. You may enable this functionality by setting {@link setEnableCustomUrl EnableCustomUrl} to true.
 * When you call THttpRequest::constructUrl() (or via TPageService::constructUrl()),
 * TUrlMapping will examine the available URL mapping patterns using their {@link TUrlMappingPattern::getServiceParameter ServiceParameter}
 * and {@link TUrlMappingPattern::getPattern Pattern} properties. A pattern is applied if its
 * {@link TUrlMappingPattern::getServiceParameter ServiceParameter} matches the service parameter passed
 * to constructUrl() and every parameter in the {@link getPattern Pattern} is found
 * in the GET variables.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web
 * @since 3.0.5
 */
class TUrlMapping extends TUrlManager
{
	/**
	 * @var TUrlMappingPattern[] list of patterns.
	 */
	protected $_patterns = [];
	/**
	 * @var TUrlMappingPattern matched pattern.
	 */
	private $_matched;
	/**
	 * @var string external configuration file
	 */
	private $_configFile;
	/**
	 * @var bool whether to enable custom contructUrl
	 */
	private $_customUrl = false;
	/**
	 * @var array rules for constructing URLs
	 */
	protected $_constructRules = [];

	private $_urlPrefix = '';

	private $_defaultMappingClass = '\Prado\Web\TUrlMappingPattern';

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param mixed $config configuration for this module, can be null
	 * @throws TConfigurationException if module is configured in the global scope.
	 */
	public function init($config)
	{
		parent::init($config);
		if ($this->getRequest()->getRequestResolved()) {
			throw new TConfigurationException('urlmapping_global_required');
		}
		if ($this->_configFile !== null) {
			$this->loadConfigFile();
		}
		$this->loadUrlMappings($config);
		if ($this->_urlPrefix === '') {
			$request = $this->getRequest();
			if ($request->getUrlFormat() === THttpRequestUrlFormat::HiddenPath) {
				$this->_urlPrefix = dirname($request->getApplicationUrl());
			} else {
				$this->_urlPrefix = $request->getApplicationUrl();
			}
		}
		$this->_urlPrefix = rtrim($this->_urlPrefix, '/');
	}

	/**
	 * Initialize the module from configuration file.
	 * @throws TConfigurationException if {@link getConfigFile ConfigFile} is invalid.
	 */
	protected function loadConfigFile()
	{
		if (is_file($this->_configFile)) {
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				$config = include $this->_configFile;
				$this->loadUrlMappings($dom);
			} else {
				$dom = new TXmlDocument;
				$dom->loadFromFile($this->_configFile);
				$this->loadUrlMappings($dom);
			}
		} else {
			throw new TConfigurationException('urlmapping_configfile_inexistent', $this->_configFile);
		}
	}

	/**
	 * Returns a value indicating whether to enable custom constructUrl.
	 * If true, constructUrl() will make use of the URL mapping rules to
	 * construct valid URLs.
	 * @return bool whether to enable custom constructUrl. Defaults to false.
	 * @since 3.1.1
	 */
	public function getEnableCustomUrl()
	{
		return $this->_customUrl;
	}

	/**
	 * Sets a value indicating whether to enable custom constructUrl.
	 * If true, constructUrl() will make use of the URL mapping rules to
	 * construct valid URLs.
	 * @param bool $value whether to enable custom constructUrl.
	 * @since 3.1.1
	 */
	public function setEnableCustomUrl($value)
	{
		$this->_customUrl = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the part that will be prefixed to the constructed URLs. Defaults to the requested script path (e.g. /path/to/index.php for a URL http://hostname/path/to/index.php)
	 * @since 3.1.1
	 */
	public function getUrlPrefix()
	{
		return $this->_urlPrefix;
	}

	/**
	 * @param string $value the part that will be prefixed to the constructed URLs. This is used by constructUrl() when EnableCustomUrl is set true.
	 * @see getUrlPrefix
	 * @since 3.1.1
	 */
	public function setUrlPrefix($value)
	{
		$this->_urlPrefix = $value;
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string $value external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TInvalidDataValueException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if (($this->_configFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt())) === null) {
			throw new TConfigurationException('urlmapping_configfile_invalid', $value);
		}
	}

	/**
	 * @return string the default class of URL mapping patterns. Defaults to TUrlMappingPattern.
	 * @since 3.1.1
	 */
	public function getDefaultMappingClass()
	{
		return $this->_defaultMappingClass;
	}

	/**
	 * Sets the default class of URL mapping patterns.
	 * When a URL matching pattern does not specify "class" attribute, it will default to the class
	 * specified by this property. You may use either a class name or a namespace format of class (if the class needs to be included first.)
	 * @param string $value the default class of URL mapping patterns.
	 * @since 3.1.1
	 */
	public function setDefaultMappingClass($value)
	{
		$this->_defaultMappingClass = $value;
	}

	/**
	 * Load and configure each url mapping pattern.
	 * @param mixed $config configuration node
	 * @throws TConfigurationException if specific pattern class is invalid
	 */
	protected function loadUrlMappings($config)
	{
		$defaultClass = $this->getDefaultMappingClass();

		if (is_array($config)) {
			if (isset($config['urls']) && is_array($config['urls'])) {
				foreach ($config['urls'] as $url) {
					$class = $url['class'] ?? $defaultClass;
					$properties = $url['properties'] ?? [];
					$this->buildUrlMapping($class, $properties, $url);
				}
			}
		} else {
			foreach ($config->getElementsByTagName('url') as $url) {
				$properties = $url->getAttributes();
				if (($class = $properties->remove('class')) === null) {
					$class = $defaultClass;
				}
				$this->buildUrlMapping($class, $properties, $url);
			}
		}
	}

	private function buildUrlMapping($class, $properties, $url)
	{
		$pattern = Prado::createComponent($class, $this);
		if (!($pattern instanceof TUrlMappingPattern)) {
			throw new TConfigurationException('urlmapping_urlmappingpattern_required');
		}
		foreach ($properties as $name => $value) {
			$pattern->setSubproperty($name, $value);
		}

		if ($url instanceof TXmlElement) {
			$text = $url -> getValue();
			if ($text) {
				$text = preg_replace('/(\s+)/S', '', $text);
				if (($regExp = $pattern->getRegularExpression()) !== '') {
					trigger_error(
						sPrintF(
						'%s.RegularExpression property value "%s" for ServiceID="%s" and ServiceParameter="%s" was replaced by node value "%s"',
						get_class($pattern),
						$regExp,
						$pattern->getServiceID(),
						$pattern->getServiceParameter(),
						$text
				),
						E_USER_NOTICE
				);
				}
				$pattern->setRegularExpression($text);
			}
		}

		$this->_patterns[] = $pattern;
		$pattern->init($url);

		$key = $pattern->getServiceID() . ':' . $pattern->getServiceParameter();
		$this->_constructRules[$key][] = $pattern;
	}

	/**
	 * Parses the request URL and returns an array of input parameters.
	 * This method overrides the parent implementation.
	 * The input parameters do not include GET and POST variables.
	 * This method uses the request URL path to find the first matching pattern. If found
	 * the matched pattern parameters are used to return as the input parameters.
	 * @return array list of input parameters
	 */
	public function parseUrl()
	{
		$request = $this->getRequest();
		foreach ($this->_patterns as $pattern) {
			$matches = $pattern->getPatternMatches($request);
			if (count($matches) > 0) {
				$this->_matched = $pattern;
				$params = [];
				foreach ($matches as $key => $value) {
					if (is_string($key)) {
						$params[$key] = $value;
					}
				}
				if (!$pattern->getIsWildCardPattern()) {
					$params[$pattern->getServiceID()] = $pattern->getServiceParameter();
				}
				return $params;
			}
		}
		return parent::parseUrl();
	}

	/**
	 * Constructs a URL that can be recognized by PRADO.
	 *
	 * This method provides the actual implementation used by {@link THttpRequest::constructUrl}.
	 * Override this method if you want to provide your own way of URL formatting.
	 * If you do so, you may also need to override {@link parseUrl} so that the URL can be properly parsed.
	 *
	 * The URL is constructed as the following format:
	 * /entryscript.php?serviceID=serviceParameter&get1=value1&...
	 * If {@link THttpRequest::setUrlFormat THttpRequest.UrlFormat} is 'Path',
	 * the following format is used instead:
	 * /entryscript.php/serviceID/serviceParameter/get1,value1/get2,value2...
	 * If {@link THttpRequest::setUrlFormat THttpRequest.UrlFormat} is 'HiddenPath',
	 * the following format is used instead:
	 * /serviceID/serviceParameter/get1,value1/get2,value2...
	 * @param string $serviceID service ID
	 * @param string $serviceParam service parameter
	 * @param array $getItems GET parameters, null if not provided
	 * @param bool $encodeAmpersand whether to encode the ampersand in URL
	 * @param bool $encodeGetItems whether to encode the GET parameters (their names and values)
	 * @return string URL
	 * @see parseUrl
	 * @since 3.1.1
	 */
	public function constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems)
	{
		if ($this->_customUrl) {
			if (!(is_array($getItems) || ($getItems instanceof \Traversable))) {
				$getItems = [];
			}
			$key = $serviceID . ':' . $serviceParam;
			$wildCardKey = ($pos = strrpos($serviceParam, '.')) !== false ?
				$serviceID . ':' . substr($serviceParam, 0, $pos) . '.*' : $serviceID . ':*';
			if (isset($this->_constructRules[$key])) {
				foreach ($this->_constructRules[$key] as $rule) {
					if ($rule->supportCustomUrl($getItems)) {
						return $rule->constructUrl($getItems, $encodeAmpersand, $encodeGetItems);
					}
				}
			} elseif (isset($this->_constructRules[$wildCardKey])) {
				foreach ($this->_constructRules[$wildCardKey] as $rule) {
					if ($rule->supportCustomUrl($getItems)) {
						$getItems['*'] = $pos ? substr($serviceParam, $pos + 1) : $serviceParam;
						return $rule->constructUrl($getItems, $encodeAmpersand, $encodeGetItems);
					}
				}
			}
		}
		return parent::constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems);
	}

	/**
	 * @return TUrlMappingPattern the matched pattern, null if not found.
	 */
	public function getMatchingPattern()
	{
		return $this->_matched;
	}
}
