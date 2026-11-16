<?php

/**
 * IAsyncHttpHandler interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\IO\Async\TFuture;
use Psr\Http\Message\RequestInterface;

/**
 * IAsyncHttpHandler interface.
 *
 * IAsyncHttpHandler is an {@see IHttpHandler} that runs its transfers on the event loop,
 * so several requests proceed at once.  {@see handleAsync()} returns the future of the
 * response; {@see IHttpHandler::handle()} awaits it, which from a worker fiber yields to
 * the loop and from the main fiber runs the loop.  {@see THttpClient} chooses an
 * asynchronous handler for a request sent from inside a fiber.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IAsyncHttpHandler extends IHttpHandler
{
	/**
	 * Starts a request and returns the future of its response.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @return TFuture Resolves with the {@see \Psr\Http\Message\ResponseInterface}, or rejects
	 *   with the exception {@see IHttpHandler::handle()} would throw.
	 */
	public function handleAsync(RequestInterface $request, array $options = []): TFuture;
}
