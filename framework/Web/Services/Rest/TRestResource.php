<?php

/**
 * TRestResource class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

use Prado\TApplicationComponent;

/**
 * TRestResource class
 *
 * TRestResource is the abstract base class for all REST resource handlers used with
 * {@see TRestService}. Each subclass represents one or more URL patterns and implements
 * the HTTP verb methods it supports.
 *
 * ## Convention Methods
 *
 * Override only the verbs your resource supports. Any convention method that is
 * **not** declared on the concrete subclass automatically responds with
 * `405 Method Not Allowed` (handled via `__call`).
 *
 * |  Method       |  Verb  |  Route type  |
 * |---------------|--------|--------------|
 * | `doIndex()`   | GET    | collection   |
 * | `doStore()`   | POST   | collection   |
 * | `doShow()`    | GET    | item         |
 * | `doUpdate()`  | PUT    | item         |
 * | `doPatch()`   | PATCH  | item         |
 * | `doDestroy()` | DELETE | item         |
 *
 * Because the base class uses `__call` rather than typed stubs, subclasses are
 * free to declare any parameter signature they need. Path parameters are injected
 * by name via PHP reflection — declare them as method parameters matching the
 * `{name}` placeholders in the route pattern:
 * ```php
 * // Route: users/{userId}/posts/{id}
 * public function doShow(string $userId, string $id): array { ... }
 * ```
 *
 * ## Status Helpers
 *
 * Use the protected helpers to set non-200 success codes before returning:
 * ```php
 * public function doStore(): array
 * {
 *     $data = $this->validateBody(['name' => 'required|string']);
 *     return $this->created(MyRecord::create($data)->toArray());
 * }
 *
 * public function doDestroy(string $id): void
 * {
 *     MyRecord::delete($id);
 *     $this->noContent();
 * }
 * ```
 *
 * ## Authentication
 *
 * Override {@see authorize()} to enforce any auth requirement. It is called before
 * dispatch and receives the `do`-prefixed method name (e.g. `'doShow'`, `'doStore'`).
 * It may throw {@see TRestException} to abort the request:
 * ```php
 * public function authorize(string $method): void
 * {
 *     if ($this->getApplication()->getUser()->getIsGuest()) {
 *         $this->unauthorized('Authentication required.');
 *     }
 * }
 * ```
 *
 * ## Validation
 *
 * {@see validateBody()} validates the parsed request body against a rule set and
 * returns only the declared fields, or throws `422 Unprocessable Entity`:
 * ```php
 * $data = $this->validateBody([
 *     'email' => 'required|email',
 *     'name'  => 'required|string|max:255',
 *     'age'   => 'nullable|integer|min:0|max:150',
 * ]);
 * ```
 *
 * Supported rules: `required`, `nullable`, `string`, `integer`, `float`, `numeric`,
 * `boolean`, `bool`, `array`, `email`, `url`, `min:N`, `max:N`, `in:a,b,c`.
 *
 * ## Example
 *
 * The following resource handles both the `/api/users` collection and the
 * `/api/users/{id}` item route. Register both patterns in `application.xml`:
 * ```xml
 * <service id="rest" class="Prado\Web\Services\Rest\TRestService" BasePath="api/">
 *   <resource pattern="users"      class="App.Api.UsersResource" />
 *   <resource pattern="users/{id}" class="App.Api.UsersResource"
 *             parameters.id="\d+" />
 * </service>
 * ```
 *
 * ```php
 * namespace App\Api;
 *
 * use Prado\Web\Services\Rest\TRestResource;
 *
 * class UsersResource extends TRestResource
 * {
 *     // Require a signed-in user for every mutating verb.
 *     public function authorize(string $method): void
 *     {
 *         if (!in_array($method, ['doIndex', 'doShow'], true)) {
 *             if ($this->getApplication()->getUser()->getIsGuest()) {
 *                 $this->unauthorized('Authentication required.');
 *             }
 *         }
 *     }
 *
 *     // GET /api/users  — supports ?role= query filter
 *     public function doIndex(): array
 *     {
 *         $role = $this->query('role');
 *         return UserDao::findAll($role ? ['role' => $role] : []);
 *     }
 *
 *     // GET /api/users/{id}
 *     public function doShow(string $id): array
 *     {
 *         return UserDao::find((int) $id)
 *             ?? $this->notFound("User {$id} not found.");
 *     }
 *
 *     // POST /api/users
 *     public function doStore(): array
 *     {
 *         $data = $this->validateBody([
 *             'name'  => 'required|string|max:255',
 *             'email' => 'required|email',
 *             'role'  => 'nullable|string|in:admin,editor,viewer',
 *         ]);
 *         $user = UserDao::create($data);
 *         $this->header('Location', '/api/users/' . $user['id']);
 *         return $this->created($user);
 *     }
 *
 *     // PUT /api/users/{id}  — full replacement
 *     public function doUpdate(string $id): array
 *     {
 *         UserDao::find((int) $id) ?? $this->notFound("User {$id} not found.");
 *         $data = $this->validateBody([
 *             'name'  => 'required|string|max:255',
 *             'email' => 'required|email',
 *             'role'  => 'required|string|in:admin,editor,viewer',
 *         ]);
 *         return UserDao::update((int) $id, $data);
 *     }
 *
 *     // PATCH /api/users/{id}  — partial update; only supplied fields are changed
 *     public function doPatch(string $id): array
 *     {
 *         UserDao::find((int) $id) ?? $this->notFound("User {$id} not found.");
 *         $data = $this->validate($this->only(['name', 'email', 'role']), [
 *             'name'  => 'nullable|string|max:255',
 *             'email' => 'nullable|email',
 *             'role'  => 'nullable|string|in:admin,editor,viewer',
 *         ]);
 *         return UserDao::update((int) $id, array_filter($data, fn($v) => $v !== null));
 *     }
 *
 *     // DELETE /api/users/{id}
 *     public function doDestroy(string $id): void
 *     {
 *         UserDao::find((int) $id) ?? $this->notFound("User {$id} not found.");
 *         UserDao::delete((int) $id);
 *         $this->noContent();
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TRestResource extends TApplicationComponent
{
	/**
	 * @var array Path parameters extracted from the matched route pattern.
	 */
	private array $_pathParameters = [];

	/**
	 * @var int HTTP status code for the response. Defaults to 200.
	 */
	private int $_statusCode = 200;

	/**
	 * @var array Additional response headers as name => value pairs.
	 */
	private array $_responseHeaders = [];

	/**
	 * @var ?array Lazily parsed request body. Null until first access.
	 */
	private ?array $_parsedBody = null;

	// ── Convention methods ─────────────────────────────────────────────────────

	/**
	 * Catches calls to the six REST convention methods that are not overridden
	 * by the concrete resource subclass.
	 *
	 * The six convention method names are: `doIndex`, `doShow`, `doStore`,
	 * `doUpdate`, `doPatch`, `doDestroy`. Calling any of them on an instance
	 * that has not overridden the method throws a `405 Method Not Allowed`
	 * exception.
	 *
	 * Using `__call` instead of concrete stub methods avoids PHP's LSP
	 * signature-compatibility requirement, which would otherwise prevent
	 * subclasses from adding typed path-parameter arguments (e.g.,
	 * `doShow(string $id): array`).
	 *
	 * @param mixed $name Method name called.
	 * @param mixed $args Arguments passed by the caller (unused).
	 * @throws TRestException 405 when $name is a known convention method.
	 * @throws \BadMethodCallException for any other undefined method.
	 * @return never
	 */
	public function __call(mixed $name, mixed $args): never
	{
		if (in_array($name, ['doIndex', 'doShow', 'doStore', 'doUpdate', 'doPatch', 'doDestroy'], true)) {
			throw TRestException::methodNotAllowed();
		}
		throw new \BadMethodCallException('Call to undefined method ' . static::class . '::' . $name . '()');
	}

	// ── Auth lifecycle ─────────────────────────────────────────────────────────

	/**
	 * Authorization hook called by {@see TRestService} before dispatch.
	 *
	 * Override to enforce authentication or authorization. The `$method`
	 * parameter is the `do`-prefixed dispatch method name (e.g. `'doShow'`,
	 * `'doStore'`). Throw a {@see TRestException} to abort the request:
	 * ```php
	 * public function authorize(string $method): void
	 * {
	 *     if ($this->getApplication()->getUser()->getIsGuest()) {
	 *         $this->unauthorized('Authentication required.');
	 *     }
	 * }
	 * ```
	 * @param string $method The dispatch method name (e.g., `'doShow'`, `'doStore'`).
	 */
	public function authorize(string $method): void
	{
	}

	// ── Direct Accessors (UAP-SE) ──────────────────────────────────────────────

	/**
	 * @return array Stored path parameters.
	 */
	protected function getPathParametersDirect(): array
	{
		return $this->_pathParameters;
	}

	/**
	 * @param array $value Path parameters to store.
	 */
	protected function setPathParametersDirect(array $value): void
	{
		$this->_pathParameters = $value;
	}

	/**
	 * @return int Stored HTTP status code.
	 */
	protected function getStatusCodeDirect(): int
	{
		return $this->_statusCode;
	}

	/**
	 * @param int $value HTTP status code to store.
	 */
	protected function setStatusCodeDirect(int $value): void
	{
		$this->_statusCode = $value;
	}

	/**
	 * @return array Stored response headers.
	 */
	protected function getResponseHeadersDirect(): array
	{
		return $this->_responseHeaders;
	}

	/**
	 * @param array $value Response headers to store.
	 */
	protected function setResponseHeadersDirect(array $value): void
	{
		$this->_responseHeaders = $value;
	}

	/**
	 * @return ?array Stored parsed body, or null if not yet parsed.
	 */
	protected function getParsedBodyDirect(): ?array
	{
		return $this->_parsedBody;
	}

	/**
	 * @param ?array $value Parsed body to store, or null to clear.
	 */
	protected function setParsedBodyDirect(?array $value): void
	{
		$this->_parsedBody = $value;
	}

	// ── Internal accessors used by TRestService ────────────────────────────────

	/**
	 * @return int HTTP status code to send with the response.
	 */
	public function getStatusCode(): int
	{
		return $this->getStatusCodeDirect();
	}

	/**
	 * Sets the HTTP status code for the response.
	 * Use the semantic helpers {@see created()}, {@see accepted()}, and
	 * {@see noContent()} in resource methods rather than calling this directly.
	 * @param int $value HTTP status code.
	 */
	protected function setStatusCode(int $value): void
	{
		$this->setStatusCodeDirect($value);
	}

	/**
	 * @return array Additional response headers as name => value pairs.
	 */
	public function getResponseHeaders(): array
	{
		return $this->getResponseHeadersDirect();
	}

	/**
	 * Adds a single response header.
	 * Called internally by {@see header()}.
	 * @param string $name Header name.
	 * @param string $value Header value.
	 */
	protected function addResponseHeader(string $name, string $value): void
	{
		$headers = $this->getResponseHeadersDirect();
		$headers[$name] = $value;
		$this->setResponseHeadersDirect($headers);
	}

	/**
	 * Sets the extracted path parameters.
	 * This method is called by {@see TRestService} before dispatch.
	 * @param array $params Path parameters keyed by parameter name.
	 */
	public function setPathParameters(array $params): void
	{
		$this->setPathParametersDirect($params);
	}

	/**
	 * @return array Path parameters extracted from the matched route pattern.
	 */
	public function getPathParameters(): array
	{
		return $this->getPathParametersDirect();
	}

	// ── Request helpers ────────────────────────────────────────────────────────

	/**
	 * Returns a single path parameter by name.
	 * @param string $name Parameter name as declared in the route pattern.
	 * @param mixed $default Value to return when the parameter is absent.
	 * @return mixed Parameter value or $default.
	 */
	public function getPathParameter(string $name, mixed $default = null): mixed
	{
		return $this->getPathParameters()[$name] ?? $default;
	}

	/**
	 * Returns the parsed request body.
	 *
	 * `Content-Type: application/json` requests decode the raw input stream.
	 * Form-encoded `POST` requests return `$_POST` directly; form-encoded
	 * `PUT` and `PATCH` requests parse `php://input` via `parse_str()` (PHP
	 * does not populate `$_POST` for non-`POST` verbs). Other verbs and
	 * invalid bodies return an empty array. The result is cached for the
	 * lifetime of the resource.
	 * @return array Parsed body data.
	 */
	public function getBody(): array
	{
		if ($this->getParsedBodyDirect() !== null) {
			return $this->getParsedBodyDirect();
		}

		$request = $this->getRequest();
		$verb = strtoupper($request->getRequestType() ?? 'GET');

		if (in_array($verb, ['POST', 'PUT', 'PATCH'], true)) {
			$contentType = $request->getContentType() ?? '';
			if (str_contains($contentType, 'application/json')) {
				$raw = $this->readRawRequestBody();
				$decoded = json_decode($raw !== '' ? $raw : '[]', true);
				$this->setParsedBodyDirect(is_array($decoded) ? $decoded : []);
			} elseif ($verb === 'POST') {
				$this->setParsedBodyDirect($_POST);
			} else {
				// PHP only populates $_POST for POST requests; parse the raw stream
				// for PUT/PATCH form-encoded bodies.
				$parsed = [];
				parse_str($this->readRawRequestBody(), $parsed);
				$this->setParsedBodyDirect($parsed);
			}
		} else {
			$this->setParsedBodyDirect([]);
		}

		return $this->getParsedBodyDirect() ?? [];
	}

	/**
	 * Reads the raw HTTP request body from `php://input`.
	 *
	 * Extracted as a protected seam so unit tests can override it without
	 * needing to register a stream wrapper.
	 * @return string Raw request body, or empty string when unavailable.
	 */
	protected function readRawRequestBody(): string
	{
		return (string) (file_get_contents('php://input') ?: '');
	}

	/**
	 * Returns a value from the request body first, then from the query string.
	 * @param string $key Field name.
	 * @param mixed $default Value to return when the key is absent.
	 * @return mixed
	 */
	public function input(string $key, mixed $default = null): mixed
	{
		$body = $this->getBody();
		if (array_key_exists($key, $body)) {
			return $body[$key];
		}
		$request = $this->getRequest();
		if ($request->contains($key)) {
			return $request->itemAt($key);
		}
		return $default;
	}

	/**
	 * Returns a value from the query string only.
	 * @param string $key Query parameter name.
	 * @param mixed $default Value to return when the key is absent.
	 * @return mixed
	 */
	public function query(string $key, mixed $default = null): mixed
	{
		$request = $this->getRequest();
		if ($request->contains($key)) {
			return $request->itemAt($key);
		}
		return $default;
	}

	/**
	 * Returns whether the key is present in the body or query string.
	 * @param string $key Field name.
	 * @return bool
	 */
	public function hasInput(string $key): bool
	{
		return array_key_exists($key, $this->getBody()) || $this->getRequest()->contains($key);
	}

	/**
	 * Returns a subset of the request body containing only the given keys.
	 * @param array $keys Field names to include.
	 * @return array
	 */
	public function only(array $keys): array
	{
		return array_intersect_key($this->getBody(), array_flip($keys));
	}

	/**
	 * Returns the request body with the given keys removed.
	 * @param array $keys Field names to exclude.
	 * @return array
	 */
	public function except(array $keys): array
	{
		return array_diff_key($this->getBody(), array_flip($keys));
	}

	// ── Status helpers ─────────────────────────────────────────────────────────

	/**
	 * Sets the response status to 201 Created and returns the provided data.
	 * Call this in {@see doStore()} after successfully creating a resource.
	 * @param mixed $data Resource data to return in the response body.
	 * @return mixed The passed $data value.
	 */
	protected function created(mixed $data = null): mixed
	{
		$this->setStatusCode(201);
		return $data;
	}

	/**
	 * Sets the response status to 202 Accepted and returns the provided data.
	 * @param mixed $data Optional response body data.
	 * @return mixed The passed $data value.
	 */
	protected function accepted(mixed $data = null): mixed
	{
		$this->setStatusCode(202);
		return $data;
	}

	/**
	 * Sets the response status to 204 No Content.
	 * The response body will be suppressed by {@see TRestService}.
	 */
	protected function noContent(): void
	{
		$this->setStatusCode(204);
	}

	/**
	 * Appends a custom response header.
	 * @param string $name Header name (e.g., `'X-Total-Count'`).
	 * @param string $value Header value.
	 * @return static
	 */
	protected function header(string $name, string $value): static
	{
		$this->addResponseHeader($name, $value);
		return $this;
	}

	// ── Exception helpers ──────────────────────────────────────────────────────

	/**
	 * Throws a {@see TRestException} with the given status code.
	 * @param int $status HTTP status code.
	 * @param string $detail Optional detail message.
	 * @param array $errors Optional field-level validation errors.
	 * @return never
	 */
	protected function abort(int $status, string $detail = '', array $errors = []): never
	{
		throw new TRestException($status, '', $detail, $errors);
	}

	/**
	 * Throws a 404 Not Found exception.
	 * @param string $detail Optional detail message.
	 * @return never
	 */
	protected function notFound(string $detail = ''): never
	{
		throw TRestException::notFound($detail);
	}

	/**
	 * Throws a 401 Unauthorized exception.
	 * @param string $detail Optional detail message.
	 * @return never
	 */
	protected function unauthorized(string $detail = ''): never
	{
		throw TRestException::unauthorized($detail);
	}

	/**
	 * Throws a 403 Forbidden exception.
	 * @param string $detail Optional detail message.
	 * @return never
	 */
	protected function forbidden(string $detail = ''): never
	{
		throw TRestException::forbidden($detail);
	}

	/**
	 * Throws a 409 Conflict exception.
	 * @param string $detail Optional detail message.
	 * @return never
	 */
	protected function conflict(string $detail = ''): never
	{
		throw TRestException::conflict($detail);
	}

	/**
	 * Throws a 422 Unprocessable Entity exception with field-level errors.
	 * @param array $errors Validation errors keyed by field name.
	 * @param string $detail Optional detail message.
	 * @return never
	 */
	protected function unprocessable(array $errors, string $detail = ''): never
	{
		throw TRestException::unprocessable($errors, $detail);
	}

	// ── Validation ─────────────────────────────────────────────────────────────

	/**
	 * Validates the given data array against a rule set.
	 *
	 * Returns the validated data (only the fields declared in $rules) with any
	 * applicable type coercions applied. Throws `422 Unprocessable Entity` if
	 * validation fails.
	 *
	 * Rule format: `'field' => 'rule1|rule2|rule3:param'`.
	 *
	 * Supported rules:
	 * - `required` — field must be present and non-null
	 * - `nullable` — field may be null; skips type rules when null
	 * - `string` — value must be a string
	 * - `integer` / `int` — value must be or cast to an integer
	 * - `float` / `numeric` — value must be numeric
	 * - `boolean` / `bool` — value must be boolean-ish (true/false/1/0/'1'/'0')
	 * - `array` — value must be an array
	 * - `email` — value must pass `FILTER_VALIDATE_EMAIL`
	 * - `url` — value must pass `FILTER_VALIDATE_URL`
	 * - `min:N` — string: minimum length N; number: minimum value N
	 * - `max:N` — string: maximum length N; number: maximum value N
	 * - `in:a,b,c` — value must be one of the comma-separated options
	 *
	 * @param array $data Input data to validate (e.g., parsed request body).
	 * @param array $rules Rule set keyed by field name.
	 * @throws TRestException 422 when validation fails.
	 * @return array Validated and type-coerced data containing only declared fields.
	 */
	protected function validate(array $data, array $rules): array
	{
		$errors = [];
		$validated = [];

		foreach ($rules as $field => $ruleString) {
			$fieldRules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
			$required = in_array('required', $fieldRules, true);
			$nullable = in_array('nullable', $fieldRules, true);
			$present = array_key_exists($field, $data);
			$value = $data[$field] ?? null;

			// Handle missing / null values
			if (!$present || $value === null) {
				if ($required && (!$present || $value === null)) {
					$errors[$field][] = "The {$field} field is required.";
				} elseif ($nullable && $present) {
					$validated[$field] = null;
				}
				continue;
			}

			$fieldValid = true;
			foreach ($fieldRules as $rule) {
				if ($rule === 'required' || $rule === 'nullable') {
					continue;
				}

				$ruleParts = explode(':', $rule, 2);
				$ruleName = $ruleParts[0];
				$ruleParam = $ruleParts[1] ?? null;

				switch ($ruleName) {
					case 'string':
						if (!is_string($value)) {
							$errors[$field][] = "The {$field} field must be a string.";
							$fieldValid = false;
						}
						break;

					case 'integer':
					case 'int':
						// Use FILTER_VALIDATE_INT so strings like '--5', '1-2', '1.5',
						// '5e2', and ' 5' are rejected rather than silently coerced.
						if (is_int($value)) {
							// already correct
						} elseif (is_string($value) || is_float($value)) {
							$filtered = filter_var($value, FILTER_VALIDATE_INT);
							if ($filtered === false) {
								$errors[$field][] = "The {$field} field must be an integer.";
								$fieldValid = false;
							} else {
								$value = $filtered;
							}
						} else {
							$errors[$field][] = "The {$field} field must be an integer.";
							$fieldValid = false;
						}
						break;

					case 'float':
					case 'numeric':
						if (is_numeric($value)) {
							$value = (float) $value;
						} else {
							$errors[$field][] = "The {$field} field must be numeric.";
							$fieldValid = false;
						}
						break;

					case 'boolean':
					case 'bool':
						if (in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
							$value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
						} else {
							$errors[$field][] = "The {$field} field must be a boolean.";
							$fieldValid = false;
						}
						break;

					case 'array':
						if (!is_array($value)) {
							$errors[$field][] = "The {$field} field must be an array.";
							$fieldValid = false;
						}
						break;

					case 'email':
						if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
							$errors[$field][] = "The {$field} field must be a valid email address.";
							$fieldValid = false;
						}
						break;

					case 'url':
						if (!filter_var($value, FILTER_VALIDATE_URL)) {
							$errors[$field][] = "The {$field} field must be a valid URL.";
							$fieldValid = false;
						}
						break;

					case 'min':
						$min = (float) $ruleParam;
						if (is_string($value)) {
							if (mb_strlen($value) < $min) {
								$errors[$field][] = "The {$field} field must be at least {$ruleParam} characters.";
								$fieldValid = false;
							}
						} elseif (is_numeric($value) && $value < $min) {
							$errors[$field][] = "The {$field} field must be at least {$ruleParam}.";
							$fieldValid = false;
						}
						break;

					case 'max':
						$max = (float) $ruleParam;
						if (is_string($value)) {
							if (mb_strlen($value) > $max) {
								$errors[$field][] = "The {$field} field must not exceed {$ruleParam} characters.";
								$fieldValid = false;
							}
						} elseif (is_numeric($value) && $value > $max) {
							$errors[$field][] = "The {$field} field must not exceed {$ruleParam}.";
							$fieldValid = false;
						}
						break;

					case 'in':
						$allowed = array_map('trim', explode(',', $ruleParam ?? ''));
						if (!in_array((string) $value, $allowed, true)) {
							$list = implode(', ', $allowed);
							$errors[$field][] = "The {$field} field must be one of: {$list}.";
							$fieldValid = false;
						}
						break;
				}
			}

			if ($fieldValid) {
				$validated[$field] = $value;
			}
		}

		if ($errors !== []) {
			throw TRestException::unprocessable($errors, 'The given data was invalid.');
		}

		return $validated;
	}

	/**
	 * Validates the parsed request body against a rule set.
	 *
	 * Convenience wrapper around {@see validate()} that reads the body
	 * automatically.
	 * @param array $rules Rule set keyed by field name.
	 * @throws TRestException 422 when validation fails.
	 * @return array Validated and type-coerced data.
	 */
	protected function validateBody(array $rules): array
	{
		return $this->validate($this->getBody(), $rules);
	}
}
