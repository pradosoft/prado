<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplication;
use Prado\Web\THttpResponse;

class THttpResponseTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app');
		}
	}

	protected function tearDown(): void
	{
	}

	public function testInit()
	{
		$response = new THttpResponse();
		$response->init(null);
		self::assertEquals($response, self::$app->getResponse());
		// force a flush
		ob_end_flush();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetCacheExpire()
	{
		$response = new THttpResponse();
		$response->init(null);
		$response->setCacheExpire(300);
		self::assertEquals(300, $response->getCacheExpire());
		// force a flush
		ob_end_flush();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetCacheControl()
	{
		$response = new THttpResponse();
		$response->init(null);
		foreach (['none', 'nocache', 'private', 'private_no_expire', 'public'] as $cc) {
			$response->setCacheControl($cc);
			self::assertEquals($cc, $response->getCacheControl());
		}
		try {
			$response->setCacheControl('invalid');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}

		// force a flush
		ob_end_flush();
	}

	public function testSetContentType()
	{
		$response = new THttpResponse();
		$response->init(null);
		$response->setContentType('image/jpeg');
		self::assertEquals('image/jpeg', $response->getContentType());
		$response->setContentType('text/plain');
		self::assertEquals('text/plain', $response->getContentType());
		// force a flush
		ob_end_flush();
	}

	public function testSetCharset()
	{
		$response = new THttpResponse();
		$response->init(null);
		$response->setCharset('UTF-8');
		self::assertEquals('UTF-8', $response->getCharset());
		$response->setCharset('ISO8859-1');
		self::assertEquals('ISO8859-1', $response->getCharset());
		// force a flush
		ob_end_flush();
	}

	public function testSetBufferOutput()
	{
		$response = new THttpResponse();
		$response->setBufferOutput(true);
		self::assertTrue($response->getBufferOutput());
		$response->init(null);
		try {
			$response->setBufferOutput(false);
			self::fail('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {
		}
		// force a flush
		ob_end_flush();
	}

	public function testSetStatusCode()
	{
		$response = new THttpResponse();
		$response->init(null);
		$response->setStatusCode(401);
		self::assertEquals(401, $response->getStatusCode());
		$response->setStatusCode(200);
		self::assertEquals(200, $response->getStatusCode());
		// force a flush
		ob_end_flush();
	}

	public function testGetCookies()
	{
		$response = new THttpResponse();
		$response->init(null);
		self::assertInstanceOf('Prado\\Web\\THttpCookieCollection', $response->getCookies());
		self::assertEquals(0, $response->getCookies()->getCount());
		// force a flush
		ob_end_flush();
	}

	public function testWrite()
	{
		// Don't know how to test headers :(( ...
		throw new PHPUnit\Framework\IncompleteTestError();

		$response = new THttpResponse();
		//self::expectOutputString("test string");
		$response->write("test string");
		self::assertContains('test string', ob_get_clean());
	}

	public function testWriteFile()
	{

	 // Don't know how to test headers :(( ...
		throw new PHPUnit\Framework\IncompleteTestError();

		$response = new THttpResponse();
		$response->setBufferOutput(true);
		// Suppress warning with headers
		$response->writeFile(__DIR__ . '/data/aTarFile.md5', null, 'text/plain', ['Pragma: public', 'Expires: 0']);

		self::assertContains('4b1ecb0b243918a8bbfbb4515937be98  aTarFile.tar', ob_get_clean());
	}

	public function testRedirect()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testReload()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testFlush()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSendContentTypeHeader()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testClear()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testAppendHeader()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testAppendLog()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testAddCookie()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testRemoveCookie()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetHtmlWriterType()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testCreateHtmlWriter()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
}
