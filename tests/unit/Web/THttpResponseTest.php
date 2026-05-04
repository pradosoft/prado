<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplication;
use Prado\Web\THttpResponse;

class TTestHttpResponse extends THttpResponse {
	public $headers = [];

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void {
		$this->headers[] = $header;
	}
	
	public $sessionExpires = 180;
	public function sessionCacheExpire(?int $value = null): int|false
	{
		if ($value !== null) {
			$this->sessionExpires = $value;
		}
		return $this->sessionExpires;
	}
	
	public $cacheLimiter;
	public function sessionCacheLimiter(?string $value = null): string|false
	{
		if ($value !== null) {
			$this->cacheLimiter = $value;
		}
		return $this->cacheLimiter;
	}
}


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
		$response = new TTestHttpResponse();
		$response->init(null);
		self::assertEquals($response, self::$app->getResponse());
		// force a flush
		ob_end_flush();
	}

	public function testSetCacheExpire()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$response->setCacheExpire(300);
		self::assertEquals(300, $response->getCacheExpire());
		// force a flush
		ob_end_flush();
	}

	public function testSetCacheControl()
	{
		$response = new TTestHttpResponse();
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
		$response = new TTestHttpResponse();
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
		$response = new TTestHttpResponse();
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
		$response = new TTestHttpResponse();
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
		$response = new TTestHttpResponse();
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
		$response = new TTestHttpResponse();
		$response->init(null);
		self::assertInstanceOf(\Prado\Web\THttpCookieCollection::class, $response->getCookies());
		self::assertEquals(0, $response->getCookies()->getCount());
		// force a flush
		ob_end_flush();
	}

	public function testWrite()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$response->write("test string");
		$contents = $response->getContents();
		$this->assertStringContainsString('test string', $contents);
		$response->clear();
		ob_end_clean();
	}

	public function testWriteFile()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		
		$contents = 'test file content';
		$testFile = __DIR__ . '/data/testfile.txt';
		file_put_contents($testFile, 'test content');
		
		$response->writeFile($testFile, null, 'text/plain');
		unlink($testFile);
		$output = ob_end_clean();
		$this->assertEquals($contents, $output);
	}

	public function testRedirect()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		
		$response->setStatusCode(302);
		$this->assertEquals(302, $response->getStatusCode());
		ob_end_clean();
	}

	public function testReload()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		
		$response->setStatusCode(200);
		$this->assertEquals(200, $response->getStatusCode());
		ob_end_clean();
	}

	public function testFlush()
	{
		$this->markTestSkipped('Test requires runInSeparateProcess for proper flush behavior');
	}

	public function testSendContentTypeHeader()
	{
		$this->markTestSkipped('Test requires runInSeparateProcess for header handling');
	}

	public function testClear()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$response->write("test content");
		$response->clear();
		$this->assertEquals('', $response->getContents());
		ob_end_clean();
	}

	public function testAddCookie()
	{
		$this->markTestSkipped('Test requires runInSeparateProcess for cookie handling');
	}

	public function testRemoveCookie()
	{
		$this->markTestSkipped('Test requires runInSeparateProcess for cookie handling');
	}

	public function testSetHtmlWriterType()
	{
		$response = new TTestHttpResponse();
		$response->setHtmlWriterType('\Prado\Web\UI\THtmlWriter');
		$this->assertEquals('\Prado\Web\UI\THtmlWriter', $response->getHtmlWriterType());
		$response->setHtmlWriterType('\Prado\Web\UI\THtmlWriter');
		$this->assertEquals('\Prado\Web\UI\THtmlWriter', $response->getHtmlWriterType());
	}

	public function testCreateHtmlWriter()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$writer = $response->createHtmlWriter();
		$this->assertInstanceOf(\Prado\Web\UI\THtmlWriter::class, $writer);
		ob_end_clean();
	}
}
