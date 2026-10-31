<?php

namespace Prado\Test\Unit\Web\Services;

use Prado\Util\Clock\TMockClock;

/**
 * A pinned TMockClock that records each sleep and runs an optional callback after it, so a
 * test publishes an event while a poll loop waits.
 */
class TServerEventMockClock extends TMockClock
{
	/** @var float[] */
	public array $sleeps = [];

	/** @var ?callable */
	public $onSleep = null;

	public function __construct(float $microtime = 1700000000.0)
	{
		parent::__construct();
		$this->setMicrotime($microtime);
	}

	public function sleep(float|int $seconds): void
	{
		$this->sleeps[] = (float) $seconds;
		parent::sleep($seconds);
		if ($this->onSleep !== null) {
			($this->onSleep)($this, count($this->sleeps));
		}
	}
}
