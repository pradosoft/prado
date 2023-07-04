<?php
/**
 * THttpRequest, THttpCookie, THttpCookieCollection, TUri class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * THttpRequestUrlFormat class.
 * THttpRequestUrlFormat defines the enumerable type for the possible URL formats
 * that can be recognized by {@see \Prado\Web\THttpRequest}.
 *
 * The following enumerable values are defined:
 * - Get: the URL format is like /path/to/index.php?name1=value1&name2=value2...
 * - Path: the URL format is like /path/to/index.php/name1,value1/name2,value2...
 * - HiddenPath: the URL format is like /path/to/name1,value1/name2,value2...
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class THttpRequestUrlFormat extends \Prado\TEnumerable
{
	public const Get = 'Get';
	public const Path = 'Path';
	public const HiddenPath = 'HiddenPath';
}
