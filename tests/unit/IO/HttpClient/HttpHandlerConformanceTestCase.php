<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\Exceptions\TCancelledException;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Http\TRequest;
use Prado\IO\HttpClient\IHttpHandler;
use Prado\IO\HttpClient\THttpClientOption;
use Prado\IO\HttpClient\THttpNetworkException;
use Prado\IO\HttpClient\THttpRequestException;
use Prado\IO\Stream\TPumpStream;
use Psr\Http\Message\ResponseInterface;

/**
 * The behavior every IHttpHandler shares, run against the PHP built-in test server.  A
 * concrete test supplies the handler through {@see createHandler()}.
 */
abstract class HttpHandlerConformanceTestCase extends \PHPUnit\Framework\TestCase
{
	use HttpServerTestTrait;

	/** @var string[] Temporary files to remove after each test. */
	private array $tempFiles = [];

	abstract protected function createHandler(): IHttpHandler;

	/**
	 * Whether the handler decodes a gzip or deflate response body.
	 */
	protected function decodesContent(): bool
	{
		return true;
	}

	public static function setUpBeforeClass(): void
	{
		self::startHttpServer();
	}

	public static function tearDownAfterClass(): void
	{
		self::stopHttpServer();
	}

	protected function setUp(): void
	{
		if (!$this->createHandler()::getIsAvailable()) {
			$this->markTestSkipped('The handler cannot run on this PHP.');
		}
		$this->requireRunningHttpServer();
	}

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		$this->tempFiles = [];
	}

	protected function send(string $method, string $path, array $headers = [], mixed $body = null, array $options = []): ResponseInterface
	{
		return $this->createHandler()->handle(new TRequest($method, self::url($path), $headers, $body), $options);
	}

	protected function echoed(ResponseInterface $response): array
	{
		return json_decode((string) $response->getBody(), true);
	}

	public function testGetReturnsStatusHeadersAndBody(): void
	{
		$response = $this->send('GET', '/');
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('OK', $response->getReasonPhrase());
		self::assertStringStartsWith('text/plain', $response->getHeaderLine('content-type'));
		self::assertSame('hello', (string) $response->getBody());
	}

	public function testPostSendsMethodHeadersAndBody(): void
	{
		$echo = $this->echoed($this->send('POST', '/echo', ['X-Test' => 'a', 'Content-Type' => 'text/plain'], 'payload'));
		self::assertSame('POST', $echo['method']);
		self::assertSame('payload', $echo['body']);
		self::assertSame('a', $echo['headers']['X-Test']);
	}

	public function testRepeatedRequestHeaderValuesAreJoined(): void
	{
		$echo = $this->echoed($this->send('GET', '/echo', ['X-Multi' => ['a', 'b']]));
		self::assertSame('a, b', $echo['headers']['X-Multi']);
	}

	public function testQueryIsSent(): void
	{
		$echo = $this->echoed($this->send('GET', '/echo?x=1&y=two'));
		self::assertSame('/echo?x=1&y=two', $echo['path']);
	}

	public function testEmptyPostSendsNoBody(): void
	{
		$echo = $this->echoed($this->send('POST', '/echo'));
		self::assertSame('POST', $echo['method']);
		self::assertSame('', $echo['body']);
	}

	public function testUnsizedRequestBody(): void
	{
		$parts = ['one-', 'two-', 'three'];
		$body = new TPumpStream(static function () use (&$parts) {
			return array_shift($parts) ?? false;
		});
		$echo = $this->echoed($this->send('PUT', '/echo', [], $body));
		self::assertSame('one-two-three', $echo['body']);
	}

	public function testErrorStatusReturnsResponse(): void
	{
		$response = $this->send('GET', '/status/404');
		self::assertSame(404, $response->getStatusCode());
		self::assertSame('status 404', (string) $response->getBody());
	}

	public function testHeadHasNoBody(): void
	{
		$response = $this->send('HEAD', '/');
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('', (string) $response->getBody());
	}

	public function testRedirectIsNotFollowed(): void
	{
		$response = $this->send('GET', '/redirect');
		self::assertSame(302, $response->getStatusCode());
		self::assertSame('/echo', $response->getHeaderLine('Location'));
	}

	public function testRepeatedResponseHeaderKeepsEachValue(): void
	{
		$response = $this->send('GET', '/cookies');
		self::assertSame(['a=1; Path=/', 'b=2; Path=/'], $response->getHeader('set-cookie'));
	}

	public function testUnsizedResponseBody(): void
	{
		self::assertSame('part1-part2', (string) $this->send('GET', '/unsized')->getBody());
	}

	public function testLargeResponseBody(): void
	{
		$response = $this->send('GET', '/large?bytes=200000');
		self::assertSame(str_repeat('A', 200000), (string) $response->getBody());
	}

	public function testSinkFilePathReceivesBody(): void
	{
		$file = tempnam(sys_get_temp_dir(), 'prado-sink-');
		$this->tempFiles[] = $file;
		$response = $this->send('GET', '/large?bytes=20000', [], null, [THttpClientOption::SINK => $file]);
		self::assertSame(20000, filesize($file));
		self::assertSame(str_repeat('A', 20000), (string) $response->getBody());
	}

	public function testSinkResourceReceivesBody(): void
	{
		$resource = fopen('php://temp', 'w+b');
		$this->send('GET', '/', [], null, [THttpClientOption::SINK => $resource]);
		rewind($resource);
		self::assertSame('hello', stream_get_contents($resource));
		fclose($resource);
	}

	public function testInvalidSinkThrowsRequestException(): void
	{
		$this->expectException(THttpRequestException::class);
		$this->send('GET', '/', [], null, [THttpClientOption::SINK => 42]);
	}

	public function testProgressReportsDownload(): void
	{
		$last = null;
		$this->send('GET', '/large?bytes=150000', [], null, [THttpClientOption::PROGRESS => function (int $total, int $downloaded) use (&$last): void {
			$last = [$total, $downloaded];
		}]);
		self::assertNotNull($last);
		self::assertSame(150000, $last[1]);
	}

	public function testDecodesCompressedContent(): void
	{
		if (!$this->decodesContent() || !function_exists('gzencode')) {
			$this->markTestSkipped('The handler does not decode content.');
		}
		self::assertSame('compressed body', (string) $this->send('GET', '/gzip')->getBody());
		self::assertSame('compressed body', (string) $this->send('GET', '/deflate')->getBody());
	}

	public function testDecodeContentOffKeepsTheEncodedBody(): void
	{
		if (!function_exists('gzencode')) {
			$this->markTestSkipped('zlib is not loaded.');
		}
		$response = $this->send('GET', '/gzip', [], null, [THttpClientOption::DECODE_CONTENT => false]);
		self::assertSame('gzip', $response->getHeaderLine('Content-Encoding'));
		self::assertSame('compressed body', gzdecode((string) $response->getBody()));
	}

	public function testTimeoutThrowsNetworkException(): void
	{
		$this->expectException(THttpNetworkException::class);
		$this->send('GET', '/slow?delay=2', [], null, [THttpClientOption::TIMEOUT => 0.3]);
	}

	public function testConnectionRefusedThrowsNetworkException(): void
	{
		$this->expectException(THttpNetworkException::class);
		$this->createHandler()->handle(new TRequest('GET', 'http://127.0.0.1:1/'), [THttpClientOption::TIMEOUT => 2.0]);
	}

	public static function provideUnsupportedUris(): array
	{
		return [['ftp://127.0.0.1/file'], ['file:///etc/passwd'], ['php://memory']];
	}

	/**
	 * @dataProvider provideUnsupportedUris
	 */
	public function testUnsupportedSchemeThrowsRequestException(string $uri): void
	{
		$this->expectException(THttpRequestException::class);
		$this->createHandler()->handle(new TRequest('GET', $uri));
	}

	public function testMissingHostThrowsRequestException(): void
	{
		$this->expectException(THttpRequestException::class);
		$this->createHandler()->handle(new TRequest('GET', '/relative'));
	}

	public function testCancelledTokenThrows(): void
	{
		$token = new TCancellationToken();
		$token->cancel();
		$this->expectException(TCancelledException::class);
		$this->send('GET', '/', [], null, [THttpClientOption::TOKEN => $token]);
	}

	public function testFragmentIsNotSent(): void
	{
		$echo = $this->echoed($this->send('GET', '/echo?q=1#section'));
		self::assertSame('/echo?q=1', $echo['path']);
	}
}
