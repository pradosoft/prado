<?php

/**
 * TOffsetClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

/**
 * TOffsetClock class.
 *
 * TOffsetClock is a {@see \Prado\Util\Clock\TClockDecorator} that shifts the held clock's {@see now()} by
 * a fixed offset of seconds — an integer or a float (sub-second). A positive offset runs the clock ahead, a
 * negative one behind; the held clock's timezone is preserved. This is useful to simulate clock skew, or to
 * run a pinned {@see \Prado\Util\Clock\TMockClock} ahead in tests. For expressing the held clock's `now()` in
 * another timezone, use {@see \Prado\Util\Clock\TTimezoneDecorator}.
 *
 * {@see setOffset} changes the offset in place.
 *
 * ```php
 * $ahead = new TOffsetClock(90);          // 90 seconds ahead of real time
 * $ahead->now()->getTimestamp();          // time() + 90
 * $ahead->setOffset(120);                 // now 120 seconds ahead
 * $ahead->setOffset(-0.5);                // now 0.5 seconds behind
 * $test = new TOffsetClock(10, $mock);    // 10 seconds ahead of a pinned clock
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TOffsetClock extends TClockDecorator
{
	// =========================================================================
	// Properties
	// =========================================================================

	/** @var float|int the offset in seconds applied to {@see now()} */
	private float|int $_offset = 0;

	/**
	 * @return float|int the offset in seconds applied to {@see now()}
	 */
	public function getOffset(): float|int
	{
		return $this->_offset;
	}

	/**
	 * Sets the offset in seconds applied to {@see now()}, {@see time()}, and {@see microtime()}.
	 * @param float|int $value the offset in seconds; positive runs ahead, negative behind
	 * @return static $this for method chaining
	 */
	public function setOffset(float|int $value): static
	{
		$this->_offset = $value;
		return $this;
	}

	/**
	 * @param float|int $offset the offset in seconds applied to {@see now()}
	 * @param ?IClock $clock the clock to wrap; null wraps the default {@see \Prado\Util\Clock\TNativeClock}
	 */
	public function __construct(float|int $offset = 0, ?IClock $clock = null)
	{
		parent::__construct($clock);
		$this->setOffset($offset);
	}

	// =========================================================================
	// ClockInterface
	// =========================================================================

	/**
	 * Returns the held clock's time shifted by the {@see getOffset offset} seconds, in the held clock's zone.
	 * @return \DateTimeImmutable the offset moment
	 */
	public function now(): \DateTimeImmutable
	{
		$base = parent::now();
		if (!$this->_offset) {
			return $base;
		}
		$shifted = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6f', (float) $base->format('U.u') + $this->_offset));
		return ($shifted ?: $base)->setTimezone($base->getTimezone());
	}

	// =========================================================================
	// IClock
	// =========================================================================

	/**
	 * Returns the held clock's timestamp shifted by the {@see getOffset offset}, consistent with {@see now()}.
	 * An integer offset adds directly to the held {@see time()}; a fractional offset floors the shifted
	 * {@see microtime()} so the whole second stays consistent with the fraction.
	 * @return int the offset Unix timestamp in whole seconds
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function time(): int
	{
		if (is_int($this->_offset)) {
			return parent::time() + $this->_offset;
		}
		return (int) floor($this->microtime());
	}

	/**
	 * Returns the held clock's microtime shifted by the {@see getOffset offset}, consistent with {@see now()}.
	 * @return float the offset Unix timestamp with microsecond precision
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function microtime(): float
	{
		return parent::microtime() + $this->_offset;
	}
}
