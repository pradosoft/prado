<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Exceptions;

use Prado\Exceptions\TNetworkException;
use Prado\Exceptions\TSocketException;
use Prado\Test\Unit\PradoUnit;

/**
 * A TSocketException that reports the sockets extension as absent.
 */
class TSocketExceptionWithoutSockets extends TSocketException
{
	protected function getSocketsLoaded(): bool
	{
		return false;
	}
}

/**
 * Tests {@see TSocketException}: a given message, the `socket_strerror()` message,
 * and the message without the sockets extension.
 */
class TSocketExceptionTest extends \PHPUnit\Framework\TestCase
{
	public function testIsANetworkException(): void
	{
		$this->assertInstanceOf(TNetworkException::class, new TSocketException(111, 'refused'));
	}

	public function testAGivenMessageIsTheMessage(): void
	{
		$ex = new TSocketException(111, 'Unable to connect to tcp://localhost:1');
		$this->assertSame('Unable to connect to tcp://localhost:1', $ex->getMessage());
		$this->assertSame(111, $ex->getCode());
	}

	public function testWithoutAMessageTheMessageIsSocketStrerror(): void
	{
		if (!extension_loaded('sockets')) {
			$this->markTestSkipped('The sockets extension is not loaded.');
		}
		$ex = new TSocketException(111);
		$this->assertSame(socket_strerror(111), $ex->getMessage());
		$this->assertSame(111, $ex->getCode());
	}

	public function testWithoutAMessageAndSocketsTheMessageNamesTheCode(): void
	{
		$ex = new TSocketExceptionWithoutSockets(111);
		$this->assertSame('Socket error 111.', $ex->getMessage());
		$this->assertSame('socketexception_error_code', $ex->getErrorCode());
		$this->assertSame(['111'], $ex->getErrorArguments());
		$this->assertSame(111, $ex->getCode());
	}

	public function testGetSocketsLoadedIsWhetherTheExtensionIsLoaded(): void
	{
		$ex = new TSocketException(0, 'x');
		$this->assertSame(extension_loaded('sockets'), PradoUnit::invoke($ex, 'getSocketsLoaded'));
	}
}
