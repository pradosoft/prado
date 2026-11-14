<?php

/**
 * TTestDbCronManager class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util\Cron;

require_once __DIR__ . '/TTestCronClockTrait.php';

use Prado\Util\Cron\TDbCronManager;

/**
 * TTestDbCronManager is a {@see \Prado\Util\Cron\TDbCronManager} harness for unit tests.
 *
 * It applies {@see TTestCronClockTrait} so the time, microtime, random, SAPI, and sleep seams
 * inherited from {@see \Prado\Util\Cron\TCronModule} can be driven deterministically — pinning "now,"
 * controlling the request-cron probability draw, and recording per-second sleeps without delaying the
 * process.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestDbCronManager extends TDbCronManager
{
	use TTestCronClockTrait;
}
