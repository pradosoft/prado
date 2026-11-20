<?php

namespace Prado\Test\Unit\Email\Net;

use Prado\Email\Net\TEmailSocketConnection;
use Prado\IO\Socket\TSocketStream;

/**
 * The server end of a socket pair whose client end is a TEmailSocketConnection.
 * A test queues the server's replies before the client runs, then reads what the client wrote.
 */
class TEmailTestServer
{
	/** @var TSocketStream the server end of the pair. */
	public TSocketStream $server;

	/** @var TEmailSocketConnection the client connection. */
	public TEmailSocketConnection $client;

	/** @var int the number of TLS handshakes the client asked for. */
	public int $tlsStarts = 0;

	/** @var bool the result of a TLS handshake. */
	public bool $tlsResult = true;

	public function __construct()
	{
		[$client, $this->server] = TSocketStream::pair();
		$this->server->setBlocking(false);
		$test = $this;
		$this->client = new class ($test) extends TEmailSocketConnection {
			public function __construct(private TEmailTestServer $_test)
			{
				parent::__construct();
			}

			protected function enableCrypto(TSocketStream $stream): bool
			{
				$this->_test->tlsStarts++;
				return $this->_test->tlsResult;
			}
		};
		$this->client->setTimeout(1);
		$this->client->attachStream($client, 'test.example:25');
	}

	/**
	 * Queues lines for the client to read.
	 */
	public function reply(string ...$lines): void
	{
		foreach ($lines as $line) {
			$this->server->write($line . "\r\n");
		}
	}

	/**
	 * Queues bytes for the client to read.
	 */
	public function replyRaw(string $data): void
	{
		$this->server->write($data);
	}

	/**
	 * @return string everything the client wrote since the last call.
	 */
	public function received(): string
	{
		$data = '';
		while (($chunk = $this->server->read(8192)) !== '') {
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * @return string[] the lines the client wrote since the last call.
	 */
	public function receivedLines(): array
	{
		$data = $this->received();
		return $data === '' ? [] : explode("\r\n", substr($data, 0, -2));
	}

	/**
	 * Closes the server end, as a server that drops the connection.
	 */
	public function hangUp(): void
	{
		$this->server->close();
	}
}
