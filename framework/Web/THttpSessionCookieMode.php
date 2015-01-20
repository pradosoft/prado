<?php
/**
 * THttpSession class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web
 */


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
 * @package System.Web
 * @since 3.0.4
 */
class THttpSessionCookieMode extends TEnumerable
{
	const None='None';
	const Allow='Allow';
	const Only='Only';
}