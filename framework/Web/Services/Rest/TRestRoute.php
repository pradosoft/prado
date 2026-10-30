<?php

/**
 * TRestRoute class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

/**
 * TRestRoute class
 *
 * TRestRoute is the PHP attribute that declares what a {@see TRestResource} method
 * handles. On a method it names the HTTP verbs and the URL pattern; on a class it
 * supplies the pattern prefix shared by every method of the resource.
 * {@see TRestService} compiles each declaration into a {@see TRestRoutePattern}.
 *
 * ```php
 * #[TRestRoute(pattern: 'users')]
 * class UsersResource extends TRestResource
 * {
 *     #[TRestRoute('GET')]
 *     public function index(): array { ... }                 // GET  users
 *
 *     #[TRestRoute('GET', '{id}', parameters: ['id' => '\d+'])]
 *     public function show(int $id): array { ... }          // GET  users/{id}
 *
 *     #[TRestRoute('PUT,PATCH', '{id:\d+}', name: 'users.save')]
 *     public function save(int $id): array { ... }          // PUT or PATCH users/{id}
 * }
 * ```
 *
 * The pattern syntax is that of {@see \Prado\Web\TUrlMappingPattern}: `{name}`
 * placeholders constrained by `$parameters`, or inline as `{name:regex}`; a
 * placeholder without a constraint matches one path segment. `$verbs` takes the
 * {@see \Prado\Web\TUrlMappingPattern::setVerbs() Verbs} syntax, `null` matching
 * every verb. `$name` is the route name for {@see TRestService::constructUrl()};
 * it defaults to the resource class short name and the method name joined by a dot.
 *
 * A method may carry the attribute more than once, one route each. A subclass
 * method without the attribute keeps the routes of the method it overrides, and a
 * class without one keeps the prefix of its nearest ancestor that declares one;
 * {@see TRestResource::getRoutes()} is the lookup, and a resource overrides it to
 * declare its routes as a table instead.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class TRestRoute
{
	/**
	 * @param ?string $verbs HTTP verbs the method handles, `null` for every verb.
	 * @param string $pattern URL pattern relative to the class prefix, `''` for the prefix itself.
	 * @param array $parameters Placeholder constraints keyed by placeholder name.
	 * @param ?string $name Route name for reverse routing; `null` derives it from the class and method.
	 * @param array $query GET variable constraints keyed by variable name.
	 */
	public function __construct(
		public readonly ?string $verbs = null,
		public readonly string $pattern = '',
		public readonly array $parameters = [],
		public readonly ?string $name = null,
		public readonly array $query = [],
	) {
	}
}
