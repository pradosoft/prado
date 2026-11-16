<?php

/**
 * IHttpHandler interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * IHttpHandler interface.
 *
 * IHttpHandler is the transport under a {@see THttpClient}: it sends one request and
 * returns its response, for every status code.  A handler sends exactly the request it
 * is given and follows no redirect; the client handles redirects, default headers, and
 * behaviors.  The `$options` keys are the {@see THttpClientOption} constants.
 *
 * A handler throws {@see THttpNetworkException} when no response arrives and
 * {@see THttpRequestException} when the request cannot be sent.  A handler that waits
 * on the event loop, such as {@see TSocketHttpHandler}, yields to other fibers while it
 * waits; {@see IAsyncHttpHandler} also returns the response as a future.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IHttpHandler
{
	/**
	 * Reports whether this handler can run on the current PHP, such as whether its
	 * extension is loaded.
	 * @return bool Whether the handler can run here.
	 */
	public static function getIsAvailable(): bool;

	/**
	 * Sends a request and returns its response.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @throws THttpNetworkException When no response arrives.
	 * @throws THttpRequestException When the request cannot be sent.
	 * @throws \Prado\Exceptions\TCancelledException When the {@see THttpClientOption::TOKEN} is cancelled.
	 * @return ResponseInterface The response.
	 */
	public function handle(RequestInterface $request, array $options = []): ResponseInterface;
}
