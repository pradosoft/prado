<?php

/**
 * THttpClientException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Exceptions\TNetworkException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * THttpClientException class.
 *
 * THttpClientException is the PSR-18 {@see ClientExceptionInterface} base of every
 * failure of a {@see THttpClient} or a client built on it, such as
 * {@see \Prado\IO\Rest\TRestClient}.  A client throws one of its subclasses:
 *
 * | Exception | Failure |
 * |---|---|
 * | {@see THttpNetworkException} | No response: DNS failure, refused connection, timeout, or a broken transfer. |
 * | {@see THttpRequestException} | The request cannot be sent: an unsupported scheme, a malformed request, or too many redirects. |
 * | {@see THttpResponseException} | A 4xx or 5xx response, thrown only by layers that ask for it, such as `TRestClient`. |
 *
 * {@see getRequest()} returns the request that failed, or null when there is none.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpClientException extends TNetworkException implements ClientExceptionInterface
{
	/** @var ?RequestInterface The request that failed. */
	private ?RequestInterface $_request = null;

	/**
	 * Returns the request that failed.
	 * @return ?RequestInterface The request, or null when there is none.
	 */
	public function getRequest(): ?RequestInterface
	{
		return $this->getRequestDirect();
	}

	/** @return ?RequestInterface Returns the stored request. */
	protected function getRequestDirect(): ?RequestInterface
	{
		return $this->_request;
	}

	/** @param ?RequestInterface $value Stores the request that failed. */
	protected function setRequestDirect(?RequestInterface $value): void
	{
		$this->_request = $value;
	}
}
