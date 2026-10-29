<?php

/**
 * TRestService class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Security\TAuthorizationRule;
use Prado\TApplicationMode;
use Prado\TPropertyValue;
use Prado\Util\Log\TLogger;
use Prado\Web\THttpHeaderName;
use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TMediaType;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TRestService class
 *
 * TRestService serves a JSON HTTP API from {@see IRestResource} classes, as the
 * backend of a single-page application or of any client of the API. Each
 * resource declares the HTTP verb and URL pattern of its handler methods with
 * {@see TRestRoute}; the service compiles them into {@see TRestRoutePattern}
 * routes, matches the request, and calls the handler with the path parameters.
 *
 * ## Request lifecycle
 *
 * | Step | What happens |
 * |---|---|
 * | `init()` | The configuration is read, each resource is created and asked for its routes, the request path is matched (see *Routing*), and the `<authorization>` rules of the matched route join the application rules |
 * | `onAuthorization` | {@see \Prado\Security\TAuthManager} applies the rules; a refusal is written as a `401` problem document |
 * | `run()` | {@see \Prado\TService::ensureCsrfToken()} when `EnableCsrfValidation` is on, a `400` problem document when the token is missing; `404` when no route matched the path; `405` with an `Allow` header when the path matched another verb; otherwise the resource receives the route, {@see onPreRunResource()} is raised, {@see IRestResource::authorize()} runs, the handler runs, and its return value is written as JSON with the status code and headers the resource chose. `HEAD` and `204` send no body |
 * | an exception | {@see onError()} is raised and the exception is written as a problem document (see *Error responses*) |
 *
 * ## Routing
 *
 * A route pattern is `BasePath`, the group prefix, the resource prefix and the
 * handler pattern joined by `/`. Placeholders are `{name}`, constrained by
 * `parameters.name` or inline as `{name:\d+}`, and match one path segment by
 * default. Routes are tried in declaration order. The path is matched first and
 * the verb second, so the first route whose path and verb both match wins, and a
 * path that matches only routes of other verbs is a `405` whose `Allow` header
 * lists their verbs. A `HEAD` request runs the `GET` handler without a body
 * unless a route names `HEAD` itself.
 *
 * {@see constructUrl()} is the reverse: the URL of a named route for a set of
 * parameter values. A route is named by its attribute, or by the resource class
 * short name and the method name joined by a dot.
 *
 * ## Configuration
 *
 * ```xml
 * <service id="rest" class="Prado\Web\Services\Rest\TRestService" BasePath="api">
 *   <resource class="App\Api\UsersResource" />
 *   <resource class="App\Api\PostsResource" prefix="posts" PageSize="50">
 *     <authorization>
 *       <allow roles="editor" verb="POST,PUT,PATCH,DELETE" />
 *       <deny users="?" verb="POST,PUT,PATCH,DELETE" />
 *     </authorization>
 *   </resource>
 *   <route verbs="GET" pattern="health" class="App\Api\StatusResource" method="health" name="health" />
 *   <group prefix="v2">
 *     <include file="Application.config.rest-v2" />
 *   </group>
 *   <group prefix="debug" enabled="Debug">
 *     <resource class="App\Api\Debug\DumpResource" />
 *   </group>
 * </service>
 * ```
 *
 * | Element | Meaning |
 * |---|---|
 * | `<resource class prefix enabled …>` | A resource class; its routes come from {@see IRestResource::getRoutes()}. `prefix` replaces the class attribute prefix. Any other attribute is a property of the resource |
 * | `<route verbs pattern class method name parameters.x query.x enabled>` | One route declared in the configuration; `class` and `method` name the handler |
 * | `<group prefix enabled>` | Resources, routes, groups and includes sharing a prefix |
 * | `<include file>` | A configuration file in PRADO namespace format, in the application's configuration type, whose root holds the same elements |
 * | `<authorization>` | `<allow>` and `<deny>` rules of {@see \Prado\Security\TAuthorizationRule}, applying to the routes of the element that holds it |
 *
 * `enabled` takes a boolean or `Debug`, which enables the element in
 * {@see \Prado\TApplicationMode::Debug} mode only. The PHP configuration uses the
 * same names as array keys:
 * ```php
 * 'services' => [
 *   'rest' => [
 *     'class' => \Prado\Web\Services\Rest\TRestService::class,
 *     'properties' => ['BasePath' => 'api'],
 *     'resources' => [
 *       ['class' => \App\Api\UsersResource::class],
 *       ['class' => \App\Api\PostsResource::class, 'prefix' => 'posts', 'PageSize' => 50,
 *        'authorization' => [['action' => 'allow', 'roles' => 'editor', 'verb' => 'POST,PUT,PATCH,DELETE']]],
 *     ],
 *     'routes' => [
 *       ['verbs' => 'GET', 'pattern' => 'health', 'class' => \App\Api\StatusResource::class, 'method' => 'health'],
 *     ],
 *     'groups' => [
 *       ['prefix' => 'v2', 'include' => 'Application.config.rest-v2'],
 *       ['prefix' => 'debug', 'enabled' => 'Debug', 'resources' => [['class' => \App\Api\Debug\DumpResource::class]]],
 *     ],
 *   ],
 * ],
 * ```
 * The request reaches the service through the URL manager as any other service
 * does; a `TUrlMapping` rule such as `<url ServiceID="rest" ServiceParameter="api" pattern="api/" UrlFormat="Path" />`
 * routes `/index.php/api/...` to it, and the service matches `PathInfo` itself.
 *
 * ## Error responses
 *
 * Every error is a {@see TRestException} written as an RFC 9457 problem document
 * with the `application/problem+json` media type:
 * ```json
 * { "status": 404, "title": "Not Found", "detail": "User 42 not found." }
 * { "status": 422, "title": "Unprocessable Entity", "detail": "The given data was invalid.",
 *   "errors": { "email": ["The email field is required."] } }
 * ```
 * A {@see \Prado\Exceptions\THttpException} keeps its status code and message. Any
 * other exception is logged and written as a `500` whose `detail` is the message
 * when {@see setExposeErrors() ExposeErrors} is on, as it is in Debug mode.
 *
 * ## Cross-origin requests
 *
 * {@see \Prado\Web\HttpHeaders\TCorsModule} answers preflights and sends the
 * `Access-Control-*` headers for every service, and a `PathPrefix` equal to
 * `BasePath` limits it to the API.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRestService extends \Prado\TService
{
	/**
	 * @var string[] the verbs an `Allow` header lists for a route without `Verbs`.
	 */
	public const STANDARD_VERBS = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

	/**
	 * @var string path prefix of every route.
	 */
	private string $_basePath = '';

	/**
	 * @var bool whether a `500` carries the exception message.
	 */
	private bool $_exposeErrors = false;

	/**
	 * @var string class of the routes.
	 */
	private string $_routeClass = TRestRoutePattern::class;

	/**
	 * @var TRestRoutePattern[] the routes in declaration order.
	 */
	private array $_routes = [];

	/**
	 * @var array<string,IRestResource> the resources keyed by class name.
	 */
	private array $_resources = [];

	/**
	 * @var array<int,array{0:TRestRoutePattern,1:array}> the routes whose path matched the request.
	 */
	private array $_pathMatches = [];

	/**
	 * @var ?TRestRoutePattern the route serving the request.
	 */
	private ?TRestRoutePattern $_route = null;

	/**
	 * @var array path parameters of the route serving the request.
	 */
	private array $_pathParameters = [];

	// ── Initialization ────────────────────────────────────────────────────────

	/**
	 * Reads the configuration, matches the request, and merges the authorization
	 * rules of the matched route into the application rules.
	 * @param null|array|TXmlElement $config the service configuration.
	 * @throws TConfigurationException when a resource, route or include is invalid.
	 */
	public function init($config): void
	{
		$this->setExposeErrors($this->getApplication()->getMode() === TApplicationMode::Debug);
		$this->loadConfig($config);
		$this->resolveRoute();
		if ($this->getRoute() !== null) {
			$this->getApplication()->getAuthorizationRules()->mergeWith($this->getRoute()->getAuthorizationRules());
		}
		$this->getApplication()->attachEventHandler('onAuthorization', [$this, 'handleUnauthorized']);
		parent::init($config);
	}

	/**
	 * Loads the resources, routes, groups and includes of a configuration.
	 * @param null|array|TXmlElement $config the service configuration.
	 */
	protected function loadConfig(mixed $config): void
	{
		if ($config instanceof TXmlElement) {
			$config = $this->xmlToArray($config);
		}
		if (is_array($config)) {
			$this->loadRoutes($config, '', []);
		}
	}

	/**
	 * Converts a configuration element to the array form: attributes become keys,
	 * `parameters.x` and `query.x` nest, and the `resource`, `route`, `group`,
	 * `include` and `authorization` children become the lists of the same names.
	 * @param TXmlElement $element the element.
	 * @return array the configuration array.
	 */
	protected function xmlToArray(TXmlElement $element): array
	{
		$config = [];
		foreach ($element->getAttributes()->toArray() as $key => $value) {
			if (str_starts_with($key, 'parameters.')) {
				$config['parameters'][substr($key, 11)] = $value;
			} elseif (str_starts_with($key, 'query.')) {
				$config['query'][substr($key, 6)] = $value;
			} else {
				$config[$key] = $value;
			}
		}
		foreach ($element->getElements() as $child) {
			switch (strtolower($child->getTagName())) {
				case 'resource':
					$config['resources'][] = $this->xmlToArray($child);
					break;
				case 'route':
					$config['routes'][] = $this->xmlToArray($child);
					break;
				case 'group':
					$config['groups'][] = $this->xmlToArray($child);
					break;
				case 'include':
					$config['include'][] = $child->getAttribute('file');
					break;
				case 'authorization':
					foreach ($child->getElements() as $rule) {
						$config['authorization'][] = ['action' => $rule->getTagName()] + $rule->getAttributes()->toArray();
					}
					break;
			}
		}
		return $config;
	}

	/**
	 * Registers the routes of a configuration level: a service, group, include or
	 * resource. The level's prefix joins the inherited prefix, and its
	 * authorization rules join the inherited rules.
	 * @param array $config the configuration of the level.
	 * @param string $prefix the inherited pattern prefix.
	 * @param TAuthorizationRule[] $rules the inherited authorization rules.
	 */
	protected function loadRoutes(array $config, string $prefix, array $rules): void
	{
		if (!$this->isEnabled($config['enabled'] ?? true)) {
			return;
		}
		$prefix = $this->joinPath($prefix, (string) ($config['prefix'] ?? ''));
		$rules = array_merge($rules, $this->createAuthorizationRules($config['authorization'] ?? []));
		foreach ((array) ($config['include'] ?? []) as $file) {
			$this->loadRoutes($this->loadIncludeFile((string) $file), $prefix, $rules);
		}
		foreach ($config['resources'] ?? [] as $item) {
			$this->registerResource($item, $prefix, $rules);
		}
		foreach ($config['routes'] ?? [] as $item) {
			$this->registerRoute($item, $prefix, $rules);
		}
		foreach ($config['groups'] ?? [] as $item) {
			$this->loadRoutes($item, $prefix, $rules);
		}
	}

	/**
	 * Registers a resource: creates it, applies its properties, and adds a route for
	 * each declaration of {@see IRestResource::getRoutes()} and each `routes` entry.
	 * @param array $item the resource configuration.
	 * @param string $prefix the inherited pattern prefix.
	 * @param TAuthorizationRule[] $rules the inherited authorization rules.
	 * @throws TConfigurationException when `class` is absent or not an IRestResource.
	 */
	protected function registerResource(array $item, string $prefix, array $rules): void
	{
		if (!$this->isEnabled($item['enabled'] ?? true)) {
			return;
		}
		$class = (string) ($item['class'] ?? '');
		if ($class === '') {
			throw new TConfigurationException('restservice_class_required');
		}
		$properties = array_diff_key($item, array_flip(['class', 'prefix', 'enabled', 'authorization', 'routes']));
		$resource = $this->createResource($class, $properties);
		$prefix = $this->joinPath($prefix, array_key_exists('prefix', $item) ? (string) $item['prefix'] : $resource->getRoutePrefix());
		$rules = array_merge($rules, $this->createAuthorizationRules($item['authorization'] ?? []));
		foreach ($resource->getRoutes() as $method => $declarations) {
			foreach ($declarations as $declaration) {
				if (!($declaration instanceof TRestRoute)) {
					throw new TConfigurationException('restservice_route_invalid', $class, $method);
				}
				$this->addRoute(
					$this->joinPath($prefix, $declaration->pattern),
					$resource::class,
					(string) $method,
					$declaration->verbs,
					$declaration->parameters,
					$declaration->query,
					$declaration->name ?? $this->getDefaultRouteName($resource::class, (string) $method),
					$rules,
					$properties,
				);
			}
		}
		foreach ($item['routes'] ?? [] as $route) {
			$this->registerRoute(['class' => $class] + $route, $prefix, $rules);
		}
	}

	/**
	 * Registers a route declared in the configuration.
	 * @param array $item the route configuration: `class`, `method`, `pattern`, and optionally `verbs`, `name`, `parameters`, `query`, `enabled`.
	 * @param string $prefix the inherited pattern prefix.
	 * @param TAuthorizationRule[] $rules the inherited authorization rules.
	 * @throws TConfigurationException when `class`, `method` or `pattern` is absent.
	 */
	protected function registerRoute(array $item, string $prefix, array $rules): void
	{
		if (!$this->isEnabled($item['enabled'] ?? true)) {
			return;
		}
		$class = (string) ($item['class'] ?? '');
		if ($class === '') {
			throw new TConfigurationException('restservice_class_required');
		}
		$method = (string) ($item['method'] ?? '');
		if ($method === '') {
			throw new TConfigurationException('restservice_route_method_required', $class);
		}
		if (!array_key_exists('pattern', $item)) {
			throw new TConfigurationException('restservice_route_pattern_required', $class, $method);
		}
		$resource = $this->getResource($class) ?? $this->createResource($class, []);
		$rules = array_merge($rules, $this->createAuthorizationRules($item['authorization'] ?? []));
		$this->addRoute(
			$this->joinPath($prefix, (string) $item['pattern']),
			$resource::class,
			$method,
			$item['verbs'] ?? null,
			(array) ($item['parameters'] ?? []),
			(array) ($item['query'] ?? []),
			(string) ($item['name'] ?? $this->getDefaultRouteName($resource::class, $method)),
			$rules,
			[],
		);
	}

	/**
	 * Creates a route and appends it to the route table.
	 * @param string $pattern the pattern under `BasePath`.
	 * @param string $class the resource class.
	 * @param string $method the handler method.
	 * @param null|array|string $verbs the verbs, `null` for every verb.
	 * @param array $parameters placeholder constraints keyed by name.
	 * @param array $query GET variable constraints keyed by name.
	 * @param string $name the route name.
	 * @param TAuthorizationRule[] $rules the authorization rules of the route.
	 * @param array $properties the resource properties of the declaration.
	 * @throws TConfigurationException when `RouteClass` is not a TRestRoutePattern.
	 * @return TRestRoutePattern the route.
	 */
	protected function addRoute(string $pattern, string $class, string $method, null|array|string $verbs, array $parameters, array $query, string $name, array $rules, array $properties): TRestRoutePattern
	{
		$route = Prado::createComponent($this->getRouteClass());
		if (!($route instanceof TRestRoutePattern)) {
			throw new TConfigurationException('restservice_routeclass_invalid', $this->getRouteClass());
		}
		$route->setServiceID($this->getID());
		$route->setPattern($this->joinPath($this->getBasePath(), $pattern));
		$route->setVerbs($verbs);
		$route->setName($name);
		$route->setResource($class);
		$route->setMethod($method);
		$route->setResourceProperties($properties);
		foreach ($parameters as $key => $value) {
			$route->getParameters()->add($key, $value);
		}
		foreach ($query as $key => $value) {
			$route->getQuery()->add($key, $value);
		}
		foreach ($rules as $rule) {
			$route->addAuthorizationRule($rule);
		}
		$route->init([]);
		$this->addRouteDirect($route);
		return $route;
	}

	/**
	 * Creates a resource and applies its configured properties. A class is created
	 * once; a later declaration of the same class reuses the instance.
	 * @param string $class the resource class, in namespace or dot notation.
	 * @param array $properties property values of the declaration.
	 * @throws TConfigurationException when the class is not an IRestResource.
	 * @return IRestResource the resource.
	 */
	protected function createResource(string $class, array $properties): IRestResource
	{
		$resource = Prado::createComponent($class);
		if (!($resource instanceof IRestResource)) {
			throw new TConfigurationException('restservice_resource_invalid', $class);
		}
		if ($properties !== [] && $resource instanceof \Prado\TComponent) {
			$resource->setSubProperties($properties);
		}
		$resources = $this->getResourcesDirect();
		$resources[$resource::class] = $resource;
		$this->setResourcesDirect($resources);
		return $resource;
	}

	/**
	 * Builds the {@see TAuthorizationRule} objects of an `authorization` list.
	 * @param array $items rules as `action`, `users`, `roles`, `verb`, `ips`, `priority` keys.
	 * @return TAuthorizationRule[] the rules.
	 */
	protected function createAuthorizationRules(array $items): array
	{
		$rules = [];
		foreach ($items as $item) {
			$rules[] = new TAuthorizationRule(
				(string) ($item['action'] ?? 'allow'),
				(string) ($item['users'] ?? ''),
				(string) ($item['roles'] ?? ''),
				(string) ($item['verb'] ?? ''),
				(string) ($item['ips'] ?? ''),
				$item['priority'] ?? null,
			);
		}
		return $rules;
	}

	/**
	 * Loads an included configuration file in the application's configuration type.
	 * @param string $file the file in PRADO namespace format, without extension.
	 * @throws TConfigurationException when the file is not found.
	 * @return array the configuration array of the file.
	 */
	protected function loadIncludeFile(string $file): array
	{
		$application = $this->getApplication();
		$path = Prado::getPathOfNamespace($file, $application->getConfigurationFileExt());
		if ($path === null || !is_file($path)) {
			throw new TConfigurationException('restservice_include_not_found', $file);
		}
		if ($application->getConfigurationType() === \Prado\TApplication::CONFIG_TYPE_PHP) {
			$config = include $path;
			return is_array($config) ? $config : [];
		}
		$dom = new TXmlDocument('1.0', 'UTF-8');
		$dom->loadFromFile($path);
		return $this->xmlToArray($dom);
	}

	/**
	 * Resolves an `enabled` value: a boolean, or `Debug` for Debug mode only.
	 * @param mixed $value the configured value.
	 * @return bool whether the element is enabled.
	 */
	protected function isEnabled(mixed $value): bool
	{
		if (is_string($value) && strcasecmp($value, 'Debug') === 0) {
			return $this->getApplication()->getMode() === TApplicationMode::Debug;
		}
		return TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Joins two pattern parts with one slash, ignoring an empty part.
	 * @param string $prefix the first part.
	 * @param string $pattern the second part.
	 * @return string the joined pattern without leading or trailing slash.
	 */
	protected function joinPath(string $prefix, string $pattern): string
	{
		$prefix = trim($prefix, '/');
		$pattern = trim($pattern, '/');
		if ($prefix === '') {
			return $pattern;
		}
		return ($pattern === '') ? $prefix : $prefix . '/' . $pattern;
	}

	/**
	 * @param string $class the resource class.
	 * @param string $method the handler method.
	 * @return string the default route name: the class short name and the method joined by a dot.
	 */
	protected function getDefaultRouteName(string $class, string $method): string
	{
		return substr($class, (int) strrpos('\\' . $class, '\\')) . '.' . $method;
	}

	// ── Routing ───────────────────────────────────────────────────────────────

	/**
	 * Matches the request against the routes: the path first, then the verb. A
	 * `HEAD` request takes a `GET` route when none names `HEAD`.
	 */
	protected function resolveRoute(): void
	{
		$request = $this->getRequest();
		$verb = strtoupper((string) ($request->getRequestType() ?? 'GET'));
		$matches = [];
		$route = null;
		$parameters = [];
		foreach ($this->getRoutes() as $candidate) {
			$values = $candidate->matchPath($request);
			if ($values === null) {
				continue;
			}
			$matches[] = [$candidate, $values];
			if ($route === null && $candidate->acceptsVerb($verb)) {
				$route = $candidate;
				$parameters = $values;
			}
		}
		if ($route === null && $verb === 'HEAD') {
			foreach ($matches as [$candidate, $values]) {
				if ($candidate->acceptsVerb('GET')) {
					$route = $candidate;
					$parameters = $values;
					break;
				}
			}
		}
		$this->setPathMatchesDirect($matches);
		$this->setRouteDirect($route);
		$this->setPathParametersDirect($parameters);
	}

	/**
	 * Returns the verbs the routes matching the request path serve, for the
	 * `Allow` header. A route without `Verbs` serves {@see STANDARD_VERBS}; `HEAD`
	 * accompanies `GET`.
	 * @return string[] the verbs in {@see STANDARD_VERBS} order.
	 */
	public function getAllowedVerbs(): array
	{
		$allowed = [];
		foreach ($this->getPathMatches() as [$route]) {
			$verbs = $route->getVerbs();
			if ($verbs === null) {
				return self::STANDARD_VERBS;
			}
			foreach ($verbs as $verb) {
				if (!str_starts_with($verb, '!') && !str_starts_with($verb, '~')) {
					$allowed[strtoupper($verb)] = true;
				}
			}
		}
		if (isset($allowed['GET'])) {
			$allowed['HEAD'] = true;
		}
		return array_values(array_filter(self::STANDARD_VERBS, fn ($verb) => isset($allowed[$verb])));
	}

	/**
	 * Returns the URL of a named route.
	 * @param string $name the route name.
	 * @param array $parameters placeholder values keyed by placeholder name.
	 * @param array $getItems GET variables appended as the query string.
	 * @param bool $encodeAmpersand whether `&amp;` separates the GET variables.
	 * @throws TInvalidDataValueException when no route has the name, or a placeholder has no value.
	 * @return string the URL, from the application entry script.
	 */
	public function constructUrl(string $name, array $parameters = [], array $getItems = [], bool $encodeAmpersand = false): string
	{
		$route = $this->getRouteByName($name);
		if ($route === null) {
			throw new TInvalidDataValueException('restservice_route_unknown', $name);
		}
		$request = $this->getRequest();
		if ($request->getUrlFormat() === THttpRequestUrlFormat::HiddenPath) {
			$base = rtrim(str_replace('\\', '/', dirname((string) $request->getApplicationUrl())), '/');
		} else {
			$base = (string) $request->getApplicationUrl();
		}
		return $base . '/' . $route->constructPath($parameters, $getItems, $encodeAmpersand);
	}

	/**
	 * @param string $name the route name.
	 * @return ?TRestRoutePattern the first route of the name, `null` for none.
	 */
	public function getRouteByName(string $name): ?TRestRoutePattern
	{
		foreach ($this->getRoutes() as $route) {
			if ($route->getName() === $name) {
				return $route;
			}
		}
		return null;
	}

	// ── Request handling ──────────────────────────────────────────────────────

	/**
	 * Runs the service: refuses a request without a route, or dispatches to the
	 * resource and writes its result.
	 */
	public function run(): void
	{
		try {
			$this->ensureCsrfToken();
			$route = $this->getRoute();
			if ($route === null) {
				if ($this->getPathMatches() !== []) {
					throw TRestException::methodNotAllowed();
				}
				throw TRestException::notFound();
			}
			$resource = $this->getResource((string) $route->getResource());
			if ($resource === null) {
				throw new TConfigurationException('restservice_resource_invalid', (string) $route->getResource());
			}
			$resource->setRoute($route, $this->getPathParameters());
			$this->onPreRunResource($resource);
			$resource->authorize($route);
			$result = $this->dispatch($resource, $route, $this->getPathParameters());
			$statusCode = $resource->getStatusCode();
			$headers = $resource->getResponseHeaders();
			$verb = strtoupper((string) $this->getRequest()->getRequestType());
			if ($verb === 'HEAD' || $statusCode === 204) {
				$this->sendHeaders($statusCode, $headers);
				return;
			}
			$this->sendJsonResponse($result, $statusCode, $headers);
		} catch (TRestException $e) {
			$this->handleError($e);
		} catch (THttpException $e) {
			$this->handleError(new TRestException($e->getStatusCode(), '', $e->getMessage()));
		} catch (\Throwable $e) {
			Prado::log($e->getMessage(), TLogger::ERROR, self::class);
			$this->handleError(new TRestException(500, '', $this->getExposeErrors() ? $e->getMessage() : ''), $e);
		}
	}

	/**
	 * Raises {@see onError()} and writes the problem document; a `405` carries the
	 * `Allow` header of the matched paths.
	 * @param TRestException $e the error.
	 * @param ?\Throwable $cause the exception behind a `500`, when there is one.
	 */
	protected function handleError(TRestException $e, ?\Throwable $cause = null): void
	{
		$this->onError($cause ?? $e);
		if ($e->getStatusCode() === 405) {
			$this->getResponse()->appendHeader(THttpHeaderName::Allow . ': ' . implode(', ', $this->getAllowedVerbs()));
		}
		$this->sendErrorResponse($e);
	}

	/**
	 * Writes the `401` problem document when the authorization rules refuse the
	 * request, since the application then ends the request without running the
	 * service. Attached to the application's `onAuthorization` event after the
	 * {@see \Prado\Security\TAuthManager} handler.
	 * @param mixed $sender the application.
	 * @param mixed $param the event parameter.
	 */
	public function handleUnauthorized($sender, $param): void
	{
		if ($this->getApplication()->getRequestCompleted() && $this->getResponse()->getStatusCode() === 401) {
			$this->handleError(TRestException::unauthorized());
		}
	}

	/**
	 * Calls the handler method with the path parameters as the arguments of the
	 * same names, cast to the scalar type each parameter declares. A parameter
	 * typed {@see \Prado\Web\THttpRequest} receives the request and one typed
	 * {@see TRestRoutePattern} receives the route.
	 * @param IRestResource $resource the resource.
	 * @param TRestRoutePattern $route the route.
	 * @param array $parameters path parameter values keyed by placeholder name.
	 * @throws TRestException 405 when the resource has no such method; 404 when a value does not fit the parameter type.
	 * @throws TConfigurationException when a required parameter has no path parameter.
	 * @return mixed the handler's return value.
	 */
	protected function dispatch(IRestResource $resource, TRestRoutePattern $route, array $parameters): mixed
	{
		$method = (string) $route->getMethod();
		if (!method_exists($resource, $method)) {
			throw TRestException::methodNotAllowed();
		}
		$arguments = [];
		foreach ((new \ReflectionMethod($resource, $method))->getParameters() as $parameter) {
			$name = $parameter->getName();
			$type = $parameter->getType();
			$typeName = ($type instanceof \ReflectionNamedType) ? $type->getName() : null;
			if (array_key_exists($name, $parameters)) {
				$arguments[] = $this->castParameter($parameters[$name], $typeName, $name);
			} elseif ($typeName !== null && is_a($this->getRequest(), $typeName)) {
				$arguments[] = $this->getRequest();
			} elseif ($typeName !== null && is_a($route, $typeName)) {
				$arguments[] = $route;
			} elseif ($parameter->isDefaultValueAvailable()) {
				$arguments[] = $parameter->getDefaultValue();
			} else {
				throw new TConfigurationException('restservice_parameter_unbound', $resource::class . '::' . $method, $name);
			}
		}
		return $resource->$method(...$arguments);
	}

	/**
	 * Casts a path parameter value to the scalar type of the handler parameter.
	 * @param string $value the path parameter value.
	 * @param ?string $type the declared type, `null` or a class name for none.
	 * @param string $name the parameter name.
	 * @throws TRestException 404 when the value does not fit the type.
	 * @return mixed the cast value.
	 */
	protected function castParameter(string $value, ?string $type, string $name): mixed
	{
		$cast = match ($type) {
			'int' => filter_var($value, FILTER_VALIDATE_INT),
			'float' => filter_var($value, FILTER_VALIDATE_FLOAT),
			'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
			default => $value,
		};
		if ($cast === false && ($type === 'int' || $type === 'float')) {
			throw TRestException::notFound();
		}
		return $cast;
	}

	// ── Events ────────────────────────────────────────────────────────────────

	/**
	 * Raised before the resource authorizes and handles the request, with the
	 * resource as the parameter. A module attaches here to reach every resource.
	 * @param IRestResource $resource the resource about to run.
	 */
	public function onPreRunResource(IRestResource $resource): void
	{
		$this->raiseEvent('onPreRunResource', $this, $resource);
	}

	/**
	 * Raised with the exception before the problem document is written.
	 * @param \Throwable $exception the error: the {@see TRestException}, or the exception behind a `500`.
	 */
	public function onError(\Throwable $exception): void
	{
		$this->raiseEvent('onError', $this, $exception);
	}

	// ── Response ──────────────────────────────────────────────────────────────

	/**
	 * Writes a JSON response.
	 * @param mixed $data the response body; `null` sends none.
	 * @param int $statusCode the status code.
	 * @param array $headers response headers as name => value pairs.
	 * @throws \JsonException when the body cannot be encoded.
	 */
	protected function sendJsonResponse(mixed $data, int $statusCode = 200, array $headers = []): void
	{
		$this->sendHeaders($statusCode, $headers);
		if ($data !== null) {
			$response = $this->getResponse();
			$response->setContentType(TMediaType::JSON);
			$response->setCharset('UTF-8');
			$response->write(json_encode($data, JSON_THROW_ON_ERROR));
		}
	}

	/**
	 * Writes the status code and headers of a response.
	 * @param int $statusCode the status code.
	 * @param array $headers response headers as name => value pairs.
	 */
	protected function sendHeaders(int $statusCode, array $headers): void
	{
		$response = $this->getResponse();
		$response->setStatusCode($statusCode);
		foreach ($headers as $name => $value) {
			$response->appendHeader($name . ': ' . $value);
		}
	}

	/**
	 * Writes an error as an `application/problem+json` document.
	 * @param TRestException $e the error.
	 * @throws \JsonException when the document cannot be encoded.
	 */
	protected function sendErrorResponse(TRestException $e): void
	{
		$response = $this->getResponse();
		$response->setStatusCode($e->getStatusCode());
		$response->setContentType(TMediaType::PROBLEM_JSON);
		$response->setCharset('UTF-8');
		$response->write(json_encode($e->toArray(), JSON_THROW_ON_ERROR));
	}

	// ── Direct Accessors (UAP-SE) ─────────────────────────────────────────────

	/**
	 * @return string the stored base path.
	 */
	protected function getBasePathDirect(): string
	{
		return $this->_basePath;
	}

	/**
	 * @param string $value the base path to store.
	 */
	protected function setBasePathDirect(string $value): void
	{
		$this->_basePath = $value;
	}

	/**
	 * @return bool the stored ExposeErrors flag.
	 */
	protected function getExposeErrorsDirect(): bool
	{
		return $this->_exposeErrors;
	}

	/**
	 * @param bool $value the ExposeErrors flag to store.
	 */
	protected function setExposeErrorsDirect(bool $value): void
	{
		$this->_exposeErrors = $value;
	}

	/**
	 * @return string the stored route class.
	 */
	protected function getRouteClassDirect(): string
	{
		return $this->_routeClass;
	}

	/**
	 * @param string $value the route class to store.
	 */
	protected function setRouteClassDirect(string $value): void
	{
		$this->_routeClass = $value;
	}

	/**
	 * @return TRestRoutePattern[] the stored routes.
	 */
	protected function getRoutesDirect(): array
	{
		return $this->_routes;
	}

	/**
	 * @param TRestRoutePattern $route a route to append.
	 */
	protected function addRouteDirect(TRestRoutePattern $route): void
	{
		$this->_routes[] = $route;
	}

	/**
	 * @return array<string,IRestResource> the stored resources.
	 */
	protected function getResourcesDirect(): array
	{
		return $this->_resources;
	}

	/**
	 * @param array<string,IRestResource> $value the resources to store.
	 */
	protected function setResourcesDirect(array $value): void
	{
		$this->_resources = $value;
	}

	/**
	 * @return array the stored path matches.
	 */
	protected function getPathMatchesDirect(): array
	{
		return $this->_pathMatches;
	}

	/**
	 * @param array $value the path matches to store.
	 */
	protected function setPathMatchesDirect(array $value): void
	{
		$this->_pathMatches = $value;
	}

	/**
	 * @return ?TRestRoutePattern the stored matched route.
	 */
	protected function getRouteDirect(): ?TRestRoutePattern
	{
		return $this->_route;
	}

	/**
	 * @param ?TRestRoutePattern $value the matched route to store.
	 */
	protected function setRouteDirect(?TRestRoutePattern $value): void
	{
		$this->_route = $value;
	}

	/**
	 * @return array the stored path parameters.
	 */
	protected function getPathParametersDirect(): array
	{
		return $this->_pathParameters;
	}

	/**
	 * @param array $value the path parameters to store.
	 */
	protected function setPathParametersDirect(array $value): void
	{
		$this->_pathParameters = $value;
	}

	// ── Properties ────────────────────────────────────────────────────────────

	/**
	 * @return string path prefix of every route, `''` by default.
	 */
	public function getBasePath(): string
	{
		return $this->getBasePathDirect();
	}

	/**
	 * Sets the path prefix of every route; `api` makes the pattern `users/{id}`
	 * match the path `api/users/42`.
	 * @param string $value the prefix, with or without slashes.
	 */
	public function setBasePath(string $value): void
	{
		$this->setBasePathDirect(trim($value, '/'));
	}

	/**
	 * @return bool whether a `500` carries the exception message; true in Debug mode by default.
	 */
	public function getExposeErrors(): bool
	{
		return $this->getExposeErrorsDirect();
	}

	/**
	 * @param bool|string $value whether a `500` carries the exception message.
	 */
	public function setExposeErrors(bool|string $value): void
	{
		$this->setExposeErrorsDirect(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return string class of the routes, {@see TRestRoutePattern} by default.
	 */
	public function getRouteClass(): string
	{
		return $this->getRouteClassDirect();
	}

	/**
	 * @param string $value class of the routes, a {@see TRestRoutePattern} subclass.
	 */
	public function setRouteClass(string $value): void
	{
		$this->setRouteClassDirect($value);
	}

	/**
	 * @return TRestRoutePattern[] the routes in declaration order.
	 */
	public function getRoutes(): array
	{
		return $this->getRoutesDirect();
	}

	/**
	 * @return array<string,IRestResource> the resources keyed by class name.
	 */
	public function getResources(): array
	{
		return $this->getResourcesDirect();
	}

	/**
	 * @param string $class the resource class, in namespace or dot notation.
	 * @return ?IRestResource the resource of the class, `null` when none is registered.
	 */
	public function getResource(string $class): ?IRestResource
	{
		$resources = $this->getResourcesDirect();
		if (isset($resources[$class])) {
			return $resources[$class];
		}
		$className = ltrim(str_replace('.', '\\', $class), '\\');
		return $resources[$className] ?? null;
	}

	/**
	 * @return array<int,array{0:TRestRoutePattern,1:array}> the routes whose path matched the request, with their parameters.
	 */
	public function getPathMatches(): array
	{
		return $this->getPathMatchesDirect();
	}

	/**
	 * @return ?TRestRoutePattern the route serving the request, `null` when none matched.
	 */
	public function getRoute(): ?TRestRoutePattern
	{
		return $this->getRouteDirect();
	}

	/**
	 * @return array path parameters of the route serving the request.
	 */
	public function getPathParameters(): array
	{
		return $this->getPathParametersDirect();
	}
}
