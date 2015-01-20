<?php
/**
 * THttpRequest, THttpCookie, THttpCookieCollection, TUri class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web
 */

/**
 * THttpCookie class.
 *
 * A THttpCookie instance stores a single cookie, including the cookie name, value,
 * domain, path, expire, and secure.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
	private $_value='';
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
	 * @var boolean if true the cookie value will be unavailable to JavaScript
	 */
	private $_httpOnly=false;

	/**
	 * Constructor.
	 * @param string name of this cookie
	 * @param string value of this cookie
	 */
	public function __construct($name,$value)
	{
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
	 * @return boolean if true the cookie value will be unavailable to JavaScript
	 */
	public function getHttpOnly()
	{
		return $this->_httpOnly;
	}

	/**
	 * @param boolean $value if true the cookie value will be unavailable to JavaScript
	 */
	public function setHttpOnly($value)
	{
		$this->_httpOnly = TPropertyValue::ensureBoolean($value);
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