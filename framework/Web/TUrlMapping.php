<?php
/**
 * TUrlMapping and TUrlMappingPattern class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web
 */

Prado::using('System.Web.TUrlManager');
Prado::using('System.Collections.TAttributeCollection');

/**
 * TUrlMapping Class
 *
 * The TUrlMapping module allows aributary URL path to be mapped to a
 * particular service and page class. This module must be configured
 * before a service is initialized, thus this module should be configured
 * globally in the <tt>application.xml</tt> file and before any services.
 * <code>
 *  <module id="request" class="THttpRequest" UrlManager="friendly-url" />
 *  <module id="friendly-url" class="System.Web.TUrlMapping">
 *    <url ServiceParameter="Posts.ViewPost" pattern="post/{id}/?" parameters.id="\d+" />
 *    <url ServiceParameter="Posts.ListPost" pattern="archive/{time}/?" parameters.time="\d{6}" />
 *    <url ServiceParameter="Posts.ListPost" pattern="category/{cat}/?" parameters.cat="\d+" />
 *  </module>
 * </code>
 *
 * See {@link TUrlMappingPattern} for details regarding the mapping patterns.
 * Similar to other modules, the <tt>&lt;url /&gt;</tt> configuration class
 * can be customised using the <tt>class</tt> property.
 *
 * The URL mapping are evaluated in order, only the first mapping that matches
 * the URL will be used. Cascaded mapping can be achieved by placing the URL mappings
 * in particular order. For example, placing the most specific mappings first.
 *
 * The mapping can be loaded from an external file by specifying a configuration
 * file using the {@link setConfigFile ConfigFile} property.
 *
 * Since TUrlMapping is a URL manager extending from {@link TUrlManager},
 * you may override {@link TUrlManager::constructUrl} to support your pattern-based
 * URL scheme.
 *
 * From PRADO v3.1.1, TUrlMapping also provides support to construct URLs according to
 * the specified the pattern. You may enable this functionality by setting {@link setEnableCustomUrl EnableCustomUrl} to true.
 * When you call THttpRequest::constructUrl() (or via TPageService::constructUrl()),
 * TUrlMapping will examine the available URL mapping patterns using their {@link getServiceParameter ServiceParameter}
 * and {@link getPattern Pattern} properties. A pattern is applied if its
 * {@link getServiceParameter ServiceParameter} matches the service parameter passed
 * to constructUrl() and every parameter in the {@link getPattern Pattern} is found
 * in the GET variables.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web
 * @since 3.0.5
 */
class TUrlMapping extends TUrlManager
{
	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.xml';
	/**
	 * @var TUrlMappingPattern[] list of patterns.
	 */
	private $_patterns=array();
	/**
	 * @var TUrlMappingPattern matched pattern.
	 */
	private $_matched;
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;
	/**
	 * @var boolean whether to enable custom contructUrl
	 */
	private $_customUrl=false;
	/**
	 * @var array rules for constructing URLs
	 */
	private $_constructRules=array();

	private $_urlPrefix='';

	private $_defaultMappingClass='TUrlMappingPattern';

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if module is configured in the global scope.
	 */
	public function init($xml)
	{
		parent::init($xml);
		if($this->getRequest()->getRequestResolved())
			throw new TConfigurationException('urlpath_dispatch_module_must_be_global');
		if($this->_configFile!==null)
			$this->loadConfigFile();
		$this->loadUrlMappings($xml);
		if($this->_urlPrefix==='')
			$this->_urlPrefix=$this->getRequest()->getApplicationUrl();
		$this->_urlPrefix=rtrim($this->_urlPrefix,'/');
	}

	/**
	 * Initialize the module from configuration file.
	 * @throws TConfigurationException if {@link getConfigFile ConfigFile} is invalid.
	 */
	protected function loadConfigFile()
	{
		if(is_file($this->_configFile))
 		{
			$dom=new TXmlDocument;
			$dom->loadFromFile($this->_configFile);
			$this->loadUrlMappings($dom);
		}
		else
			throw new TConfigurationException(
				'urlpath_dispatch_configfile_invalid',$this->_configFile);
	}

