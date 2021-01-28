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

use Prado\TPropertyValue;

/**
 * THttpCookie class.
 *
 * A THttpCookie instance stores a single cookie, including the cookie name, value,
 * domain, path, expire, and secure.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web
 * @since 3.0
 */
class THttpCookie extends \Prado\TComponent
{
	/**
	 * @var string domain of the cookie
	 */
	private $_domain = '';
	/**
	 * @var string name of the cookie
	 */
	private $_name;
	/**
	 * @var string value of the cookie
	 */
	private $_value = '';
	/**
	 * @var int expire of the cookie
	 */
	private $_expire = 0;
	/**
	 * @var string path of the cookie
	 */
	private $_path = '/';
	/**
	 * @var bool whether cookie should be sent via secure connection
	 */
	private $_secure = false;
	/**
	 * @var bool if true the cookie value will be unavailable to JavaScript
	 */
	private $_httpOnly = false;
	/**
	 * @var THttpCookieSameSite SameSite prevents the browser from sending this cookie on cross-site requests.
	 * @since 4.1.2
	 */
	private $_sameSite = THttpCookieSameSite::Lax;

	/**
	 * Constructor.
	 * @param string $name name of this cookie
	 * @param string $value value of this cookie
	 */
	public function __construct($name, $value)
	{
		$this->_name = $name;
		$this->_value = $value;
	}

	/**
	 * @return string the domain to associate the cookie with
	 */
	public function getDomain()
	{
		return $this->_domain;
	}

	/**
	 * @param string $value the domain to associate the cookie with
	 */
	public function setDomain($value)
	{
		$this->_domain = $value;
	}

	/**
	 * @return int the time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
	 */
	public function getExpire()
	{
		return $this->_expire;
	}

	/**
	 * @param int $value the time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
	 */
	public function setExpire($value)
	{
		$this->_expire = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return bool if true the cookie value will be unavailable to JavaScript
	 */
	public function getHttpOnly()
	{
		return $this->_httpOnly;
	}

	/**
	 * @param bool $value if true the cookie value will be unavailable to JavaScript
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
	 * @param string $value the name of the cookie
	 */
	public function setName($value)
	{
		$this->_name = $value;
	}

	/**
	 * @return string the value of the cookie
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @param string $value the value of the cookie
	 */
	public function setValue($value)
	{
		$this->_value = $value;
	}

	/**
	 * @return string the path on the server in which the cookie will be available on, default is '/'
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * @param string $value the path on the server in which the cookie will be available on
	 */
	public function setPath($value)
	{
		$this->_path = $value;
	}

	/**
	 * @return bool whether the cookie should only be transmitted over a secure HTTPS connection
	 */
	public function getSecure()
	{
		return $this->_secure;
	}

	/**
	 * @param bool $value ether the cookie should only be transmitted over a secure HTTPS connection
	 */
	public function setSecure($value)
	{
		$this->_secure = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return THttpCookieSameSite SameSite policy for this cookie. Defaults to THttpCookieSameSite::None.
	 */
	public function getSameSite()
	{
		return $this->_sameSite;
	}

	/**
	 * @param THttpCookieSameSite $value SameSite policy for this cookie
	 */
	public function setSameSite($value)
	{
		$this->_sameSite = TPropertyValue::ensureEnum($value, '\Prado\Web\THttpCookieSameSite');
	}

	/**
	 * @param mixed $expiresKey
	 * @return array cookie options as used in php's setcookie() and session_set_cookie_params().
	 * The 'expires' key can be customized since setcookie() uses 'expires' and
	 * session_set_cookie_params() uses 'lifetime'.
	 */
	public function getPhpOptions($expiresKey = 'expires')
	{
		return [
			$expiresKey => $this->_expire,
			'path' => $this->_path,
			'domain' => $this->_domain,
			'secure' => $this->_secure,
			'httponly' => $this->_httpOnly,
			'samesite' => $this->_sameSite,
		];
	}
}
