<?php

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\THttpSession;
use Prado\Web\THttpSessionHandler;

class THttpSessionHandlerTestSession extends THttpSession
{
	public array $calls = [];
	public mixed $gcResult = true;

	public function _open($savePath, $sessionName)
	{
		$this->calls[] = ['open', $savePath, $sessionName];
		return true;
	}

	public function _close()
	{
		$this->calls[] = ['close'];
		return true;
	}

	public function _read($id)
	{
		$this->calls[] = ['read', $id];
		return 'data-' . $id;
	}

	public function _write($id, $data)
	{
		$this->calls[] = ['write', $id, $data];
		return false;
	}

	public function _destroy($id)
	{
		$this->calls[] = ['destroy', $id];
		return true;
	}

	public function _gc($maxLifetime)
	{
		$this->calls[] = ['gc', $maxLifetime];
		return $this->gcResult;
	}
}

class THttpSessionHandlerTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructRequiresASession()
	{
		try {
			new THttpSessionHandler(new \stdClass());
			$this->fail('TInvalidDataTypeException was not thrown');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('httpsession_handler_invalid', $e->getErrorCode());
		}
	}

	public function testForwardsEveryCallToTheSession()
	{
		$session = new THttpSessionHandlerTestSession();
		$handler = new THttpSessionHandler($session);
		$this->assertTrue($handler->open('/path', 'NAME'));
		$this->assertSame('data-abc', $handler->read('abc'));
		$this->assertFalse($handler->write('abc', 'payload'));
		$this->assertTrue($handler->destroy('abc'));
		$this->assertTrue($handler->close());
		$this->assertSame([
			['open', '/path', 'NAME'],
			['read', 'abc'],
			['write', 'abc', 'payload'],
			['destroy', 'abc'],
			['close'],
		], $session->calls);
	}

	public function testGcReturnsZeroForTrueAndTheCountOtherwise()
	{
		$session = new THttpSessionHandlerTestSession();
		$handler = new THttpSessionHandler($session);
		$this->assertSame(0, $handler->gc(60));
		$session->gcResult = 5;
		$this->assertSame(5, $handler->gc(60));
		$session->gcResult = false;
		$this->assertFalse($handler->gc(60));
		$this->assertSame([['gc', 60], ['gc', 60], ['gc', 60]], $session->calls);
	}
}
