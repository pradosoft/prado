<?php

/**
 * TTestMemorySessionTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\THttpSession;

/**
 * Tests for {@see TTestMemorySession}, the array-backed {@see THttpSession} fixture.
 */
class TTestMemorySessionTest extends PHPUnit\Framework\TestCase
{
	private function newSession(): TTestMemorySession
	{
		return new TTestMemorySession();
	}

	public function testIsHttpSessionSubclass(): void
	{
		self::assertInstanceOf(THttpSession::class, $this->newSession());
	}

	public function testFlagsDefaultFalse(): void
	{
		$session = $this->newSession();
		self::assertFalse($session->opened);
		self::assertFalse($session->destroyed);
		self::assertFalse($session->regenerated);
		self::assertSame([], $session->data);
	}

	public function testOpenSetsFlagWithoutStartingPhpSession(): void
	{
		$session = $this->newSession();
		$session->open();
		self::assertTrue($session->opened);
	}

	public function testAddAndItemAtRoundTrip(): void
	{
		$session = $this->newSession();
		$session->add('key', 'value');
		self::assertSame('value', $session->itemAt('key'));
		self::assertSame(['key' => 'value'], $session->data);
	}

	public function testItemAtReturnsNullForMissingKey(): void
	{
		self::assertNull($this->newSession()->itemAt('absent'));
	}

	public function testRemoveReturnsAndDeletesValue(): void
	{
		$session = $this->newSession();
		$session->add('key', 'value');

		self::assertSame('value', $session->remove('key'));
		self::assertNull($session->itemAt('key'));
		self::assertArrayNotHasKey('key', $session->data);
	}

	public function testRemoveReturnsNullForMissingKey(): void
	{
		self::assertNull($this->newSession()->remove('absent'));
	}

	public function testDestroyFlagsAndClearsData(): void
	{
		$session = $this->newSession();
		$session->add('key', 'value');

		$session->destroy();

		self::assertTrue($session->destroyed);
		self::assertSame([], $session->data);
	}

	public function testRegenerateFlagsAndReturnsId(): void
	{
		$session = $this->newSession();
		self::assertSame('regenerated-id', $session->regenerate(true));
		self::assertTrue($session->regenerated);
	}
}
