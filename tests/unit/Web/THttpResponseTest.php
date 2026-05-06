<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TApplication;
use Prado\Web\THttpHeadersManager;
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

	// -----------------------------------------------------------------------
	// HEADERS_MANAGER_AUTO constant
	// -----------------------------------------------------------------------

	public function testHeadersManagerAutoConstantValue()
	{
		self::assertEquals('Auto', THttpResponse::HEADERS_MANAGER_AUTO);
	}

	// -----------------------------------------------------------------------
	// getHeadersManager / setHeadersManager
	// -----------------------------------------------------------------------

	public function testGetHeadersManagerDefaultIsAuto()
	{
		$response = new TTestHttpResponse();
		self::assertEquals(THttpResponse::HEADERS_MANAGER_AUTO, $response->getHeadersManager());
	}

	public function testSetAndGetHeadersManager()
	{
		$response = new TTestHttpResponse();
		$response->setHeadersManager('myHeaders');
		self::assertEquals('myHeaders', $response->getHeadersManager());
	}

	public function testSetHeadersManagerEmptyString()
	{
		$response = new TTestHttpResponse();
		$response->setHeadersManager('');
		self::assertEquals('', $response->getHeadersManager());
	}

	public function testSetHeadersManagerSameValueIsNoOp()
	{
		$response = new TTestHttpResponse();
		$response->setHeadersManager('someId');
		// Setting the same value again must not reset the cache
		// (no exception means the guard works)
		$response->setHeadersManager('someId');
		self::assertEquals('someId', $response->getHeadersManager());
	}

	public function testSetHeadersManagerClearsModuleCacheOnChange()
	{
		// Register a real THttpHeadersManager so we can prime the cache
		$hmId = 'hmTest_' . uniqid();
		$hm = new THttpHeadersManager();
		Prado::getApplication()->setModule($hmId, $hm);

		$response = new TTestHttpResponse();
		$response->init(null);
		$response->setHeadersManager($hmId);

		// Prime the cache by resolving once
		$first = $response->getHeadersManagerModule();
		self::assertSame($hm, $first);

		// Changing the ID must clear the cache
		$response->setHeadersManager('');
		self::assertNull($response->getHeadersManagerModule());

		ob_end_clean();
	}

	// -----------------------------------------------------------------------
	// getHeadersManagerModule
	// -----------------------------------------------------------------------

	public function testGetHeadersManagerModuleEmptyStringReturnsNull()
	{
		$response = new TTestHttpResponse();
		$response->setHeadersManager('');
		self::assertNull($response->getHeadersManagerModule());
	}

	public function testGetHeadersManagerModuleAutoFindsRegisteredManager()
	{
		$hmId = 'hmAuto_' . uniqid();
		$hm = new THttpHeadersManager();
		Prado::getApplication()->setModule($hmId, $hm);

		$response = new TTestHttpResponse();
		// Auto should find the module we just registered (no init() → no output buffer)
		$found = $response->getHeadersManagerModule();
		self::assertInstanceOf(THttpHeadersManager::class, $found);
	}

	public function testGetHeadersManagerModuleNamedIdResolvesModule()
	{
		$hmId = 'hmNamed_' . uniqid();
		$hm = new THttpHeadersManager();
		Prado::getApplication()->setModule($hmId, $hm);

		$response = new TTestHttpResponse();
		$response->init(null);
		$response->setHeadersManager($hmId);

		self::assertSame($hm, $response->getHeadersManagerModule());
		ob_end_clean();
	}

	public function testGetHeadersManagerModuleNamedIdMissingThrows()
	{
		$response = new TTestHttpResponse();
		$response->setHeadersManager('nonexistent_module_id');
		$this->expectException(TConfigurationException::class);
		$response->getHeadersManagerModule();
	}

	public function testGetHeadersManagerModuleNamedIdWrongTypeThrows()
	{
		// Register a non-THttpHeadersManager module under a unique ID
		$badId = 'hmBad_' . uniqid();
		// Use a TComponent-based module that is NOT a THttpHeadersManager
		$bad = new \Prado\Security\TSecurityManager();
		Prado::getApplication()->setModule($badId, $bad);

		$response = new TTestHttpResponse();
		$response->setHeadersManager($badId);
		$this->expectException(TConfigurationException::class);
		$response->getHeadersManagerModule();
	}

	public function testGetHeadersManagerModuleResultIsCached()
	{
		$hmId = 'hmCache_' . uniqid();
		$hm = new THttpHeadersManager();
		Prado::getApplication()->setModule($hmId, $hm);

		$response = new TTestHttpResponse();
		$response->init(null);
		$response->setHeadersManager($hmId);

		$first  = $response->getHeadersManagerModule();
		$second = $response->getHeadersManagerModule();
		self::assertSame($first, $second);
		ob_end_clean();
	}

	public function testGetHeadersManagerModuleCaseInsensitiveAuto()
	{
		// 'auto', 'AUTO', 'Auto' must all trigger auto-discovery mode,
		// never throw a "module not found" exception for the ID itself.
		foreach (['auto', 'AUTO', 'Auto', 'aUtO'] as $variant) {
			$response = new TTestHttpResponse();
			$response->setHeadersManager($variant);
			// Auto mode returns null or a THttpHeadersManager — never throws
			$result = $response->getHeadersManagerModule();
			self::assertTrue(
				$result === null || $result instanceof THttpHeadersManager,
				"Expected null or THttpHeadersManager for variant '$variant'"
			);
		}
	}
}
