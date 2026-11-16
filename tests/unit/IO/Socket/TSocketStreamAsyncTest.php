<?php

namespace Prado\Test\Unit\IO\Socket;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TSocketException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TAsyncStream;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TFuture;
use Prado\IO\Socket\TSocketReactor;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;

/**
 * A reactor whose writable callbacks never fire, so a connect waits until its timeout.
 */
class TSocketStreamAsyncTestNeverWritableReactor extends TSocketReactor
{
	public function onWritable(mixed $stream, callable $callback): string
	{
		return $this->delay(3600.0, static fn () => null);
	}
}

/**
 * A socket that records the URIs of its onConnect events.
 */
class TSocketStreamAsyncTestRecordingSocket extends TSocketStream
{
	public array $connected = [];

	public function onConnect(mixed $param): void
	{
		$this->connected[] = $param;
		parent::onConnect($param);
	}
}

class TSocketStreamAsyncTest extends \PHPUnit\Framework\TestCase
{
	/** @var ?string The PEM file (certificate and key) of the TLS tests. */
	private ?string $_pem = null;

	protected function tearDown(): void
	{
		if ($this->_pem !== null) {
			@unlink($this->_pem);
			$this->_pem = null;
		}
		parent::tearDown();
	}

	/**
	 * Returns the exception a future rejected with.
	 * @param TFuture $future The settled or pending future.
	 * @return ?\Throwable The rejection, or null when the future resolved.
	 */
	private function rejectionOf(TFuture $future): ?\Throwable
	{
		try {
			$future->await();
		} catch (\Throwable $e) {
			return $e;
		}
		return null;
	}

	// ---------------------------------------------------------------------
	// connectAsync
	// ---------------------------------------------------------------------

	public function testConnectAsyncResolvesWithANonBlockingConnectedSocket(): void
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$uri = 'tcp://127.0.0.1:' . $server->getPort();
		$future = TSocketStream::connectAsync($uri, 2.0);
		$client = $future->await();
		self::assertInstanceOf(TSocketStream::class, $client);
		self::assertFalse($client->getBlocking(), 'The socket stays non-blocking after the connect.');
		self::assertSame($server->getPort(), $client->getRemoteAddress()->getPort());
		self::assertSame([], TAsync::getLoop()->getIdentifiers(), 'The readiness callback and the timer are cancelled.');

