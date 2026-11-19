<?php

namespace Prado\Test\Unit\Util\Cron\Wake;

use Prado\IO\Async\TAsync;
use Prado\Util\Cron\Wake\TCronWakePoll;

/**
 * A TCronWakePoll whose stamp is a counter in memory.
 */
class TCronWakePollTestTransport extends TCronWakePoll
{
	public int $stamp = 0;

	protected function readStamp(): mixed
	{
		return $this->stamp;
	}

	protected function writeStamp(): bool
	{
		$this->stamp++;
		return true;
	}

	protected function getIsAvailable(): bool
	{
		return true;
	}
}

/**
 * Tests for {@see TCronWakePoll}: the waits whose poll loop runs one slice.
 */
class TCronWakePollTest extends \PHPUnit\Framework\TestCase
{
	public function testAFirstWaitWokenInItsOnlySlice()
	{
		$t = (new TCronWakePollTestTransport())->setPollInterval(0.5);
		TAsync::getLoop()->delay(0.01, static fn () => $t->signal(microtime(true)));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 0.2, $start), 'a stamp written during the slice wakes the wait');
		self::assertLessThan(0.5, microtime(true) - $start, 'the slice is the remaining time, not the poll interval');
	}

	public function testALaterWaitWithAnUnchangedStampPollsOneSlice()
	{
		$t = (new TCronWakePollTestTransport())->setPollInterval(0.5);
		$now = microtime(true);
		self::assertFalse($t->wait($now, $now), 'the first wait takes the stamp as seen');

		TAsync::getLoop()->delay(0.01, static fn () => $t->signal(microtime(true)));
		$start = microtime(true);
		self::assertTrue($t->wait($start + 0.2, $start), 'an unchanged stamp polls, and a write during the slice wakes it');

		$start = microtime(true);
		self::assertFalse($t->wait($start + 0.02, $start), 'no write during the one slice times out');
		self::assertGreaterThanOrEqual(0.015, microtime(true) - $start);
		$t->close();
	}
}
