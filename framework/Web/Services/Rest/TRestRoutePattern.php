<?php

/**
 * TRestRoutePattern class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Security\TAuthorizationRule;
use Prado\TPropertyValue;
use Prado\Web\THttpRequest;
use Prado\Web\TUrlManager;
use Prado\Web\TUrlMappingPattern;

/**
 * TRestRoutePattern class
 *
 * TRestRoutePattern is one route of a {@see TRestService}: a URL pattern bound to
 * a {@see IRestResource} class and the method that handles it. It is a
 * {@see \Prado\Web\TUrlMappingPattern}, so the pattern syntax, the
 * {@see getParameters() Parameters} and {@see getQuery() Query} constraints,
 * {@see getVerbs() Verbs} and {@see getCaseSensitive() CaseSensitive} are the ones
 * of the URL manager. Two things are added for REST patterns:
 *
 * - A placeholder may carry its constraint inline, `{id:\d+}`, and a placeholder
 *   without a constraint matches one path segment, `[^/]+`.
 * - The path is matched apart from the verb. {@see matchPath()} returns the path
 *   parameters of a request whose path matches, whatever its verb, and
 *   {@see acceptsVerb()} tells whether the route serves a verb, so the service
 *   answers `405` with a correct `Allow` header when the path matches another verb.
 *
 * The pattern holds the full path under the service's `BasePath`, as the service
 * registers it. {@see constructPath()} is the reverse: the path for a set of
 * parameter values, used by {@see TRestService::constructUrl()}.
 *
 * {@see getResource() Resource} and {@see getMethod() Method} name the handler,
 * {@see getName() Name} identifies the route for reverse routing, and
 * {@see getAuthorizationRules() AuthorizationRules} are merged into the
 * application rules when the route is the matched one.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRestRoutePattern extends TUrlMappingPattern
{
	/**
	 * @var string Constraint of a placeholder that declares none.
	 */
	public const DEFAULT_CONSTRAINT = '[^/]+';

	/**
	 * @var string Pattern of one `{name}` or `{name:constraint}` placeholder.
	 */
	public const PLACEHOLDER_PATTERN = '/\{([A-Za-z_][A-Za-z0-9_]*)(?::([^{}]+))?\}/';

	/**
	 * @var ?string resource class, in namespace or dot notation.
	 */
	private ?string $_resource = null;

	/**
	 * @var ?string handler method on the resource.
	 */
	private ?string $_method = null;

	/**
	 * @var ?string route name.
	 */
	private ?string $_name = null;

	/**
	 * @var array property values applied to the resource after creation.
	 */
	private array $_resourceProperties = [];

	/**
	 * @var TAuthorizationRule[] authorization rules of the route.
	 */
	private array $_authorizationRules = [];

	/**
	 * @var string[] placeholder names in pattern order, filled by {@see init()}.
	 */
	private array $_parameterNames = [];

	/**
	 * Constructor. A REST route has no URL manager to construct its URL with, so
	 * the manager is optional; {@see constructPath()} builds the path itself.
	 * @param ?TUrlManager $manager the URL manager, `null` for a route of a service.
	 */
	public function __construct(?TUrlManager $manager = null)
	{
		if ($manager !== null) {
			parent::__construct($manager);
		} else {
			\Prado\TComponent::__construct();
		}
	}

	/**
	 * Initializes the route. Inline `{name:constraint}` placeholders move their
	 * constraint to {@see getParameters() Parameters}, placeholders without one
	 * get {@see DEFAULT_CONSTRAINT}, and the {@see setServiceParameter() ServiceParameter}
	 * defaults to the route {@see getName() Name}.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration of the route.
	 * @throws TConfigurationException when a placeholder is repeated or malformed.
	 */
	public function init($config)
	{
		if ($this->getServiceParameter() === null) {
			$this->setServiceParameter($this->getName());
		}
		parent::init($config);
		$this->compilePlaceholders();
	}

	/**
	 * Reads the placeholders of the pattern into {@see getParameters() Parameters}
	 * and {@see getParameterNames()}.
	 * @throws TConfigurationException when a placeholder is repeated or malformed.
	 */
	protected function compilePlaceholders(): void
	{
		$pattern = (string) $this->getPattern();
		$parameters = $this->getParameters();
		$names = [];
		preg_match_all(self::PLACEHOLDER_PATTERN, $pattern, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$name = $match[1];
			if (in_array($name, $names, true)) {
				throw new TConfigurationException('restroute_parameter_duplicate', $name, $pattern);
			}
			$names[] = $name;
			if (isset($match[2])) {
				$parameters->add($name, $match[2]);
			} elseif (!$parameters->contains($name)) {
				$parameters->add($name, self::DEFAULT_CONSTRAINT);
			}
		}
		$pattern = preg_replace(self::PLACEHOLDER_PATTERN, '{$1}', $pattern);
		if (preg_match('/\{[^}]*\}/', str_replace(array_map(fn ($name) => '{' . $name . '}', $names), '', $pattern))) {
			throw new TConfigurationException('restroute_pattern_invalid', $pattern);
		}
		$this->setPattern($pattern);
		$this->_parameterNames = $names;
	}

	/**
	 * Replaces the placeholders of a pattern part with named groups. Each constraint
	 * is wrapped in a non-capturing group so an alternation such as `\d+|new` stays
	 * inside its placeholder.
	 * @param string $pattern the path or query string part of the pattern.
	 * @return string the regular expression of the part.
	 */
	protected function substituteParameters(string $pattern): string
	{
		$search = [];
		$replace = [];
		foreach ($this->getParameters()->toArray() as $key => $value) {
			$search[] = '{' . $key . '}';
			$replace[] = '(?P<' . $key . '>(?:' . $value . '))';
		}
		$search[] = '/';
		$replace[] = '\\/';
		return str_replace($search, $replace, $pattern);
	}

	/**
	 * Matches the path of a request, and its query constraints, apart from the verb.
	 * @param THttpRequest $request the request.
	 * @return ?array path parameter values keyed by placeholder name, `null` when the path does not match.
	 */
	public function matchPath(THttpRequest $request): ?array
	{
		if ($this->getQuery()->getCount() > 0 && !$this->matchesQuery($this->getQueryItems())) {
			return null;
		}
		$matches = [];
		if (($regexp = $this->getRegularExpression()) !== '') {
			$matched = preg_match($regexp, $this->getMatchSubject($request, (string) $request->getPathInfo()), $matches);
		} else {
			$matched = preg_match($this->getParameterizedPattern(), $this->getMatchSubject($request, trim((string) $request->getPathInfo(), '/') . '/'), $matches);
		}
		if (!$matched) {
			return null;
		}
		$parameters = [];
		foreach ($this->getParameterNames() as $name) {
			if (isset($matches[$name])) {
				$parameters[$name] = $matches[$name];
			}
		}
		if ($this->_constants) {
			foreach ($this->_constants->toArray() as $key => $value) {
				$parameters[$key] = $value;
			}
		}
		return $parameters;
	}

	/**
	 * @param string $verb an HTTP method.
	 * @return bool whether the route serves the verb, by its {@see getVerbs() Verbs}.
	 */
	public function acceptsVerb(string $verb): bool
	{
		return $this->matchesVerb($verb);
	}

	/**
	 * Builds the path of the route for a set of parameter values.
	 * @param array $parameters placeholder values keyed by placeholder name.
	 * @param array $getItems GET variables appended as the query string.
	 * @param bool $encodeAmpersand whether `&amp;` separates the GET variables.
	 * @throws TInvalidDataValueException when a placeholder has no value.
	 * @return string the path, without a leading or trailing slash.
	 */
	public function constructPath(array $parameters = [], array $getItems = [], bool $encodeAmpersand = false): string
	{
		$replace = [];
		foreach ($this->getParameterNames() as $name) {
			if (!isset($parameters[$name])) {
				throw new TInvalidDataValueException('restroute_parameter_missing', $name, $this->getPattern());
			}
			$replace['{' . $name . '}'] = rawurlencode((string) $parameters[$name]);
		}
		$path = trim(strtr((string) $this->getPattern(), $replace), '/');
		if ($getItems !== []) {
			$path .= '?' . http_build_query($getItems, '', $encodeAmpersand ? '&amp;' : '&', PHP_QUERY_RFC3986);
		}
		return $path;
	}

	/**
	 * Constructs the path of the route from GET variables: the ones that name a
	 * placeholder fill it, the rest form the query string.
	 * @param array $getItems GET variables.
	 * @param bool $encodeAmpersand whether `&amp;` separates the GET variables.
	 * @param bool $encodeGetItems unused; the values are always encoded.
	 * @return string the path, without a leading slash.
	 */
	public function constructUrl($getItems, $encodeAmpersand, $encodeGetItems)
	{
		$names = array_flip($this->getParameterNames());
		return $this->constructPath(array_intersect_key($getItems, $names), array_diff_key($getItems, $names), (bool) $encodeAmpersand);
	}

	/**
	 * @return string[] placeholder names in pattern order.
	 */
	public function getParameterNames(): array
	{
		return $this->_parameterNames;
	}

	/**
	 * @return ?string resource class, in namespace or dot notation.
	 */
	public function getResource(): ?string
	{
		return $this->_resource;
	}

	/**
	 * @param ?string $value resource class, in namespace or dot notation.
	 */
	public function setResource(?string $value): void
	{
		$this->_resource = $value;
	}

	/**
	 * @return ?string handler method on the resource.
	 */
	public function getMethod(): ?string
	{
		return $this->_method;
	}

	/**
	 * @param ?string $value handler method on the resource.
	 */
	public function setMethod(?string $value): void
	{
		$this->_method = $value;
	}

	/**
	 * @return string route name; defaults to the pattern.
	 */
	public function getName(): string
	{
		return $this->_name ?? (string) $this->getPattern();
	}

	/**
	 * @param ?string $value route name for {@see TRestService::constructUrl()}.
	 */
	public function setName(?string $value): void
	{
		$this->_name = $value;
	}

	/**
	 * @return array property values applied to the resource after creation.
	 */
	public function getResourceProperties(): array
	{
		return $this->_resourceProperties;
	}

	/**
	 * @param array $value property values applied to the resource after creation.
	 */
	public function setResourceProperties(array $value): void
	{
		$this->_resourceProperties = $value;
	}

	/**
	 * @return TAuthorizationRule[] authorization rules of the route.
	 */
	public function getAuthorizationRules(): array
	{
		return $this->_authorizationRules;
	}

	/**
	 * Adds an authorization rule that applies when the route is matched.
	 * @param TAuthorizationRule $rule the rule.
	 */
	public function addAuthorizationRule(TAuthorizationRule $rule): void
	{
		$this->_authorizationRules[] = $rule;
	}

	/**
	 * Sets the route verbs from the attribute or configuration value.
	 * @param null|array|string $value verbs, `null` or empty for every verb.
	 */
	public function setVerbs($value)
	{
		parent::setVerbs(is_string($value) ? strtoupper($value) : $value);
	}

	/**
	 * @param array|string $value the pattern, or the `Pattern` key of an array.
	 */
	public function setPattern($value)
	{
		parent::setPattern(is_array($value) ? ($value['Pattern'] ?? '') : TPropertyValue::ensureString($value));
	}
}
