<?php

/**
 * TRestException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\Rest;

use Prado\Exceptions\THttpException;

/**
 * TRestException class
 *
 * TRestException represents an HTTP error that has occurred during REST request
 * processing. It carries an HTTP status code, a human-readable title, an optional
 * detail message, and an optional structured array of field-level validation errors.
 *
 * The error payload is inspired by RFC 7807 (Problem Details for HTTP APIs) and is
 * serialized as JSON by {@see TRestService} when returned to the client.
 *
 * Use the static factory methods for the most common HTTP error conditions:
 * ```php
 * throw TRestException::notFound('User not found.');
 * throw TRestException::unprocessable(['email' => ['The email field is required.']]);
 * ```
 *
 * The {@see toArray()} method produces the JSON-serializable error payload:
 * ```json
 * {
 *   "status": 422,
 *   "title": "Unprocessable Entity",
 *   "detail": "The given data was invalid.",
 *   "errors": { "email": ["The email field is required."] }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRestException extends THttpException
{
	/**
	 * Standard HTTP status reason phrases.
	 */
	private static array $HTTP_TITLES = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		409 => 'Conflict',
		415 => 'Unsupported Media Type',
		422 => 'Unprocessable Entity',
		429 => 'Too Many Requests',
		500 => 'Internal Server Error',
		503 => 'Service Unavailable',
	];

	/**
	 * @var string Short, human-readable summary of the problem.
	 */
	private string $_title;

	/**
	 * @var string Human-readable explanation specific to this occurrence.
	 */
	private string $_detail;

	/**
	 * @var array Field-level validation errors keyed by field name.
	 */
	private array $_errors;

	/**
	 * Constructor. Falls back to the standard reason phrase when `$title`
	 * is empty, and forces `Exception::$code` to equal `$statusCode` so that
	 * `getCode() === getStatusCode()` and PHPUnit's `expectExceptionCode()`
	 * works as expected.
	 * @param int $statusCode HTTP status code (e.g. 404, 422).
	 * @param string $title Short problem title; empty uses the reason phrase.
	 * @param string $detail Human-readable detail message.
	 * @param array $errors Field-level validation errors keyed by field name.
	 */
	public function __construct(int $statusCode, string $title = '', string $detail = '', array $errors = [])
	{
		$this->setTitleDirect($title !== '' ? $title : (self::$HTTP_TITLES[$statusCode] ?? 'Error'));
		$this->setDetailDirect($detail);
		$this->setErrorsDirect($errors);
		parent::__construct($statusCode, 'restservice_http_error', $statusCode, $this->getTitleDirect());
		// THttpException uses old-style TException construction, so PHP's Exception::$code
		// stays 0. Set it explicitly so getCode() === getStatusCode() and PHPUnit's
		// expectExceptionCode() works as expected.
		$this->code = $statusCode;
	}

	// ── Direct Accessors (UAP-SE) ──────────────────────────────────────────────

	/**
	 * @return string Stored title value.
	 */
	protected function getTitleDirect(): string
	{
		return $this->_title;
	}

	/**
	 * @param string $value Title to store.
	 */
	protected function setTitleDirect(string $value): void
	{
		$this->_title = $value;
	}

	/**
	 * @return string Stored detail message.
	 */
	protected function getDetailDirect(): string
	{
		return $this->_detail;
	}

	/**
	 * @param string $value Detail message to store.
	 */
	protected function setDetailDirect(string $value): void
	{
		$this->_detail = $value;
	}

	/**
	 * @return array Stored field-level errors.
	 */
	protected function getErrorsDirect(): array
	{
		return $this->_errors;
	}

	/**
	 * @param array $value Field-level errors to store.
	 */
	protected function setErrorsDirect(array $value): void
	{
		$this->_errors = $value;
	}

	// ── Public accessors ──────────────────────────────────────────────────────

	/**
	 * @return string Short problem title.
	 */
	public function getTitle(): string
	{
		return $this->getTitleDirect();
	}

	/**
	 * @return string Human-readable detail message, empty string if none.
	 */
	public function getDetail(): string
	{
		return $this->getDetailDirect();
	}

	/**
	 * @return array Field-level validation errors, empty array if none.
	 */
	public function getErrors(): array
	{
		return $this->getErrorsDirect();
	}

	/**
	 * Returns the exception as an array suitable for JSON serialization.
	 * The `status` and `title` keys are always present. The `detail` key is
	 * included only when non-empty. The `errors` key is included only when
	 * the errors array is non-empty.
	 * @return array RFC 7807-inspired problem detail array.
	 */
	public function toArray(): array
	{
		$result = [
			'status' => $this->getStatusCode(),
			'title' => $this->getTitle(),
		];
		if ($this->getDetail() !== '') {
			$result['detail'] = $this->getDetail();
		}
		if ($this->getErrors() !== []) {
			$result['errors'] = $this->getErrors();
		}
		return $result;
	}

	// ── Static factory methods ─────────────────────────────────────────────────

	/**
	 * Creates a 400 Bad Request exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function badRequest(string $detail = ''): self
	{
		return new self(400, '', $detail);
	}

	/**
	 * Creates a 401 Unauthorized exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function unauthorized(string $detail = ''): self
	{
		return new self(401, '', $detail);
	}

	/**
	 * Creates a 403 Forbidden exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function forbidden(string $detail = ''): self
	{
		return new self(403, '', $detail);
	}

	/**
	 * Creates a 404 Not Found exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function notFound(string $detail = ''): self
	{
		return new self(404, '', $detail);
	}

	/**
	 * Creates a 405 Method Not Allowed exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function methodNotAllowed(string $detail = ''): self
	{
		return new self(405, '', $detail);
	}

	/**
	 * Creates a 409 Conflict exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function conflict(string $detail = ''): self
	{
		return new self(409, '', $detail);
	}

	/**
	 * Creates a 415 Unsupported Media Type exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function unsupportedMediaType(string $detail = ''): self
	{
		return new self(415, '', $detail);
	}

	/**
	 * Creates a 422 Unprocessable Entity exception with optional field errors.
	 * @param array $errors Field-level validation errors keyed by field name,
	 *   where each value is an array of error message strings.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function unprocessable(array $errors = [], string $detail = ''): self
	{
		return new self(422, '', $detail, $errors);
	}

	/**
	 * Creates a 429 Too Many Requests exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function tooManyRequests(string $detail = ''): self
	{
		return new self(429, '', $detail);
	}

	/**
	 * Creates a 500 Internal Server Error exception.
	 * @param string $detail Optional detail message.
	 * @return self
	 */
	public static function internalError(string $detail = ''): self
	{
		return new self(500, '', $detail);
	}
}
