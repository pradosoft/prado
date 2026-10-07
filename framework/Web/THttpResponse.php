<?php

/**
 * THttpResponse class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TExitException;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\TFileStream;
use Prado\IO\TOutputStream;
use Prado\IO\Util\TStreamHelper;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;
use Prado\Web\HttpHeaders\THttpHeaderRange;
use Prado\Web\HttpHeaders\THttpHeadersManager;
use Prado\Web\THttpHeaderName;

/**
 * THttpResponse class
 *
 * THttpResponse implements the mechanism for sending output to client users.
 *
 * To output a string to client, use {@see write()}. By default, the output is
 * buffered until {@see flush()} is called or the application ends. The output in
 * the buffer can also be cleaned by {@see clear()}. To disable output buffering,
 * set BufferOutput property to false.
 *
 * To send cookies to client, use {@see getCookies()}.
 * To redirect client browser to a new URL, use {@see redirect()}.
 * To send a file to client, use {@see writeFile()}.
 *
 * {@see writeFile()} serves a single byte range of the file to a `GET` request that
 * carries a `Range` header (RFC 9110 §14).  It sends `Accept-Ranges: bytes`, and a
 * `Last-Modified` header for a server file, so a client can resume a download or
 * seek in media.  {@see setAcceptRanges() AcceptRanges} turns range serving off.
 *
 * | Request | Response |
 * |---|---|
 * | no `Range`, an ignored `Range`, or an `If-Range` that does not match | `200` with the full file |
 * | a single satisfiable range | `206 Partial Content` with `Content-Range` and the range |
 * | a single range past the end of the file | `416 Range Not Satisfiable` with a `Content-Range` giving the file size, and no body |
 *
 * By default, THttpResponse is registered with {@see \Prado\TApplication} as the
 * response module. It can be accessed via {@see \Prado\TApplication::getResponse()}.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="response" class="Prado\Web\THttpResponse"
 *       CacheExpire="20" CacheControl="nocache" BufferOutput="true" />
 * </modules>
 * ```
 * where {@see getCacheExpire CacheExpire}, {@see getCacheControl CacheControl}
 * and {@see getBufferOutput BufferOutput} are optional properties of THttpResponse.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'response' => [
 *             'class' => 'Prado\Web\THttpResponse',
 *             'properties' => [
 *                 'CacheExpire' => '20',
 *                 'CacheControl' => 'nocache',
 *                 'BufferOutput' => 'true',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * THttpResponse sends charset header if either {@see setCharset() Charset}
 * or {@see \Prado\I18N\TGlobalization::setCharset() TGlobalization.Charset} is set.
 *
 * Since 3.1.2, HTTP status code can be set with the {@see setStatusCode StatusCode} property.
 *
 * Note: Some HTTP Status codes can require additional header or body information. So, if you use {@see setStatusCode StatusCode}
 * in your application, be sure to add theses informations.
 * E.g : to make an http authentication :
 * ```php
 *  public function clickAuth ($sender, $param)
 *  {
 *     $response=$this->getResponse();
 *     $response->setStatusCode(401);
 *     $response->appendHeader('WWW-Authenticate: Basic realm="Test"');
 *  }
 * ```
 *
 * This event handler will sent the 401 status code (Unauthorized) to the browser, with the WWW-Authenticate header field. This
 * will force the browser to ask for a username and a password.
 *
 * Behaviors extend the response through dynamic events.  {@see flushContent()}
 * raises `dyFlushContent` before any header is sent, so a behavior can transform the
 * buffered body and add the headers that describe it.  {@see writeFile()} raises
 * `dyWriteFile` with a handled flag before it sends any header.  A behavior that sends
 * the file itself, such as through `X-Sendfile`, returns true and writeFile() sends
 * nothing.  A behavior that only observes learns that the response declares its own
 * `Content-Length`.  {@see redirect()} filters its URL through
 * `dyRedirect`, so a behavior can restrict redirect targets for full-page and callback
 * redirects alike.  {@see addCookie()} and {@see removeCookie()} filter the cookie through
 * `dySetCookie`, so a behavior can apply one `Secure`, `HttpOnly`, and `SameSite` policy
 * to every `Set-Cookie` header.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method void dyFlushContent(bool $continueBuffering) Raised by {@see flushContent()} before any header is sent and before the buffer is flushed.
 * @method bool dyWriteFile(bool $handled, string $fileName, ?string $content, string $mimeType, ?array $headers, ?bool $forceDownload, string $clientFileName, int $fileSize) Raised by {@see writeFile()} before any header is sent; returns true when a behavior sent the file.
 * @method string dyRedirect(string $url) Filters the URL of {@see redirect()} before the adapter or {@see httpRedirect()} sends it.
 * @method THttpCookie dySetCookie(THttpCookie $cookie, bool $remove) Filters the cookie of {@see addCookie()} and {@see removeCookie()} before its `Set-Cookie` header is sent.
 */
