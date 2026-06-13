<?php

/**
 * TResponseStubTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Tests for {@see TResponseStub}, the appendHeader-recording response stand-in.
 */
class TResponseStubTest extends PHPUnit\Framework\TestCase
{
	public function testCapturedCallsDefaultEmpty(): void
	{
		self::assertSame([], (new TResponseStub())->capturedCalls);
	}

	public function testAppendHeaderRecordsCall(): void
	{
		$stub = new TResponseStub();
		$stub->appendHeader('X-Test: 1', true);

		self::assertSame(
			[['header' => 'X-Test: 1', 'replace' => true]],
			$stub->capturedCalls,
		);
	}

	public function testAppendHeaderRecordsCallsInOrderPreservingReplaceFlag(): void
	{
		$stub = new TResponseStub();
		$stub->appendHeader('X-First: a', false);
		$stub->appendHeader('X-Second: b', true);

		self::assertSame(
			[
				['header' => 'X-First: a', 'replace' => false],
				['header' => 'X-Second: b', 'replace' => true],
			],
			$stub->capturedCalls,
		);
	}
}
