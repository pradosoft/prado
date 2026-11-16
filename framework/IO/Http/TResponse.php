<?php

/**
 * TResponse class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Http;

use Prado\IO\TStream;
use Psr\Http\Message\ResponseInterface;

/**
 * TResponse class
 *
 * TResponse is the immutable PSR-7 {@see ResponseInterface} of an HTTP response: the
 * status code, the reason phrase, the headers, and the body.  An
 * {@see \Prado\IO\HttpClient\THttpClient} returns it for every status.
 * ```php
 * $response = $client->sendRequest($request);
 * if ($response->isSuccess()) {
 *     $data = json_decode((string) $response->getBody(), true);
 * }
 * ```
 *
 * The status defaults to 200.  A status given without a reason phrase takes the standard
 * phrase of {@see REASON_PHRASES}, or `''` for an unregistered status.
 * {@see isInformational()}, {@see isSuccess()}, {@see isRedirect()}, {@see isClientError()},
 * and {@see isServerError()} classify the status.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TResponse extends TMessage implements ResponseInterface
{
	/** The default status code. */
	public const DEFAULT_STATUS = 200;

	/**
	 * The standard reason phrase of each status code, per the IANA HTTP Status Code Registry
	 * (RFC 9110 and related).  {@see \Prado\Web\THttpResponse} uses the same table.
	 * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 */
	public const REASON_PHRASES = [
		100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
		200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used',
		300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
		400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed', 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Too Early', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required',
	];

	/** @var int The status code. */
	private int $_statusCode = self::DEFAULT_STATUS;

	/** @var string The reason phrase. */
	private string $_reasonPhrase = '';

	/**
	 * Builds a response.
	 * @param int $status The status code, 100 to 599.
	 * @param array<string, string|string[]> $headers The headers by name.
	 * @param mixed $body The body: a {@see \Psr\Http\Message\StreamInterface}, a string, a resource, or null for none.
	 * @param string $version The protocol version.
	 * @param ?string $reason The reason phrase, or null for the standard phrase.
	 * @throws \InvalidArgumentException When the status, a header, or the version is invalid.
	 */
	public function __construct(int $status = self::DEFAULT_STATUS, array $headers = [], mixed $body = null, string $version = self::DEFAULT_PROTOCOL_VERSION, ?string $reason = null)
	{
		parent::__construct();
		$this->setStatusCodeDirect($this->filterStatusCode($status));
		$this->setReasonPhraseDirect($reason ?? static::getStandardReasonPhrase($status));
		$this->setHeaders($headers);
		$this->setProtocolVersionDirect($this->filterProtocolVersion($version));
		if ($body !== null && $body !== '') {
			$this->setBodyDirect(TStream::for($body));
		}
	}

	/**
	 * Returns the standard reason phrase of a status code.
	 * @param int $status The status code.
	 * @return string The phrase, or `''` for an unregistered status.
	 */
	public static function getStandardReasonPhrase(int $status): string
	{
		return static::REASON_PHRASES[$status] ?? '';
	}

	//
	// ─── Protected raw accessors ─────────────────────────────────────────────
	//

	/** @return int Returns the stored status code. */
	protected function getStatusCodeDirect(): int
	{
		return $this->_statusCode;
	}

	/** @param int $value Stores the status code. */
	protected function setStatusCodeDirect(int $value): void
	{
		$this->_statusCode = $value;
	}

	/** @return string Returns the stored reason phrase. */
	protected function getReasonPhraseDirect(): string
	{
		return $this->_reasonPhrase;
	}

	/** @param string $value Stores the reason phrase. */
	protected function setReasonPhraseDirect(string $value): void
	{
		$this->_reasonPhrase = $value;
	}

	//
	// ─── PSR-7 ResponseInterface ─────────────────────────────────────────────
	//

	/**
	 * Returns the status code.
	 * @return int The status code.
	 */
	public function getStatusCode(): int
	{
		return $this->getStatusCodeDirect();
	}

	/**
	 * Returns an instance with the specified status code and reason phrase.  An empty
	 * reason takes the standard phrase of the status.
	 * @param int $code The status code, 100 to 599.
	 * @param string $reasonPhrase The reason phrase, or `''` for the standard phrase.
	 * @throws \InvalidArgumentException When the status code is out of range.
	 * @return static The new instance.
	 */
	public function withStatus(int $code, string $reasonPhrase = ''): static
	{
		$code = $this->filterStatusCode($code);
		$clone = clone $this;
		$clone->setStatusCodeDirect($code);
		$clone->setReasonPhraseDirect($reasonPhrase === '' ? static::getStandardReasonPhrase($code) : $reasonPhrase);
		return $clone;
	}

	/**
	 * Returns the reason phrase.
	 * @return string The reason phrase, or `''` when none applies.
	 */
	public function getReasonPhrase(): string
	{
		return $this->getReasonPhraseDirect();
	}

	//
	// ─── Status classes ──────────────────────────────────────────────────────
	//

	/**
	 * Reports whether the status code is 1xx.
	 * @return bool Whether the status code is 1xx.
	 */
	public function isInformational(): bool
	{
		return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
	}

	/**
	 * Reports whether the status code is 2xx.
	 * @return bool Whether the status code is 2xx.
	 */
	public function isSuccess(): bool
	{
		return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
	}

	/**
	 * Reports whether the status code is 3xx.
	 * @return bool Whether the status code is 3xx.
	 */
	public function isRedirect(): bool
	{
		return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
	}

	/**
	 * Reports whether the status code is 4xx.
	 * @return bool Whether the status code is 4xx.
	 */
	public function isClientError(): bool
	{
		return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
	}

	/**
	 * Reports whether the status code is 5xx.
	 * @return bool Whether the status code is 5xx.
	 */
	public function isServerError(): bool
	{
		return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
	}

	/**
	 * Validates a status code: 100 to 599.
	 * @param int $code The status code.
	 * @throws \InvalidArgumentException When the code is out of range.
	 * @return int The code.
	 */
	protected function filterStatusCode(int $code): int
	{
		if ($code < 100 || $code > 599) {
			throw new \InvalidArgumentException("Invalid HTTP status code: {$code}.");
		}
		return $code;
	}
}
