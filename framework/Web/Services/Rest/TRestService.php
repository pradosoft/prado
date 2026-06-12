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
use Prado\Exceptions\TIOException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;
use Prado\Xml\TXmlDocument;

/**
 * TRestService class
 *
 * TRestService provides a self-contained REST API layer for PRADO applications,
 * intended as the backend for single-page applications built with React, Vue,
 * Svelte, or any other client that consumes a JSON HTTP API.
 *
 * ## Request lifecycle
 *
 * On each request the service:
 *
 *  1. Emits CORS headers and short-circuits `OPTIONS` preflights when
 *     {@see setEnableCors EnableCors} is on.
 *  2. Strips {@see setBasePath BasePath} from `PATH_INFO` and matches the
 *     remainder against its compiled route table in declaration order; the
 *     first matching pattern wins, otherwise the service returns `404`.
 *     Requests whose path lies outside `BasePath` also return `404`.
 *  3. Instantiates the matched {@see TRestResource} subclass, applies any
 *     extra XML attributes as object properties, and injects the captured
 *     path parameters.
 *  4. Calls {@see TRestResource::authorize() authorize()} (which may throw),
 *     then dispatches to a `do…()` method chosen from the HTTP verb and the
 *     pattern's shape (see *Routing & dispatch* below).
 *  5. JSON-encodes the return value and writes it with the resource's chosen
 *     status code and headers. `HEAD` and `204` responses suppress the body.
 *     Any {@see TRestException} (or other `Throwable`) is converted to a JSON
 *     error envelope (see *Error responses* below).
 *
 * ## Routing & dispatch
 *
 * Patterns use `{name}` placeholders, optionally constrained per-parameter
 * with `parameters.<name>="<regex>"` (default: `[^/]+`). A route is classified
 * as an **item** route when its final path segment is a placeholder, and as a
 * **collection** route otherwise. The HTTP verb and the route shape together
 * select the resource method:
 *
 * | Verb       | Collection route | Item route                  |
 * |------------|------------------|-----------------------------|
 * | GET / HEAD | `doIndex()`      | `doShow()` (body for HEAD)  |
 * | POST       | `doStore()`      | `doStore()`                 |
 * | PUT        | `doStore()`      | `doUpdate()`                |
 * | PATCH      | `doStore()`      | `doPatch()`                 |
 * | DELETE     | `doDestroy()`    | `doDestroy()`               |
 *
 * `PUT` and `PATCH` on a collection route both fall through to `doStore()`
 * so that a resource may treat them as upsert; subclasses that do not want
 * that behaviour simply omit `doStore()`, which yields `405`. Unknown verbs
 * and resources that do not implement the selected method also yield `405
 * Method Not Allowed`. Path parameters are passed to the resource method
 * **by name** via PHP reflection — parameter order in the signature does
 * not matter, and unmatched parameters fall back to PHP defaults.
 *
 * ## Configuring resources
 *
 * Routes are declared as `<resource>` elements directly under the `<service>`,
 * or inside `<group>` elements that share a common URL prefix. Groups support
 * an `enabled` attribute that accepts boolean-ish values plus the
 * case-insensitive string `'Debug'`, which evaluates to `true` only when
 * `TApplicationMode::Debug` is active — useful for diagnostic endpoints that
 * should disappear in `Normal` and `Performance` modes. URL routing is set up
 * once on the request module, then resources are declared on the service:
 *
 * ```xml
 * <module id="request" class="THttpRequest" UrlManager="url-manager" />
 * <module id="url-manager" class="Prado\Web\TUrlMapping" EnableCustomUrl="true">
 *   <url ServiceID="rest" ServiceParameter="1" pattern="api/{*}" />
 * </module>
 *
 * <service id="rest" class="Prado\Web\Services\Rest\TRestService"
 *   BasePath="api/"
 *   EnableCors="true"
 *   AllowOrigin="https://myapp.example.com">
 *
 *   <!-- Top-level resources: collection + item + nested -->
 *   <resource pattern="users"                   class="App.Api.UsersResource" />
 *   <resource pattern="users/{id}"              class="App.Api.UsersResource"  parameters.id="\d+" />
 *   <resource pattern="users/{userId}/posts"    class="App.Api.UserPostsResource" parameters.userId="\d+" />
 *   <resource pattern="users/{userId}/posts/{id}" class="App.Api.UserPostsResource"
 *             parameters.userId="\d+" parameters.id="\d+" />
 *
 *   <!-- Grouped resources sharing a prefix; combines every group option -->
 *   <group prefix="v1/" enabled="true">
 *     <resource pattern="users" class="App.Api.V1.UsersResource" />
 *   </group>
 *   <group prefix="v2/" groupfile="Application.config.rest-v2" />     <!-- loaded from file -->
 *   <group prefix="v3/" enabled="false">                              <!-- temporarily off -->
 *     <resource pattern="users" class="App.Api.V3.UsersResource" />
 *   </group>
 *   <group prefix="debug/" enabled="Debug">                           <!-- Debug-mode-only -->
 *     <resource pattern="dump" class="App.Api.Debug.DumpResource" />
 *   </group>
 * </service>
 * ```
 *
 * The same configuration expressed as a PHP array (when the application is
 * configured with `CONFIG_TYPE_PHP`) — `<resource>` and `<group>` attributes
 * become array keys, and `parameters.<name>="…"` collapses to a nested
 * `'parameters'` array:
 *
 * ```php
 * 'services' => [
 *   'rest' => [
 *     'class' => 'Prado\\Web\\Services\\Rest\\TRestService',
 *     'properties' => [
 *       'BasePath' => 'api/',
 *       'EnableCors' => 'true',
 *       'AllowOrigin' => 'https://myapp.example.com',
 *     ],
 *     'resources' => [
 *       ['pattern' => 'users',                     'class' => 'App.Api.UsersResource'],
 *       ['pattern' => 'users/{id}',                'class' => 'App.Api.UsersResource',
 *        'parameters' => ['id' => '\d+']],
 *       ['pattern' => 'users/{userId}/posts',      'class' => 'App.Api.UserPostsResource',
 *        'parameters' => ['userId' => '\d+']],
 *       ['pattern' => 'users/{userId}/posts/{id}', 'class' => 'App.Api.UserPostsResource',
 *        'parameters' => ['userId' => '\d+', 'id' => '\d+']],
 *     ],
 *     'groups' => [
 *       ['prefix' => 'v1/', 'enabled' => true, 'resources' => [
 *         ['pattern' => 'users', 'class' => 'App.Api.V1.UsersResource'],
 *       ]],
 *       ['prefix' => 'v2/', 'groupfile' => 'Application.config.rest-v2'],
 *       ['prefix' => 'v3/', 'enabled' => false, 'resources' => [
 *         ['pattern' => 'users', 'class' => 'App.Api.V3.UsersResource'],
 *       ]],
 *       ['prefix' => 'debug/', 'enabled' => 'Debug', 'resources' => [
 *         ['pattern' => 'dump', 'class' => 'App.Api.Debug.DumpResource'],
 *       ]],
 *     ],
 *   ],
 * ],
 * ```
 *
 * ## External configuration files
 *
 * Either the full service config or a single group's resources can live in a
 * separate file referenced by Prado namespace path (without extension). The
 * loader prefers `.php` and falls back to `.xml`; for XML files the root
 * element name is arbitrary, only direct `<resource>` (and, for `configfile`,
 * `<group>`) children are read.
 *
 *  - **`<service configfile="…">`** loads top-level `resources` and `groups`.
 *    Inline entries on the `<service>` element are appended afterwards, so
 *    file contents act as a base that inline entries can extend.
 *  - **`<group groupfile="…">`** loads only the `resources` for that group.
 *    The `prefix` and `enabled` flag stay on the referencing `<group>`.
 *
 * A representative pair of external files — one service-level, one
 * group-level — looks like:
 *
 * ```xml
 * <!-- Application/config/rest.xml — referenced by <service configfile="..."> -->
 * <rest>
 *   <resource pattern="users" class="App.Api.UsersResource" />
 *   <group prefix="v2/" groupfile="Application.config.rest-v2" />
 *   <group prefix="debug/" enabled="Debug">
 *     <resource pattern="dump" class="App.Api.Debug.DumpResource" />
 *   </group>
 * </rest>
 *
 * <!-- Application/config/rest-v2.xml — referenced by <group groupfile="..."> -->
 * <resources>
 *   <resource pattern="users"      class="App.Api.V2.UsersResource" />
 *   <resource pattern="users/{id}" class="App.Api.V2.UsersResource" parameters.id="\d+" />
 * </resources>
 * ```
 *
 * The PHP-array equivalents return `['resources' => […], 'groups' => […]]`
 * for a service-level file and `['resources' => […]]` for a group-level
 * file, with each entry using the same keys as the XML attributes.
 *
 * ## CORS
 *
 * When {@see setEnableCors EnableCors} is on, `Access-Control-Allow-Origin`
 * (plus `Access-Control-Allow-Credentials` and `Vary: Origin` when
 * applicable) is added to every response, and `OPTIONS` preflights are
 * answered with `204 No Content` carrying the preflight-only headers
 * `Access-Control-Allow-Methods`, `Access-Control-Allow-Headers`, and
 * `Access-Control-Max-Age`. {@see setAllowOrigin AllowOrigin},
 * {@see setAllowMethods AllowMethods}, {@see setAllowHeaders AllowHeaders},
 * {@see setAllowCredentials AllowCredentials}, and {@see setMaxAge MaxAge}
 * control the emitted values. Combining {@see setAllowCredentials
 * AllowCredentials} with the wildcard `'*'` origin is rejected as a
 * configuration error — reflecting arbitrary origins while allowing
 * credentials would grant every website authenticated access to the API, so
 * an explicit origin must be configured instead.
 *
 * ## Error responses
 *
 * Every error — routing failures, method mismatches, thrown
 * {@see TRestException} instances, and uncaught exceptions — is serialized as
 * an RFC 7807-inspired JSON envelope. Validation failures additionally
 * include an `errors` key:
 *
 * ```json
 * { "status": 404, "title": "Not Found", "detail": "User 42 not found." }
 * { "status": 422, "title": "Unprocessable Entity",
 *   "detail": "The given data was invalid.",
 *   "errors": { "email": ["The email field is required."] } }
 * ```
 *
 * Uncaught exceptions become `500` responses; their `getMessage()` is
 * included in `detail` only when {@see setExposeErrors ExposeErrors} is on
 * (the default in `TApplicationMode::Debug`).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRestService extends \Prado\TService
{
	/**
	 * @var string Base URL path prefix stripped before route matching.
	 *   For example, `"api/"` means `PATH_INFO` of `/api/users/1` is matched
	 *   as `users/1`. Include the trailing slash. Defaults to empty string.
	 */
	private string $_basePath = '';

	/**
	 * @var bool Whether to emit CORS headers. Defaults to false.
	 */
	private bool $_enableCors = false;

	/**
	 * @var string Value of the `Access-Control-Allow-Origin` header.
	 *   Use `'*'` to allow any origin. Defaults to `'*'`.
	 */
	private string $_allowOrigin = '*';

	/**
	 * @var string Comma-separated list of allowed HTTP methods.
	 */
	private string $_allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD';

	/**
	 * @var string Comma-separated list of allowed request headers.
	 */
	private string $_allowHeaders = 'Content-Type, Authorization, X-Requested-With, Accept';

	/**
	 * @var bool Whether `Access-Control-Allow-Credentials` is sent. Defaults to false.
	 */
	private bool $_allowCredentials = false;

	/**
	 * @var int Max-age in seconds for preflight cache. Defaults to 86400 (24 h).
	 */
	private int $_maxAge = 86400;

	/**
	 * @var bool Whether to expose internal exception messages in 500 responses.
	 *   Automatically true when the application runs in Debug mode.
	 */
	private bool $_exposeErrors = false;

	/**
	 * @var array Compiled route table. Each entry:
	 *   [ 'pattern' => string, 'regex' => string, 'class' => string,
	 *     'isItem' => bool, 'paramOrder' => string[], 'properties' => array ]
	 */
	private array $_resources = [];

	// ── Initialisation ─────────────────────────────────────────────────────────

	/**
	 * Initializes the service by validating the CORS configuration and
	 * compiling the resource route table.
	 * @param mixed $config Service configuration element.
	 * @throws TConfigurationException when CORS credentials are combined with
	 *   the wildcard origin.
	 */
	public function init($config): void
	{
		$this->setExposeErrors($this->getApplication()->getMode() === TApplicationMode::Debug);
		$this->assertValidCorsConfig();
		$this->loadResources($config);
		parent::init($config);
	}

	/**
	 * Guards against the insecure combination of credentialed CORS and a
	 * wildcard origin.
	 *
	 * Reflecting arbitrary request origins while sending
	 * `Access-Control-Allow-Credentials: true` would let any website make
	 * authenticated requests with the user's cookies, which the CORS
	 * specification deliberately forbids. Called from {@see init()} for
	 * fail-fast configuration errors and from {@see sendCorsHeaders()} to
	 * cover properties changed programmatically after initialization.
	 *
	 * @throws TConfigurationException when EnableCors and AllowCredentials are
	 *   both true while AllowOrigin is `'*'`.
	 */
	protected function assertValidCorsConfig(): void
	{
		if ($this->getEnableCors() && $this->getAllowCredentials() && $this->getAllowOrigin() === '*') {
			throw new TConfigurationException('restservice_cors_credentials_wildcard');
		}
	}

	/**
	 * Parses `<resource>` and `<group>` elements from the service configuration
	 * and compiles each resource pattern into a named-capture regular expression.
	 *
	 * XML configurations are first normalized to a PHP array via
	 * {@see xmlConfigToArray()}; only the PHP array path is then parsed. This
	 * ensures a single, consistent parsing code path regardless of config format.
	 *
	 * Top-level `<resource>` elements are registered directly. `<group>` elements
	 * are delegated to {@see loadGroup()}, which prepends the group prefix to every
	 * pattern before the routes are added to the table. Disabled groups
	 * (`enabled="false"`) are skipped entirely.
	 *
	 * A `configfile` attribute on the service (or `'configfile'` key in PHP
	 * config) loads an external file containing the same `resources` + `groups`
	 * structure; its contents are processed first, then inline entries are
	 * appended. The external file may itself reference further `groupfile`s.
	 *
	 * @param mixed $config Service configuration element.
	 * @throws TConfigurationException when required attributes are missing.
	 * @throws TIOException when a referenced config or group file cannot be found.
	 */
	protected function loadResources(mixed $config): void
	{
		if ($config === null) {
			return;
		}

		if ($config instanceof \Prado\Xml\TXmlElement) {
			$phpConfig = $this->xmlConfigToArray($config);
		} elseif (is_array($config)) {
			$phpConfig = $config;
		} else {
			return;
		}

		// Merge an external configfile (if any) ahead of inline entries.
		$configFile = (string) ($phpConfig['configfile'] ?? '');
		if ($configFile !== '') {
			$external = $this->loadConfigFile($configFile);
			$phpConfig['resources'] = array_merge($external['resources'] ?? [], $phpConfig['resources'] ?? []);
			$phpConfig['groups'] = array_merge($external['groups'] ?? [], $phpConfig['groups'] ?? []);
		}

		foreach ($phpConfig['resources'] ?? [] as $item) {
			$this->registerResource($item);
		}
		foreach ($phpConfig['groups'] ?? [] as $groupConfig) {
			$this->loadGroup($groupConfig);
		}
	}

	/**
	 * Converts a service XML config element to a PHP array suitable for
	 * {@see loadResources()}.
	 *
	 * Only direct child elements are inspected: `<resource>` elements are
	 * collected under `'resources'`; `<group>` elements are collected under
	 * `'groups'`. This prevents double-registration of resources that live
	 * inside `<group>` elements. The `configfile` attribute, if present on
	 * the service element, is preserved under the `'configfile'` key.
	 *
	 * @param \Prado\Xml\TXmlElement $config Service XML element.
	 * @return array{resources: array, groups: array, configfile?: string}
	 */
	protected function xmlConfigToArray(\Prado\Xml\TXmlElement $config): array
	{
		$result = ['resources' => [], 'groups' => []];

		$configFile = $config->getAttribute('configfile');
		if ($configFile !== null && $configFile !== '') {
			$result['configfile'] = $configFile;
		}

		foreach ($config->getElements() as $element) {
			$tagName = strtolower($element->getTagName());
			if ($tagName === 'resource') {
				$result['resources'][] = $this->xmlResourceToArray($element);
			} elseif ($tagName === 'group') {
				$result['groups'][] = $this->xmlGroupToArray($element);
			}
		}

		return $result;
	}

	/**
	 * Converts a `<group>` XML element to a PHP array.
	 *
	 * All attributes are preserved as top-level keys. Direct `<resource>` child
	 * elements are collected under the `'resources'` key via
	 * {@see xmlResourceToArray()}.
	 *
	 * @param \Prado\Xml\TXmlElement $element XML group element.
	 * @return array Group config array with at least a `'resources'` key.
	 */
	protected function xmlGroupToArray(\Prado\Xml\TXmlElement $element): array
	{
		$group = [];

		foreach ($element->getAttributes()->toArray() as $key => $value) {
			$group[$key] = $value;
		}

		$resources = [];
		foreach ($element->getElements() as $child) {
			if (strtolower($child->getTagName()) === 'resource') {
				$resources[] = $this->xmlResourceToArray($child);
			}
		}
		$group['resources'] = $resources;

		return $group;
	}

	/**
	 * Converts a `<resource>` XML element to a PHP array.
	 *
	 * Attributes prefixed with `'parameters.'` are collected into a nested
	 * `'parameters'` array. All remaining attributes are preserved as top-level
	 * keys (including `'pattern'` and `'class'`).
	 *
	 * @param \Prado\Xml\TXmlElement $element XML resource element.
	 * @return array Resource config array.
	 */
	protected function xmlResourceToArray(\Prado\Xml\TXmlElement $element): array
	{
		$item = [];
		$parameters = [];

		foreach ($element->getAttributes()->toArray() as $key => $value) {
			if (str_starts_with($key, 'parameters.')) {
				$parameters[substr($key, 11)] = $value;
			} else {
				$item[$key] = $value;
			}
		}

		if ($parameters !== []) {
			$item['parameters'] = $parameters;
		}

		return $item;
	}

	/**
	 * Registers a resource from a PHP array config entry.
	 *
	 * Validates that `'pattern'` and `'class'` keys are present, extracts
	 * `'parameters'` constraints, and passes all remaining keys as resource
	 * properties to {@see addResource()}.
	 *
	 * @param array $item Resource config array. Must contain `'pattern'` and `'class'`.
	 * @param string $prefix Optional URL prefix to prepend to the pattern.
	 * @throws TConfigurationException when a required key is absent.
	 */
	protected function registerResource(array $item, string $prefix = ''): void
	{
		if (!isset($item['pattern'])) {
			throw new TConfigurationException('restservice_pattern_required');
		}
		if (!isset($item['class'])) {
			throw new TConfigurationException('restservice_class_required');
		}

		$pattern = $prefix . $item['pattern'];
		$class = $item['class'];
		$parameters = $item['parameters'] ?? [];
		$properties = array_diff_key($item, array_flip(['pattern', 'class', 'parameters']));

		$this->addResource($pattern, $class, $parameters, $properties);
	}

	/**
	 * Processes a group config array and registers all enabled resources.
	 *
	 * Reads `'prefix'`, `'enabled'`, and `'groupfile'` from the config.
	 * All other keys are available to subclass overrides. File-based resources
	 * are loaded first via {@see loadGroupFile()}; inline `'resources'` entries
	 * are appended afterward. Disabled groups are skipped entirely.
	 *
	 * The `'enabled'` value is resolved by {@see isEnabled()}, which accepts
	 * standard boolean-ish strings plus the special value `'Debug'` (enabled
	 * only when the application runs in Debug mode).
	 *
	 * @param array $config Group config array.
	 * @throws TIOException when a referenced group file cannot be found.
	 */
	protected function loadGroup(array $config): void
	{
		$prefix = (string) ($config['prefix'] ?? '');
		$enabled = $this->isEnabled($config['enabled'] ?? true);
		$groupFile = (string) ($config['groupfile'] ?? '');

		if (!$enabled) {
			return;
		}

		$resources = [];

		if ($groupFile !== '') {
			$resources = $this->loadGroupFile($groupFile);
		}

		foreach ($config['resources'] ?? [] as $item) {
			$resources[] = $item;
		}

		foreach ($resources as $item) {
			$this->registerResource($item, $prefix);
		}
	}

	/**
	 * Resolves whether a group is enabled based on a config value.
	 *
	 * Accepts any value `TPropertyValue::ensureBoolean()` understands, plus the
	 * case-insensitive string `'Debug'`, which resolves to `true` only when the
	 * application's mode is {@see TApplicationMode::Debug} (and `false`
	 * for `Normal` or `Performance` mode).
	 *
	 * @param mixed $value Raw `enabled` value from the config.
	 * @return bool Whether the group is enabled in the current application mode.
	 */
	protected function isEnabled(mixed $value): bool
	{
		if (is_string($value) && strcasecmp($value, 'Debug') === 0) {
			return $this->getApplication()->getMode() === TApplicationMode::Debug;
		}
		return TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Resolves and loads an external service-level config file.
	 *
	 * The file contains the same structure as the inline service config —
	 * top-level `resources` and/or `groups` entries. Tries `.php` first; falls
	 * back to `.xml`. The PHP file must return an array with `'resources'`
	 * and/or `'groups'` keys. The XML file is treated like an inline `<service>`
	 * element: direct `<resource>` and `<group>` children are loaded; the root
	 * element name is arbitrary.
	 *
	 * Groups loaded from the external file may themselves reference
	 * `groupfile` entries — those are resolved by {@see loadGroupFile()} when
	 * {@see loadGroup()} processes each group.
	 *
	 * @param string $file Prado namespace path without extension (e.g.
	 *   `'Application.config.rest'`).
	 * @throws TIOException when neither a `.php` nor a `.xml` file is found.
	 * @return array{resources: array, groups: array} Service config array.
	 */
	protected function loadConfigFile(string $file): array
	{
		$phpFile = Prado::getPathOfNamespace($file, '.php');
		if ($phpFile !== null && is_file($phpFile)) {
			$config = include $phpFile;
			if (!is_array($config)) {
				return ['resources' => [], 'groups' => []];
			}
			return [
				'resources' => $config['resources'] ?? [],
				'groups' => $config['groups'] ?? [],
			];
		}

		$xmlFile = Prado::getPathOfNamespace($file, '.xml');
		if ($xmlFile !== null && is_file($xmlFile)) {
			$dom = new TXmlDocument('1.0', 'UTF-8');
			$dom->loadFromFile($xmlFile);
			return $this->xmlConfigToArray($dom);
		}

		throw new TIOException('restconfig_file_not_found', $file);
	}

	/**
	 * Resolves and loads an external resource definition file.
	 *
	 * Tries a `.php` file first; falls back to `.xml`. The PHP file must return
	 * an array with a `'resources'` key. The XML file must have `<resource>`
	 * elements as direct children of its root element; the root element name
	 * itself is arbitrary and ignored.
	 *
	 * @param string $file Prado namespace path without extension (e.g.
	 *   `'Application.config.rest-v2'`).
	 * @throws TIOException when neither a `.php` nor a `.xml` file is found.
	 * @return array Flat array of resource config arrays.
	 */
	protected function loadGroupFile(string $file): array
	{
		$phpFile = Prado::getPathOfNamespace($file, '.php');
		if ($phpFile !== null && is_file($phpFile)) {
			$config = include $phpFile;
			return is_array($config) ? ($config['resources'] ?? []) : [];
		}

		$xmlFile = Prado::getPathOfNamespace($file, '.xml');
		if ($xmlFile !== null && is_file($xmlFile)) {
			$dom = new TXmlDocument('1.0', 'UTF-8');
			$dom->loadFromFile($xmlFile);
			$resources = [];
			foreach ($dom->getElementsByTagName('resource') as $element) {
				$resources[] = $this->xmlResourceToArray($element);
			}
			return $resources;
		}

		throw new TIOException('restgroup_file_not_found', $file);
	}

	/**
	 * Adds a compiled resource entry to the route table.
	 * @param string $pattern URL pattern (e.g., `'users/{id}'`).
	 * @param string $class Resource class in PRADO namespace format.
	 * @param array $parameters Per-parameter regex constraints (key = param name, value = regex fragment).
	 * @param array $properties Additional properties to set on the resource after instantiation.
	 */
	protected function addResource(string $pattern, string $class, array $parameters = [], array $properties = []): void
	{
		[$regex, $paramOrder, $isItem] = $this->compilePattern($pattern, $parameters);

		$this->addResourceEntryDirect([
			'pattern' => $pattern,
			'regex' => $regex,
			'class' => $class,
			'isItem' => $isItem,
			'paramOrder' => $paramOrder,
			'properties' => $properties,
		]);
	}

	/**
	 * Compiles a URL pattern string into a named-capture regular expression.
	 *
	 * `{paramName}` placeholders become `(?P<paramName>(?:constraint))`; the
	 * inner non-capturing group lets user constraints contain top-level
	 * alternation (`\d+|new`) without leaking out of the named capture.
	 * Literal path segments are regex-escaped and the result is anchored.
	 *
	 * @param string $pattern URL pattern, e.g., `'users/{userId}/posts/{id}'`.
	 * @param array $parameters Regex constraints keyed by parameter name. Defaults to `[^/]+`.
	 * @return array{0: string, 1: string[], 2: bool} [regex, paramOrder, isItem]
	 */
	protected function compilePattern(string $pattern, array $parameters): array
	{
		// Extract parameter names in order
		preg_match_all('/\{([^}]+)\}/', $pattern, $matches);
		$paramOrder = $matches[1];

		// Split pattern on {param} tokens, escape literal parts, rejoin with named captures
		$literalParts = preg_split('/\{[^}]+\}/', $pattern);
		$regexParts = [];

		foreach ($paramOrder as $i => $paramName) {
			$constraint = $parameters[$paramName] ?? '[^/]+';
			// Wrap constraint in (?:...) so user-supplied alternations like "\d+|\d{4}-\d{2}"
			// don't break the named capture group.
			$regexParts[] = preg_quote($literalParts[$i], '#') . '(?P<' . $paramName . '>(?:' . $constraint . '))';
		}
		$regexParts[] = preg_quote($literalParts[count($paramOrder)], '#');

		$regex = '#^' . implode('', $regexParts) . '$#u';

		// A route is an "item" route when the last path segment is a {param}
		$isItem = (bool) preg_match('/\{[^}]+\}\s*$/', rtrim($pattern, '/'));

		return [$regex, $paramOrder, $isItem];
	}

	// ── Request Handling ───────────────────────────────────────────────────────

	/**
	 * Runs the service. Handles CORS, route matching, dispatch, and response encoding.
	 */
	public function run(): void
	{
		$request = $this->getRequest();
		$response = $this->getResponse();

		try {
			// CORS — must be first so preflight OPTIONS responses work
			if ($this->getEnableCors()) {
				$this->sendCorsHeaders();
				if (strtoupper($request->getRequestType() ?? '') === 'OPTIONS') {
					$response->setStatusCode(204);
					return;
				}
			}

			$path = $this->getApiPath();
			[$resourceConfig, $pathParams] = $this->matchRoute($path);

			// Instantiate the resource
			$resource = $this->createResource($resourceConfig);
			$resource->setPathParameters($pathParams);

			// Resolve HTTP verb → dispatch method name
			$verb = strtoupper($request->getRequestType() ?? 'GET');
			$method = $this->resolveMethod($verb, $resourceConfig['isItem']);

			// Auth hook — may throw TRestException
			$resource->authorize($method);

			// Dispatch — inject path params by name via reflection
			$result = $this->dispatchToResource($resource, $method, $pathParams);

			// Send response
			$statusCode = $resource->getStatusCode();
			$headers = $resource->getResponseHeaders();

			// HEAD: same logic as GET but no body
			// 204 No Content: no body regardless of verb
			if ($verb === 'HEAD' || $statusCode === 204) {
				$response->setStatusCode($statusCode);
				foreach ($headers as $name => $value) {
					$response->appendHeader("{$name}: {$value}");
				}
				return;
			}

			$this->sendJsonResponse($result, $statusCode, $headers);
		} catch (TRestException $e) {
			if ($e->getStatusCode() === 405 && isset($resource, $resourceConfig)) {
				// RFC 7231 §6.5.5: a 405 response must carry an Allow header.
				$response->appendHeader('Allow: ' . implode(', ', $this->getAllowedVerbs($resource, $resourceConfig['isItem'])));
			}
			$this->sendErrorResponse($e);
		} catch (\Prado\Exceptions\THttpException $e) {
			$this->sendErrorResponse(new TRestException($e->getStatusCode(), '', $e->getMessage()));
		} catch (\Throwable $e) {
			$detail = $this->getExposeErrors() ? $e->getMessage() : '';
			Prado::log($e->getMessage(), \Prado\Util\TLogger::ERROR, self::class);
			$this->sendErrorResponse(new TRestException(500, '', $detail));
		}
	}

	// ── Routing ────────────────────────────────────────────────────────────────

	/**
	 * Returns the portion of PATH_INFO that follows the configured BasePath.
	 *
	 * For example, if BasePath is `"api/"` and PATH_INFO is `/api/users/42`,
	 * this method returns `"users/42"`.
	 * @return string Relative API path, with no leading slash.
	 */
	protected function getApiPath(): string
	{
		$pathInfo = ltrim($this->getRequest()->getPathInfo() ?? '', '/');
		return $this->applyBasePath($pathInfo);
	}

	/**
	 * Strips the configured BasePath prefix from a raw path-info string.
	 *
	 * Leading slashes are removed before comparison. When BasePath is empty,
	 * the path is returned unchanged. A path equal to BasePath without its
	 * trailing slash resolves to the empty root path. A path outside BasePath
	 * is rejected with `404` so routes are reachable only under the
	 * configured prefix.
	 *
	 * @param string $pathInfo Raw path info (leading slash already stripped).
	 * @throws TRestException 404 when the path lies outside BasePath.
	 * @return string API-relative path, with no leading slash.
	 */
	protected function applyBasePath(string $pathInfo): string
	{
		$basePath = ltrim($this->getBasePath(), '/');

		if ($basePath === '') {
			return $pathInfo;
		}
		if (str_starts_with($pathInfo, $basePath)) {
			return ltrim(substr($pathInfo, strlen($basePath)), '/');
		}
		if (rtrim($basePath, '/') === $pathInfo) {
			return '';
		}

		throw TRestException::notFound('The requested resource was not found.');
	}

	/**
	 * Matches the given path against the compiled route table.
	 *
	 * Routes are tested in declaration order; the first match wins.
	 *
	 * @param string $path API path (relative, no leading slash).
	 * @throws TRestException 404 when no route matches.
	 * @return array{0: array, 1: array} [resourceConfig, pathParams]
	 */
	protected function matchRoute(string $path): array
	{
		foreach ($this->getResourcesDirect() as $resourceConfig) {
			if (preg_match($resourceConfig['regex'], $path, $matches)) {
				$pathParams = [];
				foreach ($resourceConfig['paramOrder'] as $paramName) {
					if (isset($matches[$paramName])) {
						$pathParams[$paramName] = $matches[$paramName];
					}
				}
				return [$resourceConfig, $pathParams];
			}
		}

		throw TRestException::notFound('The requested resource was not found.');
	}

	// ── Dispatch ───────────────────────────────────────────────────────────────

	/**
	 * Instantiates a TRestResource from a compiled route config entry.
	 *
	 * Applies any extra properties declared in the resource element. Path
	 * parameters are injected separately by {@see run()} after instantiation.
	 *
	 * @param array $resourceConfig Compiled route config entry.
	 * @throws TConfigurationException when the class does not extend TRestResource.
	 * @return TRestResource
	 */
	protected function createResource(array $resourceConfig): TRestResource
	{
		$class = $resourceConfig['class'];

		// Prado::createComponent handles both dot-notation and PHP namespaces
		$resource = Prado::createComponent($class);

		if (!($resource instanceof TRestResource)) {
			throw new TConfigurationException('restservice_resource_invalid', $class);
		}

		foreach ($resourceConfig['properties'] as $name => $value) {
			$resource->setSubproperty($name, $value);
		}

		return $resource;
	}

	/**
	 * Maps an HTTP verb and route type to the TRestResource dispatch method name.
	 *
	 * | Verb | Collection | Item |
	 * |------|-----------|------|
	 * | GET / HEAD | `doIndex` | `doShow` |
	 * | POST | `doStore` | `doStore` |
	 * | PUT | `doStore` | `doUpdate` |
	 * | PATCH | `doStore` | `doPatch` |
	 * | DELETE | `doDestroy` | `doDestroy` |
	 *
	 * @param string $verb Uppercase HTTP method (e.g., `'GET'`, `'POST'`).
	 * @param bool $isItem True for item routes (last segment is a `{param}`).
	 * @throws TRestException 405 for unsupported verbs.
	 * @return string Method name to call on the resource.
	 */
	protected function resolveMethod(string $verb, bool $isItem): string
	{
		return match ($verb) {
			'GET', 'HEAD' => $isItem ? 'doShow' : 'doIndex',
			'POST' => 'doStore',
			'PUT' => $isItem ? 'doUpdate' : 'doStore',
			'PATCH' => $isItem ? 'doPatch' : 'doStore',
			'DELETE' => 'doDestroy',
			default => throw TRestException::methodNotAllowed("HTTP method {$verb} is not supported."),
		};
	}

	/**
	 * Returns the HTTP verbs the given resource supports for the matched route.
	 *
	 * A verb is supported when the resource subclass declares the convention
	 * method that {@see resolveMethod()} maps it to. `OPTIONS` is appended
	 * when CORS is enabled because the service answers preflights itself.
	 * Used to populate the `Allow` header on `405` responses.
	 *
	 * @param TRestResource $resource Resource instance for the matched route.
	 * @param bool $isItem True for item routes (last segment is a `{param}`).
	 * @return string[] Supported verbs in canonical order.
	 */
	protected function getAllowedVerbs(TRestResource $resource, bool $isItem): array
	{
		$verbs = [];
		foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'] as $verb) {
			if (method_exists($resource, $this->resolveMethod($verb, $isItem))) {
				$verbs[] = $verb;
			}
		}
		if ($this->getEnableCors()) {
			$verbs[] = 'OPTIONS';
		}
		return $verbs;
	}

	/**
	 * Calls a method on a TRestResource, injecting path parameters by name.
	 *
	 * Uses PHP reflection to read the method's parameter list and passes only
	 * those path params whose names match declared parameters, in declaration order.
	 * Parameters with default values are used when a matching path param is absent.
	 *
	 * @param TRestResource $resource Resource instance.
	 * @param string $method Method name to call.
	 * @param array $pathParams Path parameters extracted from the URL, keyed by name.
	 * @throws TRestException 405 when the resource does not implement the method.
	 * @throws TRestException 500 when a required parameter cannot be satisfied.
	 * @return mixed Return value of the resource method.
	 */
	protected function dispatchToResource(TRestResource $resource, string $method, array $pathParams): mixed
	{
		if (!method_exists($resource, $method)) {
			throw TRestException::methodNotAllowed();
		}

		$ref = new \ReflectionMethod($resource, $method);
		$args = [];

		foreach ($ref->getParameters() as $param) {
			$name = $param->getName();
			if (array_key_exists($name, $pathParams)) {
				$args[] = $pathParams[$name];
			} elseif ($param->isDefaultValueAvailable()) {
				$args[] = $param->getDefaultValue();
			} else {
				throw new TRestException(
					500,
					'Internal Server Error',
					"Resource method '{$method}' requires path parameter '\${$name}' which is not defined in the matched route."
				);
			}
		}

		return $resource->$method(...$args);
	}

	// ── Response ───────────────────────────────────────────────────────────────

	/**
	 * Encodes $data as JSON and writes it to the HTTP response with the given status code.
	 *
	 * A `null` $data value produces a response with no body (useful for `204`).
	 *
	 * @param mixed $data Response payload. Must be JSON-serializable.
	 * @param int $statusCode HTTP status code. Defaults to 200.
	 * @param array $headers Additional response headers as name => value pairs.
	 */
	protected function sendJsonResponse(mixed $data, int $statusCode = 200, array $headers = []): void
	{
		$response = $this->getResponse();
		$response->setStatusCode($statusCode);

		foreach ($headers as $name => $value) {
			$response->appendHeader("{$name}: {$value}");
		}

		if ($data !== null) {
			$response->setContentType('application/json');
			$response->setCharset('UTF-8');
			$response->write(json_encode($data, JSON_THROW_ON_ERROR));
		}
	}

	/**
	 * Serializes a TRestException to JSON and writes it to the HTTP response.
	 * @param TRestException $e Exception to serialize.
	 */
	protected function sendErrorResponse(TRestException $e): void
	{
		$response = $this->getResponse();
		$response->setStatusCode($e->getStatusCode());
		$response->setContentType('application/json');
		$response->setCharset('UTF-8');
		$response->write(json_encode($e->toArray(), JSON_THROW_ON_ERROR));
	}

	// ── CORS ───────────────────────────────────────────────────────────────────

	/**
	 * Emits `Access-Control-*` headers for CORS support.
	 *
	 * Called at the top of {@see run()} when {@see getEnableCors()} is true.
	 * The origin headers (`Access-Control-Allow-Origin`,
	 * `Access-Control-Allow-Credentials`, `Vary: Origin`) are sent on every
	 * response; the preflight-only headers (`Access-Control-Allow-Methods`,
	 * `Access-Control-Allow-Headers`, `Access-Control-Max-Age`) are sent only
	 * for `OPTIONS` requests because browsers ignore them elsewhere.
	 *
	 * @throws TConfigurationException when CORS credentials are combined with
	 *   the wildcard origin (see {@see assertValidCorsConfig()}).
	 */
	protected function sendCorsHeaders(): void
	{
		$this->assertValidCorsConfig();

		$response = $this->getResponse();
		$origin = $this->getAllowOrigin();

		$response->appendHeader("Access-Control-Allow-Origin: {$origin}");

		if ($this->getAllowCredentials()) {
			$response->appendHeader('Access-Control-Allow-Credentials: true');
		}

		if ($origin !== '*') {
			$response->appendHeader('Vary: Origin');
		}

		if (strtoupper($this->getRequest()->getRequestType() ?? '') === 'OPTIONS') {
			$response->appendHeader("Access-Control-Allow-Methods: {$this->getAllowMethods()}");
			$response->appendHeader("Access-Control-Allow-Headers: {$this->getAllowHeaders()}");
			$response->appendHeader("Access-Control-Max-Age: {$this->getMaxAge()}");
		}
	}

	// ── Direct Accessors (UAP-SE) ──────────────────────────────────────────────

	/**
	 * @return string Base URL path prefix stored value.
	 */
	protected function getBasePathDirect(): string
	{
		return $this->_basePath;
	}

	/**
	 * @param string $value Base URL path prefix.
	 */
	protected function setBasePathDirect(string $value): void
	{
		$this->_basePath = $value;
	}

	/**
	 * @return bool Stored EnableCors flag.
	 */
	protected function getEnableCorsDirect(): bool
	{
		return $this->_enableCors;
	}

	/**
	 * @param bool $value EnableCors stored value.
	 */
	protected function setEnableCorsDirect(bool $value): void
	{
		$this->_enableCors = $value;
	}

	/**
	 * @return string Stored AllowOrigin value.
	 */
	protected function getAllowOriginDirect(): string
	{
		return $this->_allowOrigin;
	}

	/**
	 * @param string $value AllowOrigin stored value.
	 */
	protected function setAllowOriginDirect(string $value): void
	{
		$this->_allowOrigin = $value;
	}

	/**
	 * @return string Stored AllowMethods value.
	 */
	protected function getAllowMethodsDirect(): string
	{
		return $this->_allowMethods;
	}

	/**
	 * @param string $value AllowMethods stored value.
	 */
	protected function setAllowMethodsDirect(string $value): void
	{
		$this->_allowMethods = $value;
	}

	/**
	 * @return string Stored AllowHeaders value.
	 */
	protected function getAllowHeadersDirect(): string
	{
		return $this->_allowHeaders;
	}

	/**
	 * @param string $value AllowHeaders stored value.
	 */
	protected function setAllowHeadersDirect(string $value): void
	{
		$this->_allowHeaders = $value;
	}

	/**
	 * @return bool Stored AllowCredentials flag.
	 */
	protected function getAllowCredentialsDirect(): bool
	{
		return $this->_allowCredentials;
	}

	/**
	 * @param bool $value AllowCredentials stored value.
	 */
	protected function setAllowCredentialsDirect(bool $value): void
	{
		$this->_allowCredentials = $value;
	}

	/**
	 * @return int Stored MaxAge value in seconds.
	 */
	protected function getMaxAgeDirect(): int
	{
		return $this->_maxAge;
	}

	/**
	 * @param int $value MaxAge stored value in seconds.
	 */
	protected function setMaxAgeDirect(int $value): void
	{
		$this->_maxAge = $value;
	}

	/**
	 * @return bool Stored ExposeErrors flag.
	 */
	protected function getExposeErrorsDirect(): bool
	{
		return $this->_exposeErrors;
	}

	/**
	 * @param bool $value ExposeErrors stored value.
	 */
	protected function setExposeErrorsDirect(bool $value): void
	{
		$this->_exposeErrors = $value;
	}

	/**
	 * Returns the compiled route table directly, bypassing any subclass override
	 * of public accessor methods.
	 * @return array Compiled route table.
	 */
	protected function getResourcesDirect(): array
	{
		return $this->_resources;
	}

	/**
	 * Appends a compiled route entry to the route table directly.
	 * @param array $entry Compiled route entry.
	 */
	protected function addResourceEntryDirect(array $entry): void
	{
		$this->_resources[] = $entry;
	}

	// ── Property Accessors ─────────────────────────────────────────────────────

	/**
	 * @return string Base URL path prefix. Defaults to empty string.
	 */
	public function getBasePath(): string
	{
		return $this->getBasePathDirect();
	}

	/**
	 * Sets the base URL path prefix that is stripped before route matching.
	 *
	 * For example, setting `BasePath="api/v1/"` means a request for `/api/v1/users`
	 * is matched against the pattern `users`.
	 * @param string $value Base path, with optional leading/trailing slashes.
	 */
	public function setBasePath(string $value): void
	{
		$this->setBasePathDirect($value);
	}

	/**
	 * @return bool Whether CORS headers are emitted. Defaults to false.
	 */
	public function getEnableCors(): bool
	{
		return $this->getEnableCorsDirect();
	}

	/**
	 * @param bool|string $value Whether to enable CORS headers.
	 */
	public function setEnableCors(bool|string $value): void
	{
		$this->setEnableCorsDirect(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return string `Access-Control-Allow-Origin` value. Defaults to `'*'`.
	 */
	public function getAllowOrigin(): string
	{
		return $this->getAllowOriginDirect();
	}

	/**
	 * Sets the allowed CORS origin(s).
	 * Use `'*'` to permit any origin (not compatible with credentials).
	 * @param string $value Origin value or `'*'`.
	 */
	public function setAllowOrigin(string $value): void
	{
		$this->setAllowOriginDirect($value);
	}

	/**
	 * @return string Comma-separated `Access-Control-Allow-Methods` value.
	 */
	public function getAllowMethods(): string
	{
		return $this->getAllowMethodsDirect();
	}

	/**
	 * @param string $value Comma-separated list of allowed HTTP methods.
	 */
	public function setAllowMethods(string $value): void
	{
		$this->setAllowMethodsDirect($value);
	}

	/**
	 * @return string Comma-separated `Access-Control-Allow-Headers` value.
	 */
	public function getAllowHeaders(): string
	{
		return $this->getAllowHeadersDirect();
	}

	/**
	 * @param string $value Comma-separated list of allowed request headers.
	 */
	public function setAllowHeaders(string $value): void
	{
		$this->setAllowHeadersDirect($value);
	}

	/**
	 * @return bool Whether `Access-Control-Allow-Credentials: true` is emitted. Defaults to false.
	 */
	public function getAllowCredentials(): bool
	{
		return $this->getAllowCredentialsDirect();
	}

	/**
	 * When true, `Access-Control-Allow-Credentials: true` is sent. Requires
	 * an explicit {@see setAllowOrigin AllowOrigin} — combining credentials
	 * with the wildcard `'*'` origin raises a configuration error.
	 * @param bool|string $value
	 */
	public function setAllowCredentials($value): void
	{
		$this->setAllowCredentialsDirect(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return int Preflight cache duration in seconds. Defaults to 86400.
	 */
	public function getMaxAge(): int
	{
		return $this->getMaxAgeDirect();
	}

	/**
	 * @param int|string $value Preflight cache duration in seconds.
	 */
	public function setMaxAge($value): void
	{
		$this->setMaxAgeDirect(TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return bool Whether internal error details are included in 500 responses.
	 */
	public function getExposeErrors(): bool
	{
		return $this->getExposeErrorsDirect();
	}

	/**
	 * When true, uncaught exception messages are included in 500 error responses.
	 * Defaults to true in Debug application mode; false otherwise.
	 * @param bool|string $value
	 */
	public function setExposeErrors($value): void
	{
		$this->setExposeErrorsDirect(TPropertyValue::ensureBoolean($value));
	}
}
