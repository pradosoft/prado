<?php

/**
 * THttpClientException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Exceptions\TException;

/**
 * THttpClientException class.
 *
 * THttpClientException reports a failure during a {@see THttpClient} call (or
 * any wrapper built on top, such as {@see \Prado\IO\TRestClient}). Two
 * distinct failure shapes share this single type:
 *
 *  - **Transport failure** — DNS resolution failed, the connection timed out,
 *    the server refused the connection, etc. The HTTP exchange never
 *    completed; {@see getResponse()} returns `null` and {@see getStatusCode()}
 *    returns `0`.
 *  - **HTTP error** — a response was received, but its status code falls
 *    outside the 2xx range and the caller is configured to throw on errors.
 *    {@see getResponse()} returns the full {@see THttpClientResponse}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpClientException extends TException
{
	/**
	 * @var ?THttpClientResponse Response object for HTTP errors; null for
	 *   transport failures.
	 */
	private ?THttpClientResponse $_response = null;

	/**
	 * Constructor.
	 * @param string $errorMessage Error message key or text.
	 * @param mixed ...$parameters Message parameters forwarded to {@see TException}.
	 */
	public function __construct($errorMessage, ...$parameters)
	{
		parent::__construct($errorMessage, ...$parameters);
	}

	/**
	 * Creates an exception representing an unsuccessful HTTP response.
	 * @param THttpClientResponse $response Response that triggered the failure.
	 * @return self
	 */
	public static function fromResponse(THttpClientResponse $response): self
	{
		$e = new self('httpclient_http_error', $response->getStatusCode());
		$e->_response = $response;
		$e->code = $response->getStatusCode();
		return $e;
	}

	/**
	 * @return ?THttpClientResponse Response for HTTP errors, or null for
	 *   transport-level failures.
	 */
	public function getResponse(): ?THttpClientResponse
	{
		return $this->_response;
	}

	/**
	 * @return int HTTP status code for HTTP errors, or 0 for transport failures.
	 */
	public function getStatusCode(): int
	{
		return $this->_response?->getStatusCode() ?? 0;
	}
}
