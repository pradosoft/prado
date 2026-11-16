<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\IO\Async\TAsync;
use Prado\IO\Async\TAsyncStream;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Http\TRequest;
use Prado\IO\HttpClient\IHttpHandler;
use Prado\IO\HttpClient\THttpClientOption;
use Prado\IO\HttpClient\THttpNetworkException;
use Prado\IO\HttpClient\TSocketHttpHandler;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;
use Prado\IO\TStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Exposes the response reader of TSocketHttpHandler.
 */
class TSocketHttpHandlerTestReader extends TSocketHttpHandler
{
	public function pubReadResponse(TAsyncStream $stream, RequestInterface $request, array $options, StreamInterface $sink, TCancellationToken $deadline): ResponseInterface
	{
		return $this->readResponse($stream, $request, $this->resolveOptions($options), $sink, $deadline);
	}
}

class TSocketHttpHandlerTest extends HttpHandlerConformanceTestCase
{
	protected function createHandler(): IHttpHandler
	{
		return new TSocketHttpHandler();
	}

	/**
	 * Reads a canned response, written from a fiber to the other end of a socket pair and
	 * then closed.
	 */
	private function read(string $raw, string $method = 'GET', array $options = []): ResponseInterface
	{
		[$client, $server] = TSocketStream::pair();
		$writer = TAsync::run(static function () use ($server, $raw): void {
			try {
				(new TAsyncStream($server))->write($raw);
			} finally {
				$server->close();
			}
		});
		try {
			return (new TSocketHttpHandlerTestReader())->pubReadResponse(new TAsyncStream($client), new TRequest($method, 'http://example.test/'), $options, TStream::fromTemp(), new TCancellationToken(5.0));
		} finally {
			$client->close();
			$writer->catch(static fn () => null);
		}
	}

	public function testHandleAsyncYieldsWhileWaiting(): void
	{
		$handler = new TSocketHttpHandler();
		$order = [];
		$future = $handler->handleAsync(new TRequest('GET', self::url('/slow?delay=0.2')));
		$future->onComplete(function () use (&$order) {
			$order[] = 'response';
		});
		$order[] = 'returned';
		$other = TAsync::run(function () use (&$order) {
			$order[] = 'other fiber';
		});
		self::assertSame('slow', (string) $future->await()->getBody());
		$other->await();
		self::assertSame(['returned', 'other fiber', 'response'], $order);
	}

	public function testHandleAsyncRunsSeveralRequests(): void
	{
		$handler = new TSocketHttpHandler();
		$futures = [];
		foreach (['/', '/status/201', '/echo'] as $path) {
			$futures[] = $handler->handleAsync(new TRequest('GET', self::url($path)));
		}
		$responses = TAsync::all($futures)->await();
		self::assertSame([200, 201, 200], array_map(fn ($r) => $r->getStatusCode(), $responses));
	}

	public function testRequestHeadersOnTheWire(): void
	{
		$echo = $this->echoed($this->send('POST', '/echo', [], 'abc'));
		self::assertSame('close', $echo['headers']['Connection']);
		self::assertSame('3', $echo['headers']['Content-Length']);
		if (function_exists('inflate_init')) {
			self::assertSame('gzip, deflate', $echo['headers']['Accept-Encoding']);
		}
	}

	public function testDecodedResponseRenamesEncodingHeaders(): void
	{
		if (!function_exists('inflate_init')) {
			$this->markTestSkipped('zlib is not loaded.');
		}
		$response = $this->send('GET', '/gzip');
		self::assertFalse($response->hasHeader('Content-Encoding'));
		self::assertSame('gzip', $response->getHeaderLine('X-Encoded-Content-Encoding'));
		self::assertNotSame('', $response->getHeaderLine('X-Encoded-Content-Length'));
	}

	public function testChunkedBodyWithExtensionsAndTrailers(): void
	{
		$response = $this->read("HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n5;ext=1\r\nhello\r\n6\r\n world\r\n0\r\nX-Trailer: t\r\n\r\n");
		self::assertSame('hello world', (string) $response->getBody());
	}

