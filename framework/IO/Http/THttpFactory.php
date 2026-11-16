<?php

/**
 * THttpFactory class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Http;

use Prado\IO\TResourceUri;
use Prado\IO\TStreamFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * THttpFactory class
 *
 * THttpFactory implements the PSR-17 request, response, URI, and stream factories in one
 * object.  It builds {@see TRequest}, {@see TResponse}, {@see TResourceUri}, and, through
 * {@see TStreamFactory}, {@see \Prado\IO\TStream}.  Hand it to any library that takes a
 * PSR-17 factory.
 * ```php
 * $factory = new THttpFactory();
 * $request = $factory->createRequest('GET', 'https://example.com/')
 *     ->withBody($factory->createStream('payload'));
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpFactory extends TStreamFactory implements RequestFactoryInterface, ResponseFactoryInterface, UriFactoryInterface
{
	/**
	 * Creates a request with the method and URI and no body.
	 * @param string $method The request method.
	 * @param string|UriInterface $uri The request URI.
	 * @throws \InvalidArgumentException When the method or URI is invalid.
	 * @return RequestInterface The {@see TRequest}.
	 */
	public function createRequest(string $method, $uri): RequestInterface
	{
		return new TRequest($method, $uri);
	}

	/**
	 * Creates a response with the status code and reason phrase and no body.
	 * @param int $code The status code.
	 * @param string $reasonPhrase The reason phrase, or `''` for the standard phrase.
	 * @throws \InvalidArgumentException When the status code is out of range.
	 * @return ResponseInterface The {@see TResponse}.
	 */
	public function createResponse(int $code = TResponse::DEFAULT_STATUS, string $reasonPhrase = ''): ResponseInterface
	{
		return new TResponse($code, [], null, TResponse::DEFAULT_PROTOCOL_VERSION, $reasonPhrase === '' ? null : $reasonPhrase);
	}

	/**
	 * Creates a URI from a string.
	 * @param string $uri The URI, or `''` for an empty URI.
	 * @throws \InvalidArgumentException When the URI cannot be parsed.
	 * @return UriInterface The {@see TResourceUri}.
	 */
	public function createUri(string $uri = ''): UriInterface
	{
		return new TResourceUri($uri);
	}
}
