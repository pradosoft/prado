<?php

/**
 * TTimezoneClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

/**
 * TTimezoneClock class.
 *
 * TTimezoneClock is a {@see \Prado\Util\Clock\TNativeClock} that reports {@see now()} in a fixed timezone
 * instead of the system default — for presenting "now" in another timezone. {@see timezone()} returns
 * the configured zone from a field, and {@see now()} builds the current instant in it.
 * {@see time()} and {@see microtime()} are the inherited bare system reads: a Unix timestamp has no zone,
 * so `time()` equals `now()->getTimestamp()`.
 *
 * The timezone is immutable per instance; {@see withTimezone} returns a new clock for a different zone.
 * Any value {@see \DateTimeZone} accepts is allowed — a region (`Asia/Tokyo`), an abbreviation
 * (`PST`/`PDT`), or a fixed offset (`+05:30`). {@see \Prado\Util\Clock\TUTCClock} is the UTC subclass.
 * For a pinned instant shown in a zone, use {@see \Prado\Util\Clock\TMockClock::setTimezone}. To express
 * any held clock's {@see now()} in a zone, use {@see \Prado\Util\Clock\TTimezoneDecorator}.
 * For shifting the *instant* by a fixed number of seconds, see {@see \Prado\Util\Clock\TOffsetClock}.
 *
 * ```php
 * $tokyo = new TTimezoneClock('Asia/Tokyo');
 * $tokyo->now()->format('H:i');               // wall-clock time in Tokyo
 * $tokyo->timezone()->getName();              // "Asia/Tokyo"
 * $tokyo->time();                             // the true Unix timestamp
 * $utc = $tokyo->withTimezone('UTC');         // a new clock, in UTC
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTimezoneClock extends TNativeClock
{
	// =========================================================================
	// Properties
	// =========================================================================

	/** @var \DateTimeZone the timezone {@see now()} reports in */
	private \DateTimeZone $_timezone;

	/**
	 * Returns a new clock that reports {@see now()} in the given timezone, leaving this one unchanged.
	 * @param \DateTimeZone|string $timezone the timezone for the new clock
	 * @return static the new clock in the given timezone
	 */
	public function withTimezone(\DateTimeZone|string $timezone): static
	{
		$clone = clone $this;
		$clone->_timezone = $this->toTimezone($timezone);
		return $clone;
	}

	/**
	 * @param null|\DateTimeZone|string $timezone the timezone to report in; null uses the system default
	 */
	public function __construct(\DateTimeZone|string|null $timezone = null)
	{
		parent::__construct();
		$this->_timezone = $this->toTimezone($timezone ?? date_default_timezone_get());
	}

	// =========================================================================
	// ClockInterface
	// =========================================================================

	/**
	 * Returns the current system time in the configured timezone.
	 * @return \DateTimeImmutable the current moment in {@see timezone()}
	 */
	public function now(): \DateTimeImmutable
	{
		return new \DateTimeImmutable('now', $this->_timezone);
	}

	// =========================================================================
	// IClock
	// =========================================================================

	/**
	 * Returns the configured timezone from its field. {@see now()} builds in this zone.
	 * @return \DateTimeZone the configured timezone
	 */
	public function timezone(): \DateTimeZone
	{
		return $this->_timezone;
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Normalizes a timezone name or object to a {@see \DateTimeZone}. Accepts anything `\DateTimeZone`
	 * takes: region names, abbreviations, and fixed offsets.
	 * @param \DateTimeZone|string $timezone the timezone name or object
	 * @return \DateTimeZone the timezone object
	 */
	private function toTimezone(\DateTimeZone|string $timezone): \DateTimeZone
	{
		return is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
	}
}
