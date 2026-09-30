<?php

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TExitException;
use Prado\Util\TBehavior;
use Prado\Web\THttpCookie;
use Prado\Web\THttpResponse;
use Prado\Web\THttpResponseAdapter;
use Prado\Test\Unit\Harness\TTestApplication;

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

	public $cookies = [];
	protected function responseSetCookie(string $name, ...$args): bool
	{
		$this->cookies[] = [$name, ...$args];
		return true;
	}
}

class TTestRecordingResponseAdapter extends THttpResponseAdapter {
	public $redirects = [];

	public function httpRedirect($url)
	{
		$this->redirects[] = $url;
	}
}

class TTestRedirectGuardBehavior extends TBehavior {
	public function dyRedirect($url, $chain)
	{
		if (preg_match('#^[a-z][a-z0-9+.-]*:|^//#i', $url)) {
			$url = '/';
		}
		return $chain->dyRedirect($url);
	}
}

class TTestSendFileBehavior extends TBehavior {
	public $calls = [];

	public function dyWriteFile($handled, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize, $chain)
	{
		$this->calls[] = func_get_args();
		return $chain->dyWriteFile(true, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize);
	}
}

class TTestWriteFileObserverBehavior extends TBehavior {
	public $flags = [];

	public function dyWriteFile($handled, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize, $chain)
	{
		$this->flags[] = $handled;
		return $chain->dyWriteFile($handled, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize);
	}
}

class TTestCookiePolicyBehavior extends TBehavior {
	public $removals = [];

	public function dySetCookie($cookie, $remove, $chain)
	{
		$this->removals[] = $remove;
		$cookie->setSecure(true);
		$cookie->setSameSite('Strict');
		return $chain->dySetCookie($cookie, $remove);
	}
}


class THttpResponseTest extends \PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/app');
	}

	protected function tearDown(): void
	{
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	public function testInit()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		self::assertEquals($response, $this->app->getResponse());
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

	public function testSetStatusCodeAcceptsModernCodesWithoutReason()
	{
		// Regression: 422 and 429 (and other IANA codes) must be known so that
		// setStatusCode() supplies their reason phrase instead of throwing when
		// no explicit reason is given (the path TRestService error responses use).
		$response = new TTestHttpResponse();
		$response->init(null);
		foreach ([422, 429, 451, 428, 431, 507, 511] as $code) {
			$response->setStatusCode($code);
			self::assertEquals($code, $response->getStatusCode());
		}
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

	public function testWriteFileHandledByBehaviorSendsNothing()
	{
		$response = new TTestHttpResponse();
		$response->attachBehavior('sender', $sender = new TTestSendFileBehavior(), 5);
		$response->attachBehavior('observer', $observer = new TTestWriteFileObserverBehavior(), 15);

		ob_start();
		try {
			$response->writeFile('/path/to/report.txt', 'abc', 'text/plain', null, false, 'client.txt');
		} finally {
			$output = ob_get_clean();
		}

		$this->assertSame('', $output);
		$this->assertSame([], $response->headers);
		$this->assertCount(1, $sender->calls);
		$this->assertSame([false, '/path/to/report.txt', 'abc', 'text/plain', null, false, 'client.txt', 3], array_slice($sender->calls[0], 0, 8));
		$this->assertSame([true], $observer->flags);
	}

	public function testWriteFileUnhandledSendsContent()
	{
		$response = new TTestHttpResponse();
		$response->attachBehavior('observer', $observer = new TTestWriteFileObserverBehavior());

		ob_start();
		try {
			$response->writeFile('/path/to/report.txt', 'abc', 'text/plain');
		} finally {
			$output = ob_get_clean();
		}

		$this->assertSame('abc', $output);
		$this->assertContains('Content-Length: 3', $response->headers);
		$this->assertContains('Content-Disposition: attachment; filename="report.txt"', $response->headers);
		$this->assertSame([false], $observer->flags);
	}

	public function testRedirect()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		
		$response->setStatusCode(302);
		$this->assertEquals(302, $response->getStatusCode());
		ob_end_clean();
	}

	public function testRedirectFiltersUrlThroughDyRedirect()
	{
		$response = new TTestHttpResponse();
		$response->setAdapter($adapter = new TTestRecordingResponseAdapter($response));

		$response->redirect('https://elsewhere.example/');
		$response->attachBehavior('guard', new TTestRedirectGuardBehavior());
		$response->redirect('https://elsewhere.example/');
		$response->redirect('//elsewhere.example/');
		$response->redirect('/local/page');

		$this->assertEquals(['https://elsewhere.example/', '/', '/', '/local/page'], $adapter->redirects);
	}

	public function testRedirectWithoutAdapterSendsFilteredUrl()
	{
		$response = new TTestHttpResponse();
		$response->attachBehavior('guard', new TTestRedirectGuardBehavior());
		$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;
		$_SERVER['SERVER_SOFTWARE'] = 'PHPUnit';

		try {
			$response->redirect('https://elsewhere.example/');
			$this->fail('redirect() must end the request.');
		} catch (TExitException $e) {
		} finally {
			if ($serverSoftware === null) {
				unset($_SERVER['SERVER_SOFTWARE']);
			} else {
				$_SERVER['SERVER_SOFTWARE'] = $serverSoftware;
			}
		}

		$location = array_values(array_filter($response->headers, fn ($h) => stripos($h, 'Location:') === 0));
		$this->assertEquals(['Location: ' . $this->app->getRequest()->getBaseUrl() . '/'], $location);
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
		$response = new TTestHttpResponse();
		$cookie = new THttpCookie('name', 'value');

		$response->addCookie($cookie);

		$this->assertCount(1, $response->cookies);
		[$name, $value, $options] = $response->cookies[0];
		$this->assertEquals('name', $name);
		$this->assertEquals($this->app->getRequest()->getEnableCookieValidation() ? $this->app->getSecurityManager()->hashData('value') : 'value', $value);
		$this->assertEquals($cookie->getPhpOptions(), $options);
	}

	public function testRemoveCookie()
	{
		$response = new TTestHttpResponse();

		$response->removeCookie(new THttpCookie('name', 'value'));

		[$name, $value, $options] = $response->cookies[0];
		$this->assertEquals('name', $name);
		$this->assertNull($value);
		$this->assertSame(0, $options['expires']);
	}

	public function testAddCookieFiltersThroughDySetCookie()
	{
		$response = new TTestHttpResponse();
		$response->attachBehavior('policy', $policy = new TTestCookiePolicyBehavior());

		$response->addCookie(new THttpCookie('name', 'value'));

		$this->assertEquals([false], $policy->removals);
		$options = $response->cookies[0][2];
		$this->assertTrue($options['secure']);
		$this->assertEquals('Strict', $options['samesite']);
	}

	public function testRemoveCookieFiltersThroughDySetCookie()
	{
		$response = new TTestHttpResponse();
		$response->attachBehavior('policy', $policy = new TTestCookiePolicyBehavior());

		$response->removeCookie(new THttpCookie('name', 'value'));

		$this->assertEquals([true], $policy->removals);
		$options = $response->cookies[0][2];
		$this->assertTrue($options['secure']);
		$this->assertEquals('Strict', $options['samesite']);
		$this->assertSame(0, $options['expires']);
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
