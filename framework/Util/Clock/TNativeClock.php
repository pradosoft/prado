<?php

/**
 * TNativeClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\TComponent;

/**
 * TNativeClock class.
 *
 * TNativeClock is the framework's real-time clock, the source the {@see \Prado\Util\Clock\TClockDecorator}
 * family wraps by default. It is {@see \Prado\Util\Clock\TClockTrait} as a component: {@see now},
 * {@see time}, {@see microtime}, {@see timezone}, and {@see sleep} are the trait's direct system reads
 * in the system default zone. It is the safe default for classes that read time
 * ({@see \Prado\Util\Clock\TClockAwareTrait}). It cannot be pinned to a fixed instant the way
 * {@see \Prado\Util\Clock\TMockClock} can, so reaching for a pinnable `TMockClock` is a deliberate choice.
 *
 * {@see now} reports in {@see timezone}. A subclass that changes the reported zone overrides both
 * together; {@see time} and {@see microtime} stay the trait's bare reads because a Unix timestamp has
 * no zone. {@see \Prado\Util\Clock\TTimezoneClock} is the subclass tuned to a fixed zone and
 * {@see \Prado\Util\Clock\TUTCClock} its UTC form. Transforming a held clock is a decorator:
 * {@see \Prado\Util\Clock\TOffsetClock} shifts the instant and {@see \Prado\Util\Clock\TTimezoneDecorator}
 * expresses it in a zone. The static {@see getSystemTimezone} exposes the system
 * default zone.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TNativeClock extends TComponent implements IClock
{
	use TClockTrait;

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Returns the system (application default) timezone, from {@see \date_default_timezone_get()}. This is
	 * the system default regardless of the zone a subclass instance reports through {@see timezone()}.
	 * @return \DateTimeZone the default timezone
	 */
	public static function getSystemTimezone(): \DateTimeZone
	{
		return new \DateTimeZone(date_default_timezone_get());
	}
}
