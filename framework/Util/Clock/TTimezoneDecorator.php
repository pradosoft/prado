<?php

/**
 * TTimezoneDecorator class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

/**
 * TTimezoneDecorator class.
 *
 * TTimezoneDecorator is a {@see \Prado\Util\Clock\TClockDecorator} that expresses the held clock's
 * {@see now()} in a chosen timezone. {@see timezone()} returns the chosen zone and {@see now()} is the held
 * instant re-expressed in it. The instant is unchanged: {@see time()} and {@see microtime()} forward to the
 * held clock, so `time()` equals `now()->getTimestamp()`. DST in the chosen zone is handled by
 * {@see \DateTimeImmutable::setTimezone}.
 *
 * {@see setTimezone} changes the chosen zone in place. The decorator wraps any `IClock`: a pinned
 * {@see \Prado\Util\Clock\TMockClock}, a {@see \Prado\Util\Clock\TOffsetClock}, or an injected clock.
 * For a source that builds {@see now()} in a zone directly, use {@see \Prado\Util\Clock\TTimezoneClock}.
 *
 * ```php
 * $tokyo = new TTimezoneDecorator('Asia/Tokyo');           // wraps a TNativeClock
 * $tokyo->now()->format('H:i');                            // wall-clock time in Tokyo
 * $tokyo->time();                                          // the true Unix timestamp
 * $test = new TTimezoneDecorator('Europe/London', $mock);  // a pinned clock seen from London
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTimezoneDecorator extends TClockDecorator
{
	// =========================================================================
	// Properties
	// =========================================================================

	/** @var \DateTimeZone the zone {@see now()} is expressed in */
	private \DateTimeZone $_timezone;

	/**
	 * @return \DateTimeZone the zone {@see now()} is expressed in
	 */
	public function getTimezone(): \DateTimeZone
	{
		return $this->_timezone;
	}

	/**
	 * Sets the zone {@see now()} is expressed in.
	 * @param \DateTimeZone|string $value the zone, or a name {@see \DateTimeZone} accepts
	 * @return static $this for method chaining
	 */
	public function setTimezone(\DateTimeZone|string $value): static
	{
		$this->_timezone = is_string($value) ? new \DateTimeZone($value) : $value;
		return $this;
	}

	/**
	 * @param null|\DateTimeZone|string $timezone the zone to express {@see now()} in; null uses the system default
	 * @param ?IClock $clock the clock to wrap; null wraps the default {@see \Prado\Util\Clock\TNativeClock}
	 */
	public function __construct(\DateTimeZone|string|null $timezone = null, ?IClock $clock = null)
	{
		parent::__construct($clock);
		$this->setTimezone($timezone ?? date_default_timezone_get());
	}

	// =========================================================================
	// ClockInterface
	// =========================================================================

	/**
	 * Returns the held clock's instant expressed in the chosen {@see timezone()}.
	 * @return \DateTimeImmutable the held moment in the chosen zone
	 */
	public function now(): \DateTimeImmutable
	{
		return parent::now()->setTimezone($this->_timezone);
	}

	// =========================================================================
	// IClock
	// =========================================================================

	/**
	 * Returns the chosen zone. {@see now()} is expressed in it.
	 * @return \DateTimeZone the chosen zone
	 */
	public function timezone(): \DateTimeZone
	{
		return $this->_timezone;
	}
}
