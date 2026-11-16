<?php

/**
 * THttpClientEventParameter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\TEventParameter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * THttpClientEventParameter class.
 *
 * THttpClientEventParameter carries a request and its outcome to the `OnBeforeSend`,
 * `OnResponse`, and `OnError` events of {@see THttpClient}.
 *
 * | Event | {@see getResponse()} | {@see getException()} |
 * |---|---|---|
 * | `OnBeforeSend` | null | null |
 * | `OnResponse` | the response | null |
 * | `OnError` | null | the exception |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpClientEventParameter extends TEventParameter
{
	/**
	 * Builds the parameter.
	 * @param RequestInterface $_request The request.
	 * @param array<string, mixed> $_options The options of the request.
	 * @param ?ResponseInterface $_response The response, or null.
	 * @param ?\Throwable $_exception The failure, or null.
	 */
	public function __construct(private RequestInterface $_request, private array $_options = [], private ?ResponseInterface $_response = null, private ?\Throwable $_exception = null)
	{
		parent::__construct();
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
	 * Returns the options of the request.
	 * @return array<string, mixed> The {@see THttpClientOption} options.
	 */
	public function getOptions(): array
	{
		return $this->_options;
	}

	/**
	 * Returns the response.
	 * @return ?ResponseInterface The response, or null before one arrives.
	 */
	public function getResponse(): ?ResponseInterface
	{
		return $this->_response;
	}

	/**
	 * Returns the failure.
	 * @return ?\Throwable The exception, or null.
	 */
	public function getException(): ?\Throwable
	{
		return $this->_exception;
	}
}
