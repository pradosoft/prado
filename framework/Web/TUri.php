<?php

/**
 * THttpRequest, THttpCookie, THttpCookieCollection, TUri class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TResourceUri;

/**
 * TUri class
 *
 * Represents a URI. Given a URI
 * http://joe:whatever@example.com:8080/path/to/script.php?param=value#anchor
 * it is decomposed as follows:
 * - scheme: http
 * - host: example.com
 * - port: 8080
 * - user: joe
 * - password: whatever
 * - path: /path/to/script.php
 * - query: param=value
 * - fragment: anchor
 *
 * As of 4.4.0, TUri is the Prado-specific PSR-7 URI: it extends
 * {@see \Prado\IO\TResourceUri} and is fully PSR-7
 * {@see \Psr\Http\Message\UriInterface} compliant (scheme and host normalize to
 * lower case, default ports suppressed, path/query/fragment percent-encoded).
 * Change a component with the inherited `with*` clone-methods.
 *
 * It has retained three Prado conveniences over the bare interface: {@see getUri()}
 * returns the recomposed URI string, and {@see getUser()}/{@see getPassword()}
 * expose the (decoded) user and password separately, complementing the PSR-7
 * {@see getUserInfo()}.
 *
 * BACKWARD-COMPATIBILITY NOTE (4.4.0): the legacy accessors now return PSR-7
 * normalized values; {@see getPort()} returns null when the port is absent or
 * matches the scheme default (it formerly returned the raw port or empty string).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TUri extends TResourceUri
{
	/**
	 * Throws the Prado-conventional exception when a URI cannot be parsed.
	 * @param string $uri The offending URI.
	 * @return \Throwable The exception to throw.
	 */
	protected function createParseException(string $uri): \Throwable
	{
		return new TInvalidDataValueException('uri_format_invalid', $uri);
	}

	/**
	 * Returns the recomposed URI string (alias of {@see __toString()}).
	 * @return string the URI
	 */
	public function getUri()
	{
		return (string) $this;
	}

	/**
	 * Returns the decoded user component, complementing {@see getUserInfo()}.
	 * @return string username of the URI
	 */
	public function getUser()
	{
		$info = $this->getUserInfo();
		$pos = strpos($info, ':');
		return rawurldecode($pos === false ? $info : substr($info, 0, $pos));
	}

	/**
	 * Returns the decoded password component, complementing {@see getUserInfo()}.
	 * @return string password of the URI
	 */
	public function getPassword()
	{
		$info = $this->getUserInfo();
		$pos = strpos($info, ':');
		return $pos === false ? '' : rawurldecode(substr($info, $pos + 1));
	}
}
