<?php

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TExitException;
use Prado\I18N\TGlobalization;
use Prado\Util\TBehavior;
use Prado\Web\THttpCookie;
use Prado\Web\THttpResponse;
use Prado\Web\TMediaType;
use Prado\Web\THttpResponseAdapter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Util\TTestNullDynamicBehavior;
use Prado\Test\Unit\PradoUnit;

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
	public $flushes = [];
	public $writers = [];

	public function httpRedirect($url)
	{
		$this->redirects[] = $url;
	}

	public function flushContent($continueBuffering = true)
	{
		$this->flushes[] = $continueBuffering;
	}

	public function createNewHtmlWriter($type, $writer)
	{
		$this->writers[] = $type;
		return parent::createNewHtmlWriter($type, $writer);
	}
}

class TTestFlushRecordingBehavior extends TBehavior {
	public $calls = [];

	public function dyFlushContent($continueBuffering, $chain)
	{
		$this->calls[] = $continueBuffering;
		return $chain->dyFlushContent($continueBuffering);
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

	public function testGetHttpHeaderSent()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		self::assertFalse($response->getHttpHeaderSent());
		$response->ensureHeadersSent();
		self::assertTrue($response->getHttpHeaderSent());
		ob_end_clean();
	}

	public function testSetStatusCodeAfterHttpHeaderSentThrows()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$response->ensureHeadersSent();
		try {
			$this->expectException(TInvalidOperationException::class);
			$this->expectExceptionMessage('THttpResponse.StatusCode cannot change after the HTTP header is sent.');
			$response->setStatusCode(500);
		} finally {
			ob_end_clean();
		}
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

