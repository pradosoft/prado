<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\Util\Clock\TMockClock;

/**
 * A pinned TMockClock that records each sleep and runs an optional callback after it, so a
 * test publishes an event while a poll loop waits. A sleep also advances {@see $elapsed}, the
 * monotonic clock of the same timeline; setting the wall time does not.
 */
class TServerEventMockClock extends TMockClock
{
	/** @var float[] */
	public array $sleeps = [];

	/** @var ?callable */
	public $onSleep = null;

	public TServerEventMockMonotonicClock $elapsed;

	public function __construct(float $microtime = 1700000000.0)
	{
		parent::__construct();
		$this->setMicrotime($microtime);
		$this->elapsed = new TServerEventMockMonotonicClock();
	}

	public function sleep(float|int $seconds): void
	{
		$this->sleeps[] = (float) $seconds;
		$this->elapsed->advance((float) $seconds);
		parent::sleep($seconds);
		if ($this->onSleep !== null) {
			($this->onSleep)($this, count($this->sleeps));
		}
	}
}
