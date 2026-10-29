<?php

/**
 * IRestResource interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

/**
 * IRestResource interface
 *
 * IRestResource is the contract between {@see TRestService} and a resource. At
 * initialization the service reads the routes the resource declares; on dispatch it
 * gives the resource its matched route and path parameters, lets it authorize the
 * request, calls the handler method the route names, and reads the status code and
 * headers the handler chose. {@see TRestResource} implements it with the request
 * and response helpers a handler needs.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IRestResource
{
	/**
	 * Returns the routes of the resource: the {@see TRestRoute} declarations of each
	 * handler method, keyed by method name.
	 * @return array<string,TRestRoute[]> route declarations keyed by method name.
	 */
	public function getRoutes(): array;

	/**
	 * @return string pattern prefix of every route of the resource, `''` for none.
	 */
	public function getRoutePrefix(): string;

	/**
	 * Receives the matched route and its path parameters before dispatch.
	 * @param TRestRoutePattern $route the matched route.
	 * @param array $parameters path parameter values keyed by placeholder name.
	 */
	public function setRoute(TRestRoutePattern $route, array $parameters): void;

	/**
	 * @return ?TRestRoutePattern the matched route, `null` before dispatch.
	 */
	public function getRoute(): ?TRestRoutePattern;

	/**
	 * @return array path parameter values keyed by placeholder name.
	 */
	public function getPathParameters(): array;

	/**
	 * Authorizes the request before the handler runs; throws to refuse it.
	 * @param TRestRoutePattern $route the matched route.
	 * @throws TRestException to refuse the request with its status code.
	 */
	public function authorize(TRestRoutePattern $route): void;

	/**
	 * @return int HTTP status code of the response.
	 */
	public function getStatusCode(): int;

	/**
	 * @return array response headers as name => value pairs.
	 */
	public function getResponseHeaders(): array;
}
