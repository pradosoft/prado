<?php

/**
 * TTestNullDynamicBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util;

use Prado\Util\IDynamicMethods;
use Prado\Util\TBehavior;

/**
 * A behavior that receives every dynamic event of its owner through `__dycall` and handles none:
 * it records the event and returns null.  A test attaches it to check that a filter dynamic event
 * keeps its value.
 */
class TTestNullDynamicBehavior extends TBehavior implements IDynamicMethods
{
	/** @var string[] the dynamic events received, in order */
	public array $events = [];

	public function __dycall($method, $args)
	{
		$this->events[] = $method;
	}
}
