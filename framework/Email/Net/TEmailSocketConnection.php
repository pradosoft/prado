<?php

/**
 * TEmailSocketConnection class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Net;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNetworkException;
use Prado\IO\Socket\TSocketStream;
use Prado\IO\Util\TStreamHelper;
use Prado\TPropertyValue;

/**
 * TEmailSocketConnection class.
 *
 * TEmailSocketConnection is the client connection of a line protocol such as SMTP
 * or IMAP, on a {@see TSocketStream}.  It opens the socket in plain text or TLS,
 * upgrades it with {@see startTls}, and reads and writes CRLF lines and counted bytes.
 *
 * Every failure is a {@see TNetworkException}: a refused connection, a failed TLS
 * handshake, a write that does not complete, and a read that times out or meets
 * the end of the stream.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailSocketConnection extends \Prado\TComponent
{
	/** The line ending of the protocol. */
	public const EOL = "\r\n";

	/** The longest line that {@see readLine} accepts. */
	public const MAX_LINE_LENGTH = 65536;

	/** @var ?TSocketStream the open socket. */
	private ?TSocketStream $_stream = null;

	/** @var string the remote "host:port", for error messages. */
	private string $_remote = '';

	/** @var bool whether the connection is encrypted. */
	private bool $_encrypted = false;

	/** @var float the connect and read timeout in seconds. */
	private float $_timeout = 30.0;

	/** @var bool whether the server certificate is verified. */
	private bool $_verifyPeer = true;

	/**
	 * Opens the connection, closing an open one first.
	 * @param string $host the host name or address.
	 * @param int $port the port.
	 * @param bool $tls whether the connection is TLS from the first byte.
	 * @throws TConfigurationException when TLS is asked for without the openssl extension.
	 * @throws TNetworkException when the connection fails.
	 */
	public function open(string $host, int $port, bool $tls = false): void
	{
		$this->close();
		if ($tls) {
			$this->ensureOpenSsl();
		}
		$verify = $this->getVerifyPeer();
		$context = stream_context_create(['ssl' => [
			'verify_peer' => $verify,
			'verify_peer_name' => $verify,
			'allow_self_signed' => !$verify,
			'peer_name' => $host,
			'SNI_enabled' => true,
		]]);
		$remote = (str_contains($host, ':') ? '[' . $host . ']' : $host) . ':' . $port;
		try {
			$stream = TSocketStream::connect(($tls ? 'tls' : 'tcp') . '://' . $remote, $this->getTimeout(), STREAM_CLIENT_CONNECT, $context);
		} catch (\Throwable $e) {
			throw new TNetworkException('emailconnection_connect_failed', $remote, rtrim($e->getMessage(), '.'));
		}
		$this->attachStream($stream, $remote, $tls);
	}

	/**
	 * Uses a connected socket as the connection.
	 * @param TSocketStream $stream the connected socket.
	 * @param string $remote the name of the remote end, for error messages.
	 * @param bool $encrypted whether the socket is encrypted.
	 */
	public function attachStream(TSocketStream $stream, string $remote = '', bool $encrypted = false): void
	{
		$this->_stream = $stream;
		$this->_remote = $remote;
		$this->_encrypted = $encrypted;
		$timeout = $this->getTimeout();
		$stream->setTimeout((int) $timeout, (int) round(($timeout - (int) $timeout) * 1000000));
	}

	/**
	 * Upgrades the open connection to TLS.
	 * @throws TConfigurationException when the openssl extension is not loaded.
	 * @throws TNetworkException when the handshake fails or the connection is closed.
	 */
	public function startTls(): void
	{
		$this->ensureOpenSsl();
		$stream = $this->getStream();
		try {
			$enabled = $this->enableCrypto($stream);
		} catch (\Throwable $e) {
			$enabled = false;
		}
		if (!$enabled) {
			throw new TNetworkException('emailconnection_starttls_failed', $this->_remote);
		}
		$this->_encrypted = true;
	}

	/**
	 * @param TSocketStream $stream the open socket.
	 * @return bool whether the TLS handshake succeeded.
	 */
	protected function enableCrypto(TSocketStream $stream): bool
	{
		return $stream->enableCrypto(true, STREAM_CRYPTO_METHOD_TLS_CLIENT) === true;
	}

	/**
	 * @throws TConfigurationException when the openssl extension is not loaded.
	 */
	protected function ensureOpenSsl(): void
	{
		if (!extension_loaded('openssl')) {
			throw new TConfigurationException('emailconnection_openssl_required');
		}
	}

	/**
	 * Writes all the data.
	 * @param string $data the bytes to write.
	 * @throws TNetworkException when the write fails or the connection is closed.
	 */
	public function write(string $data): void
	{
		$stream = $this->getStream();
		try {
			while ($data !== '') {
				if (($written = $stream->write($data)) <= 0) {
					break;
				}
				$data = (string) substr($data, $written);
			}
		} catch (\Throwable $e) {
		}
		if ($data !== '') {
			throw new TNetworkException('emailconnection_write_failed', $this->_remote);
		}
	}

	/**
	 * Writes a line followed by CRLF.
	 * @param string $line the line without its ending.
	 * @throws TNetworkException when the write fails or the connection is closed.
	 */
	public function writeLine(string $line): void
	{
		$this->write($line . self::EOL);
	}

	/**
	 * Reads one line.
	 * @throws TNetworkException when the read times out, the connection ends, or the line is longer than {@see MAX_LINE_LENGTH}.
	 * @return string the line without its ending.
	 */
	public function readLine(): string
	{
		$stream = $this->getStream();
		try {
			$line = TStreamHelper::readLine($stream, self::MAX_LINE_LENGTH);
		} catch (\Throwable $e) {
			$line = '';
		}
		if (!str_ends_with($line, "\n")) {
			throw new TNetworkException('emailconnection_read_failed', $this->_remote);
		}
		return rtrim($line, "\r\n");
	}

	/**
	 * Reads an exact number of bytes.
	 * @param int $length the number of bytes.
	 * @throws TNetworkException when the read times out or the connection ends first.
	 * @return string the bytes.
	 */
	public function readBytes(int $length): string
	{
		$stream = $this->getStream();
		$data = '';
		try {
			while (strlen($data) < $length && ($chunk = $stream->read($length - strlen($data))) !== '') {
				$data .= $chunk;
			}
		} catch (\Throwable $e) {
		}
		if (strlen($data) !== $length) {
			throw new TNetworkException('emailconnection_read_failed', $this->_remote);
		}
		return $data;
	}

	/**
	 * Closes the connection.  Closing a closed connection does nothing.
	 */
	public function close(): void
	{
		if ($this->_stream !== null) {
			try {
				$this->_stream->close();
			} catch (\Throwable $e) {
			}
			$this->_stream = null;
			$this->_encrypted = false;
		}
	}

	/**
	 * @throws TNetworkException when the connection is closed.
	 * @return TSocketStream the open socket.
	 */
	protected function getStream(): TSocketStream
	{
		if ($this->_stream === null) {
			throw new TNetworkException('emailconnection_closed');
		}
		return $this->_stream;
	}

	/**
	 * @return bool whether the connection is open.
	 */
	public function getIsOpen(): bool
	{
		return $this->_stream !== null;
	}

	/**
	 * @return bool whether the connection is encrypted.
	 */
	public function getIsEncrypted(): bool
	{
		return $this->_encrypted;
	}

	/**
	 * @return float the connect and read timeout in seconds. Defaults to 30.
	 */
	public function getTimeout(): float
	{
		return $this->_timeout;
	}

	/**
	 * @param float|string $value the connect and read timeout in seconds, at least 1; it applies to the next connection.
	 */
	public function setTimeout($value)
	{
		$this->_timeout = max(1.0, TPropertyValue::ensureFloat($value));
	}

	/**
	 * @return bool whether the server certificate is verified. Defaults to true.
	 */
	public function getVerifyPeer(): bool
	{
		return $this->_verifyPeer;
	}

	/**
	 * @param bool|string $value whether the server certificate is verified; it applies to the next connection.
	 */
	public function setVerifyPeer($value)
	{
		$this->_verifyPeer = TPropertyValue::ensureBoolean($value);
	}
}
