<?php

/**
 * THttpNetworkException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * THttpNetworkException class.
 *
 * THttpNetworkException is the PSR-18 {@see NetworkExceptionInterface}: the request was
 * not answered because of a network failure, such as a DNS failure, a refused
 * connection, a timeout, or a transfer that broke before the response was complete.
 * ```php
 * try {
 *     $response = $client->sendRequest($request);
 * } catch (THttpNetworkException $e) {
 *     $failed = $e->getRequest();
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpNetworkException extends THttpClientException implements NetworkExceptionInterface
{
	/**
	 * Builds the exception for a request.
	 * @param RequestInterface $request The request that failed.
	 * @param string $errorMessage The error message code.
	 * @param mixed ...$args The message arguments; a trailing Throwable is the previous exception.
	 */
	public function __construct(RequestInterface $request, string $errorMessage, mixed ...$args)
	{
		parent::__construct($errorMessage, ...$args);
		$this->setRequestDirect($request);
	}

	/**
	 * Returns the request that failed.
	 * @return RequestInterface The request.
	 */
	public function getRequest(): RequestInterface
	{
		/** @var RequestInterface */
		return $this->getRequestDirect();
	}
}