		$conn = $server->accept(1.0);
		$stream = new TAsyncStream($client);
		$stream->write('ping');
		self::assertSame('ping', $conn->read(4));
		$conn->write('pong');
		self::assertSame('pong', $stream->read(4));

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testConnectAsyncBuildsTheCalledClassAndRaisesOnConnect(): void
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$uri = 'tcp://127.0.0.1:' . $server->getPort();
		$client = TSocketStreamAsyncTestRecordingSocket::connectAsync($uri, 2.0)->await();
		self::assertInstanceOf(TSocketStreamAsyncTestRecordingSocket::class, $client);
		self::assertSame([$uri], $client->connected, 'onConnect is raised once the socket has a peer.');
		$client->close();
		$server->close();
	}

	public function testConnectAsyncFromAWorkerFiber(): void
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$uri = 'tcp://127.0.0.1:' . $server->getPort();
		$future = TAsync::run(static fn () => TSocketStream::connectAsync($uri, 2.0)->await()->getRemoteAddress()->getPort());
		self::assertSame($server->getPort(), $future->await());
		$server->close();
	}

	public function testConnectAsyncToAClosedPortRejects(): void
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$server->close();

		$error = $this->rejectionOf(TSocketStream::connectAsync('tcp://127.0.0.1:' . $port, 2.0));
		self::assertInstanceOf(TSocketException::class, $error);
		self::assertSame([], TAsync::getLoop()->getIdentifiers());
	}

	public function testConnectAsyncTimeoutRejectsAndCancelsTheWait(): void
	{
		$prior = TAsync::getLoop();
		$reactor = new TSocketStreamAsyncTestNeverWritableReactor();
		TAsync::setLoop($reactor);
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		try {
			$error = $this->rejectionOf(TSocketStream::connectAsync('tcp://127.0.0.1:' . $server->getPort(), 0.02));
			self::assertInstanceOf(TSocketException::class, $error);
			self::assertStringContainsString('within 0.02 seconds', $error->getMessage());
			self::assertSame([], $reactor->getIdentifiers(), 'The timeout cancels the readiness callback.');
		} finally {
			TAsync::setLoop($prior);
			$server->close();
		}
	}

	public function testConnectAsyncWithACancelledTokenRejectsWithoutASocket(): void
	{
		$token = new TCancellationToken();
		$token->cancel();
		$future = TSocketStream::connectAsync('tcp://127.0.0.1:1', 2.0, null, $token);
		self::assertTrue($future->isRejected(), 'The future rejects at once.');
		self::assertSame($token->getReason(), $this->rejectionOf($future));
		self::assertSame([], TAsync::getLoop()->getIdentifiers(), 'No socket is waited on.');
	}

	public function testConnectAsyncTokenCancellationRejectsThePendingConnect(): void
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$token = new TCancellationToken();
		$future = TSocketStream::connectAsync('tcp://127.0.0.1:' . $server->getPort(), 2.0, null, $token);
		self::assertFalse($future->isComplete(), 'The connect waits on the loop.');
		$token->cancel();
		self::assertTrue($future->isRejected());
		self::assertSame($token->getReason(), $this->rejectionOf($future));
		self::assertSame([], TAsync::getLoop()->getIdentifiers(), 'The readiness callback and the timer are cancelled.');
		$server->close();
	}

	public function testConnectAsyncRejectsATlsScheme(): void
	{
		self::expectException(TInvalidDataValueException::class);
		TSocketStream::connectAsync('tls://127.0.0.1:443');
	}

	// ---------------------------------------------------------------------
	// enableCryptoAsync
	// ---------------------------------------------------------------------

	public function testEnableCryptoAsyncCompletesBothHandshakesOnTheLoop(): void
	{
		$pem = $this->requireCertificate();
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$context = stream_context_create(['ssl' => [
			'verify_peer' => false,
			'verify_peer_name' => false,
			'peer_name' => 'localhost',
			'alpn_protocols' => 'h2',
		]]);
		$client = TSocketStream::connectAsync('tcp://127.0.0.1:' . $server->getPort(), 2.0, $context)->await();
		$conn = $server->accept(2.0);
		stream_context_set_option($conn->getResource(), 'ssl', 'local_cert', $pem);
		stream_context_set_option($conn->getResource(), 'ssl', 'alpn_protocols', 'h2,http/1.1');

		$handshakes = TAsync::all([
			'server' => $conn->enableCryptoAsync(true, STREAM_CRYPTO_METHOD_TLS_SERVER, 5.0),
			'client' => $client->enableCryptoAsync(true, null, 5.0),
		]);
		self::assertSame(['server' => true, 'client' => true], $handshakes->await());
		self::assertSame([], TAsync::getLoop()->getIdentifiers(), 'Every handshake callback is cancelled.');
		self::assertSame('h2', $client->getAlpnProtocol(), 'The context options of the connect apply to the handshake.');

		$serverStream = new TAsyncStream($conn);
		$reply = TAsync::run(static function () use ($serverStream): void {
			$serverStream->write(strtoupper($serverStream->read(4)));
		});
		$clientStream = new TAsyncStream($client);
		$clientStream->write('ping');
		self::assertSame('PING', $clientStream->read(4));
		$reply->await();

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testEnableCryptoAsyncRejectsWhenThePeerSpeaksPlainText(): void
	{
		$this->requireCertificate();
		[$server, $client, $conn] = $this->tcpConnection();
		$conn->write("HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n");
		$conn->close();

		$error = $this->rejectionOf($client->enableCryptoAsync(true, null, 5.0));
		self::assertInstanceOf(TSocketException::class, $error);
		self::assertStringContainsString('TLS negotiation failed', $error->getMessage());
		self::assertFalse($client->isOpen(), 'A failed handshake closes the socket.');
		self::assertSame([], TAsync::getLoop()->getIdentifiers());
		$server->close();
	}

	public function testEnableCryptoAsyncTimeoutRejectsAndClosesTheSocket(): void
	{
		$this->requireCertificate();
		[$server, $client, $conn] = $this->tcpConnection();

		$error = $this->rejectionOf($client->enableCryptoAsync(true, null, 0.05));
		self::assertInstanceOf(TSocketException::class, $error);
		self::assertStringContainsString('within 0.05 seconds', $error->getMessage());
		self::assertFalse($client->isOpen(), 'The timeout closes the socket.');
		self::assertSame([], TAsync::getLoop()->getIdentifiers());
		$conn->close();
		$server->close();
	}

	public function testEnableCryptoAsyncTokenCancellationRejectsAndClosesTheSocket(): void
	{
		$this->requireCertificate();
		[$server, $client, $conn] = $this->tcpConnection();
		$token = new TCancellationToken();

		$future = $client->enableCryptoAsync(true, null, 5.0, $token);
		self::assertFalse($future->isComplete(), 'The handshake waits for the silent peer.');
		$token->cancel();
		self::assertSame($token->getReason(), $this->rejectionOf($future));
		self::assertFalse($client->isOpen());
		self::assertSame([], TAsync::getLoop()->getIdentifiers());
		$conn->close();
		$server->close();
	}

	public function testEnableCryptoAsyncWithACancelledTokenLeavesTheSocketUntouched(): void
	{
		[$server, $client, $conn] = $this->tcpConnection();
		$token = new TCancellationToken();
		$token->cancel();

		$future = $client->enableCryptoAsync(true, null, 5.0, $token);
		self::assertInstanceOf(TCancelledException::class, $this->rejectionOf($future));
		self::assertTrue($client->isOpen());
		self::assertTrue($client->getBlocking(), 'No handshake was started.');
		$conn->close();
		$client->close();
		$server->close();
	}

	public function testEnableCryptoAsyncOnAClosedSocketRejects(): void
	{
		[$server, $client, $conn] = $this->tcpConnection();
		$client->close();

		$error = $this->rejectionOf($client->enableCryptoAsync());
		self::assertInstanceOf(TSocketException::class, $error);
		self::assertSame([], TAsync::getLoop()->getIdentifiers());
		$conn->close();
		$server->close();
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	/**
	 * Connects a blocking loopback TCP client and accepts its server-side connection.
	 * @return array{0: TSocketServer, 1: TSocketStream, 2: TSocketStream} The server, client, and accepted connection.
	 */
	private function tcpConnection(): array
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 2.0);
		$conn = $server->accept(2.0);
		return [$server, $client, $conn];
	}

	/**
	 * Returns a self-signed PEM file, skipping the test when ext-openssl cannot make one.
	 * @return string The PEM file holding the certificate and the key.
	 */
	private function requireCertificate(): string
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('ext-openssl is required for TLS.');
		}
		$key = @openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		$csr = $key === false ? false : @openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
		$x509 = $csr === false ? false : @openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
		if ($x509 === false) {
			$this->markTestSkipped('Could not generate a self-signed certificate (no openssl config).');
		}
		openssl_x509_export($x509, $certPem);
		openssl_pkey_export($key, $keyPem);
		$this->_pem = tempnam(sys_get_temp_dir(), 'pradotls');
		file_put_contents($this->_pem, $certPem . $keyPem);
		return $this->_pem;
	}
}