	/**
	 * Returns a value indicating whether to enable custom constructUrl.
	 * If true, constructUrl() will make use of the URL mapping rules to
	 * construct valid URLs.
	 * @return boolean whether to enable custom constructUrl. Defaults to false.
	 */
	public function getEnableCustomUrl()
	{
		return $this->_customUrl;
	}

	/**
	 * Sets a value indicating whether to enable custom constructUrl.
	 * If true, constructUrl() will make use of the URL mapping rules to
	 * construct valid URLs.
	 * @param boolean whether to enable custom constructUrl.
	 */
	public function setEnableCustomUrl($value)
	{
		$this->_customUrl=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the part that will be prefixed to the constructed URLs. Defaults to the requested script path (e.g. /path/to/index.php for a URL http://hostname/path/to/index.php)
	 */
	public function getUrlPrefix()
	{
		return $this->_urlPrefix;
	}

	/**
	 * @param string the part that will be prefixed to the constructed URLs. This is used by constructUrl() when EnableCustomUrl is set true.
	 * @see getUrlPrefix
	 */
	public function setUrlPrefix($value)
	{
		$this->_urlPrefix=$value;
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TInvalidDataValueException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if(($this->_configFile=Prado::getPathOfNamespace($value,self::CONFIG_FILE_EXT))===null)
			throw new TConfigurationException('urlpath_configfile_invalid',$value);
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
	 * @param string the default class of URL mapping patterns.
	 * @since 3.1.1
	 */
	public function setDefaultMappingClass($value)
	{
		$this->_defaultMappingClass=$value;
	}

	/**
	 * Load and configure each url mapping pattern.
	 * @param TXmlElement configuration node
	 * @throws TConfigurationException if specific pattern class is invalid
	 */
	protected function loadUrlMappings($xml)
	{
		foreach($xml->getElementsByTagName('url') as $url)
		{
			$properties=$url->getAttributes();
			if(($class=$properties->remove('class'))===null)
				$class=$this->getDefaultMappingClass();
			$pattern = Prado::createComponent($class,$this);
			if(!($pattern instanceof TUrlMappingPattern))
				throw new TConfigurationException('urlmapping_urlmappingpattern_required');
			foreach($properties as $name=>$value)
				$pattern->setSubproperty($name,$value);
			$this->_patterns[] = $pattern;
			$pattern->init($url);

			$key=$pattern->getServiceID().':'.$pattern->getServiceParameter();
			$this->_constructRules[$key][]=$pattern;
		}
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
		$url = $this->getRequest()->getUrl();
		foreach($this->_patterns as $pattern)
		{
			$matches = $pattern->getPatternMatches($url);
			if(count($matches) > 0)
			{
				$this->_matched=$pattern;
				$params=array();
				foreach($matches as $key=>$value)
					if(is_string($key))
						$params[$key]=$value;
				$params[$pattern->getServiceID()]=$pattern->getServiceParameter();
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
	 * @param string service ID
	 * @param string service parameter
	 * @param array GET parameters, null if not provided
	 * @param boolean whether to encode the ampersand in URL
	 * @param boolean whether to encode the GET parameters (their names and values)
	 * @return string URL
	 * @see parseUrl
	 * @since 3.1.1
	 */
	public function constructUrl($serviceID,$serviceParam,$getItems,$encodeAmpersand,$encodeGetItems)
	{
		if(!$this->_customUrl)
			return parent::constructUrl($serviceID,$serviceParam,$getItems,$encodeAmpersand,$encodeGetItems);
 		if(!(is_array($getItems) || ($getItems instanceof Traversable)))
 			$getItems=array();
		$key=$serviceID.':'.$serviceParam;
		if(isset($this->_constructRules[$key]))
		{
			foreach($this->_constructRules[$key] as $rule)
			{
				if($rule->supportCustomUrl($getItems))
					return $rule->constructUrl($getItems,$encodeAmpersand,$encodeGetItems);
			}
		}
		return parent::constructUrl($serviceID,$serviceParam,$getItems,$encodeAmpersand,$encodeGetItems);
	}

	/**
	 * @return TUrlMappingPattern the matched pattern, null if not found.
	 */
	public function getMatchingPattern()
	{
		return $this->_matched;
	}
}

/**
 * URL Mapping Pattern Class
 *
 * Describes an URL mapping pattern, if a given URL matches the pattern, the
 * TUrlMapping class will alter the THttpRequest parameters. The
 * url matching is done using patterns and regular expressions.
 *
 * The {@link setPattern Pattern} property takes an string expression with
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
 * to form a complete regular expression string. A full regular expression
 * may be expressed using the <tt>RegularExpression</tt> attribute or
 * as the body content of the &lt;module&gt; tag. The above pattern is equivalent
 * to the following regular expression pattern.
 * <code>
 * /articles\/(?P<year>\d{4})\/(?P<month>\d{2})\/(?P<day>\d+)/u
 * </code>
 * The above regular expression used the "named group" feature available in PHP.
 * Notice that you need to escape the slash in regular expressions.
 *
 * In the TUrlMappingPattern class, the pattern is matched against the
 * <b>path</b> property of the url only.
 *
 * Thus, only an url that matches the pattern will be valid. For example,
 * an url "<tt>http://example.com/articles/2006/07/21</tt>" will matches and is valid.
 * However, "<tt>http://example.com/articles/2006/07/hello</tt>" is not
 * valid since the "day" parameter pattern is not satisfied.
 *
 * The parameter values are available through the standard <tt>Request</tt>
 * object. For example, <tt>$this->Request['year']</tt>.
 *
 * The {@link setServiceParameter ServiceParameter} and {@link setServiceID ServiceID}
 * (the default ID is 'page') set the service parameter and service id respectively.
 * The service parameter for the TPageService is the Page class name, other service
 * may use the service parameter differently.
 *
 * For more complicated mappings, the body of the <tt>&lt;url&gt;</tt>
 * can be used to specify the mapping pattern.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web
 * @since 3.0.5
 */
class TUrlMappingPattern extends TComponent
{
	/**
	 * @var string service parameter such as Page class name.
	 */
	private $_serviceParameter;
	/**
	 * @var string service ID, default is 'page'.
	 */
	private $_serviceID='page';
	/**
	 * @var string url pattern to match.
	 */
	private $_pattern='';
	/**
	 * @var TMap parameter regular expressions.
	 */
	private $_parameters;
	/**
	 * @var string regular expression pattern.
	 */
	private $_regexp='';
	/**
	 * @var boolean case sensitive matching, default is true
	 */
	private $_caseSensitive=true;

	private $_customUrl=true;

	private $_manager;

	public function __construct(TUrlManager $manager)
	{
		$this->_manager=$manager;
		$this->_parameters=new TAttributeCollection;
		$this->_parameters->setCaseSensitive(true);
	}

	public function getManager()
	{
		return $this->_manager;
	}

	/**
	 * Initialize the pattern, uses the body content as pattern is available.
	 * @param TXmlElement configuration for this module.
	 * @throws TConfigurationException if page class is not specified.
	 */
	public function init($config)
	{
		if(($body=trim($config->getValue()))!=='')
			$this->setRegularExpression($body);
		if($this->_serviceParameter===null)
			throw new TConfigurationException('urlmappingpattern_serviceparameter_required', $this->getPattern());
	}

	/**
	 * Substitute the parameter key value pairs as named groupings
	 * in the regular expression matching pattern.
	 * @return string regular expression pattern with parameter subsitution
	 */
	protected function getParameterizedPattern()
	{
		$params= array();
		$values = array();
		foreach($this->parameters as $key => $value)
		{
			$params[] = '{'.$key.'}';
			$values[] = '(?P<'.$key.'>'.$value.')';
		}
		$params[] = '/';
		$values[] = '\\/';
		$regexp = str_replace($params,$values,$this->getPattern());
		$modifiers = $this->getModifiers();
		return '/'.$regexp.'/'.$modifiers;
	}

	/**
	 * @return string full regular expression mapping pattern
	 */
	public function getRegularExpression()
	{
		return $this->_regexp;
	}

	/**
	 * @param string full regular expression mapping pattern.
	 */
	public function setRegularExpression($value)
	{
		$this->_regexp=$value;
	}

	/**
	 * @param string service parameter, such as page class name.
	 */
	public function setServiceParameter($value)
	{
		$this->_serviceParameter=$value;
	}

	/**
	 * @return string service parameter, such as page class name.
	 */
	public function getServiceParameter()
	{
		return $this->_serviceParameter;
	}

	/**
	 * @param string service id to handle.
	 */
	public function setServiceID($value)
	{
		$this->_serviceID=$value;
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
	 * @param string url pattern to match.
	 */
	public function setPattern($value)
	{
		$this->_pattern = $value;
	}

	/**
	 * @param boolean case sensitive pattern matching, default is true.
	 */
	public function setCaseSensitive($value)
	{
		$this->_caseSensitive=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean case sensitive pattern matching, default is true.
	 */
	public function getCaseSensitive()
	{
		return $this->_caseSensitive;
	}

	/**
	 * @return TAttributeCollection parameter key value pairs.
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * @param TAttributeCollection new parameter key value pairs.
	 */
	public function setParameters($value)
	{
		$this->_parameters=$value;
	}

	/**
	 * Uses URL pattern (or full regular expression if available) to
	 * match the given url path.
	 * @param TUri url to match against
	 * @return array matched parameters, empty if no matches.
	 */
	public function getPatternMatches($url)
	{
		$matches=array();
		if(($pattern=$this->getRegularExpression())==='')
			$pattern=$this->getParameterizedPattern();
		preg_match($pattern,$url->getPath(),$matches);
		return $matches;
	}

	/**
	 * @return string regular expression matching modifiers.
	 */
	protected function getModifiers()
	{
		$modifiers = 'u';
		if(!$this->getCaseSensitive())
			$modifiers .= 'i';
		return $modifiers;
	}

	/**
	 * Returns a value indicating whether to use this pattern to construct URL.
	 * @return boolean whether to enable custom constructUrl. Defaults to true.
	 * @since 3.1.1
	 */
	public function getEnableCustomUrl()
	{
		return $this->_customUrl;
	}

	/**
	 * Sets a value indicating whether to enable custom constructUrl using this pattern
	 * @param boolean whether to enable custom constructUrl.
	 */
	public function setEnableCustomUrl($value)
	{
		$this->_customUrl=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @param array list of GET items to be put in the constructed URL
	 * @return boolean whether this pattern IS the one for constructing the URL with the specified GET items.
	 * @since 3.1.1
	 */
	public function supportCustomUrl($getItems)
	{
		if(!$this->_customUrl || $this->getPattern()==='')
			return false;
		foreach($this->_parameters as $key=>$value)
			if(!isset($getItems[$key]))
				return false;

		return true;
	}

	/**
	 * Constructs a URL using this pattern.
	 * @param array list of GET variables
	 * @param boolean whether the ampersand should be encoded in the constructed URL
	 * @param boolean whether the GET variables should be encoded in the constructed URL
	 * @return string the constructed URL
	 * @since 3.1.1
	 */
	public function constructUrl($getItems,$encodeAmpersand,$encodeGetItems)
	{
		$extra=array();
		$replace=array();
		// for the GET variables matching the pattern, put them in the URL path
		foreach($getItems as $key=>$value)
		{
			if($encodeGetItems)
				$value=urlencode($value);
			if($this->_parameters->contains($key))
				$replace['{'.$key.'}']=$value;
			else
				$extra[$key]=$value;
		}

		$url=$this->_manager->getUrlPrefix().'/'.trim(strtr($this->getPattern(),$replace),'/');

		// for the rest of the GET variables, put them in the query string
		if(count($extra)>0)
		{
			$url2='';
			$amp=$encodeAmpersand?'&amp;':'&';
			if($encodeGetItems)
			{
				foreach($extra as $name=>$value)
				{
					if(is_array($value))
					{
						$name=urlencode($name.'[]');
						foreach($value as $v)
							$url2.=$amp.$name.'='.urlencode($v);
					}
					else
						$url2.=$amp.urlencode($name).'='.urlencode($value);
				}
			}
			else
			{
				foreach($extra as $name=>$value)
				{
					if(is_array($value))
					{
						foreach($value as $v)
							$url2.=$amp.$name.'[]='.$v;
					}
					else
						$url2.=$amp.$name.'='.$value;
				}
			}
			$url=$url.'?'.substr($url2,strlen($amp));
		}
		return $url;
	}
}

?>