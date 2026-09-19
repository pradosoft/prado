<?php

/**
 * TTestCacheClockTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

/**
 * TTestCacheClockTrait provides a fakeable clock for {@see \Prado\Caching\TCache} harnesses.
 *
 * Setting {@see setFakeNow FakeNow} or {@see setFakeMicrotime FakeMicrotime} installs a
 * {@see TTestCacheClock} on the cache (through {@see \Prado\Util\Clock\TClockAwareTrait::setClock()}),
 * so expiry and least-recently-used behavior run deterministically. The two knobs are independent, and
 * {@see pubTime()} / {@see pubMicrotime()} expose what the cache reads through its clock.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TTestCacheClockTrait
{
	/** @var ?TTestCacheClock the fake clock, installed on first use of a fake knob */
	private ?TTestCacheClock $_fakeClock = null;

	/**
	 * @return TTestCacheClock the fake clock, creating and installing it on first use.
	 */
	private function fakeCacheClock(): TTestCacheClock
	{
		if ($this->_fakeClock === null) {
			$this->_fakeClock = new TTestCacheClock();
			$this->setClock($this->_fakeClock);
		}
		return $this->_fakeClock;
	}

	/**
	 * @param ?int $value the Unix timestamp {@see \Prado\Caching\TCache::time()} reads, or null for the real clock
	 */
	public function setFakeNow(?int $value): void
	{
		$this->fakeCacheClock()->fakeNow = $value;
	}

	/**
	 * @return ?int the pinned timestamp, or null when none is set
	 */
	public function getFakeNow(): ?int
	{
		return $this->_fakeClock?->fakeNow;
	}

	/**
	 * @param ?float $value the timestamp with microseconds the cache reads, or null for the real clock
	 */
	public function setFakeMicrotime(?float $value): void
	{
		$this->fakeCacheClock()->fakeMicrotime = $value;
	}

	/**
	 * @return ?float the pinned sub-second timestamp, or null when none is set
	 */
	public function getFakeMicrotime(): ?float
	{
		return $this->_fakeClock?->fakeMicrotime;
	}

	public function pubTime(): int
	{
		return $this->getClock()->time();
	}

	public function pubMicrotime(): float
	{
		return $this->getClock()->microtime();
	}
}
