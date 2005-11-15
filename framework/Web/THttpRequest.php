<?php
/**
 * THttpRequest class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web
 */

/**
 * THttpRequest class
 *
 * THttpRequest provides storage and access scheme for user request sent via HTTP.
 * It also encapsulates a uniform way to parse and construct URLs.
 *
 * THttpRequest is the default "request" module for prado application.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpRequest extends TComponent implements IModule
{
	/**
	 * GET variable name to store service information
	 */
	const SERVICE_VAR='sp';
	/**
	 * @var boolean whether the module is initialized
	 */
	private $_initialized=false;
	/**
	 * @var string module ID
	 */
	private $_id;
	/**
	 * @var string requested service ID
	 */
	private $_serviceID=null;
	/**
	 * @var string requested service parameter
	 */
	private $_serviceParam=null;
	/**
	 * @var THttpCookieCollection cookies sent from user
	 */
	private $_cookies=null;
	/**
	 * @var string requested URI (URL w/o host info)
	 */
	private $_requestUri;
	/**
	 * @var string path info of URL
	 */
	private $_pathInfo;
	/**
	 * @var TMap list of input variables (including GET and POST)
	 */
	private $_items;

	/**
	 * Constructor.
	 * Analyzes and resolves user request.
	 */
	public function __construct()
	{
		// Info about server variables:
		// PHP_SELF contains real URI (w/ path info, w/o query string)
		// SCRIPT_NAME is the real URI for the requested script (w/o path info and query string)
		// REQUEST_URI contains the URI part entered in the browser address bar
		// SCRIPT_FILENAME is the file path to the executing script
		parent::__construct();
		if(isset($_SERVER['REQUEST_URI']))
			$this->_requestUri=$_SERVER['REQUEST_URI'];
		else  // TBD: in this case, SCRIPT_NAME need to be escaped
			$this->_requestUri=$_SERVER['SCRIPT_NAME'].(empty($_SERVER['QUERY_STRING'])?'':'?'.$_SERVER['QUERY_STRING']);

		if(isset($_SERVER['PATH_INFO']))
			$this->_pathInfo=$_SERVER['PATH_INFO'];
		else if(strpos($_SERVER['PHP_SELF'],$_SERVER['SCRIPT_NAME'])===0)
			$this->_pathInfo=substr($_SERVER['PHP_SELF'],strlen($_SERVER['SCRIPT_NAME']));
		else
			$this->_pathInfo='';

		if(get_magic_quotes_gpc())
		{
			if(isset($_GET))
				$_GET=array_map(array($this,'stripSlashes'),$_GET);
			if(isset($_POST))
				$_POST=array_map(array($this,'stripSlashes'),$_POST);
			if(isset($_REQUEST))
				$_REQUEST=array_map(array($this,'stripSlashes'),$_REQUEST);
			if(isset($_COOKIE))
				$_COOKIE=array_map(array($this,'stripSlashes'),$_COOKIE);
		}

		$this->_items=new TMap(array_merge($_POST,$_GET));

		$this->resolveRequest();
	}

	/**
	 * Strips slashes from input data.
	 * This method is applied when magic quotes is enabled.
	 * Do not call this method.
	 * @param mixed input data to be processed
	 * @param mixed processed data
	 */
	public function stripSlashes(&$data)
	{
		return is_array($data)?array_map(array($this,'stripSlashes'),$data):stripslashes($data);
	}

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param IApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application,$config)
	{
		$this->_initialized=true;
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return TUri the request URL
	 */
	public function getUrl()
	{
		if($this->_url===null)
		{
			$secure=$this->getIsSecureConnection();
			$url=$secure?'https://':'http://';
			if(empty($_SERVER['HTTP_HOST']))
			{
				$url.=$_SERVER['SERVER_NAME'];
				$port=$_SERVER['SERVER_PORT'];
				if(($port!=80 && !$secure) || ($port!=443 && $secure))
					$url.=':'.$port;
			}
			else
				$url.=$_SERVER['HTTP_HOST'];
			$url.=$this->getRequestUri();
			$this->_url=new TUri($url);
		}
		return $this->_url;
	}

	/**
	 * @return string request type, can be GET, POST, HEAD, or PUT
	 */
	public function getRequestType()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * @return boolean if the request is sent via secure channel (https)
	 */
	public function getIsSecureConnection()
	{
		return !empty($_SERVER['HTTPS']);
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
		return isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:'';
	}

	/**
	 * @return string part of that request URL after the host info (including pathinfo and query string)
	 */
	public function getRequestUri()
	{
		return $this->_requestUri;
	}

	/**
	 * @return string entry script URL (w/o host part)
	 */
	public function getApplicationPath()
	{
		return $_SERVER['SCRIPT_NAME'];
	}

	/**
	 * @return string application entry script file path
	 */
	public function getPhysicalApplicationPath()
	{
		return strtr($_SERVER['SCRIPT_FILENAME'],'\\','/');
	}

	/**
	 * @return string server name
	 */
	public function getServerName()
	{
		return $_SERVER['SERVER_NAME'];
	}

	/**
	 * @return integer server port number
	 */
	public function getServerPort()
	{
		return $_SERVER['SERVER_PORT'];
	}

	/**
	 * @return string URL referrer, null if not present
	 */
	public function getUrlReferrer()
	{
		return isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:null;
	}

	/**
	 * @return array user browser capabilities
	 * @see get_browser
	 */
	public function getBrowser()
	{
		return get_browser();
	}

	/**
	 * @return string user agent
	 */
	public function getUserAgent()
	{
		return $_SERVER['HTTP_USER_AGENT'];
	}

	/**
	 * @return string user IP address
	 */
	public function getUserHostAddress()
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * @return string user host name, null if cannot be determined
	 */
	public function getUserHost()
	{
		return isset($_SERVER['REMOTE_HOST'])?$_SERVER['REMOTE_HOST']:null;
	}

	/**
	 * @return string user browser accept types
	 */
	public function getAcceptTypes()
	{
		// TBD: break it into array??
		return $_SERVER['HTTP_ACCEPT'];
	}

	/**
	 * @return string languages user browser supports
	 */
	public function getUserLanguages()
	{
		// TBD ask wei about this
		return $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	}

	/**
	 * @return TMap list of input variables, include GET, POST
	 */
	public function getItems()
	{
		return $this->_items;
	}

	/**
	 * @return THttpCookieCollection list of cookies to be sent
	 */
	public function getCookies()
	{
		if($this->_cookies===null)
		{
			$this->_cookies=new THttpCookieCollection;
			foreach($_COOKIE as $key=>$value)
				$this->_cookies->add(new THttpCookie($key,$value));
		}
		return $this->_cookies;
	}

	/**
	 * @return TMap list of uploaded files.
	 */
	public function getUploadedFiles()
	{
		if($this->_files===null)
			$this->_files=new TMap($_FILES);
		return $this->_files;
	}

	/**
	 * @return TMap list of server variables.
	 */
	public function getServerVariables()
	{
		if($this->_server===null)
			$this->_server=new TMap($_SERVER);
		return $this->_server;
	}

	/**
	 * @return TMap list of environment variables.
	 */
	public function getEnvironmentVariables()
	{
		if($this->_env===null)
			$this->_env=new TMap($_ENV);
		return $this->_env;
	}

	/**
	 * Constructs a URL that is recognizable by Prado.
	 * You may override this method to provide your own way of URL formatting.
	 * The URL is constructed as the following format:
	 * /entryscript.php?sp=serviceID.serviceParameter&get1=value1&...
	 * @param string service ID
	 * @param string service parameter
	 * @param array GET parameters, null if not needed
	 * @return string URL
	 */
	public function constructUrl($serviceID,$serviceParam,$getItems=null)
	{
		$url=$this->getApplicationPath();
		$url.='?'.self::SERVICE_VAR.'='.$serviceID;
		if(!empty($serviceParam))
			$url.='.'.$serviceParam;
		if(is_array($getItems) || $getItems instanceof Traversable)
		{
			foreach($getItems as $name=>$value)
				$url.='&'.urlencode($name).'='.urlencode($value);
		}
		if(defined('SID') && SID != '')
			$url.='&'.SID;
		return $url;
	}

	/**
	 * Resolves the requested servie.
	 * This method implements a URL-based service resolution.
	 * A URL in the format of /index.php?sp=serviceID.serviceParameter
	 * will be resolved with the serviceID and the serviceParameter.
	 * You may override this method to provide your own way of service resolution.
	 * @see constructUrl
	 */
	protected function resolveRequest()
	{
		if(($sp=$this->_items->itemAt(self::SERVICE_VAR))!==null)
		{
			if(($pos=strpos($sp,'.'))===false)
				$this->setServiceID($sp);
			else
			{
				$this->setServiceID(substr($sp,0,$pos));
				$this->setServiceParameter(substr($sp,$pos+1));
			}
		}
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
	 * @param string requested service ID
	 */
	protected function setServiceID($value)
	{
		$this->_serviceID=$value;
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
	 * @param string requested service parameter
	 */
	protected function setServiceParameter($value)
	{
		$this->_serviceParam=$value;
	}
}

/**
 * THttpCookieCollection class.
 *
 * THttpCookieCollection implements a collection class to store cookies.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpCookieCollection extends TList
{
	/**
	 * @var mixed owner of this collection
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param mixed owner of this collection.
	 */
	public function __construct($owner=null)
	{
		parent::__construct();
		$this->_o=$owner;
	}

	/**
	 * Adds the cookie if owner of this collection is of THttpResponse.
	 * This method will be invoked whenever an item is added to the collection.
	 */
	protected function addedItem($item)
	{
		if($this->_o instanceof THttpResponse)
			$this->_o->addCookie($item);
	}

	/**
	 * Removes the cookie if owner of this collection is of THttpResponse.
	 * This method will be invoked whenever an item is removed from the collection.
	 */
	protected function removedItem($item)
	{
		if($this->_o instanceof THttpResponse)
			$this->_o->removeCookie($item);
	}

	/**
	 * Restricts acceptable item of this collection to THttpCookie.
	 * This method will be invoked whenever an item is to be added into the collection.
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof THttpCookie);
	}
}

/**
 * THttpCookie class.
 *
 * A THttpCookie instance stores a single cookie, including the cookie name, value,
 * domain, path, expire, and secure.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpCookie extends TComponent
{
	/**
	 * @var string domain of the cookie
	 */
	private $_domain='';
	/**
	 * @var string name of the cookie
	 */
	private $_name;
	/**
	 * @var string value of the cookie
	 */
	private $_value=0;
	/**
	 * @var integer expire of the cookie
	 */
	private $_expire=0;
	/**
	 * @var string path of the cookie
	 */
	private $_path='/';
	/**
	 * @var boolean whether cookie should be sent via secure connection
	 */
	private $_secure=false;

	/**
	 * Constructor.
	 * @param string name of this cookie
	 * @param string value of this cookie
	 */
	public function __construct($name,$value)
	{
		parent::__construct();
		$this->_name=$name;
		$this->_value=$value;
	}

	/**
	 * @return string the domain to associate the cookie with
	 */
	public function getDomain()
	{
		return $this->_domain;
	}

	/**
	 * @param string the domain to associate the cookie with
	 */
	public function setDomain($value)
	{
		$this->_domain=$value;
	}

	/**
	 * @return integer the time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
	 */
	public function getExpire()
	{
		return $this->_expire;
	}

	/**
	 * @param integer the time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
	 */
	public function setExpire($value)
	{
		$this->_expire=TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string the name of the cookie
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string the name of the cookie
	 */
	public function setName($value)
	{
		$this->_name=$value;
	}

	/**
	 * @return string the value of the cookie
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @param string the value of the cookie
	 */
	public function setValue($value)
	{
		$this->_value=$value;
	}

	/**
	 * @return string the path on the server in which the cookie will be available on, default is '/'
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * @param string the path on the server in which the cookie will be available on
	 */
	public function setPath($value)
	{
		$this->_path=$value;
	}

	/**
	 * @return boolean whether the cookie should only be transmitted over a secure HTTPS connection
	 */
	public function getSecure()
	{
		return $this->_secure;
	}

	/**
	 * @param boolean ether the cookie should only be transmitted over a secure HTTPS connection
	 */
	public function setSecure($value)
	{
		$this->_secure=TPropertyValue::ensureBoolean($value);
	}
}

/**
 * TUri class
 *
 * TUri represents a URI. Given a URI
 * http://joe:whatever@example.com:8080/path/to/script.php?param=value#anchor
 * it will be decomposed as follows,
 * - scheme: http
 * - host: example.com
 * - port: 8080
 * - user: joe
 * - password: whatever
 * - path: /path/to/script.php
 * - query: param=value
 * - fragment: anchor
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class TUri extends TComponent
{
	/**
	 * @var array list of default ports for known schemes
	 */
	private static $_defaultPort=array(
		'ftp'=>21,
		'gopher'=>70,
		'http'=>80,
		'https'=>443,
		'news'=>119,
		'nntp'=>119,
		'wais'=>210,
		'telnet'=>23
	);
	/**
	 * @var string scheme of the URI
	 */
	private $_scheme;
	/**
	 * @var string host name of the URI
	 */
	private $_host;
	/**
	 * @var integer port of the URI
	 */
	private $_port;
	/**
	 * @var string user of the URI
	 */
	private $_user;
	/**
	 * @var string password of the URI
	 */
	private $_pass;
	/**
	 * @var string path of the URI
	 */
	private $_path;
	/**
	 * @var string query string of the URI
	 */
	private $_query;
	/**
	 * @var string fragment of the URI
	 */
	private $_fragment;
	/**
	 * @var string the URI
	 */
	private $_uri;

	/**
	 * Constructor.
	 * Decomposes the specified URI into parts.
	 * @param string URI to be represented
	 * @throws TInvalidDataValueException if URI is of bad format
	 */
	public function __construct($uri)
	{
		parent::__construct();
		if(($ret=@parse_url($uri))!==false)
		{
			// decoding???
			$this->_scheme=$ret['scheme'];
			$this->_host=$ret['host'];
			$this->_port=$ret['port'];
			$this->_user=$ret['user'];
			$this->_pass=$ret['pass'];
			$this->_path=$ret['path'];
			$this->_query=$ret['query'];
			$this->_fragment=$ret['fragment'];
			$this->_uri=$uri;
		}
		else
		{
			throw new TInvalidDataValueException('uri_format_invalid',$uri);
		}
	}

	/**
	 * @return string URI
	 */
	public function getUri()
	{
		return $this->_uri;
	}

	/**
	 * @return string scheme of the URI, such as 'http', 'https', 'ftp', etc.
	 */
	public function getScheme()
	{
		return $this->_scheme;
	}

	/**
	 * @return string hostname of the URI
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * @return integer port number of the URI
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * @return string username of the URI
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * @return string password of the URI
	 */
	public function getPassword()
	{
		return $this->_pass;
	}

	/**
	 * @return string path of the URI
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * @return string query string of the URI
	 */
	public function getQuery()
	{
		return $this->_query;
	}

	/**
	 * @return string fragment of the URI
	 */
	public function getFragment()
	{
		return $this->_fragment;
	}
}

?>