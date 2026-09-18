<?php

/**
 * TMockClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\TComponent;

/**
 * TMockClock class.
 *
 * TMockClock is a standalone, settable {@see \Prado\Util\Clock\IClock}, the Prado equivalent of Symfony's
 * `MockClock`. While {@see getNow Now} is null it reports the real system time; once an instant is pinned,
 * {@see now()} returns that fixed instant and {@see sleep} advances it instead of blocking. This makes
 * time deterministic for tests and for any code that needs to freeze or control the clock. Unlike
 * {@see \Prado\Util\Clock\TNativeClock} (the safe default that cannot be pinned), reaching for
 * `TMockClock` is a deliberate choice. It does not extend `TNativeClock`, so it is a *sibling* of the
 * real clocks; it still implements {@see \Prado\Util\Clock\IClock}, so it drops into any
 * {@see \Prado\Util\Clock\TClockAwareTrait} holder.
 *
 * Each reported value has a setter:
 *
 * | Setter | Effect |
 * |---|---|
 * | {@see setNow} | pins the instant: a `\DateTimeInterface`, a string `\DateTimeImmutable` parses, an int or float Unix timestamp, or null to unpin |
 * | {@see setTime} | pins the instant at a whole-second Unix timestamp, in {@see timezone} |
 * | {@see setMicrotime} | pins the instant at a fractional Unix timestamp, in {@see timezone} |
 * | {@see setTimezone} | fixes the reported zone; a pinned instant is re-expressed in it |
 *
 * {@see timezone()} reports the pinned instant's zone, else the {@see setTimezone fixed zone}, else the
 * system default. {@see time} and {@see microtime} read the pinned instant when set and otherwise fall
 * through to the fast {@see \Prado\Util\Clock\TClockTrait} path, so the unpinned clock stays cheap.
 *
 * ```php
 * $clock = new TMockClock();
 * $clock->now();                              // real system time
 * $clock->setNow('@1700000000');
 * $clock->now()->getTimestamp();              // 1700000000
 * $clock->time();                             // 1700000000
 * $clock->sleep(60);                          // advances the pin to 1700000060 (no real delay)
 * $clock->setTime(1700000000);                // pin by timestamp
 * $clock->setTimezone('Asia/Tokyo');          // the pin is now expressed in Tokyo
 * $clock->setNow(null);                       // back to the system clock, still reporting in Tokyo
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMockClock extends TComponent implements IClock
{
	use TClockTrait;

	// =========================================================================
	// Properties
	// =========================================================================

	/** @var ?\DateTimeImmutable the pinned instant, or null to use the real system time */
	private ?\DateTimeImmutable $_now = null;

	/** @var ?\DateTimeZone the fixed reported zone, or null for the pin's zone or the system default */
	private ?\DateTimeZone $_timezone = null;

	/**
	 * @return ?\DateTimeImmutable the pinned instant, or null when the clock is not pinned
	 */
	public function getNow(): ?\DateTimeImmutable
	{
		return $this->_now;
	}

	/**
	 * Pins the clock to a fixed instant, or releases it back to the real system time. A string is parsed by
	 * {@see \DateTimeImmutable} (any value it accepts, e.g. `'2024-01-01 12:00:00'` or `'@1700000000'`).
	 * An int is a whole-second Unix timestamp and a float a fractional one, both expressed in
	 * {@see timezone}. When a {@see setTimezone fixed zone} is set the pin is expressed in that zone.
	 * @param null|\DateTimeInterface|float|int|string $value the instant to pin to, or null to unpin
	 * @return $this for method chaining.
	 */
	public function setNow(\DateTimeInterface|string|int|float|null $value): static
	{
		$now = match (true) {
			$value === null, $value instanceof \DateTimeImmutable => $value,
			is_int($value) => (new \DateTimeImmutable('@' . $value))->setTimezone($this->timezone()),
			is_float($value) => $this->fromMicrotime($value),
			is_string($value) => new \DateTimeImmutable($value, $this->_timezone),
			default => \DateTimeImmutable::createFromInterface($value),
		};
		$this->_now = ($now !== null && $this->_timezone !== null) ? $now->setTimezone($this->_timezone) : $now;
		return $this;
	}

	/**
	 * Pins the clock at a whole-second Unix timestamp, expressed in {@see timezone}.
	 * @param ?int $time the Unix timestamp to pin to, or null to unpin
	 * @return $this for method chaining.
	 */
	public function setTime(?int $time): static
	{
		return $this->setNow($time);
	}

	/**
	 * Pins the clock at a Unix timestamp with microseconds, expressed in {@see timezone}.
	 * @param ?float $microtime the fractional Unix timestamp to pin to, or null to unpin
	 * @return $this for method chaining.
	 */
	public function setMicrotime(?float $microtime): static
	{
		return $this->setNow($microtime);
	}

	/**
	 * @return ?\DateTimeZone the fixed reported zone, or null when none is set
	 */
	public function getTimezone(): ?\DateTimeZone
	{
		return $this->_timezone;
	}

	/**
	 * Fixes the zone the clock reports in. A pinned instant is re-expressed in the zone (same instant);
	 * an unpinned clock reports the real time in it. Null clears the fixed zone; a pinned instant keeps
	 * the zone it already has.
	 * @param null|\DateTimeZone|string $timezone the zone, a name {@see \DateTimeZone} accepts, or null
	 * @return $this for method chaining.
	 */
	public function setTimezone(\DateTimeZone|string|null $timezone): static
	{
		$this->_timezone = is_string($timezone) ? new \DateTimeZone($timezone) : $timezone;
		if ($this->_now !== null && $this->_timezone !== null) {
			$this->_now = $this->_now->setTimezone($this->_timezone);
		}
		return $this;
	}

	// =========================================================================
	// ClockInterface
	// =========================================================================

	/**
	 * Returns the pinned instant when set, otherwise the real system time in {@see timezone}.
	 * @return \DateTimeImmutable the current moment
	 */
	public function now(): \DateTimeImmutable
	{
		return $this->_now ?? new \DateTimeImmutable('now', $this->timezone());
	}

	// =========================================================================
	// IClock
	// =========================================================================

	/**
	 * Returns the pinned timestamp when set, otherwise the fast real time.
	 * @return int the current Unix timestamp in whole seconds
	 */
	public function time(): int
	{
		return $this->_now?->getTimestamp() ?? time();
	}

	/**
	 * Returns the pinned timestamp with microseconds when set, otherwise the fast real time.
	 * @return float the current Unix timestamp with microsecond precision
	 */
	public function microtime(): float
	{
		return $this->_now !== null ? (float) $this->_now->format('U.u') : microtime(true);
	}

	/**
	 * Returns the pinned instant's zone when pinned, else the {@see setTimezone fixed zone} when set, else
	 * the system default.
	 * @return \DateTimeZone the zone {@see now()} reports in
	 */
	public function timezone(): \DateTimeZone
	{
		return $this->_now?->getTimezone() ?? $this->_timezone ?? $this->systemTimezone();
	}

	/**
	 * Advances the pinned instant by the given seconds instead of blocking, so time-dependent code under a
	 * frozen clock runs without real delay. When the clock is not pinned this blocks with {@see \usleep()}.
	 * Microsecond fractions are honored and the displayed timezone is preserved; a negative value rewinds.
	 * @param float|int $seconds the number of seconds to advance the pinned instant, or to pause
	 */
	public function sleep(float|int $seconds): void
	{
		if ($this->_now === null) {
			if ($seconds > 0) {
				\usleep((int) \round($seconds * 1000000));
			}
			return;
		}
		$this->_now = $this->fromMicrotime((float) $this->_now->format('U.u') + $seconds);
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Builds an instant from a fractional Unix timestamp, expressed in {@see timezone}.
	 * @param float $seconds the Unix timestamp with microseconds
	 * @return \DateTimeImmutable the instant in {@see timezone}
	 */
	private function fromMicrotime(float $seconds): \DateTimeImmutable
	{
		$instant = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6f', $seconds))
			?: new \DateTimeImmutable('@' . (int) floor($seconds));
		return $instant->setTimezone($this->timezone());
	}
}