class THttpResponse extends \Prado\TModule implements \Prado\IO\ITextWriter
{
	use TInitializedTrait;

	public const DEFAULT_CONTENTTYPE = TMediaType::HTML;
	public const DEFAULT_CHARSET = 'UTF-8';

	/**
	 * @var array<int, string> The HTTP status codes and their reason phrases,
	 *   per the IANA HTTP Status Code Registry (RFC 9110 and related).
	 * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 */
	private static $HTTP_STATUS_CODES = [
		100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
		200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used',
		300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
		400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 415 => 'Unsupported Media Type', 416 => 'Requested range not satisfiable', 417 => 'Expectation Failed', 421 => 'Misdirected Request', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Too Early', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required',
	];

	/**
	 * @var bool whether to buffer output
	 */
	private $_bufferOutput = true;
	/**
	 * @var THttpCookieCollection list of cookies to return
	 */
	private $_cookies;
	/**
	 * @var int response status code
	 */
	private $_status = 200;
	/**
	 * @var string reason correspond to status code
	 */
	private $_reason = 'OK';
	/**
	 * @var string HTML writer type
	 */
	private $_htmlWriterType = '\Prado\Web\UI\THtmlWriter';
	/**
	 * @var string content type
	 */
	private $_contentType;
	/**
	 * @var bool|string character set, e.g. UTF-8 or false if no character set should be send to client
	 */
	private $_charset = '';
	/**
	 * @var THttpResponseAdapter adapter.
	 */
	private $_adapter;
	/**
	 * @var bool whether http response header has been sent
	 */
	private $_httpHeaderSent;
	/**
	 * @var bool whether content-type header has been sent
	 */
	private $_contentTypeHeaderSent;
	/**
	 * @var THttpHeadersManager the headers manager module
	 */
	private $_headersManager;
	/**
	 * @var string the ID of the headers manager module
	 */
	private $_headersManagerID = '';
	/**
	 * @var bool whether {@see writeFile()} serves byte ranges
	 */
	private $_acceptRanges = true;

	/**
	 * Destructor.
	 * Flushes any existing content in buffer.
	 */
	public function __destruct()
	{
		//if($this->_bufferOutput)
		//	@ob_end_flush();
		parent::__destruct();
	}

	/**
	 * @param THttpResponseAdapter $adapter response adapter
	 */
	public function setAdapter(THttpResponseAdapter $adapter)
	{
		$this->_adapter = $adapter;
	}

	/**
	 * @return THttpResponseAdapter response adapter, null if not exist.
	 */
	public function getAdapter()
	{
		return $this->_adapter;
	}

	/**
	 * @return bool true if adapter exists, false otherwise.
	 */
	public function getHasAdapter()
	{
		return $this->_adapter !== null;
	}

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It starts output buffer if it is enabled.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		if ($this->_bufferOutput) {
			ob_start();
		}
		$this->setAppResponse();
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * Registers this module as the application response when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.4.0
	 */
	protected function setAppResponse()
	{
		$this->getApplication()?->setResponse($this);
	}

	/**
	 * @return int time-to-live for cached session pages in minutes, this has no effect for nocache limiter. Defaults to 180.
	 */
	public function getCacheExpire()
	{
		return $this->sessionCacheExpire();
	}

