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

/**
 * TUrlMapping Class
 *
 * The TUrlMapping module allows aributary URL path to be mapped to a
 * particular service and page class. This module must be configured
 * before a service is initialized, thus this module should be configured
 * globally in the <tt>application.xml</tt> file and before any services.
 *
 * The mapping format is as follows.
 * <code>
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
 * The mapping can be load from an external file by specifying a configuration
 * file using the {@link setConfigFile ConfigFile} property.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web
 * @since 3.0.5
 */
class TUrlMapping extends TModule
{
	/**
	 * @var string default pattern class.
	 */
	private $_defaultPatternClass='TUrlMappingPattern';
	/**
	 * @var TUrlMappingPattern[] list of patterns.
	 */
	private $_patterns=array();
	/**
	 * @var TUrlMappingPattern matched pattern.
	 */
	private $_matched;
	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.xml';
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if module is configured in the global scope.
	 */
	public function init($xml)
	{
		if($this->getRequest()->getRequestResolved())
			throw new TConfigurationException('urlpath_dispatch_module_must_be_global');
		if($this->_configFile!==null)
			$this->loadConfigFile();
		$this->loadUrlMappings($xml);
		$this->resolveMappings();
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
			throw new TConfigurationException('logrouter_configfile_invalid',$value);
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
			$class=$properties->remove('class');
			if($class===null)
				$class = $this->_defaultPatternClass;
			$pattern = Prado::createComponent($class);
			if(!($pattern instanceof TUrlMappingPattern))
				throw new TConfigurationException('urlpath_dispatch_invalid_pattern_class');
			foreach($properties as $name=>$value)
				$pattern->setSubproperty($name,$value);
			$this->_patterns[] = $pattern;
			$pattern->init($url);
		}
	}

	/**
	 * Using the request URL path, find the first matching pattern. If found
	 * the matched pattern parameters are used in the Request object.
	 */
	protected function resolveMappings()
	{
		$url = $this->getRequest()->getUrl();
		foreach($this->_patterns as $pattern)
		{
			$matches = $pattern->getPatternMatches($url);
			if(count($matches) > 0)
			{
				$this->changeServiceParameters($pattern);
				$this->initializeRequestParameters($matches);
				$this->_matched=$pattern;
				break;
			}
		}
	}

	/**
	 * @return TUrlMappingPattern the matched pattern, null if not found.
	 */
	public function getMatchingPattern()
	{
		return $this->_matched;
	}

	/**
	 * @param array initialize the Request with matched parameters.
	 */
	protected function initializeRequestParameters($matches)
	{
		$request = $this->getRequest();
		foreach($matches as $k => $v)
		{
			if(!is_int($k))
				$request->add($k,$v);
		}
	}

	/**
	 * @param TUrlMappingPattern change the Request service ID and page class.
	 */
	protected function changeServiceParameters($pattern)
	{
		$request = $this->getRequest();
		$id = $pattern->getServiceID();
		$param = $pattern->getServiceParameter();
		$request->setServiceID($id);
		$request->setServiceParameter($param);
		$request->add($id,$param);
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
 * The pattens for each parameter can be set using {@link getParameters Parameters}
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
	private $_pattern;
	/**
	 * @var TMap parameter regular expressions.
	 */
	private $_parameters;
	/**
	 * @var string regular expression pattern.
	 */
	private $_regexp;
	/**
	 * @var boolean case sensitive matching, default is true
	 */
	private $_caseSensitive=true;

	public function __construct()
	{
		$this->_parameters = Prado::createComponent('System.Collections.TAttributeCollection');
	}

	/**
	 * Initialize the pattern, uses the body content as pattern is available.
	 * @param TXmlElement configuration for this module.
	 * @throws TConfigurationException if page class is not specified.
	 */
	public function init($config)
	{
		$body = trim($config->getValue());
		if(strlen($body)>0)
			$this->setRegularExpression($body);
		if(is_null($this->_serviceParameter))
		{
			throw new TConfigurationException(
				'dispatcher_url_service_parameter_missing', $this->getPattern());
		}
	}

	/**
	 * Subsitutue the parameter key value pairs as named groupings
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
	 * @param string full regular expression mapping patern.
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
	 * @return string url pattern to match.
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
		$path = $url->getPath();
		$matches=array();
		$pattern = $this->getRegularExpression();
		if($pattern === null)
			$pattern = $this->getParameterizedPattern();
		preg_match($pattern, $path, $matches);
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
}

?>