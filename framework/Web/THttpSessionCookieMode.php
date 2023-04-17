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
 * THttpSessionCookieMode enum.
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
enum THttpSessionCookieMode: string
{
	case None = 'None';
	case Allow = 'Allow';
	case Only = 'Only';
}