	public function testChunkedGzipBody(): void
	{
		if (!function_exists('inflate_init')) {
			$this->markTestSkipped('zlib is not loaded.');
		}
		$data = gzencode(str_repeat('abc', 1000));
		$chunks = '';
		foreach (str_split($data, 100) as $part) {
			$chunks .= dechex(strlen($part)) . "\r\n" . $part . "\r\n";
		}
		$response = $this->read("HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\nTransfer-Encoding: chunked\r\n\r\n" . $chunks . "0\r\n\r\n");
		self::assertSame(str_repeat('abc', 1000), (string) $response->getBody());
	}

	public function testInterimResponsesAreSkipped(): void
	{
		$response = $this->read("HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 103 Early Hints\r\nLink: </a>\r\n\r\nHTTP/1.1 201 Created\r\nContent-Length: 2\r\n\r\nok");
		self::assertSame(201, $response->getStatusCode());
		self::assertFalse($response->hasHeader('Link'));
		self::assertSame('ok', (string) $response->getBody());
	}

	public function testBodyToEndOfConnection(): void
	{
		self::assertSame('until close', (string) $this->read("HTTP/1.0 200 OK\r\n\r\nuntil close")->getBody());
	}

	public function testNoBodyStatusesIgnoreTheStream(): void
	{
		self::assertSame('', (string) $this->read("HTTP/1.1 204 No Content\r\n\r\n")->getBody());
		self::assertSame('', (string) $this->read("HTTP/1.1 304 Not Modified\r\nContent-Length: 10\r\n\r\n")->getBody());
		self::assertSame('', (string) $this->read("HTTP/1.1 200 OK\r\nContent-Length: 10\r\n\r\n", 'HEAD')->getBody());
	}

	public function testReasonPhraseAndVersion(): void
	{
		$response = $this->read("HTTP/1.0 418 I Am A Teapot\r\nContent-Length: 0\r\n\r\n");
		self::assertSame(418, $response->getStatusCode());
		self::assertSame('I Am A Teapot', $response->getReasonPhrase());
		self::assertSame('1.0', $response->getProtocolVersion());
	}

	public static function provideInvalidResponses(): array
	{
		return [
			'not http' => ["SSH-2.0-OpenSSH\r\n\r\n"],
			'bad status' => ["HTTP/1.1 99 Low\r\n\r\n"],
			'closed in headers' => ["HTTP/1.1 200 OK\r\nContent-Le"],
			'short body' => ["HTTP/1.1 200 OK\r\nContent-Length: 10\r\n\r\nshort"],
			'bad length' => ["HTTP/1.1 200 OK\r\nContent-Length: -1\r\n\r\n"],
			'bad chunk size' => ["HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\nzz\r\n"],
			'chunk overrun' => ["HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n2\r\nabc\r\n0\r\n\r\n"],
			'truncated chunk' => ["HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n5\r\nab"],
			'huge headers' => ["HTTP/1.1 200 OK\r\nX-Big: " . str_repeat('a', 70000)],
		];
	}

	/**
	 * @dataProvider provideInvalidResponses
	 */
	public function testInvalidResponseThrowsNetworkException(string $raw): void
	{
		$this->expectException(THttpNetworkException::class);
		$this->read($raw);
	}

	public function testCorruptCompressedBodyThrows(): void
	{
		if (!function_exists('inflate_init')) {
			$this->markTestSkipped('zlib is not loaded.');
		}
		$this->expectException(THttpNetworkException::class);
		$this->read("HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\nContent-Length: 9\r\n\r\nnot gzip!");
	}

	public function testUnknownEncodingIsLeftAlone(): void
	{
		$response = $this->read("HTTP/1.1 200 OK\r\nContent-Encoding: br\r\nContent-Length: 3\r\n\r\nraw");
		self::assertSame('raw', (string) $response->getBody());
		self::assertSame('br', $response->getHeaderLine('Content-Encoding'));
	}