	public function testSetStatusCodeRejectsLineBreakAtReasonStart()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$rejected = 0;
		foreach (["\r\nX-Injected: 1", "\nX-Injected: 1", "OK\r\nX-Injected: 1"] as $reason) {
			try {
				$response->setStatusCode(299, $reason);
			} catch (TInvalidDataValueException $e) {
				$rejected++;
			}
		}
		self::assertSame(3, $rejected);
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
		$file = $this->rangeFile('test file content');
		try {
			$this->assertSame('test file content', $this->captureWriteFile($response, $file, null, 'text/plain'));
		} finally {
			unlink($file);
		}
		$this->assertContains('Content-Type: text/plain', $response->headers);
		$this->assertContains('Content-Length: 17', $response->headers);
		$this->assertContains('Content-Disposition: attachment; filename="' . basename($file) . '"', $response->headers);
		$this->assertContains('Pragma: public', $response->headers);
	}

	public function testWriteFileInlineWithHeaders()
	{
		$response = new TTestHttpResponse();
		$output = $this->captureWriteFile($response, '/path/to/report.txt', 'abc', 'text/plain', ['X-Custom: 1'], false, '/other/name.txt', -1);

		$this->assertSame('abc', $output);
		$this->assertContains('X-Custom: 1', $response->headers);
		$this->assertNotContains('Pragma: public', $response->headers);
		$this->assertContains('Content-Length: 3', $response->headers);
		$this->assertContains('Content-Disposition: inline; filename="name.txt"', $response->headers);
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

	public function testWriteFileDetectsMimeType()
	{
		$response = new TTestHttpResponse();
		$this->captureWriteFile($response, '/path/to/report.PDF', 'abc');
		$this->assertContains('Content-Type: application/pdf', $response->headers);

		$response = new TTestHttpResponse();
		$this->captureWriteFile($response, '/path/to/report.unknown', 'abc');
		$this->assertContains('Content-Type: text/plain', $response->headers);

		$response = new TTestHttpResponse();
		$this->captureWriteFile($response, '/path/to/report', 'abc');
		$this->assertContains('Content-Type: text/plain', $response->headers);

		$file = $this->rangeFile();
		try {
			$response = new TTestHttpResponse();
			$this->assertSame(self::RANGE_BODY, $this->captureWriteFile($response, $file));
			// Without fileinfo, a file with no known extension is text/plain.
			$this->assertContains('Content-Type: ' . (function_exists('mime_content_type') ? mime_content_type($file) : TMediaType::PLAIN), $response->headers);
		} finally {
			unlink($file);
		}

		$response = new TTestHttpResponse();
		$response->attachBehavior('sender', $sender = new TTestSendFileBehavior());
		$this->captureWriteFile($response, '/missing/report.css', null, null, null, true, null, 10);
		$this->assertSame(TMediaType::CSS, $sender->calls[0][3]);
	}

	// -----------------------------------------------------------------------
	// writeFile() byte ranges
	// -----------------------------------------------------------------------

	private const RANGE_BODY = '0123456789abcdefghij';
	private const RANGE_MTIME = 1790000000;

	/**
	 * Sets the request method and request headers that writeFile() reads, runs the test
	 * body, and restores the request.
	 */
	private function withRequest(string $method, array $headers, callable $test): void
	{
		$request = $this->app->getRequest();
		$hadMethod = array_key_exists('REQUEST_METHOD', $_SERVER);
		$method0 = $_SERVER['REQUEST_METHOD'] ?? null;
		$headers0 = PradoUnit::getProp($request, '_headers');
		$_SERVER['REQUEST_METHOD'] = $method;
		PradoUnit::setProp($request, '_headers', $headers);
		try {
			$test();
		} finally {
			PradoUnit::setProp($request, '_headers', $headers0);
			if ($hadMethod) {
				$_SERVER['REQUEST_METHOD'] = $method0;
			} else {
				unset($_SERVER['REQUEST_METHOD']);
			}
		}
	}

	private function rangeFile(string $body = self::RANGE_BODY): string
	{
		$file = tempnam(sys_get_temp_dir(), 'prado-range');
		file_put_contents($file, $body);
		touch($file, self::RANGE_MTIME);
		return $file;
	}

	private function captureWriteFile(THttpResponse $response, mixed ...$args): string
	{
		ob_start();
		try {
			$response->writeFile(...$args);
		} finally {
			$output = ob_get_clean();
		}
		return $output;
	}

	private function lastModified(): string
	{
		return gmdate('D, d M Y H:i:s', self::RANGE_MTIME) . ' GMT';
	}

	public function testAcceptRangesProperty()
	{
		$response = new TTestHttpResponse();
		$this->assertTrue($response->getAcceptRanges());
		$response->setAcceptRanges('false');
		$this->assertFalse($response->getAcceptRanges());
		$response->setAcceptRanges(true);
		$this->assertTrue($response->getAcceptRanges());
	}

	public function testWriteFileWithoutRangeSendsFullFileAndAdvertisesRanges()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', [], function () use ($file) {
				$response = new TTestHttpResponse();
				$output = $this->captureWriteFile($response, $file, null, 'text/plain');

				$this->assertSame(self::RANGE_BODY, $output);
				$this->assertSame(200, $response->getStatusCode());
				$this->assertContains('Accept-Ranges: bytes', $response->headers);
				$this->assertContains('Last-Modified: ' . $this->lastModified(), $response->headers);
				$this->assertContains('Content-Length: 20', $response->headers);
				$this->assertEmpty(preg_grep('/^Content-Range:/', $response->headers));
			});
		} finally {
			unlink($file);
		}
	}

	public function testWriteFileRangeSendsPartialContent()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', ['Range' => 'bytes=5-9'], function () use ($file) {
				$response = new TTestHttpResponse();
				$output = $this->captureWriteFile($response, $file, null, 'text/plain');

				$this->assertSame('56789', $output);
				$this->assertSame(206, $response->getStatusCode());
				$this->assertStringContainsString(' 206 Partial Content', $response->headers[0]);
				$this->assertContains('Content-Range: bytes 5-9/20', $response->headers);
				$this->assertContains('Content-Length: 5', $response->headers);
				$this->assertContains('Accept-Ranges: bytes', $response->headers);
				$this->assertContains('Content-Disposition: attachment; filename="' . basename($file) . '"', $response->headers);
			});
		} finally {
			unlink($file);
		}
	}

	public function testWriteFileSuffixAndOpenRanges()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', ['Range' => 'bytes=-4'], function () use ($file) {
				$response = new TTestHttpResponse();
				$this->assertSame('ghij', $this->captureWriteFile($response, $file));
				$this->assertContains('Content-Range: bytes 16-19/20', $response->headers);
			});
			$this->withRequest('GET', ['Range' => 'bytes=18-'], function () use ($file) {
				$response = new TTestHttpResponse();
				$this->assertSame('ij', $this->captureWriteFile($response, $file));
				$this->assertContains('Content-Range: bytes 18-19/20', $response->headers);
			});
		} finally {
			unlink($file);
		}
	}

	public function testWriteFileRangeOfContent()
	{
		$this->withRequest('GET', ['range' => 'bytes=0-2'], function () {
			$response = new TTestHttpResponse();
			$output = $this->captureWriteFile($response, '/path/to/report.txt', 'abcdef', 'text/plain');

			$this->assertSame('abc', $output);
			$this->assertSame(206, $response->getStatusCode());
			$this->assertContains('Content-Range: bytes 0-2/6', $response->headers);
			$this->assertContains('Content-Length: 3', $response->headers);
			$this->assertEmpty(preg_grep('/^Last-Modified:/', $response->headers));
		});
	}

	public function testWriteFileMergesContiguousRanges()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', ['Range' => 'bytes=8-11,2-5,6-7'], function () use ($file) {
				$response = new TTestHttpResponse();
				$output = $this->captureWriteFile($response, $file, null, 'text/plain');

				$this->assertSame('23456789ab', $output);
				$this->assertSame(206, $response->getStatusCode());
				$this->assertContains('Content-Range: bytes 2-11/20', $response->headers);
				$this->assertContains('Content-Length: 10', $response->headers);
			});
		} finally {
			unlink($file);
		}
	}

	public function testWriteFileUnsatisfiableRangeSends416()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', ['Range' => 'bytes=20-'], function () use ($file) {
				$response = new TTestHttpResponse();
				$output = $this->captureWriteFile($response, $file, null, 'text/plain');

				$this->assertSame('', $output);
				$this->assertSame(416, $response->getStatusCode());
				$this->assertContains('Content-Range: bytes */20', $response->headers);
				$this->assertContains('Content-Length: 0', $response->headers);
				$this->assertEmpty(preg_grep('/^Content-Disposition:/', $response->headers));
			});
		} finally {
			unlink($file);
		}
	}

	public static function ignoredRangeProvider(): array
	{
		return [
			'disjoint ranges' => ['GET', ['Range' => 'bytes=0-1,5-6']],
			'invalid range' => ['GET', ['Range' => 'bytes=9-2']],
			'other unit' => ['GET', ['Range' => 'items=0-1']],
			'post' => ['POST', ['Range' => 'bytes=0-1']],
			'head' => ['HEAD', ['Range' => 'bytes=0-1']],
		];
	}

	/**
	 * @dataProvider ignoredRangeProvider
	 */
	public function testWriteFileIgnoredRangeSendsFullFile(string $method, array $headers)
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest($method, $headers, function () use ($file) {
				$response = new TTestHttpResponse();
				$this->assertSame(self::RANGE_BODY, $this->captureWriteFile($response, $file));
				$this->assertSame(200, $response->getStatusCode());
				$this->assertContains('Content-Length: 20', $response->headers);
				$this->assertEmpty(preg_grep('/^Content-Range:/', $response->headers));
			});
		} finally {
			unlink($file);
		}
	}

	public function testWriteFileRangeIgnoredWhenStatusIsNot200()
	{
		$this->withRequest('GET', ['Range' => 'bytes=0-1'], function () {
			$response = new TTestHttpResponse();
			$response->setStatusCode(404);
			$this->assertSame('abcdef', $this->captureWriteFile($response, '/path/to/x.txt', 'abcdef', 'text/plain'));
			$this->assertSame(404, $response->getStatusCode());
		});
	}

	public function testWriteFileIfRangeMatchesLastModified()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', ['Range' => 'bytes=0-1', 'If-Range' => $this->lastModified()], function () use ($file) {
				$response = new TTestHttpResponse();
				$this->assertSame('01', $this->captureWriteFile($response, $file));
				$this->assertSame(206, $response->getStatusCode());
			});
			$this->withRequest('GET', ['Range' => 'bytes=0-1', 'If-Range' => gmdate('D, d M Y H:i:s', self::RANGE_MTIME - 1) . ' GMT'], function () use ($file) {
				$response = new TTestHttpResponse();
				$this->assertSame(self::RANGE_BODY, $this->captureWriteFile($response, $file));
				$this->assertSame(200, $response->getStatusCode());
			});
		} finally {
			unlink($file);
		}
	}

	public static function ifRangeEtagProvider(): array
	{
		return [
			'strong match' => ['"v1"', 'ETag: "v1"', 206],
			'mismatch' => ['"v2"', 'ETag: "v1"', 200],
			'weak request' => ['W/"v1"', 'ETag: "v1"', 200],
			'weak etag' => ['"v1"', 'ETag: W/"v1"', 200],
			'no etag' => ['"v1"', 'X-Other: 1', 200],
			'date without validator' => ['Tue, 15 Sep 2026 00:00:00 GMT', 'ETag: "v1"', 200],
			'weak request without etag' => ['W/"v1"', 'X-Other: 1', 200],
			'invalid date' => ['not a date', 'Last-Modified: Tue, 15 Sep 2026 00:00:00 GMT', 200],
			'date match' => ['Tue, 15 Sep 2026 00:00:00 GMT', 'Last-Modified: Tue, 15 Sep 2026 00:00:00 GMT', 206],
		];
	}

	/**
	 * @dataProvider ifRangeEtagProvider
	 */
	public function testWriteFileIfRangeEtag(string $ifRange, string $header, int $status)
	{
		$this->withRequest('GET', ['Range' => 'bytes=1-2', 'If-Range' => $ifRange], function () use ($header, $status) {
			$response = new TTestHttpResponse();
			$output = $this->captureWriteFile($response, '/path/to/x.txt', 'abcdef', 'text/plain', [$header]);
			$this->assertSame($status, $response->getStatusCode());
			$this->assertSame($status === 206 ? 'bc' : 'abcdef', $output);
		});
	}

	public function testWriteFileCallerLastModifiedIsTheValidatorAndIsNotDuplicated()
	{
		$file = $this->rangeFile();
		$date = 'Wed, 01 Jan 2025 00:00:00 GMT';
		try {
			$this->withRequest('GET', ['Range' => 'bytes=0-1', 'If-Range' => $date], function () use ($file, $date) {
				$response = new TTestHttpResponse();
				$output = $this->captureWriteFile($response, $file, null, 'text/plain', ['last-modified: ' . $date]);
				$this->assertSame('01', $output);
				$this->assertCount(1, preg_grep('/^last-modified:/i', $response->headers));
			});
		} finally {
			unlink($file);
		}
	}

	public function testWriteFileAcceptRangesOffKeepsPriorBehavior()
	{
		$file = $this->rangeFile();
		try {
			$this->withRequest('GET', ['Range' => 'bytes=0-1'], function () use ($file) {
				$response = new TTestHttpResponse();
				$response->setAcceptRanges(false);
				$output = $this->captureWriteFile($response, $file, null, 'text/plain');

				$this->assertSame(self::RANGE_BODY, $output);
				$this->assertSame(200, $response->getStatusCode());
				$this->assertEmpty(preg_grep('/^(Accept-Ranges|Last-Modified|Content-Range):/', $response->headers));
				$this->assertContains('Content-Length: 20', $response->headers);
			});
		} finally {
			unlink($file);
		}
	}

	public function testAppendFileRangeStreamsAcrossChunks()
	{
		$body = '';
		for ($i = 0; $i < 3000; $i++) {
			$body .= sprintf('%05d|', $i);
		}
		$file = $this->rangeFile($body);
		try {
			$response = new TTestHttpResponse();
			ob_start();
			try {
				$written = $response->appendFileRange($file, 6000, 9000);
			} finally {
				$output = ob_get_clean();
			}
			$this->assertSame(9000, $written);
			$this->assertSame(substr($body, 6000, 9000), $output);
		} finally {
			unlink($file);
		}
	}

	public function testAppendFileRangePastEndThrows()
	{
		$file = $this->rangeFile();
		try {
			$response = new TTestHttpResponse();
			$this->expectException(\RuntimeException::class);
			ob_start();
			try {
				$response->appendFileRange($file, 15, 10);
			} finally {
				ob_end_clean();
			}
		} finally {
			unlink($file);
		}
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

	public function testDynamicBehaviorIgnoringTheFiltersKeepsTheUrlAndTheCookie()
	{
		// __dycall returns null for dyRedirect and dySetCookie; the URL and the cookie pass through.
		$response = new TTestHttpResponse();
		$response->setAdapter($adapter = new TTestRecordingResponseAdapter($response));
		$response->attachBehavior('nulldy', new TTestNullDynamicBehavior());
		$response->redirect('/local/page');
		$this->assertEquals(['/local/page'], $adapter->redirects);

		$response = new TTestHttpResponse();
		$response->attachBehavior('nulldy', $behavior = new TTestNullDynamicBehavior());
		$response->addCookie(new THttpCookie('name', 'value'));
		$response->removeCookie(new THttpCookie('gone', 'value'));
		$this->assertEquals('name', $response->cookies[0][0]);
		$this->assertEquals('gone', $response->cookies[1][0]);
		$this->assertEquals(['dySetCookie', 'dySetCookie'], array_values(array_filter($behavior->events, static fn ($e) => $e === 'dySetCookie')));
	}

	public function testRedirectWithoutAdapterSendsFilteredUrl()
	{
		$response = new TTestHttpResponse();
		$response->attachBehavior('guard', new TTestRedirectGuardBehavior());
		$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;
		unset($_SERVER['SERVER_SOFTWARE']);

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

	public function testFlushUsesTheAdapter()
	{
		$response = new TTestHttpResponse();
		$response->setAdapter($adapter = new TTestRecordingResponseAdapter($response));
		$response->flush();
		$response->flush(false);
		$this->assertSame([true, false], $adapter->flushes);
		$this->assertSame([], $response->headers);
	}

	public function testFlushWithoutAdapterFlushesTheBuffer()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$response->attachBehavior('flushes', $behavior = new TTestFlushRecordingBehavior());
		$level = ob_get_level();
		try {
			$response->write('abc');
			$this->expectOutputString('abc');
			$response->flush();
			$this->assertSame('', $response->getContents());
			$this->assertTrue($response->getBufferOutput());
			$this->assertSame([true], $behavior->calls);
			$this->assertSame('HTTP/1.1 200 OK', $response->headers[0]);
		} finally {
			while (ob_get_level() >= $level) {
				ob_end_clean();
			}
		}
	}

	public function testFlushContentEndsBuffering()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$level = ob_get_level();
		$response->write('abc');
		$this->expectOutputString('abc');
		$response->flushContent(false);
		$this->assertSame($level - 1, ob_get_level());
		$this->assertFalse($response->getBufferOutput());
		$this->assertSame('', $response->getContents());
	}

	public function testFlushContentWithEmptyBufferKeepsBuffering()
	{
		$response = new TTestHttpResponse();
		$response->init(null);
		$level = ob_get_level();
		try {
			$response->flushContent(false);
			$this->assertSame($level, ob_get_level());
			$this->assertTrue($response->getBufferOutput());
			$this->assertTrue($response->getHttpHeaderSent());
		} finally {
			ob_end_clean();
		}
	}

	public function testFlushContentWithoutBuffer()
	{
		$response = new TTestHttpResponse();
		$response->setBufferOutput(false);
		$level = ob_get_level();
		$response->init(null);
		$this->assertSame($level, ob_get_level());
		$response->flushContent();
		$this->assertSame($level, ob_get_level());
		$this->assertTrue($response->getHttpHeaderSent());
	}

	public function testSendHttpHeaderUsesTheRequestProtocol()
	{
		$protocol = $_SERVER['SERVER_PROTOCOL'] ?? null;
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';
		try {
			$response = new TTestHttpResponse();
			$response->setStatusCode(404);
			$response->ensureHeadersSent();
			$response->ensureHeadersSent();
		} finally {
			if ($protocol === null) {
				unset($_SERVER['SERVER_PROTOCOL']);
			} else {
				$_SERVER['SERVER_PROTOCOL'] = $protocol;
			}
		}
		$this->assertSame(['HTTP/2.0 404 Not Found', 'Content-Type: text/html;charset=UTF-8'], $response->headers);
	}

	public function testSendContentTypeHeader()
	{
		$response = new TTestHttpResponse();
		$response->setContentType('text/plain');
		$response->setCharset('ISO-8859-1');
		$response->ensureHeadersSent();
		$this->assertSame('Content-Type: text/plain;charset=ISO-8859-1', $response->headers[1]);
		try {
			$response->setContentType('text/css');
			$this->fail('setContentType() must throw after the Content-Type header is sent.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('THttpResponse.ContentType cannot change after the Content-Type header is sent.', $e->getMessage());
			$this->assertSame('text/plain', $response->getContentType());
		}
	}

	public function testSendContentTypeHeaderWithoutCharset()
	{
		$response = new TTestHttpResponse();
		$response->setCharset('FALSE');
		$this->assertFalse($response->getCharset());
		$response->ensureHeadersSent();
		$this->assertSame(['HTTP/1.1 200 OK', 'Content-Type: text/html'], $response->headers);
	}

	public function testSendContentTypeHeaderUsesTheGlobalizationCharset()
	{
		$globalization = new TGlobalization();
		$globalization->setCharset('ISO-8859-2');
		$this->app->setGlobalization($globalization);
		$response = new TTestHttpResponse();
		$response->ensureHeadersSent();
		$this->assertSame('Content-Type: text/html;charset=ISO-8859-2', $response->headers[1]);

		$globalization->setCharset('');
		$response = new TTestHttpResponse();
		$response->ensureHeadersSent();
		$this->assertSame('Content-Type: text/html;charset=UTF-8', $response->headers[1]);
	}

	public function testAdapterAccessors()
	{
		$response = new TTestHttpResponse();
		$this->assertNull($response->getAdapter());
		$this->assertFalse($response->getHasAdapter());
		$response->setAdapter($adapter = new TTestRecordingResponseAdapter($response));
		$this->assertSame($adapter, $response->getAdapter());
		$this->assertTrue($response->getHasAdapter());
	}

	public function testSessionCacheWrappersReadThePhpSettings()
	{
		$response = new THttpResponse();
		$this->assertSame(session_cache_expire(), $response->getCacheExpire());
		$this->assertSame(session_cache_limiter(), $response->getCacheControl());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testResponseSetCookieSendsTheCookie()
	{
		$response = new THttpResponse();
		$this->assertTrue(PradoUnit::invoke($response, 'responseSetCookie', 'name', 'value', ['path' => '/']));
	}

	public function testSetStatusCodeReason()
	{
		$response = new TTestHttpResponse();
		$this->assertSame('OK', $response->getStatusReason());
		$response->setStatusCode('404');
		$this->assertSame(404, $response->getStatusCode());
		$this->assertSame('Not Found', $response->getStatusReason());
		$response->setStatusCode(299, 'Custom Reason');
		$this->assertSame(299, $response->getStatusCode());
		$this->assertSame('Custom Reason', $response->getStatusReason());
		foreach ([null, ''] as $reason) {
			try {
				$response->setStatusCode(298, $reason);
				$this->fail('An unknown status code needs a reason.');
			} catch (TInvalidDataValueException $e) {
				$this->assertStringContainsString('response_status_reason_missing', $e->getErrorCode());
			}
		}
		foreach (["Bad\rReason", "Bad\nReason"] as $reason) {
			try {
				$response->setStatusCode(298, $reason);
				$this->fail('A reason with a line break must throw.');
			} catch (TInvalidDataValueException $e) {
				$this->assertStringContainsString('response_status_reason_barchars', $e->getErrorCode());
			}
		}
		$this->assertSame(299, $response->getStatusCode());
		$this->assertSame('Custom Reason', $response->getStatusReason());
	}

	public function testWriteWithoutBufferSendsHeadersOnce()
	{
		$response = new TTestHttpResponse();
		$response->setBufferOutput(false);
		$this->expectOutputString('ab');
		$response->write('a');
		$this->assertTrue($response->getHttpHeaderSent());
		$this->assertCount(2, $response->headers);
		$response->write('b');
		$this->assertCount(2, $response->headers);
		$this->assertSame('', $response->getContents());
	}

	public function testClearWithoutBufferOrContent()
	{
		$response = new TTestHttpResponse();
		$response->setBufferOutput(false);
		$this->expectOutputString('a');
		echo 'a';
		$response->clear();

		$response = new TTestHttpResponse();
		$response->init(null);
		try {
			$response->clear();
			$this->assertSame('', $response->getContents());
		} finally {
			ob_end_clean();
		}
	}

	public function testGetHeadersIsEmptyUnderCli()
	{
		// The CLI SAPI keeps no response headers, so headers_list() is empty.
		$response = new THttpResponse();
		$this->assertSame([], $response->getHeaders());
		$this->assertSame([], $response->getHeaders(CASE_LOWER));
	}

	public function testAppendLogWritesToTheDestination()
	{
		$file = tempnam(sys_get_temp_dir(), 'prado-log');
		try {
			(new TTestHttpResponse())->appendLog('logged message', 3, $file);
			$this->assertSame('logged message', file_get_contents($file));
		} finally {
			unlink($file);
		}
	}

	public function testAddCookieHashesTheValueWithCookieValidation()
	{
		$request = $this->app->getRequest();
		$validation = $request->getEnableCookieValidation();
		$request->setEnableCookieValidation(true);
		try {
			$response = new TTestHttpResponse();
			$response->addCookie(new THttpCookie('name', 'value'));
			$this->assertSame($this->app->getSecurityManager()->hashCookieData('value'), $response->cookies[0][1]);

			$request->setEnableCookieValidation(false);
			$response->addCookie(new THttpCookie('name', 'value'));
			$this->assertSame('value', $response->cookies[1][1]);
		} finally {
			$request->setEnableCookieValidation($validation);
		}
	}

	public function testCreateHtmlWriterThroughTheAdapter()
	{
		$response = new TTestHttpResponse();
		$response->setAdapter($adapter = new TTestRecordingResponseAdapter($response));
		$this->assertInstanceOf(\Prado\Web\UI\THtmlWriter::class, $response->createHtmlWriter());
		$this->assertInstanceOf(\Prado\Web\UI\THtmlWriter::class, $response->createHtmlWriter(\Prado\Web\UI\THtmlWriter::class));
		$this->assertSame(['\Prado\Web\UI\THtmlWriter', \Prado\Web\UI\THtmlWriter::class], $adapter->writers);

		$response = new TTestHttpResponse();
		$writer = $response->createHtmlWriter(\Prado\Web\UI\THtmlWriter::class);
		$this->assertInstanceOf(\Prado\Web\UI\THtmlWriter::class, $writer);
	}

	public function testGetFileLastModifiedOfAMissingFile()
	{
		$this->assertNull(PradoUnit::invoke(new TTestHttpResponse(), 'getFileLastModified', '/missing/prado-file'));
	}

	public static function findHeaderValueProvider(): array
	{
		return [
			'null list' => [null, null],
			'empty list' => [[], null],
			'no colon' => [['ETag'], null],
			'other name' => [['X-Other: 1'], null],
			'match' => [[' etag : "a" '], '"a"'],
			'first match' => [['X-Other: 1', 'ETag: "a"', 'ETag: "b"'], '"a"'],
		];
	}

	/**
	 * @dataProvider findHeaderValueProvider
	 */
	public function testFindHeaderValue(?array $headers, ?string $expected)
	{
		$this->assertSame($expected, PradoUnit::invoke(THttpResponse::class, 'findHeaderValue', $headers, 'ETag'));
	}

	// -----------------------------------------------------------------------
	// httpRedirect()
	// -----------------------------------------------------------------------

	/**
	 * Runs httpRedirect() with a server software name and returns the headers it sent.
	 */
	private function redirectHeaders(TTestHttpResponse $response, string $url, ?string $software): array
	{
		$software0 = $_SERVER['SERVER_SOFTWARE'] ?? null;
		if ($software === null) {
			unset($_SERVER['SERVER_SOFTWARE']);
		} else {
			$_SERVER['SERVER_SOFTWARE'] = $software;
		}
		try {
			$response->httpRedirect($url);
			$this->fail('httpRedirect() must end the request.');
		} catch (TExitException $e) {
		} finally {
			if ($software0 === null) {
				unset($_SERVER['SERVER_SOFTWARE']);
			} else {
				$_SERVER['SERVER_SOFTWARE'] = $software0;
			}
		}
		return array_slice($response->headers, 2);
	}

	public function testHttpRedirectWithRedirectStatus()
	{
		$response = new TTestHttpResponse();
		$response->setStatusCode(301);
		$this->assertSame(['Location: https://elsewhere.example/?a=1&b=2'], $this->redirectHeaders($response, 'https://elsewhere.example/?a=1&amp;b=2', 'Apache'));
	}

	public function testHttpRedirectUnderIis()
	{
		$base = $this->app->getRequest()->getBaseUrl();

		$response = new TTestHttpResponse();
		$response->setStatusCode(307);
		$this->assertSame(['HTTP/1.1 307 Temporary Redirect', 'Location: ' . $base . '/page'], $this->redirectHeaders($response, '/page', 'Microsoft-IIS/10.0'));

		$response = new TTestHttpResponse();
		$response->setStatusCode(399, 'Custom Redirect');
		$this->assertSame(['HTTP/1.1 399 Custom Redirect', 'Location: ' . $base . '/page'], $this->redirectHeaders($response, '/page', 'Microsoft-IIS/10.0'));

		$response = new TTestHttpResponse();
		$this->assertSame(['HTTP/1.1 302 Found', 'Location: https://elsewhere.example/'], $this->redirectHeaders($response, 'https://elsewhere.example/', 'microsoft-iis/8.5'));
	}

	public function testRedirectWithoutAdapterCallsHttpRedirect()
	{
		$response = new class () extends TTestHttpResponse {
			public array $redirects = [];

			public function httpRedirect($url)
			{
				$this->redirects[] = $url;
			}
		};
		$response->redirect('/page');
		$this->assertSame(['/page'], $response->redirects);
	}

	public function testReloadRedirectsToTheRequestUri()
	{
		$response = new TTestHttpResponse();
		$response->setAdapter($adapter = new TTestRecordingResponseAdapter($response));
		$response->reload();
		$this->assertSame([$this->app->getRequest()->getRequestUri()], $adapter->redirects);
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
		$this->assertEquals($this->app->getRequest()->getEnableCookieValidation() ? $this->app->getSecurityManager()->hashCookieData('value') : 'value', $value);
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

	public function testSetStatusCodeAndContentTypeThrowOperationExceptionAfterSent()
	{
		$response = new TTestHttpResponse();
		$response->ensureHeadersSent();
		try {
			$response->setStatusCode(500);
			$this->fail('setStatusCode() must throw after the HTTP header is sent.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('THttpResponse.StatusCode cannot change after the HTTP header is sent.', $e->getMessage());
		}
		$this->expectException(TInvalidOperationException::class);
		$this->expectExceptionMessage('THttpResponse.ContentType cannot change after the Content-Type header is sent.');
		$response->setContentType('text/css');
	}

	public function testContentTypeWithoutCharsetIsSentOnce()
	{
		foreach (['false', false] as $charset) {
			$response = new TTestHttpResponse();
			$response->setCharset($charset);
			$this->assertFalse($response->getCharset());
			$response->ensureHeadersSent();
			$response->ensureHeadersSent();
			$this->assertSame(['HTTP/1.1 200 OK', 'Content-Type: text/html'], $response->headers);
			try {
				$response->setContentType('text/css');
				$this->fail('setContentType() must throw after the Content-Type header is sent.');
			} catch (TInvalidOperationException $e) {
				$this->assertNull($response->getContentType());
			}
		}
	}

	public function testHttpRedirectUnderIisSendsTheCustomReason()
	{
		$software0 = $_SERVER['SERVER_SOFTWARE'] ?? null;
		$_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';
		$response = new TTestHttpResponse();
		$response->setStatusCode(399, 'Custom Redirect');
		try {
			$response->httpRedirect('https://elsewhere.example/');
			$this->fail('httpRedirect() must end the request.');
		} catch (TExitException $e) {
		} finally {
			if ($software0 === null) {
				unset($_SERVER['SERVER_SOFTWARE']);
			} else {
				$_SERVER['SERVER_SOFTWARE'] = $software0;
			}
		}
		$this->assertSame(['HTTP/1.1 399 Custom Redirect', 'Location: https://elsewhere.example/'], array_slice($response->headers, 2));
	}

	public function testGetHeadersParsesTheHeadersList()
	{
		$response = new class() extends THttpResponse {
			protected function responseHeadersList(): array
			{
				return ['Content-Type: text/html', 'Set-Cookie: a=1', 'Set-Cookie: b=2', 'Link: <https://example.com/>; rel=preload', 'X-Empty:'];
			}
		};
		$this->assertSame([
			'Content-Type' => 'text/html',
			'Set-Cookie' => 'a=1, b=2',
			'Link' => '<https://example.com/>; rel=preload',
			'X-Empty' => '',
		], $response->getHeaders());
		$this->assertSame('a=1, b=2', $response->getHeaders(CASE_UPPER)['SET-COOKIE']);
		$this->assertArrayHasKey('content-type', $response->getHeaders(CASE_LOWER));
	}

	public function testGetHeadersParsesOneHeader()
	{
		$response = new class() extends THttpResponse {
			protected function responseHeadersList(): array
			{
				return ['X-One: 1'];
			}
		};
		$this->assertSame(['X-One' => '1'], $response->getHeaders());
		$this->assertSame(['x-one' => '1'], $response->getHeaders(CASE_LOWER));
	}

	/**
	 * Redirects with each URL kind, status class, and server, and checks the status and Location headers.
	 */
	public function testHttpRedirectStatusAndServerCombinations()
	{
		$base = $this->app->getRequest()->getBaseUrl();
		foreach (['/page' => $base . '/page', 'https://elsewhere.example/' => 'https://elsewhere.example/'] as $url => $location) {
			foreach ([200 => 302, 404 => 302, 307 => 307] as $status => $sent) {
				foreach (['Apache', 'Microsoft-IIS/10.0'] as $software) {
					$response = new TTestHttpResponse();
					$response->setStatusCode($status);
					$expected = ['Location: ' . $location];
					if ($software !== 'Apache') {
						array_unshift($expected, 'HTTP/1.1 ' . $sent . ' ' . ($sent === 302 ? 'Found' : 'Temporary Redirect'));
					}
					$this->assertSame($expected, $this->redirectHeaders($response, $url, $software), "$url $status $software");
				}
			}
		}
	}
}
