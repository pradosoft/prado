<?php

/**
 * TCurlTransfer class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * TCurlTransfer class.
 *
 * TCurlTransfer holds the state of one cURL transfer of a {@see TCurlHttpHandler} or
 * {@see TCurlMultiHttpHandler}: the request, its options, the cURL handle, the response
 * body sink, the response header lines collected so far, and the first exception a
 * cURL callback raised.  The cURL callbacks write to it while the transfer runs.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCurlTransfer
{
	/** @var ?\CurlHandle The cURL handle. */
	private ?\CurlHandle $_handle = null;

	/** @var string[] The response header lines of the current header block. */
	private array $_headerLines = [];

	/** @var ?\Throwable The first exception a callback raised. */
	private ?\Throwable $_error = null;

	/**
	 * Builds the state of a transfer.
	 * @param RequestInterface $_request The request.
	 * @param array<string, mixed> $_options The resolved options.
	 * @param StreamInterface $_sink The response body sink.
	 */
	public function __construct(private RequestInterface $_request, private array $_options, private StreamInterface $_sink)
	{
	}

	/**
	 * Returns the request.
	 * @return RequestInterface The request.
	 */
	public function getRequest(): RequestInterface
	{
		return $this->_request;
	}

	/**
	 * Returns the resolved options.
	 * @return array<string, mixed> The options.
	 */
	public function getOptions(): array
	{
		return $this->_options;
	}

	/**
	 * Returns the response body sink.
	 * @return StreamInterface The sink.
	 */
	public function getSink(): StreamInterface
	{
		return $this->_sink;
	}

	/**
	 * Returns the cURL handle.
	 * @return \CurlHandle The handle.
	 */
	public function getHandle(): \CurlHandle
	{
		/** @var \CurlHandle */
		return $this->_handle;
	}

	/**
	 * Sets the cURL handle.
	 * @param \CurlHandle $value The handle.
	 */
	public function setHandle(\CurlHandle $value): void
	{
		$this->_handle = $value;
	}

	/**
	 * Returns the response header lines of the current header block.
	 * @return string[] The lines, the status line first.
	 */
	public function getHeaderLines(): array
	{
		return $this->_headerLines;
	}

	/**
	 * Appends a response header line.
	 * @param string $line The line, without its line ending.
	 */
	public function addHeaderLine(string $line): void
	{
		$this->_headerLines[] = $line;
	}

	/**
	 * Clears the header lines, at the status line of a new header block.
	 */
	public function clearHeaderLines(): void
	{
		$this->_headerLines = [];
	}

	/**
	 * Returns the first exception a callback raised.
	 * @return ?\Throwable The exception, or null.
	 */
	public function getError(): ?\Throwable
	{
		return $this->_error;
	}

	/**
	 * Records an exception a callback raised; a later one does not replace the first.
	 * @param \Throwable $value The exception.
	 */
	public function setError(\Throwable $value): void
	{
		$this->_error ??= $value;
	}
}