	/**
	 * Starts a one-request TLS server in a fiber on a free port; the fiber returns the
	 * request head it read.
	 * @return array{0: int, 1: \Prado\IO\Async\TFuture, 2: TSocketServer}
	 */
	private function startTlsServer(string $pem, string $reply): array
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$fiber = TAsync::run(static function () use ($server, $pem, $reply): string {
			$loop = TAsync::getLoop();
			$suspension = $loop->suspension();
			$id = $loop->onReadable($server, static fn () => $suspension->resume());
			$suspension->suspend();
			$loop->cancel($id);
			$conn = $server->accept(2.0);
			try {
				stream_context_set_option($conn->getResource(), 'ssl', 'local_cert', $pem);
				$conn->enableCryptoAsync(true, STREAM_CRYPTO_METHOD_TLS_SERVER, 5.0)->await();
				$stream = new TAsyncStream($conn);
				$head = '';
				while (!str_contains($head, "\r\n\r\n")) {
					$head .= $stream->read(8192);
				}
				$stream->write($reply);
				return $head;
			} finally {
				$conn->close();
			}
		});
		return [$server->getPort(), $fiber, $server];
	}

	private function createCertificate(): string
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('ext-openssl is required for TLS.');
		}
		$key = @openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		$csr = $key === false ? false : @openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
		$x509 = $csr === false ? false : @openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
		if ($x509 === false) {
			$this->markTestSkipped('Could not generate a self-signed certificate.');
		}
		openssl_x509_export($x509, $certPem);
		openssl_pkey_export($key, $keyPem);
		$pem = tempnam(sys_get_temp_dir(), 'prado-tls-');
		file_put_contents($pem, $certPem . $keyPem);
		return $pem;
	}

	public function testHttpsRequestOverTls(): void
	{
		$pem = $this->createCertificate();
		try {
			[$port, $fiber, $server] = $this->startTlsServer($pem, "HTTP/1.1 200 OK\r\nContent-Length: 6\r\n\r\nsecure");
			$response = (new TSocketHttpHandler())->handle(new TRequest('GET', "https://127.0.0.1:{$port}/tls"), [
				THttpClientOption::VERIFY => false,
				THttpClientOption::TIMEOUT => 5.0,
			]);
			self::assertSame('secure', (string) $response->getBody());
			self::assertStringStartsWith('GET /tls HTTP/1.1', $fiber->await());
			$server->close();
		} finally {
			unlink($pem);
		}
	}

	public function testHttpsVerificationRejectsAnUntrustedCertificate(): void
	{
		$pem = $this->createCertificate();
		try {
			[$port, $fiber, $server] = $this->startTlsServer($pem, "HTTP/1.1 200 OK\r\nContent-Length: 0\r\n\r\n");
			try {
				(new TSocketHttpHandler())->handle(new TRequest('GET', "https://127.0.0.1:{$port}/"), [THttpClientOption::TIMEOUT => 5.0]);
				self::fail('A self-signed certificate is not trusted.');
			} catch (THttpNetworkException $e) {
				self::assertStringContainsString('could not connect', $e->getMessage());
			}
			$fiber->catch(static fn () => null);
			$server->close();
		} finally {
			unlink($pem);
		}
	}

	public function testHttp10UnsizedBodyIsSentWithContentLength(): void
	{
		$parts = ['ab', 'cd'];
		$body = new \Prado\IO\Stream\TPumpStream(static function () use (&$parts) {
			return array_shift($parts) ?? false;
		});
		$request = new TRequest('PUT', self::url('/echo'), [], $body, '1.0');
		$echo = $this->echoed((new TSocketHttpHandler())->handle($request, [THttpClientOption::TIMEOUT => 5.0]));
		self::assertSame('abcd', $echo['body']);
		self::assertSame('4', $echo['headers']['Content-Length']);
	}
}
