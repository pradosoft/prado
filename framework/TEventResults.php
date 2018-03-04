<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */

namespace Prado;

class TEventResults extends \Prado\TEnumerable
{
	const EVENT_RESULT_FEED_FORWARD = 1;
	const EVENT_RESULT_FILTER = 2;
	const EVENT_RESULT_ALL = 4;
}
