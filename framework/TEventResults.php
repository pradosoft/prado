<?php

/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <belisoful@icloud.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TEventResults class
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */
class TEventResults extends \Prado\TEnumerable
{
	public const EVENT_RESULT_FEED_FORWARD = 1;
	public const EVENT_RESULT_FILTER = 2;
	public const EVENT_RESULT_ALL = 4;
	public const EVENT_REVERSE = 8;
}
