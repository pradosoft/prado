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

use Prado\Exceptions\TInvalidDataValueException;

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
 * @package Prado\Web
 * @since 3.0
 */
class TUri extends \Prado\TComponent
{
	/**
	 * @var array list of default ports for known schemes
	 */
	private static $_defaultPort = [
		'ftp' => 21,
		'gopher' => 70,
		'http' => 80,
		'https' => 443,
		'news' => 119,
		'nntp' => 119,
		'wais' => 210,
		'telnet' => 23
	];
	/**
	 * @var string scheme of the URI
	 */
	private $_scheme;
	/**
	 * @var string host name of the URI
	 */
	private $_host;
	/**
	 * @var int port of the URI
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
	 * @param string $uri URI to be represented
	 * @throws TInvalidDataValueException if URI is of bad format
	 */
	public function __construct($uri)
	{
		if (($ret = @parse_url($uri)) !== false) {
			// decoding???
			$this->_scheme = $ret['scheme'] ?? '';
			$this->_host = $ret['host'] ?? '';
			$this->_port = $ret['port'] ?? '';
			$this->_user = $ret['user'] ?? '';
			$this->_pass = $ret['pass'] ?? '';
			$this->_path = $ret['path'] ?? '';
			$this->_query = $ret['query'] ?? '';
			$this->_fragment = $ret['fragment'] ?? '';
			$this->_uri = $uri;
		} else {
			throw new TInvalidDataValueException('uri_format_invalid', $uri);
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
	 * @return int port number of the URI
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
