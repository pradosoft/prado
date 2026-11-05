<?php

/**
 * IClock interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Psr\Clock\ClockInterface;

/**
 * IClock interface.
 *
 * IClock is Prado's clock contract. It extends PSR-20 {@see \Psr\Clock\ClockInterface} (so an IClock is
 * usable anywhere a PSR clock is expected) and adds accessors derived from
 * {@see \Psr\Clock\ClockInterface::now now()}: the integer- and float-second {@see time}/{@see microtime},
 * and the {@see timezone} the clock reports in. It also adds {@see sleep}, a clock-aware pause that a
 * pinned clock advances instead of blocking. {@see \Prado\Util\Clock\TClockTrait} provides them all.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IClock extends ClockInterface
{
	/**
	 * @return int the current Unix timestamp in whole seconds, from {@see now()}
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function time(): int;

	/**
	 * @return float the current Unix timestamp with microsecond precision, from {@see now()}
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function microtime(): float;

	/**
	 * @return \DateTimeZone the timezone {@see now()} reports in
	 */
	public function timezone(): \DateTimeZone;

	/**
	 * Pauses execution for the given number of seconds. A real clock blocks; a pinned clock advances its
	 * instant by the same amount instead of blocking.
	 * @param float|int $seconds the number of seconds to pause, fractions allowed
	 */
	public function sleep(float|int $seconds): void;
}
