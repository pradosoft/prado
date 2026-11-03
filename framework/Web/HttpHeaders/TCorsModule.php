<?php

/**
 * TCorsModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Exceptions\TConfigurationException;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Web\THttpHeaderName;

/**
 * TCorsModule class
 *
 * TCorsModule sends the Cross-Origin Resource Sharing (CORS) response headers
 * for every service of the application and answers preflight requests. It
 * attaches {@see handleRequest()} to {@see \Prado\TApplication::onBeginRequest()},
 * so the headers are sent before any service runs.
 *
 * A request without an `Origin` header is not a cross-origin request and is
 * left alone. A request whose origin is outside {@see setAllowOrigin AllowOrigin}
 * receives no `Access-Control-*` headers, which makes the browser block the
 * response. A preflight request (`OPTIONS` with `Access-Control-Request-Method`)
 * is answered with status `204`, and {@see \Prado\TApplication::completeRequest()}
 * stops the application lifecycle so no service runs. The preflight answer lists
 * the configured methods and headers; the browser compares them with the actual
 * request.
 *
 * XML configuration:
 *
 * ```xml
 * <module id="cors" class="Prado\Web\HttpHeaders\TCorsModule"
 *   AllowOrigin="https://app.example.com"
 *   AllowCredentials="true"
 *   PathPrefix="api" />
 * ```
 *
 * PHP configuration:
 *
 * ```php
 * 'modules' => [
 *     'cors' => [
 *         'class' => 'Prado\Web\HttpHeaders\TCorsModule',
 *         'properties' => [
 *             'AllowOrigin' => 'https://app.example.com',
 *             'AllowCredentials' => 'true',
 *             'PathPrefix' => 'api',
 *         ],
 *     ],
 * ],
 * ```
 *
 * {@see setAllowOrigin AllowOrigin} is `'*'` (every origin) or a comma-separated
 * allow-list of exact origins, compared without regard to case. The CORS
 * specification forbids `Access-Control-Allow-Origin: *` together with
 * `Access-Control-Allow-Credentials: true`, so {@see init()} throws a
 * {@see TConfigurationException} when {@see setAllowCredentials AllowCredentials}
 * is on while `AllowOrigin` is `'*'`. An allow-list, or credentials, echoes the
 * request origin back and adds `Vary: Origin` to every response, the ones of a
 * refused origin included, so a shared cache keeps the responses apart.
 *
 * {@see setPathPrefix PathPrefix} limits the module to one part of the
 * application. The request path (`PathInfo` without its leading slash) must
 * equal the prefix or continue it at a `/` boundary: `api` matches `api` and
 * `api/users`, and does not match `apidocs`. `PathInfo` is the raw URL path, so
 * a prefix needs the `Path` or `HiddenPath` URL format; in the `Get` format it is
 * empty and a prefix matches nothing.
 *
 * | Request | Headers sent |
 * |---|---|
 * | No `Origin` header, or path outside `PathPrefix` | none |
 * | Origin outside the allow-list | `Vary: Origin` |
 * | Allowed origin, `AllowOrigin` is `*`, credentials off | `Access-Control-Allow-Origin: *` |
 * | Allowed origin, allow-list or credentials on | `Access-Control-Allow-Origin: <origin>`, `Vary: Origin` |
 * | Credentials on | adds `Access-Control-Allow-Credentials: true` |
 * | Actual request, `ExposeHeaders` set | adds `Access-Control-Expose-Headers` |
 * | Preflight (`OPTIONS` with `Access-Control-Request-Method`) | adds `Access-Control-Allow-Methods` and `Access-Control-Allow-Headers` when not empty (an empty `AllowHeaders` echoes `Access-Control-Request-Headers`), `Access-Control-Max-Age`; status `204`; the request completes |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCorsModule extends TModule
{
	/** The `AllowOrigin` value that allows every origin. */
	public const WILDCARD_ORIGIN = '*';

	/** Default value of {@see setAllowMethods AllowMethods}. */
	public const DEFAULT_ALLOW_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD';

	/** Default value of {@see setAllowHeaders AllowHeaders}. */
	public const DEFAULT_ALLOW_HEADERS = 'Content-Type, Authorization, X-Requested-With, Accept, X-Prado-Csrf-Token';

	/** Default value of {@see setMaxAge MaxAge}, in seconds. */
	public const DEFAULT_MAX_AGE = 86400;

	/** @var string `'*'` or a comma-separated allow-list of origins. */
	private string $_allowOrigin = self::WILDCARD_ORIGIN;

	/** @var string Comma-separated `Access-Control-Allow-Methods` value. */
	private string $_allowMethods = self::DEFAULT_ALLOW_METHODS;

	/** @var string Comma-separated `Access-Control-Allow-Headers` value. */
	private string $_allowHeaders = self::DEFAULT_ALLOW_HEADERS;

	/** @var string Comma-separated `Access-Control-Expose-Headers` value. */
	private string $_exposeHeaders = '';

	/** @var bool Whether `Access-Control-Allow-Credentials: true` is sent. */
	private bool $_allowCredentials = false;

	/** @var int `Access-Control-Max-Age` value, in seconds. */
	private int $_maxAge = self::DEFAULT_MAX_AGE;

	/** @var string The request path prefix the module acts on; `''` for every path. */
	private string $_pathPrefix = '';

	/**
	 * Validates the configuration and attaches {@see handleRequest()} to
	 * {@see \Prado\TApplication::onBeginRequest()}.
	 * @param null|array|\Prado\Xml\TXmlElement $config Module configuration element.
	 * @throws TConfigurationException when AllowCredentials is on while AllowOrigin is `'*'`.
	 */
	public function init($config): void
	{
		parent::init($config);
		$this->assertValidConfig();
		$this->getApplication()->attachEventHandler('onBeginRequest', [$this, 'handleRequest']);
	}

	/**
	 * Rejects credentials combined with the wildcard origin. Reflecting every
	 * origin while sending `Access-Control-Allow-Credentials: true` lets any site
	 * make requests with the visitor's cookies, which the CORS specification forbids.
	 * @throws TConfigurationException when AllowCredentials is on while AllowOrigin is `'*'`.
	 */
	protected function assertValidConfig(): void
	{
		if ($this->getAllowCredentials() && $this->getAllowOrigin() === self::WILDCARD_ORIGIN) {
			throw new TConfigurationException('corsmodule_credentials_wildcard');
		}
	}

	/**
	 * Sends the CORS headers for a cross-origin request and answers a preflight.
	 * Does nothing when the request has no `Origin` header or the path is outside
	 * {@see setPathPrefix PathPrefix}. When the allowed origin depends on the
	 * request origin, every response carries `Vary: Origin`; a refused origin gets
	 * nothing else. A preflight gets status `204` and completes the request.
	 * @param mixed $sender The application raising `onBeginRequest`.
	 * @param mixed $param The event parameter.
	 * @throws TConfigurationException when AllowCredentials is on while AllowOrigin is `'*'`.
	 */
	public function handleRequest($sender, $param): void
	{
		$request = $this->getRequest();
		$origin = trim((string) $request->getHeader(THttpHeaderName::Origin, ''));
		if ($origin === '' || !$this->appliesToPath((string) $request->getPathInfo())) {
			return;
		}
		$this->assertValidConfig();

		$response = $this->getResponse();
		$echo = $this->getAllowOrigin() !== self::WILDCARD_ORIGIN || $this->getAllowCredentials();
		if ($echo) {
			$response->appendHeader(THttpHeaderName::Vary . ': ' . THttpHeaderName::Origin, false);
		}
		if (!$this->isOriginAllowed($origin)) {
			return;
		}
		$response->appendHeader(THttpHeaderName::AccessControlAllowOrigin . ': ' . ($echo ? $origin : self::WILDCARD_ORIGIN));
		if ($this->getAllowCredentials()) {
			$response->appendHeader(THttpHeaderName::AccessControlAllowCredentials . ': true');
		}

		if (!$this->getIsPreflight()) {
			if (($expose = $this->getExposeHeaders()) !== '') {
				$response->appendHeader(THttpHeaderName::AccessControlExposeHeaders . ': ' . $expose);
			}
			return;
		}
		if (($methods = $this->getAllowMethods()) !== '') {
			$response->appendHeader(THttpHeaderName::AccessControlAllowMethods . ': ' . $methods);
		}
		$headers = $this->getAllowHeaders();
		if ($headers === '') {
			$headers = $this->getRequestedHeaders();
		}
		if ($headers !== '') {
			$response->appendHeader(THttpHeaderName::AccessControlAllowHeaders . ': ' . $headers);
		}
		$response->appendHeader(THttpHeaderName::AccessControlMaxAge . ': ' . $this->getMaxAge());
		$response->setStatusCode(204);
		$this->getApplication()->completeRequest();
	}

	/**
	 * Returns whether the request is a CORS preflight: an `OPTIONS` request that
	 * carries `Access-Control-Request-Method`.
	 * @return bool Whether the request is a preflight.
	 */
	protected function getIsPreflight(): bool
	{
		$request = $this->getRequest();
		return strtoupper((string) $request->getRequestType()) === 'OPTIONS'
			&& $request->getHeader(THttpHeaderName::AccessControlRequestMethod) !== null;
	}

	/**
	 * Returns the `Access-Control-Request-Headers` of a preflight, reduced to header
	 * name characters, commas and spaces.
	 * @return string The requested header names, `''` when the request names none.
	 */
	protected function getRequestedHeaders(): string
	{
		$requested = (string) $this->getRequest()->getHeader(THttpHeaderName::AccessControlRequestHeaders, '');
		return trim((string) preg_replace('/[^A-Za-z0-9_\-, ]/', '', $requested));
	}

	/**
	 * Returns whether an origin is allowed: every origin when AllowOrigin is
	 * `'*'`, otherwise an origin that equals an entry of the allow-list without
	 * regard to case. Entries are trimmed; no pattern matching is done.
	 * @param string $origin The request origin.
	 * @return bool Whether the origin is allowed.
	 */
	public function isOriginAllowed(string $origin): bool
	{
		$allowOrigin = $this->getAllowOrigin();
		if ($allowOrigin === self::WILDCARD_ORIGIN) {
			return true;
		}
		$origin = trim($origin);
		foreach (explode(',', $allowOrigin) as $allowed) {
			if ($allowed !== '' && strcasecmp(trim($allowed), $origin) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether the module acts on a request path. Every path applies when
	 * PathPrefix is `''`. Otherwise the path, without its leading slash, must
	 * equal the prefix or continue it at a `/` boundary.
	 * @param string $pathInfo The request `PathInfo`.
	 * @return bool Whether the path is within PathPrefix.
	 */
	public function appliesToPath(string $pathInfo): bool
	{
		$prefix = trim($this->getPathPrefix(), '/');
		if ($prefix === '') {
			return true;
		}
		$path = ltrim($pathInfo, '/');
		return $path === $prefix || str_starts_with($path, $prefix . '/');
	}

	// ── Direct Accessors (UAP-SE) ──────────────────────────────────────────────

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
	 * @return string Stored ExposeHeaders value.
	 */
	protected function getExposeHeadersDirect(): string
	{
		return $this->_exposeHeaders;
	}

	/**
	 * @param string $value ExposeHeaders stored value.
	 */
	protected function setExposeHeadersDirect(string $value): void
	{
		$this->_exposeHeaders = $value;
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
	 * @return string Stored PathPrefix value.
	 */
	protected function getPathPrefixDirect(): string
	{
		return $this->_pathPrefix;
	}

	/**
	 * @param string $value PathPrefix stored value.
	 */
	protected function setPathPrefixDirect(string $value): void
	{
		$this->_pathPrefix = $value;
	}

	// ── Properties ─────────────────────────────────────────────────────────────

	/**
	 * @return string `'*'` or the comma-separated allow-list of origins. Defaults to `'*'`.
	 */
	public function getAllowOrigin(): string
	{
		return $this->getAllowOriginDirect();
	}

	/**
	 * Sets the allowed origins: `'*'` for every origin, or a comma-separated
	 * allow-list of exact origins such as `https://app.example.com, https://admin.example.com`;
	 * no pattern matches a subdomain. `'*'` is incompatible with
	 * {@see setAllowCredentials AllowCredentials}. Do not list `null`, the origin of
	 * a sandboxed document, in an allow-list used with credentials.
	 * @param mixed $value `'*'` or the comma-separated allow-list.
	 */
	public function setAllowOrigin(mixed $value): void
	{
		$this->setAllowOriginDirect((string) TPropertyValue::ensureString($value));
	}

	/**
	 * @return string Comma-separated `Access-Control-Allow-Methods` value.
	 */
	public function getAllowMethods(): string
	{
		return $this->getAllowMethodsDirect();
	}

	/**
	 * @param mixed $value Comma-separated list of the HTTP methods a preflight allows.
	 */
	public function setAllowMethods(mixed $value): void
	{
		$this->setAllowMethodsDirect((string) TPropertyValue::ensureString($value));
	}

	/**
	 * @return string Comma-separated `Access-Control-Allow-Headers` value.
	 */
	public function getAllowHeaders(): string
	{
		return $this->getAllowHeadersDirect();
	}

	/**
	 * @param mixed $value Comma-separated list of the request headers a preflight allows;
	 *   `''` echoes the `Access-Control-Request-Headers` of the preflight.
	 */
	public function setAllowHeaders(mixed $value): void
	{
		$this->setAllowHeadersDirect((string) TPropertyValue::ensureString($value));
	}

	/**
	 * @return string Comma-separated `Access-Control-Expose-Headers` value. Defaults to `''`.
	 */
	public function getExposeHeaders(): string
	{
		return $this->getExposeHeadersDirect();
	}

	/**
	 * @param mixed $value Comma-separated list of the response headers scripts may read; `''` sends none.
	 */
	public function setExposeHeaders(mixed $value): void
	{
		$this->setExposeHeadersDirect((string) TPropertyValue::ensureString($value));
	}

	/**
	 * @return bool Whether `Access-Control-Allow-Credentials: true` is sent. Defaults to false.
	 */
	public function getAllowCredentials(): bool
	{
		return $this->getAllowCredentialsDirect();
	}

	/**
	 * Sets whether `Access-Control-Allow-Credentials: true` is sent. Requires an
	 * explicit {@see setAllowOrigin AllowOrigin}; `'*'` raises a configuration error.
	 * @param mixed $value Whether credentials are allowed.
	 */
	public function setAllowCredentials(mixed $value): void
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
	 * @param mixed $value Preflight cache duration in seconds.
	 */
	public function setMaxAge(mixed $value): void
	{
		$this->setMaxAgeDirect(TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return string The request path prefix the module acts on. Defaults to `''` (every path).
	 */
	public function getPathPrefix(): string
	{
		return $this->getPathPrefixDirect();
	}

	/**
	 * Sets the request path prefix the module acts on, such as `api`. Surrounding
	 * slashes are ignored; `''` acts on every path. The prefix is compared with the
	 * raw `PathInfo`, which the `Path` and `HiddenPath` URL formats carry.
	 * @param mixed $value The path prefix.
	 */
	public function setPathPrefix(mixed $value): void
	{
		$this->setPathPrefixDirect((string) TPropertyValue::ensureString($value));
	}
}
