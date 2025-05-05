<?php

/**
 * THttpSession class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * THttpSessionCookieMode class.
 * THttpSessionCookieMode defines the enumerable type for the possible methods of
 * using cookies to store session ID.
 *
 * The following enumerable values are defined:
 * - None: not using cookie.
 * - Allow: using cookie.
 * - Only: using cookie only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class THttpSessionCookieMode extends \Prado\TEnumerable
{
	/**
	 * @deprecated 4.3.1 Since PHP 8.4 disabling session.use_only_cookies
	 * INI setting is deprecated; use THttpSessionCookieMode::Only instead.
	 */
	public const None = 'None';

	/**
	 * @deprecated 4.3.1 Since PHP 8.4 disabling session.use_only_cookies
	 * INI setting is deprecated; use THttpSessionCookieMode::Only instead.
	 */
	public const Allow = 'Allow';

	public const Only = 'Only';
}
