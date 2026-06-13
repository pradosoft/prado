<?php

use Prado\Exceptions\TSocketException;
use Prado\IO\Socket\TSocketAddress;
use Prado\IO\Socket\TSocketServer;
use Prado\IO\Socket\TSocketStream;
use Psr\Http\Message\StreamInterface;

class TSocketStreamTest extends PHPUnit\Framework\TestCase
{
	public function testTransports()
	{
		self::assertContains('tcp', TSocketStream::getTransports());
		self::assertTrue(TSocketStream::supportsTransport('tcp'));
		self::assertFalse(TSocketStream::supportsTransport('not-a-transport'));
	}

	public function testConnectFailureThrows()
	{
		self::expectException(TSocketException::class);
		TSocketStream::connect('tcp://127.0.0.1:1', 0.5);   // port 1 refuses quickly
	}

	public function testBindFailureThrows()
	{
		self::expectException(TSocketException::class);
		TSocketStream::bind('udp://256.256.256.256:5000');  // invalid bind address
	}

	public function testTcpRoundTripAndAddresses()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);

		self::assertInstanceOf(StreamInterface::class, $client);
		$client->write('ping');
		self::assertSame('ping', $conn->read(4));
		$conn->write('pong');
		self::assertSame('pong', $client->read(4));

		self::assertInstanceOf(TSocketAddress::class, $client->getRemoteAddress());
		$local = $client->getLocalAddress();
		self::assertInstanceOf(TSocketAddress::class, $local);
		self::assertSame('127.0.0.1', $local->getHost());

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testShutdown()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$conn = $server->accept(1.0);

		self::assertTrue($client->shutdown(STREAM_SHUT_WR));

		$conn->close();
		$client->close();
		$server->close();
	}

	public function testUdpDatagramRoundTrip()
	{
		// Bind a UDP socket (bind only, no listen/accept).
		$server = TSocketStream::bind('udp://127.0.0.1:0');
		$server->setTimeout(2);
		$port = $server->getLocalAddress()->getPort();
		self::assertNotNull($port);

		$client = TSocketStream::connect('udp://127.0.0.1:' . $port);
		$client->setTimeout(2);
		$client->write('ping');

		$peer = null;
		self::assertSame('ping', $server->recvFrom(65535, 0, $peer));
		self::assertNotEmpty($peer, 'recvFrom captures the sender address.');

		self::assertNotFalse($server->sendTo('pong', 0, $peer));
		self::assertSame('pong', $client->read(65535));

		$client->close();
		$server->close();
	}

	public function testPair()
	{
		[$a, $b] = TSocketStream::pair();
		self::assertInstanceOf(TSocketStream::class, $a);
		self::assertInstanceOf(TSocketStream::class, $b);
		$a->write('ping');
		self::assertSame('ping', $b->read(4));
		$b->write('pong');
		self::assertSame('pong', $a->read(4));
		$a->close();
		$b->close();
	}

	public function testCryptoMetadataEmptyWithoutTls()
	{
		[$a, $b] = TSocketStream::pair();
		self::assertSame([], $a->getCryptoMeta(), 'A plain socket has no crypto metadata.');
		self::assertNull($a->getAlpnProtocol(), 'A plain socket has no negotiated ALPN.');
		$a->close();
		$b->close();
	}

	public function testTlsAlpnNegotiation()
	{
		if (!extension_loaded('openssl')) {
			$this->markTestSkipped('ext-openssl is required for TLS.');
		}
		$pem = $this->makeSelfSignedCert();
		if ($pem === null) {
			$this->markTestSkipped('Could not generate a self-signed certificate (no openssl config).');
		}

		try {
			$server = TSocketServer::bind('tcp://127.0.0.1:0');
			$client = TSocketStream::connect('tcp://127.0.0.1:' . $server->getPort(), 2.0);
			$conn = $server->accept(2.0);

			stream_context_set_option($client->getResource(), ['ssl' => ['alpn_protocols' => 'h2', 'verify_peer' => false, 'verify_peer_name' => false, 'peer_name' => 'localhost']]);
			stream_context_set_option($conn->getResource(), ['ssl' => ['local_cert' => $pem, 'alpn_protocols' => 'h2,http/1.1', 'verify_peer' => false]]);
			$client->setBlocking(false);
			$conn->setBlocking(false);

			// Drive both non-blocking handshakes to completion (enableCrypto returns 0 while in progress).
			$clientDone = $serverDone = false;
			$deadline = microtime(true) + 5.0;
			while ((!$clientDone || !$serverDone) && microtime(true) < $deadline) {
				if (!$serverDone && ($r = @$conn->enableCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER)) !== 0) {
					$serverDone = $r === true;
				}
				if (!$clientDone && ($r = @$client->enableCrypto(true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) !== 0) {
					$clientDone = $r === true;
				}
				usleep(2000);
			}

			self::assertTrue($clientDone && $serverDone, 'The TLS handshake completed.');
			self::assertSame('h2', $client->getAlpnProtocol(), 'The client sees the negotiated h2 ALPN.');
			self::assertSame('h2', $conn->getAlpnProtocol(), 'The server sees the negotiated h2 ALPN.');
			self::assertArrayHasKey('protocol', $client->getCryptoMeta(), 'Crypto metadata exposes the TLS protocol.');

			$conn->close();
			$client->close();
			$server->close();
		} finally {
			@unlink($pem);
		}
	}

	/** @return ?string A temp PEM file (cert + key), or null when generation is unavailable. */
	private function makeSelfSignedCert(): ?string
	{
		$key = @openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
		if ($key === false) {
			return null;
		}
		$csr = @openssl_csr_new(['commonName' => 'localhost'], $key, ['digest_alg' => 'sha256']);
		if ($csr === false) {
			return null;
		}
		$x509 = @openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
		if ($x509 === false) {
			return null;
		}
		openssl_x509_export($x509, $certPem);
		openssl_pkey_export($key, $keyPem);
		$pem = tempnam(sys_get_temp_dir(), 'pradotls');
		file_put_contents($pem, $certPem . $keyPem);
		return $pem;
	}

	public function testClosedSocketOperationsReturnFalseOrNull()
	{
		$server = TSocketServer::bind('tcp://127.0.0.1:0');
		$port = $server->getPort();
		$client = TSocketStream::connect('tcp://127.0.0.1:' . $port, 1.0);
		$server->accept(1.0);
		$client->close();

		self::assertFalse($client->recvFrom(10));
		self::assertFalse($client->sendTo('x'));
		self::assertFalse($client->shutdown());
		self::assertFalse($client->enableCrypto());
		self::assertNull($client->getRemoteAddress());
		self::assertNull($client->getLocalAddress());

		$server->close();
	}
}
