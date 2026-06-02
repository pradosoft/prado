<?php

/**
 * TTestCacheClockTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * TTestCacheClockTrait provides a fakeable clock for {@see \Prado\Caching\TCache} harnesses.
 *
 * It overrides the protected {@see \Prado\Caching\TCache::time()} /
 * {@see \Prado\Caching\TCache::microtime()} seams behind the public {@see $fakeNow} /
 * {@see $fakeMicrotime} fields so expiry and LRU behavior can be driven deterministically,
 * and exposes them via {@see pubTime()} / {@see pubMicrotime()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TTestCacheClockTrait
{
	/** @var ?int when set, {@see time()} returns this instead of the real clock */
	public ?int $fakeNow = null;

	/** @var ?float when set, {@see microtime()} returns this instead of the real clock */
	public ?float $fakeMicrotime = null;

	protected function time(): int
	{
		return $this->fakeNow ?? parent::time();
	}

	protected function microtime(): float
	{
		return $this->fakeMicrotime ?? parent::microtime();
	}

	public function pubTime(): int
	{
		return $this->time();
	}

	public function pubMicrotime(): float
	{
		return $this->microtime();
	}
}
