<?php

/**
 * TUTCClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\Exceptions\TInvalidOperationException;

/**
 * TUTCClock class.
 *
 * TUTCClock is a {@see \Prado\Util\Clock\TTimezoneClock} fixed to UTC: it reports {@see now()} in UTC
 * regardless of the system default timezone — for storage, logging, and cross-host comparisons.
 * {@see time()} and {@see microtime()} are the true Unix timestamp. Unlike `TTimezoneClock`, the zone
 * cannot be changed: {@see withTimezone} throws.
 *
 * ```php
 * $clock = new TUTCClock();
 * $clock->now()->getTimezone()->getName();    // "UTC"
 * $clock->now()->format('H:i');               // wall-clock time in UTC
 * $clock->time();                             // the true Unix timestamp
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUTCClock extends TTimezoneClock
{
	public function __construct()
	{
		parent::__construct('UTC');
	}

	/**
	 * The zone is fixed to UTC and cannot be changed; use a {@see \Prado\Util\Clock\TTimezoneClock} for
	 * another zone.
	 * @param \DateTimeZone|string $timezone ignored
	 * @throws TInvalidOperationException always; a TUTCClock is always UTC
	 * @return static never returns
	 */
	public function withTimezone(\DateTimeZone|string $timezone): static
	{
		throw new TInvalidOperationException('utcclock_timezone_fixed');
	}
}