	/**
	 * @param int $value time-to-live for cached session pages in minutes, this has no effect for nocache limiter.
	 */
	public function setCacheExpire($value)
	{
		$this->sessionCacheExpire(TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return string cache control method to use for session pages
	 */
	public function getCacheControl()
	{
		return $this->sessionCacheLimiter();
	}

	/**
	 * @param string $value cache control method to use for session pages. Valid values
	 *               include none/nocache/private/private_no_expire/public
	 */
	public function setCacheControl($value)
	{
		$value = TPropertyValue::ensureEnum($value, ['none', 'nocache', 'private', 'private_no_expire', 'public']);
		$this->sessionCacheLimiter($value);
	}

	/**
	 * @param string $type content type, default is text/html
	 */
	public function setContentType($type)
	{
		if ($this->_contentTypeHeaderSent) {
			throw new \Exception('Unable to alter content-type as it has been already sent');
		}
		$this->_contentType = $type;
	}

	/**
	 * @return string current content type
	 */
	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * @return bool|string output charset.
	 */
	public function getCharset()
	{
		return $this->_charset;
	}

	/**
	 * @param bool|string $charset output charset.
	 */
	public function setCharset($charset)
	{
		$this->_charset = (strToLower($charset) === 'false') ? false : (string) $charset;
	}

	/**
	 * @return bool whether to enable output buffer
	 */
	public function getBufferOutput()
	{
		return $this->_bufferOutput;
	}

	/**
	 * @param bool $value whether to enable output buffer
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setBufferOutput($value)
	{
		$this->assertUninitialized('BufferOutput');
		$this->_bufferOutput = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool Whether {@see writeFile()} serves byte ranges, defaults to true.
	 * @since 4.4.0
	 */
	public function getAcceptRanges()
	{
		return $this->_acceptRanges;
	}

	/**
	 * Sets whether {@see writeFile()} serves byte ranges.  When false, writeFile() adds
	 * neither `Accept-Ranges` nor `Last-Modified` and ignores a `Range` request header.
	 * @param bool|string $value Whether to serve byte ranges.
	 * @since 4.4.0
	 */
	public function setAcceptRanges($value)
	{
		$this->_acceptRanges = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return int HTTP status code, defaults to 200
	 */
	public function getStatusCode()
	{
		return $this->_status;
	}

	/**
	 * Set the HTTP status code for the response.
	 * The code and its reason will be sent to client using the currently requested http protocol version (see {@see \Prado\Web\THttpRequest::getHttpProtocolVersion})
	 * Keep in mind that HTTP/1.0 clients might not understand all status codes from HTTP/1.1
	 *
	 * @param int $status HTTP status code
	 * @param null|string $reason HTTP status reason, defaults to standard HTTP reasons
	 */
	public function setStatusCode($status, $reason = null)
	{
		if ($this->_httpHeaderSent) {
			throw new \Exception('Unable to alter response as HTTP header already sent');
		}
		$status = TPropertyValue::ensureInteger($status);
		if (isset(self::$HTTP_STATUS_CODES[$status])) {
			$this->_reason = self::$HTTP_STATUS_CODES[$status];
		} else {
			if ($reason === null || $reason === '') {
				throw new TInvalidDataValueException("response_status_reason_missing");
			}
			$reason = TPropertyValue::ensureString($reason);
			if (strpos($reason, "\r") != false || strpos($reason, "\n") != false) {
				throw new TInvalidDataValueException("response_status_reason_barchars");
			}
			$this->_reason = $reason;
		}
		$this->_status = $status;
	}

	/**
	 * @return string HTTP status reason
	 */
	public function getStatusReason()
	{
		return $this->_reason;
	}

	/**
	 * @return THttpCookieCollection list of output cookies
	 */
	public function getCookies()
	{
		if ($this->_cookies === null) {
			$this->_cookies = new THttpCookieCollection($this);
		}
		return $this->_cookies;
	}

	/**
	 * Outputs a string.
	 * It may not be sent back to user immediately if output buffer is enabled.
	 * @param string $str string to be output
	 */
	public function write($str)
	{
		// when starting output make sure we send the headers first
		if (!$this->_bufferOutput && !$this->_httpHeaderSent) {
			$this->ensureHeadersSent();
		}
		echo $str;
	}

	/**
	 * Sends a file back to user.
	 * Make sure not to output anything else after calling this method.
	 *
	 * Before any header is sent, this raises `dyWriteFile` with a handled flag of false,
	 * followed by the parameters with the media type, client file name, and size resolved.
	 * When the chain returns true, a behavior has sent the file and this method sends
	 * nothing.  A behavior that sends the file passes true along the chain, so later
	 * behaviors still see the call; a behavior that only observes passes the flag on
	 * unchanged.  A file handed to the web server needs `$content` to be null.
	 *
	 * With {@see getAcceptRanges() AcceptRanges} on, this sends `Accept-Ranges: bytes`, and
	 * for a server file a `Last-Modified` header unless `$headers` has one.  A `Range` request
	 * header then selects the response, as {@see resolveRequestRange()} describes: `200` with
	 * the full file, `206` with a single range, or `416` with no body.  An `ETag` or
	 * `Last-Modified` in `$headers` is the validator an `If-Range` request header matches.
	 * @param string $fileName file name
	 * @param null|string $content content to be set. If null, the content will be read from the server file pointed to by $fileName.
	 * @param null|string $mimeType mime type of the content.
	 * @param null|array $headers list of headers to be sent. Each array element represents a header string (e.g. 'Content-Type: text/plain').
	 * @param null|bool $forceDownload force download of file, even if browser able to display inline. Defaults to 'true'.
	 * @param null|string $clientFileName force a specific file name on client side. Defaults to 'null' means auto-detect.
	 * @param null|int $fileSize size of file or content in bytes if already known. Defaults to 'null' means auto-detect.
	 * @throws TInvalidDataValueException if the file cannot be found
	 */
	public function writeFile($fileName, $content = null, $mimeType = null, $headers = null, $forceDownload = true, $clientFileName = null, $fileSize = null)
	{
		static $defaultMimeTypes = [
			'css' => TMediaType::CSS,
			'gif' => TMediaType::GIF,
			'png' => TMediaType::PNG,
			'jpg' => TMediaType::JPEG,
			'jpeg' => TMediaType::JPEG,
			'htm' => TMediaType::HTML,
			'html' => TMediaType::HTML,
			'js' => TMediaType::JAVASCRIPT,
			'pdf' => TMediaType::PDF,
			'xls' => TMediaType::XLS,
		];

		if ($mimeType === null) {
			$mimeType = TMediaType::PLAIN;
			if (function_exists('mime_content_type')) {
				$mimeType = mime_content_type($fileName);
			} elseif (($ext = strrchr($fileName, '.')) !== false) {
				$ext = substr($ext, 1);
				if (isset($defaultMimeTypes[$ext])) {
					$mimeType = $defaultMimeTypes[$ext];
				}
			}
		}

		if ($clientFileName === null) {
			$clientFileName = basename($fileName);
		} else {
			$clientFileName = basename($clientFileName);
		}

		if ($fileSize === null || $fileSize < 0) {
			$fileSize = ($content === null ? filesize($fileName) : strlen($content));
		}

		if ($this->dyWriteFile(false, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize) === true) {
			return;
		}

		$range = null;
		$lastModified = null;
		$sendLastModified = false;
		if ($this->getAcceptRanges()) {
			$lastModified = static::findHeaderValue($headers, THttpHeaderName::LastModified);
			if ($lastModified === null && $content === null) {
				$lastModified = $this->getFileLastModified($fileName);
				$sendLastModified = $lastModified !== null;
			}
			$range = $this->resolveRequestRange($fileSize, static::findHeaderValue($headers, THttpHeaderName::ETag), $lastModified);
			if ($range === false) {
				$this->setStatusCode(416);
			} elseif ($range !== null) {
				$this->setStatusCode(206);
			}
		}

		$this->sendHttpHeader();
		if (is_array($headers)) {
			foreach ($headers as $h) {
				$this->appendHeader($h);
			}
		} else {
			$this->appendHeader(THttpHeaderName::Pragma . ': public');
			$this->appendHeader(THttpHeaderName::Expires . ': 0');
			$this->appendHeader(THttpHeaderName::CacheControl . ': must-revalidate, post-check=0, pre-check=0');
			$this->appendHeader(THttpHeaderName::ContentType . ': ' . $mimeType);
			$this->_contentTypeHeaderSent = true;
		}

		if ($this->getAcceptRanges()) {
			$this->appendHeader(THttpHeaderName::AcceptRanges . ': ' . THttpHeaderRange::UNIT_BYTES);
		}
		if ($sendLastModified) {
			$this->appendHeader(THttpHeaderName::LastModified . ': ' . $lastModified);
		}

		if ($range === false) {
			$this->appendHeader(THttpHeaderName::ContentRange . ': ' . THttpHeaderRange::UNIT_BYTES . ' */' . $fileSize);
			$this->appendHeader(THttpHeaderName::ContentLength . ': 0');
			return;
		}

		$offset = 0;
		$length = $fileSize;
		if ($range !== null) {
			[$offset, $end] = $range;
			$length = $end - $offset + 1;
			$this->appendHeader(THttpHeaderName::ContentRange . ': ' . THttpHeaderRange::UNIT_BYTES . ' ' . $offset . '-' . $end . '/' . $fileSize);
		}

		$this->appendHeader(THttpHeaderName::ContentLength . ': ' . $length);
		$this->appendHeader(THttpHeaderName::ContentDisposition . ': ' . ($forceDownload ? 'attachment' : 'inline') . "; filename=\"$clientFileName\"");
		$this->appendHeader('Content-Transfer-Encoding: binary');
		if ($content === null) {
			if ($range === null) {
				$this->appendFile($fileName);
			} else {
				$this->appendFileRange($fileName, $offset, $length);
			}
		} else {
			echo $range === null ? $content : substr($content, $offset, $length);
		}
	}

	/**
	 * Resolves the `Range` request header of a `GET` request against the size being sent.
	 * The header is ignored, and the full representation is sent, when:
	 * - the status code is not 200 or the status line is already sent;
	 * - the request method is not `GET` (RFC 9110 §14.2);
	 * - an `If-Range` request header does not match the validator, per {@see matchesIfRange()};
	 * - the header is invalid, has a unit other than `bytes`, or has more than one range.
	 * @param int $size The size of the representation in bytes.
	 * @param ?string $etag The `ETag` sent with the representation, or null.
	 * @param ?string $lastModified The `Last-Modified` date sent with the representation, or null.
	 * @return null|array{0: int, 1: int}|false The inclusive `[start, end]` span, false when the
	 *   range is unsatisfiable, or null to send the full representation.
	 * @since 4.4.0
	 */
	protected function resolveRequestRange(int $size, ?string $etag, ?string $lastModified): array|false|null
	{
		if ($this->getStatusCode() !== 200 || $this->_httpHeaderSent) {
			return null;
		}
		$request = $this->getRequest();
		if ($request === null || strcasecmp((string) $request->getRequestType(), 'GET') !== 0) {
			return null;
		}
		$value = $request->getHeader(THttpHeaderName::Range);
		if ($value === null) {
			return null;
		}
		$ifRange = $request->getHeader(THttpHeaderName::IfRange);
		if ($ifRange !== null && !$this->matchesIfRange($ifRange, $etag, $lastModified)) {
			return null;
		}
		$range = new THttpHeaderRange();
		$range->setHeaderValue($value);
		return $range->resolve($size);
	}

	/**
	 * Tests an `If-Range` request header against the representation's validator (RFC 9110
	 * §13.1.5).  An entity tag matches a strong `ETag` that is the same string; a weak entity
	 * tag never matches.  A date matches a `Last-Modified` date naming the same second.
	 * @param string $ifRange The `If-Range` request header value.
	 * @param ?string $etag The `ETag` sent with the representation, or null.
	 * @param ?string $lastModified The `Last-Modified` date sent with the representation, or null.
	 * @return bool Whether the range applies.
	 * @since 4.4.0
	 */
	protected function matchesIfRange(string $ifRange, ?string $etag, ?string $lastModified): bool
	{
		$ifRange = trim($ifRange);
		if (str_starts_with($ifRange, '"') || strncasecmp($ifRange, 'W/', 2) === 0) {
			return $etag !== null && $ifRange[0] === '"' && $ifRange === $etag;
		}
		if ($lastModified === null) {
			return false;
		}
		$since = strtotime($ifRange);
		return $since !== false && $since === strtotime($lastModified);
	}

	/**
	 * Formats the modification time of a server file as an HTTP date.
	 * @param string $fileName The file name.
	 * @return ?string The date, such as `Tue, 06 Oct 2026 14:00:00 GMT`, or null when it is unavailable.
	 * @since 4.4.0
	 */
	protected function getFileLastModified(string $fileName): ?string
	{
		$time = @filemtime($fileName);
		return $time === false ? null : gmdate('D, d M Y H:i:s', $time) . ' GMT';
	}

	/**
	 * Finds the value of the first header line in a list that has the given name.
	 * @param ?array $headers The header lines, such as `ETag: "abc"`, or null.
	 * @param string $name The header name, matched without regard to case.
	 * @return ?string The trimmed value, or null when no line has the name.
	 * @since 4.4.0
	 */
	protected static function findHeaderValue(?array $headers, string $name): ?string
	{
		foreach ($headers ?? [] as $header) {
			$parts = explode(':', (string) $header, 2);
			if (count($parts) === 2 && strcasecmp(trim($parts[0]), $name) === 0) {
				return trim($parts[1]);
			}
		}
		return null;
	}

	/**
	 * Redirects the browser to the specified URL.
	 * The current application will be terminated after this method is invoked.
	 * The URL passes through the `dyRedirect` filter first, as given and before a relative
	 * URL gains the base URL.
	 * @param string $url URL to be redirected to. If the URL is a relative one, the base URL of
	 * the current request will be inserted at the beginning.
	 */
	public function redirect($url)
	{
		$url = $this->dyRedirect($url);
		if ($this->getHasAdapter()) {
			$this->_adapter->httpRedirect($url);
		} else {
			$this->httpRedirect($url);
		}
	}

	/**
	 * Redirect the browser to another URL and exists the current application.
	 * This method is used internally. Please use {@see redirect} instead.
	 *
	 * @since 3.1.5
	 * You can set the set {@see setStatusCode StatusCode} to a value between 300 and 399 before
	 * calling this function to change the type of redirection.
	 * If not specified, StatusCode will be 302 (Found) by default
	 *
	 * @param string $url URL to be redirected to. If the URL is a relative one, the base URL of
	 * the current request will be inserted at the beginning.
	 */
	public function httpRedirect($url)
	{
		$this->ensureHeadersSent();

		// Under IIS, explicitly send an HTTP response including the status code
		// this is handled automatically by PHP on Apache and others
		$isIIS = (stripos((string) $this->getRequest()->getServerSoftware(), "microsoft-iis") !== false);
		if ($url[0] === '/') {
			$url = $this->getRequest()->getBaseUrl() . $url;
		}
		if ($this->_status >= 300 && $this->_status < 400) {
			// The status code has been modified to a valid redirection status, send it
			if ($isIIS) {
				$this->appendHeader('HTTP/1.1 ' . $this->_status . ' ' . self::$HTTP_STATUS_CODES[
					array_key_exists($this->_status, self::$HTTP_STATUS_CODES)
						? $this->_status
						: 302
					]);
			}
			$this->appendHeader(THttpHeaderName::Location . ': ' . str_replace('&amp;', '&', $url), true, $this->_status);
		} else {
			if ($isIIS) {
				$this->appendHeader('HTTP/1.1 302 ' . self::$HTTP_STATUS_CODES[302]);
			}
			$this->appendHeader(THttpHeaderName::Location . ': ' . str_replace('&amp;', '&', $url));
		}

		if (!$this->getApplication()->getRequestCompleted()) {
			throw new TExitException();
		}

		exit();
	}

	/**
	 * Reloads the current page.
	 * The effect of this method call is the same as user pressing the
	 * refresh button on his browser (without post data).
	 **/
	public function reload()
	{
		$this->redirect($this->getRequest()->getRequestUri());
	}

	/**
	 * Flush the response contents and headers.
	 * @param bool $continueBuffering
	 */
	public function flush($continueBuffering = true)
	{
		if ($this->getHasAdapter()) {
			$this->_adapter->flushContent($continueBuffering);
		} else {
			$this->flushContent($continueBuffering);
		}
	}

	/**
	 * Ensures that HTTP response and content-type headers are sent
	 */
	public function ensureHeadersSent()
	{
		$this->ensureHttpHeaderSent();
		$this->ensureContentTypeHeaderSent();
		$headersManager = $this->getHeadersManagerModule();
		if ($headersManager !== null) {
			$headersManager->ensureHeadersSent();
		}
	}

	/**
	 * Outputs the buffered content, sends content-type and charset header.
	 * This method is used internally. Please use {@see flush} instead.
	 * Raises `dyFlushContent` before any header is sent.
	 * @param bool $continueBuffering whether to continue buffering after flush if buffering was active
	 */
	public function flushContent($continueBuffering = true)
	{
		Prado::trace("Flushing output", THttpResponse::class);
		$this->dyFlushContent($continueBuffering);
		$this->ensureHeadersSent();
		if ($this->_bufferOutput) {
			// avoid forced send of http headers (ob_flush() does that) if there's no output yet
			if (ob_get_length() > 0) {
				if (!$continueBuffering) {
					$this->_bufferOutput = false;
					ob_end_flush();
				} else {
					ob_flush();
				}
				flush();
			}
		} else {
			flush();
		}
	}

	/**
	 * Ensures that the HTTP header with the status code and status reason are sent
	 */
	protected function ensureHttpHeaderSent()
	{
		if (!$this->_httpHeaderSent) {
			$this->sendHttpHeader();
		}
	}

	/**
	 * Send the HTTP header with the status code (defaults to 200) and status reason (defaults to OK)
	 */
	protected function sendHttpHeader()
	{
		$protocol = $this->getRequest()->getHttpProtocolVersion();
		if ($this->getRequest()->getHttpProtocolVersion() === null) {
			$protocol = 'HTTP/1.1';
		}

		$this->appendHeader($protocol . ' ' . $this->_status . ' ' . $this->_reason, true, TPropertyValue::ensureInteger($this->_status));

		$this->_httpHeaderSent = true;
	}

	/**
	 * Ensures that the HTTP header with the status code and status reason are sent
	 */
	protected function ensureContentTypeHeaderSent()
	{
		if (!$this->_contentTypeHeaderSent) {
			$this->sendContentTypeHeader();
		}
	}

	/**
	 * Sends content type header with optional charset.
	 */
	protected function sendContentTypeHeader()
	{
		$contentType = $this->_contentType === null ? self::DEFAULT_CONTENTTYPE : $this->_contentType;
		$charset = $this->getCharset();
		if ($charset === false) {
			$this->appendHeader(THttpHeaderName::ContentType . ': ' . $contentType);
			return;
		}

		if ($charset === '' && ($globalization = $this->getApplication()->getGlobalization(false)) !== null) {
			$charset = $globalization->getCharset();
		}

		if ($charset === '') {
			$charset = self::DEFAULT_CHARSET;
		}
		$this->appendHeader(THttpHeaderName::ContentType . ': ' . $contentType . ';charset=' . $charset);

		$this->_contentTypeHeaderSent = true;
	}

	/**
	 * Returns the content in the output buffer.
	 * The buffer will NOT be cleared after calling this method.
	 * Use {@see clear()} is you want to clear the buffer.
	 * @return string output that is in the buffer.
	 */
	public function getContents()
	{
		Prado::trace("Retrieving output", THttpResponse::class);
		return $this->_bufferOutput ? ob_get_contents() : '';
	}

	/**
	 * Clears any existing buffered content.
	 */
	public function clear()
	{
		if ($this->_bufferOutput && ob_get_length() > 0) {
			ob_clean();
		}
		Prado::trace("Clearing output", THttpResponse::class);
	}

	/**
	 * @param null|int $case Either {@see CASE_UPPER} or {@see CASE_LOWER} or as is null (default)
	 * @return array
	 */
	public function getHeaders($case = null)
	{
		$result = [];
		$headers = headers_list();
		foreach ($headers as $header) {
			$tmp = explode(':', $header);
			$key = trim(array_shift($tmp));
			$value = trim(implode(':', $tmp));
			if (isset($result[$key])) {
				$result[$key] .= ', ' . $value;
			} else {
				$result[$key] = $value;
			}
		}

		if ($case !== null) {
			return array_change_key_case($result, $case);
		}

		return $result;
	}

	/**
	 * Sends a header.
	 * @param string $header header
	 * @param bool $replace whether the header should replace a previous similar header, or add a second header of the same type
	 * @param int $response_code the code to add to the header
	 */
	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		Prado::trace("Sending header '$header'", static::class);
		header($header, $replace, $response_code);
	}

	/**
	 * Reads a file and writes it to the output buffer.
	 * @param string $filename The filename being read.
	 * @param bool $use_include_path You can use the optional second parameter and set it to true, if you want to search for the file in the include_path, too.
	 * @param mixed $context A context stream resource.
	 * @return false|int Returns the number of bytes read from the file on success, or false on failure;
	 * @since 4.3.3
	 */
	public function appendFile(string $filename, bool $use_include_path = false, mixed $context = null): int|false
	{
		Prado::trace("Sending file '$filename'", static::class);
		return readfile($filename, $use_include_path, $context);
	}

	/**
	 * Writes a byte range of a file to the output buffer.  The bytes stream from the
	 * file to `php://output` in chunks through {@see TStreamHelper::copyRange()}, so
	 * a range larger than memory is sent without loading it.
	 * @param string $filename The filename being read.
	 * @param int $offset The byte offset of the range.
	 * @param int $length The number of bytes to write.
	 * @throws \Prado\Exceptions\TIOException When the file cannot be opened.
	 * @throws \RuntimeException When the file ends before the range does.
	 * @return int The number of bytes written.
	 * @since 4.4.0
	 */
	public function appendFileRange(string $filename, int $offset, int $length): int
	{
		Prado::trace("Sending $length bytes at $offset of file '$filename'", static::class);
		$source = new TFileStream($filename);
		$output = new TOutputStream();
		try {
			return TStreamHelper::copyRange($source, $offset, $length, $output);
		} finally {
			$source->close();
			$output->close();
		}
	}

	/**
	 * Writes a log message into error log.
	 * This method is simple wrapper of PHP function error_log.
	 * @param string $message The error message that should be logged
	 * @param int $messageType where the error should go
	 * @param string $destination The destination. Its meaning depends on the message parameter as described above
	 * @param string $extraHeaders The extra headers. It's used when the message parameter is set to 1. This message type uses the same internal function as mail() does.
	 * @see http://us2.php.net/manual/en/function.error-log.php
	 */
	public function appendLog($message, $messageType = 0, $destination = '', $extraHeaders = '')
	{
		error_log($message, $messageType, $destination, $extraHeaders);
	}

	/**
	 * Sends a cookie.
	 * Do not call this method directly. Operate with the result of {@see getCookies} instead.
	 * The cookie passes through the `dySetCookie` filter before its value is hashed.
	 * @param THttpCookie $cookie cook to be sent
	 */
	public function addCookie($cookie)
	{
		$cookie = $this->dySetCookie($cookie, false);
		$request = $this->getRequest();
		if ($request->getEnableCookieValidation()) {
			$value = $this->getApplication()->getSecurityManager()->hashData($cookie->getValue());
		} else {
			$value = $cookie->getValue();
		}

		$this->responseSetCookie(
			$cookie->getName(),
			$value,
			$cookie->getPhpOptions()
		);
	}

	/**
	 * Deletes a cookie.
	 * Do not call this method directly. Operate with the result of {@see getCookies} instead.
	 * The cookie passes through the `dySetCookie` filter first, so the deletion carries the
	 * same `Path`, `Domain`, and `Secure` policy as the cookie it deletes.
	 * @param THttpCookie $cookie cook to be deleted
	 */
	public function removeCookie($cookie)
	{
		$cookie = $this->dySetCookie($cookie, true);
		$options = $cookie->getPhpOptions();
		$options['expires'] = 0;
		$this->responseSetCookie(
			$cookie->getName(),
			null,
			$options
		);
	}

	/**
	 * @return string the type of HTML writer to be used, defaults to THtmlWriter
	 */
	public function getHtmlWriterType()
	{
		return $this->_htmlWriterType;
	}

	/**
	 * @param string $value the type of HTML writer to be used, may be the class name or the namespace
	 */
	public function setHtmlWriterType($value)
	{
		$this->_htmlWriterType = $value;
	}

	/**
	 * Creates a new instance of HTML writer.
	 * If the type of the HTML writer is not supplied, {@see getHtmlWriterType HtmlWriterType} will be assumed.
	 * @param string $type type of the HTML writer to be created. If null, {@see getHtmlWriterType HtmlWriterType} will be assumed.
	 */
	public function createHtmlWriter($type = null)
	{
		if ($type === null) {
			$type = $this->getHtmlWriterType();
		}
		if ($this->getHasAdapter()) {
			return $this->_adapter->createNewHtmlWriter($type, $this);
		} else {
			return $this->createNewHtmlWriter($type, $this);
		}
	}

	/**
	 * Create a new html writer instance.
	 * This method is used internally. Please use {@see createHtmlWriter} instead.
	 * @param string $type type of HTML writer to be created.
	 * @param \Prado\IO\ITextWriter $writer text writer holding the contents.
	 */
	public function createNewHtmlWriter($type, $writer)
	{
		return Prado::createComponent($type, $writer);
	}

	//	----- session_* abstractions

	/**
	 * This keeps `setcookie` isolated, and subject to children overrides.
	 * @param string $name
	 * @param mixed ...$args
	 * @return bool Returns true on success or false on failure.
	 * @since 4.3.3
	 * @see https://www.php.net/manual/en/function.setcookie.php
	 */
	protected function responseSetCookie(
		string $name,
		...$args
	): bool {
		return setcookie($name, ...$args);
	}

	/**
	 * Wrapper for `session_cache_expire`, and returns the name of the current cache expiration.
	 * @param ?int $value length of time until being removed from the cache.
	 * @return false|int Returns the current setting of session.cache_expire. The value returned should be read in minutes, defaults to 180. On failure to change the value, false is returned
	 * @since 4.3.3
	 * @see https://www.php.net/manual/en/function.session-cache-expire.php
	 */
	protected function sessionCacheExpire(?int $value = null): int|false
	{
		return session_cache_expire($value);
	}

	/**
	 * Wrapper for `session_cache_limiter`, and returns the name of the current cache limiter.
	 * @param ?string $value
	 * @return false|string the type of HTML writer to be used, defaults to THtmlWriter
	 * @since 4.3.3
	 * @see https://www.php.net/manual/en/function.session-cache-limiter.php
	 */
	protected function sessionCacheLimiter(?string $value = null): string|false
	{
		return session_cache_limiter($value);
	}

	/**
	 * @return string the ID of the URL manager module
	 */
	public function getHeadersManager()
	{
		return $this->_headersManagerID;
	}

	/**
	 * Sets the headers manager module.
	 * By default no header manager module is used
	 * You may specify a different module for headers managing tasks
	 * by loading it as an application module and setting this property
	 * with the module ID.
	 * @param string $value the ID of the URL manager module
	 */
	public function setHeadersManager($value)
	{
		$this->_headersManagerID = $value;
	}

	/**
	 * @return null|THttpHeadersManager the URL manager module
	 */
	public function getHeadersManagerModule()
	{
		$headersManagerId = $this->getHeadersManager();
		if ($headersManagerId === '') {
			return null;
		}

		$headersManager = $this->getApplication()->getModule($headersManagerId);
		if ($headersManager === null) {
			throw new TConfigurationException('httpresponse_headersmanager_inexist', $headersManagerId);
		}
		if (!($headersManager instanceof THttpHeadersManager)) {
			throw new TConfigurationException('httpresponse_headersmanager_invalid', $headersManagerId);
		}
		$this->_headersManager = $headersManager;

		return $this->_headersManager;
	}
}
