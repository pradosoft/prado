<?php
/**
 * THttpCookieSameSite class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web
 */

namespace Prado\Web;

/**
 * THttpCookieSameSite class.
 * THttpCookieSameSite defines the enumerable type for the possible values of the
 * SameSite property of {@link THttpCookie}.
 *
 * SameSite is a cookie attribute (similar to HTTPOnly, Secure etc.) which aims to
 * mitigate CSRF attacks. It prevents the browser from sending the cookie along on
 * cross-site requests.
 *
 * The following enumerable values are defined:
 * - Lax: Cookies are allowed to be sent with top-level navigations and will be sent along with GET request initiated by third party website (but not with POST, PUT, PATCH requests).
 * - Strict: Cookies will only be sent in a first-party context and not be sent along with requests initiated by third party websites.
 * - None: Cookies will be sent in all contexts, i.e sending cross-origin is allowed
 *
 * Please note that this feature requires PHP 7.3.0
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\Web
 * @since 4.1.2
 */
class THttpCookieSameSite extends \Prado\TEnumerable
{
	const Lax = 'Lax';
	const Strict = 'Strict';
	const None = 'None';
}
