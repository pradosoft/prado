<?php

/**
 * THttpRequestException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * THttpRequestException class.
 *
 * THttpRequestException is the PSR-18 {@see RequestExceptionInterface}: the request
 * cannot be sent or completed as given, such as a request with an unsupported scheme, a
 * malformed request, or a redirect chain longer than the client allows.
 * {@see THttpResponseException} extends it for an error response.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpRequestException extends THttpClientException implements RequestExceptionInterface
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
