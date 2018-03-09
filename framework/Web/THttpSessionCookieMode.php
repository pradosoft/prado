<?php
/**
 * THttpSession class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web
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
 * @package Prado\Web
 * @since 3.0.4
 */
class THttpSessionCookieMode extends \Prado\TEnumerable
{
	const None = 'None';
	const Allow = 'Allow';
	const Only = 'Only';
}
