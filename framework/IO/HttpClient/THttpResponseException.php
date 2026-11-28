<?php

/**
 * THttpResponseException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * THttpResponseException class.
 *
 * THttpResponseException reports a 4xx or 5xx response as an error.  A PSR-18
 * {@see THttpClient} returns such a response without throwing; a layer that treats it as
 * a failure throws this exception, such as {@see \Prado\IO\Rest\TRestClient} or a
 * client whose {@see THttpClient::setHttpErrors HttpErrors} is true.  The exception code
 * is the status code.
 * ```php
 * try {
 *     $items = $api->get('items');
 * } catch (THttpResponseException $e) {
 *     $status = $e->getStatusCode();
 *     $body = (string) $e->getResponse()->getBody();
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpResponseException extends THttpRequestException
{
	/** @var ResponseInterface The error response. */
	private ResponseInterface $_response;

	/**
	 * Builds the exception for an error response.
	 * @param RequestInterface $request The request.
	 * @param ResponseInterface $response The error response.
	 * @param string $errorMessage The error message code; its first argument is the status code.
	 * @param mixed ...$args Further message arguments; a trailing Throwable is the previous exception.
	 */
	public function __construct(RequestInterface $request, ResponseInterface $response, string $errorMessage = 'httpclient_http_error', mixed ...$args)
	{
		parent::__construct($request, $errorMessage, $response->getStatusCode(), $response->getReasonPhrase(), ...$args);
		$this->_response = $response;
		$this->code = $response->getStatusCode();
	}

	/**
	 * Returns the error response.
	 * @return ResponseInterface The response.
	 */
	public function getResponse(): ResponseInterface
	{
		return $this->_response;
	}

	/**
	 * Returns the status code of the error response.
	 * @return int The status code.
	 */
	public function getStatusCode(): int
	{
		return $this->getResponse()->getStatusCode();
	}
}
