<?php

/**
 * TTestCacheClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

use Prado\TComponent;
use Prado\Util\Clock\IClock;
use Prado\Util\Clock\TClockTrait;

/**
 * TTestCacheClock is an {@see IClock} with independently settable {@see time()} and {@see microtime()}
 * for {@see \Prado\Caching\TCache} harnesses.
 *
 * {@see $fakeNow} pins {@see time()} and {@see $fakeMicrotime} pins {@see microtime()}; either left null
 * falls back to the real system clock. The two knobs are independent so a harness can drive expiry (whole
 * seconds) and least-recently-used ordering (sub-second) separately. {@see now()}, {@see timezone()}, and
 * {@see sleep()} keep the {@see TClockTrait} system-time defaults, which cache code does not read.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestCacheClock extends TComponent implements IClock
{
	use TClockTrait;

	/** @var ?int when set, {@see time()} returns this instead of the real clock */
	public ?int $fakeNow = null;

	/** @var ?float when set, {@see microtime()} returns this instead of the real clock */
	public ?float $fakeMicrotime = null;

	public function time(): int
	{
		return $this->fakeNow ?? \time();
	}

	public function microtime(): float
	{
		return $this->fakeMicrotime ?? \microtime(true);
	}
}
