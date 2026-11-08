<?php

/**
 * TTestCronModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util\Cron;

require_once __DIR__ . '/TTestCronClockTrait.php';

use Prado\Util\Cron\TCronModule;

/**
 * TTestCronModule is a {@see \Prado\Util\Cron\TCronModule} harness for unit tests.
 *
 * It applies {@see TTestCronClockTrait} so the time, microtime, random, SAPI, and sleep seams can be
 * driven deterministically — pinning "now," controlling the request-cron probability draw, and
 * recording per-second sleeps without delaying the process.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestCronModule extends TCronModule
{
	use TTestCronClockTrait;
}
