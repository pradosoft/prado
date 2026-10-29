<?php

/**
 * TRestResource class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\TComponentReflection;
use Prado\Util\TInputValidationException;
use Prado\Util\TInputValidator;

/**
 * TRestResource class
 *
 * TRestResource is the base class of the resources a {@see TRestService} serves.
 * A resource declares which HTTP verb and URL pattern each of its methods handles,
 * and the service calls that method with the path parameters as arguments.
 *
 * ## Declaring routes
 *
 * A handler is any public method with a {@see TRestRoute} attribute. The class
 * attribute gives the prefix shared by every route of the resource:
 * ```php
 * namespace App\Api;
 *
 * use Prado\Web\Services\Rest\TRestResource;
 * use Prado\Web\Services\Rest\TRestRoute;
 *
 * #[TRestRoute(pattern: 'users')]
 * class UsersResource extends TRestResource
 * {
 *     #[TRestRoute('GET')]
 *     public function index(): array
 *     {
 *         return UserDao::findAll(['role' => $this->query('role')]);
 *     }
 *
 *     #[TRestRoute('GET', '{id:\d+}')]
 *     public function show(int $id): array
 *     {
 *         return UserDao::find($id) ?? $this->notFound("User {$id} not found.");
 *     }
 *
 *     #[TRestRoute('POST')]
 *     public function store(): array
 *     {
 *         $data = $this->validateBody([
 *             'name' => 'required|string|max:255',
 *             'email' => 'required|email',
 *         ]);
 *         $user = UserDao::create($data);
 *         $this->header('Location', $this->getService()->constructUrl('UsersResource.show', ['id' => $user['id']]));
 *         return $this->created($user);
 *     }
 *
 *     #[TRestRoute('DELETE', '{id:\d+}')]
 *     public function destroy(int $id): void
 *     {
 *         UserDao::delete($id);
 *         $this->noContent();
 *     }
 * }
 * ```
 * Path parameters are passed by name and cast to the scalar type the parameter
 * declares. A method the route names but the verb does not reach answers `405`.
 *
 * {@see getRoutes()} is the lookup behind the attributes. It walks the parent
 * classes, so a subclass that overrides `show()` without the attribute keeps the
 * parent's route. A resource overrides it to declare the routes as a table instead
 * of, or in addition to, the attributes:
 * ```php
 * public function getRoutes(): array
 * {
 *     return parent::getRoutes() + [
 *         'export' => [new TRestRoute('GET', 'export/{format:csv|json}')],
 *     ];
 * }
 * ```
 * The service registers the resource in the application configuration:
 * ```xml
 * <service id="rest" class="Prado\Web\Services\Rest\TRestService" BasePath="api">
 *   <resource class="App\Api\UsersResource" />
 * </service>
 * ```
 *
 * ## Request helpers
 *
 * | Method | Reads |
 * |---|---|
 * | {@see getBody()} | the body decoded by {@see \Prado\Web\THttpRequest::getParsedBody()} |
 * | {@see query()} | one query-string variable |
 * | {@see input()} | one body field, then one query-string variable |
 * | {@see only()}, {@see except()} | a subset of the body |
 * | {@see validateBody()}, {@see validate()} | the body, or any array, against {@see \Prado\Util\TInputValidator} rules; a failure is a `422` with field errors |
 *
 * ## Response helpers
 *
 * {@see created()}, {@see accepted()} and {@see noContent()} set the status code;
 * {@see header()} adds a response header. {@see notFound()}, {@see unauthorized()},
 * {@see forbidden()}, {@see conflict()}, {@see unprocessable()} and {@see abort()}
 * throw the {@see TRestException} the service writes as a problem document.
 *
 * ## Authorization
 *
 * `<authorization>` rules in the configuration are enforced by the application's
 * {@see \Prado\Security\TAuthManager} before the service runs. {@see authorize()}
 * is the hook of the resource itself, called with the matched route before the
 * handler; throw to refuse the request.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TRestResource extends TApplicationComponent implements IRestResource
{
	/**
	 * @var ?TRestRoutePattern the matched route.
	 */
	private ?TRestRoutePattern $_route = null;

	/**
	 * @var array path parameters of the matched route.
	 */
	private array $_pathParameters = [];

	/**
	 * @var int HTTP status code of the response.
	 */
	private int $_statusCode = 200;

	/**
	 * @var array response headers as name => value pairs.
	 */
	private array $_responseHeaders = [];

	/**
	 * @var ?TInputValidator the validator of {@see validate()}.
	 */
	private ?TInputValidator $_validator = null;

	// ── Routes ────────────────────────────────────────────────────────────────

	/**
	 * Returns the routes of the resource from the {@see TRestRoute} attributes of its
	 * public methods. A method without the attribute takes the attributes of the
	 * method it overrides, up the parent classes. Override this method to declare
	 * routes as a table.
	 * @return array<string,TRestRoute[]> route declarations keyed by method name.
	 */
	public function getRoutes(): array
	{
		$routes = [];
		foreach ((new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			if ($method->isStatic() || $method->isConstructor()) {
				continue;
			}
			$name = $method->getName();
			for ($class = static::class; $class !== false; $class = get_parent_class($class)) {
				$attributes = TComponentReflection::getMethodAttributesByType($class, $name, TRestRoute::class);
				if ($attributes !== []) {
					$routes[$name] = $attributes;
					break;
				}
			}
		}
		return $routes;
	}

	/**
	 * Returns the pattern prefix of the resource from the {@see TRestRoute} attribute
	 * of the class, or of its nearest ancestor that carries one.
	 * @return string the prefix, `''` for none.
	 */
	public function getRoutePrefix(): string
	{
		foreach (TComponentReflection::getClassAttributesByType($this, TRestRoute::class) as $attribute) {
			return $attribute->pattern;
		}
		return '';
	}

	/**
	 * Receives the matched route and its path parameters before dispatch.
	 * @param TRestRoutePattern $route the matched route.
	 * @param array $parameters path parameter values keyed by placeholder name.
	 */
	public function setRoute(TRestRoutePattern $route, array $parameters): void
	{
		$this->setRouteDirect($route);
		$this->setPathParametersDirect($parameters);
	}

	/**
	 * @return ?TRestRoutePattern the matched route, `null` before dispatch.
	 */
	public function getRoute(): ?TRestRoutePattern
	{
		return $this->getRouteDirect();
	}

	/**
	 * @return array path parameter values keyed by placeholder name.
	 */
	public function getPathParameters(): array
	{
		return $this->getPathParametersDirect();
	}

	/**
	 * @param string $name placeholder name.
	 * @param mixed $default value when the placeholder is absent.
	 * @return mixed the path parameter value, or `$default`.
	 */
	public function getPathParameter(string $name, mixed $default = null): mixed
	{
		return $this->getPathParameters()[$name] ?? $default;
	}

	/**
	 * Authorizes the request before the handler runs. The default accepts every
	 * request; override and throw, for example through {@see unauthorized()} or
	 * {@see forbidden()}, to refuse one.
	 * @param TRestRoutePattern $route the matched route.
	 */
	public function authorize(TRestRoutePattern $route): void
	{
	}

	/**
	 * @return ?TRestService the service running the resource, `null` outside a request.
	 */
	public function getService(): ?TRestService
	{
		$service = $this->getApplication()->getService();
		return ($service instanceof TRestService) ? $service : null;
	}

	// ── Direct Accessors (UAP-SE) ─────────────────────────────────────────────

	/**
	 * @return ?TRestRoutePattern the stored route.
	 */
	protected function getRouteDirect(): ?TRestRoutePattern
	{
		return $this->_route;
	}

	/**
	 * @param ?TRestRoutePattern $value the route to store.
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

	/**
	 * @return int the stored status code.
	 */
	protected function getStatusCodeDirect(): int
	{
		return $this->_statusCode;
	}

	/**
	 * @param int $value the status code to store.
	 */
	protected function setStatusCodeDirect(int $value): void
	{
		$this->_statusCode = $value;
	}

	/**
	 * @return array the stored response headers.
	 */
	protected function getResponseHeadersDirect(): array
	{
		return $this->_responseHeaders;
	}

	/**
	 * @param array $value the response headers to store.
	 */
	protected function setResponseHeadersDirect(array $value): void
	{
		$this->_responseHeaders = $value;
	}

	/**
	 * @return ?TInputValidator the stored validator.
	 */
	protected function getValidatorDirect(): ?TInputValidator
	{
		return $this->_validator;
	}

	/**
	 * @param ?TInputValidator $value the validator to store.
	 */
	protected function setValidatorDirect(?TInputValidator $value): void
	{
		$this->_validator = $value;
	}

	// ── Response state ────────────────────────────────────────────────────────

	/**
	 * @return int HTTP status code of the response, 200 by default.
	 */
	public function getStatusCode(): int
	{
		return $this->getStatusCodeDirect();
	}

	/**
	 * Sets the HTTP status code of the response. {@see created()}, {@see accepted()}
	 * and {@see noContent()} set the usual ones.
	 * @param int $value HTTP status code.
	 */
	protected function setStatusCode(int $value): void
	{
		$this->setStatusCodeDirect($value);
	}

	/**
	 * @return array response headers as name => value pairs.
	 */
	public function getResponseHeaders(): array
	{
		return $this->getResponseHeadersDirect();
	}

	/**
	 * Adds a response header, replacing one of the same name.
	 * @param string $name header name.
	 * @param string $value header value.
	 * @throws TInvalidDataValueException when the name is not a token or the value holds CR or LF.
	 */
	protected function addResponseHeader(string $name, string $value): void
	{
		if ($name === '' || preg_match('/[^!#$%&\'*+\-.^_`|~0-9A-Za-z]/', $name) || preg_match('/[\r\n]/', $value)) {
			throw new TInvalidDataValueException('restresource_invalid_header', $name);
		}
		$headers = $this->getResponseHeadersDirect();
		$headers[$name] = $value;
		$this->setResponseHeadersDirect($headers);
	}

	// ── Request helpers ───────────────────────────────────────────────────────

	/**
	 * Returns the request body decoded by its media type, through
	 * {@see \Prado\Web\THttpRequest::getParsedBody()}: JSON, form-encoded on any
	 * verb, or multipart. A malformed JSON body is a `400`.
	 * @return array the decoded body, `[]` when there is none.
	 */
	public function getBody(): array
	{
		return $this->getRequest()->getParsedBody();
	}

	/**
	 * @param string $key field name.
	 * @param mixed $default value when the key is absent.
	 * @return mixed the body field, then the query-string variable, then `$default`.
	 */
	public function input(string $key, mixed $default = null): mixed
	{
		$body = $this->getBody();
		if (array_key_exists($key, $body)) {
			return $body[$key];
		}
		return $this->getRequest()->getQueryParameters()[$key] ?? $default;
	}

	/**
	 * @param string $key query-string variable name.
	 * @param mixed $default value when the variable is absent.
	 * @return mixed the query-string variable; body fields and route parameters are not read.
	 */
	public function query(string $key, mixed $default = null): mixed
	{
		return $this->getRequest()->getQueryParameters()[$key] ?? $default;
	}

	/**
	 * @param string $key field name.
	 * @return bool whether the body or the query string holds the key.
	 */
	public function hasInput(string $key): bool
	{
		return array_key_exists($key, $this->getBody()) || array_key_exists($key, $this->getRequest()->getQueryParameters());
	}

	/**
	 * @param string[] $keys field names.
	 * @return array the body fields named in `$keys`.
	 */
	public function only(array $keys): array
	{
		return array_intersect_key($this->getBody(), array_flip($keys));
	}

	/**
	 * @param string[] $keys field names.
	 * @return array the body without the fields named in `$keys`.
	 */
	public function except(array $keys): array
	{
		return array_diff_key($this->getBody(), array_flip($keys));
	}

	// ── Status helpers ────────────────────────────────────────────────────────

	/**
	 * Sets the status to `201 Created` and returns the data.
	 * @param mixed $data the response body.
	 * @return mixed `$data`.
	 */
	protected function created(mixed $data = null): mixed
	{
		$this->setStatusCode(201);
		return $data;
	}

	/**
	 * Sets the status to `202 Accepted` and returns the data.
	 * @param mixed $data the response body.
	 * @return mixed `$data`.
	 */
	protected function accepted(mixed $data = null): mixed
	{
		$this->setStatusCode(202);
		return $data;
	}

	/**
	 * Sets the status to `204 No Content`; the service sends no body.
	 */
	protected function noContent(): void
	{
		$this->setStatusCode(204);
	}

	/**
	 * Adds a response header.
	 * @param string $name header name, such as `X-Total-Count`.
	 * @param string $value header value.
	 * @return static the resource.
	 */
	protected function header(string $name, string $value): static
	{
		$this->addResponseHeader($name, $value);
		return $this;
	}

	// ── Exception helpers ─────────────────────────────────────────────────────

	/**
	 * Throws a {@see TRestException} with the status code.
	 * @param int $status HTTP status code.
	 * @param string $detail detail message for the visitor.
	 * @param array $errors field errors keyed by field name.
	 * @return never
	 */
	protected function abort(int $status, string $detail = '', array $errors = []): never
	{
		throw new TRestException($status, '', $detail, $errors);
	}

	/**
	 * Throws a `404 Not Found`.
	 * @param string $detail detail message for the visitor.
	 * @return never
	 */
	protected function notFound(string $detail = ''): never
	{
		throw TRestException::notFound($detail);
	}

	/**
	 * Throws a `401 Unauthorized`.
	 * @param string $detail detail message for the visitor.
	 * @return never
	 */
	protected function unauthorized(string $detail = ''): never
	{
		throw TRestException::unauthorized($detail);
	}

	/**
	 * Throws a `403 Forbidden`.
	 * @param string $detail detail message for the visitor.
	 * @return never
	 */
	protected function forbidden(string $detail = ''): never
	{
		throw TRestException::forbidden($detail);
	}

	/**
	 * Throws a `409 Conflict`.
	 * @param string $detail detail message for the visitor.
	 * @return never
	 */
	protected function conflict(string $detail = ''): never
	{
		throw TRestException::conflict($detail);
	}

	/**
	 * Throws a `422 Unprocessable Entity` with field errors.
	 * @param array $errors field errors keyed by field name.
	 * @param string $detail detail message for the visitor.
	 * @return never
	 */
	protected function unprocessable(array $errors, string $detail = ''): never
	{
		throw TRestException::unprocessable($errors, $detail);
	}

	// ── Validation ────────────────────────────────────────────────────────────

	/**
	 * @return TInputValidator the validator of {@see validate()}, created on first use.
	 */
	public function getValidator(): TInputValidator
	{
		if ($this->getValidatorDirect() === null) {
			$this->setValidatorDirect(Prado::createComponent(TInputValidator::class));
		}
		return $this->getValidatorDirect();
	}

	/**
	 * @param TInputValidator $value the validator of {@see validate()}, for extra rules.
	 */
	public function setValidator(TInputValidator $value): void
	{
		$this->setValidatorDirect($value);
	}

	/**
	 * Validates data against {@see \Prado\Util\TInputValidator} rules and returns the
	 * declared fields, coerced. A failure is a `422 Unprocessable Entity` whose
	 * `errors` hold the messages of each field.
	 * @param array $data the data, such as {@see getBody()}.
	 * @param array $rules rules keyed by field name, such as `'email' => 'required|email'`.
	 * @throws TRestException 422 when the data is invalid.
	 * @return array the validated fields.
	 */
	protected function validate(array $data, array $rules): array
	{
		try {
			return $this->getValidator()->validate($data, $rules);
		} catch (TInputValidationException $e) {
			throw TRestException::unprocessable($e->getErrors(), Prado::localize('The given data was invalid.'));
		}
	}

	/**
	 * Validates the request body with {@see validate()}.
	 * @param array $rules rules keyed by field name.
	 * @throws TRestException 422 when the body is invalid.
	 * @return array the validated fields.
	 */
	protected function validateBody(array $rules): array
	{
		return $this->validate($this->getBody(), $rules);
	}
}
