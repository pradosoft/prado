<?php

/**
 * THttpHeaderName class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Web\HttpHeaders\TCspDirective;
use Prado\Web\HttpHeaders\THttpHeaderCsp;

/**
 * THttpHeaderName class.
 *
 * THttpHeaderName defines the enumerable type for common HTTP header names.
 * Each constant holds the canonical mixed-case header name string as it
 * appears in HTTP messages, so constants can be used directly with
 * {@see THttpHeader}:
 *
 * ```php
 * 'headers' => [
 *     ['properties' => ['HeaderName' => THttpHeaderName::StrictTransportSecurity,
 *                       'HeaderValue' => 'max-age=31536000; includeSubDomains']],
 *     ['properties' => ['HeaderName' => THttpHeaderName::XContentTypeOptions,
 *                       'HeaderValue' => 'nosniff']],
 *     ['class' => THttpHeaderCsp::class, 'policies' => [...]],
 * ]
 * ```
 *
 * Headers are grouped by function:
 * - **Request** — Common headers sent by the client.
 * - **Response** — Common headers sent by the server.
 * - **Content** — Type, encoding, length, language, and disposition.
 * - **Range** — Byte-serving: range requests, content ranges, and resume support.
 * - **Caching** — Freshness, validators, and cache-control negotiation.
 * - **Security** — CSP, HSTS, framing, CORS, and related protective headers.
 * - **CORS** — Cross-origin resource sharing negotiation headers.
 * - **WebDAV** — RFC 4918 extensions for distributed authoring and versioning.
 * - **Reporting** — Endpoints for browser-generated violation and error reports.
 * - **Deprecated** — Retained for backward-compatibility documentation only.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see THttpHeader
 * @since 4.4.0
 */
class THttpHeaderName extends \Prado\TEnumerable
{
	// =========================================================================
	// Common request headers
	// =========================================================================

	/**
	 * `Accept` — Informs the server about the content types the client can
	 * process.
	 *
	 * Examples:
	 * ```
	 * Accept: text/html, application/xhtml+xml, application/xml;q=0.9, text/*;q=0.8
	 * Accept: application/json
	 * ```
	 */
	public const Accept = 'Accept';

	/**
	 * `Accept-Encoding` — Advertises the content encodings (compressions) the
	 * client understands.
	 *
	 * Example:
	 * ```
	 * Accept-Encoding: gzip, deflate, br, zstd
	 * ```
	 */
	public const AcceptEncoding = 'Accept-Encoding';

	/**
	 * `Accept-Language` — Advertises the natural languages the client prefers,
	 * with optional quality weights.
	 *
	 * Example:
	 * ```
	 * Accept-Language: en-US, en;q=0.9, fr;q=0.8
	 * ```
	 */
	public const AcceptLanguage = 'Accept-Language';

	/**
	 * `Authorization` — Contains credentials for authenticating the client
	 * with the server.
	 *
	 * Examples:
	 * ```
	 * Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
	 * Authorization: Basic dXNlcjpwYXNzd29yZA==
	 * ```
	 */
	public const Authorization = 'Authorization';

	/**
	 * `Cookie` — Contains stored HTTP cookies sent by the client to the server.
	 *
	 * Example:
	 * ```
	 * Cookie: session=abc123; theme=dark
	 * ```
	 */
	public const Cookie = 'Cookie';

	/**
	 * `Host` — Specifies the host and port number of the server to which the
	 * request is directed. Required in HTTP/1.1.
	 *
	 * Example:
	 * ```
	 * Host: example.com
	 * ```
	 */
	public const Host = 'Host';

	/**
	 * `Origin` — Indicates the origin of the request. Sent by the browser in
	 * CORS and same-site POST requests.
	 *
	 * Example:
	 * ```
	 * Origin: https://example.com
	 * ```
	 */
	public const Origin = 'Origin';

	/**
	 * `Referer` — The URL of the page that linked to the requested resource.
	 * Note: the header name is a historical misspelling of "referrer".
	 * Controlled by the {@see ReferrerPolicy} response header.
	 *
	 * Example:
	 * ```
	 * Referer: https://example.com/page.html
	 * ```
	 */
	public const Referer = 'Referer';

	/**
	 * `User-Agent` — Identifies the client software making the request,
	 * including application name, version, operating system, and rendering engine.
	 *
	 * Example:
	 * ```
	 * User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36
	 * ```
	 */
	public const UserAgent = 'User-Agent';

	/**
	 * `X-Requested-With` — Commonly added by JavaScript libraries to identify
	 * XMLHttpRequest (Ajax) calls. The conventional value is `XMLHttpRequest`.
	 * Not sent by fetch-based requests unless set explicitly.
	 *
	 * Example:
	 * ```
	 * X-Requested-With: XMLHttpRequest
	 * ```
	 */
	public const XRequestedWith = 'X-Requested-With';

	/**
	 * `Forwarded` — RFC 7239 standardised replacement for the `X-Forwarded-*`
	 * family. Encodes client IP, proxy host, and protocol in a single header
	 * with a defined grammar.
	 *
	 * Example:
	 * ```
	 * Forwarded: for=192.0.2.60; proto=https; by=203.0.113.43; host=example.com
	 * ```
	 *
	 * @see XForwardedFor for the widely-deployed non-standard predecessor.
	 */
	public const Forwarded = 'Forwarded';

	/**
	 * `X-Forwarded-For` — De-facto standard appended by proxies and load
	 * balancers to carry the originating client IP address through the request
	 * chain. The rightmost IP is usually the most recently added by a trusted
	 * proxy.
	 *
	 * Example:
	 * ```
	 * X-Forwarded-For: 203.0.113.195, 198.51.100.17
	 * ```
	 *
	 * @see Forwarded for the RFC 7239 standardised replacement.
	 */
	public const XForwardedFor = 'X-Forwarded-For';

	/**
	 * `X-Forwarded-Proto` — Carries the original request scheme (`http` or
	 * `https`) when a reverse proxy terminates TLS and forwards the request
	 * over plain HTTP internally.
	 *
	 * Example:
	 * ```
	 * X-Forwarded-Proto: https
	 * ```
	 */
	public const XForwardedProto = 'X-Forwarded-Proto';

	/**
	 * `X-Forwarded-Host` — Carries the original `Host` header value when a
	 * reverse proxy rewrites the host during forwarding.
	 *
	 * Example:
	 * ```
	 * X-Forwarded-Host: example.com
	 * ```
	 */
	public const XForwardedHost = 'X-Forwarded-Host';

	/**
	 * `If-Match` — Makes the request conditional; the server processes it only
	 * if the resource's current ETag matches one of the listed values. Used to
	 * prevent mid-air collisions on PUT / DELETE and to make range requests
	 * conditional.
	 *
	 * Examples:
	 * ```
	 * If-Match: "33a64df5"
	 * If-Match: *
	 * ```
	 *
	 * @see IfNoneMatch for the inverse conditional.
	 * @see IfRange for combining a range request with an ETag condition.
	 */
	public const IfMatch = 'If-Match';

	/**
	 * `If-None-Match` — Makes the request conditional; the server sends the
	 * full response only if the ETag does not match.
	 *
	 * Examples:
	 * ```
	 * If-None-Match: "33a64df5"
	 * If-None-Match: *
	 * ```
	 */
	public const IfNoneMatch = 'If-None-Match';

	/**
	 * `If-Modified-Since` — Makes the request conditional; the server sends
	 * the full response only if the resource was modified after the given date.
	 *
	 * Example:
	 * ```
	 * If-Modified-Since: Wed, 21 Oct 2015 07:28:00 GMT
	 * ```
	 */
	public const IfModifiedSince = 'If-Modified-Since';

	/**
	 * `If-Unmodified-Since` — Makes the request conditional; the server
	 * processes it only if the resource has **not** been modified since the
	 * given date. Used with PUT to prevent overwriting concurrent changes.
	 *
	 * Example:
	 * ```
	 * If-Unmodified-Since: Wed, 21 Oct 2015 07:28:00 GMT
	 * ```
	 *
	 * @see IfModifiedSince for the inverse conditional.
	 */
	public const IfUnmodifiedSince = 'If-Unmodified-Since';

	/**
	 * `Proxy-Authorization` — Credentials for authenticating the client with
	 * an intermediate proxy. Analogous to {@see Authorization} but consumed by
	 * the proxy rather than the origin server.
	 *
	 * Example:
	 * ```
	 * Proxy-Authorization: Basic dXNlcjpwYXNzd29yZA==
	 * ```
	 */
	public const ProxyAuthorization = 'Proxy-Authorization';

	// =========================================================================
	// Common response headers
	// =========================================================================

	/**
	 * `Allow` — Lists the HTTP methods supported by the requested resource.
	 * Mandatory in a `405 Method Not Allowed` response.
	 *
	 * Example:
	 * ```
	 * Allow: GET, HEAD, POST, OPTIONS
	 * ```
	 */
	public const Allow = 'Allow';

	/**
	 * `Date` — The date and time at which the message was originated, in
	 * HTTP-date format (RFC 7231 §7.1.1.1). Required on all responses except
	 * those with `1xx` or `5xx` status codes when the clock is unreliable.
	 *
	 * Example:
	 * ```
	 * Date: Tue, 15 Nov 2024 08:12:31 GMT
	 * ```
	 */
	public const Date = 'Date';

	/**
	 * `Server` — Describes the software handling the request on the origin
	 * server. Commonly omitted or obscured in production to reduce information
	 * disclosure.
	 *
	 * Example:
	 * ```
	 * Server: Apache/2.4.54 (Unix)
	 * ```
	 */
	public const Server = 'Server';

	/**
	 * `Set-Cookie` — Sends a cookie from the server to the user agent. Multiple
	 * headers may be present. Supports `Secure`, `HttpOnly`, `SameSite`, `Path`,
	 * `Domain`, and `Expires`/`Max-Age` attributes.
	 *
	 * Examples:
	 * ```
	 * Set-Cookie: sessionId=abc123; Path=/; Secure; HttpOnly; SameSite=Strict
	 * Set-Cookie: theme=dark; Max-Age=604800; SameSite=Lax
	 * ```
	 */
	public const SetCookie = 'Set-Cookie';

	/**
	 * `Location` — Indicates the URL to redirect the client to. Used with
	 * 3xx redirect status codes and with `201 Created`.
	 *
	 * Examples:
	 * ```
	 * Location: /new-path
	 * Location: https://example.com/resource/123
	 * ```
	 */
	public const Location = 'Location';

	/**
	 * `Content-Location` — Indicates an alternate URL for the resource in the
	 * response body. Useful when content negotiation selects a specific variant
	 * and the client should use that URL for future requests.
	 *
	 * Example:
	 * ```
	 * Content-Location: /documents/foo.en.html
	 * ```
	 */
	public const ContentLocation = 'Content-Location';

	/**
	 * `WWW-Authenticate` — Defines the authentication method that should be
	 * used to access a resource. Sent with a `401 Unauthorized` response.
	 *
	 * Examples:
	 * ```
	 * WWW-Authenticate: Basic realm="My Site"
	 * WWW-Authenticate: Bearer realm="api", error="invalid_token"
	 * ```
	 */
	public const WWWAuthenticate = 'WWW-Authenticate';

	/**
	 * `Proxy-Authenticate` — Issued by a proxy to challenge the client for
	 * credentials. Accompanies a `407 Proxy Authentication Required` response.
	 *
	 * Example:
	 * ```
	 * Proxy-Authenticate: Basic realm="Proxy"
	 * ```
	 *
	 * @see ProxyAuthorization for the corresponding client request header.
	 */
	public const ProxyAuthenticate = 'Proxy-Authenticate';

	/**
	 * `Retry-After` — Indicates how long the client should wait before making
	 * a follow-up request. Sent with `429 Too Many Requests` or `503 Service
	 * Unavailable`.
	 *
	 * Examples:
	 * ```
	 * Retry-After: 120
	 * Retry-After: Fri, 31 Dec 2025 23:59:59 GMT
	 * ```
	 */
	public const RetryAfter = 'Retry-After';

	/**
	 * `Link` — Provides metadata links for the response, similar to the HTML
	 * `<link>` element. Used for preloading, pagination, API discovery, etc.
	 *
	 * Examples:
	 * ```
	 * Link: <https://example.com/style.css>; rel="preload"; as="style"
	 * Link: <https://example.com/page/2>; rel="next"
	 * ```
	 */
	public const Link = 'Link';

	/**
	 * `Transfer-Encoding` — Specifies the transfer encoding applied to the
	 * message body between nodes (hop-by-hop), not stored in caches.
	 *
	 * Example:
	 * ```
	 * Transfer-Encoding: chunked
	 * ```
	 */
	public const TransferEncoding = 'Transfer-Encoding';

	/**
	 * `Server-Timing` — Communicates one or more server-side performance
	 * metrics for the current request to the browser's Resource Timing API.
	 * Useful for surfacing cache hits, database query time, and application
	 * latency in browser DevTools.
	 *
	 * Example:
	 * ```
	 * Server-Timing: db;dur=53, app;dur=47.2
	 * Server-Timing: cache;desc="Cache Read";dur=23.2
	 * ```
	 */
	public const ServerTiming = 'Server-Timing';

	// =========================================================================
	// Content headers
	// =========================================================================

	/**
	 * `Content-Type` — Indicates the media type of the resource, optionally
	 * with a `charset` parameter for text types.
	 *
	 * Examples:
	 * ```
	 * Content-Type: text/html; charset=UTF-8
	 * Content-Type: application/json
	 * Content-Type: multipart/form-data; boundary=something
	 * ```
	 */
	public const ContentType = 'Content-Type';

	/**
	 * `Content-Encoding` — The encoding transformations applied to the
	 * response body (e.g. compression). Clients use this to decode the body.
	 *
	 * Examples:
	 * ```
	 * Content-Encoding: gzip
	 * Content-Encoding: br
	 * Content-Encoding: deflate, gzip
	 * ```
	 */
	public const ContentEncoding = 'Content-Encoding';

	/**
	 * `Content-Length` — The size of the response body in bytes.
	 *
	 * Example:
	 * ```
	 * Content-Length: 3495
	 * ```
	 */
	public const ContentLength = 'Content-Length';

	/**
	 * `Content-Language` — Describes the natural language(s) of the intended
	 * audience for the response.
	 *
	 * Examples:
	 * ```
	 * Content-Language: en-US
	 * Content-Language: en, fr
	 * ```
	 */
	public const ContentLanguage = 'Content-Language';

	/**
	 * `Content-Disposition` — Indicates whether a response should be displayed
	 * inline or downloaded as an attachment, and optionally provides a filename.
	 *
	 * Examples:
	 * ```
	 * Content-Disposition: inline
	 * Content-Disposition: attachment; filename="report.pdf"
	 * ```
	 */
	public const ContentDisposition = 'Content-Disposition';

	// =========================================================================
	// Range headers (byte-serving — RFC 7233)
	// =========================================================================

	/**
	 * `Accept-Ranges` — Advertises that the server supports byte-range
	 * requests for a resource. The value `bytes` enables range serving;
	 * `none` explicitly disables it.
	 *
	 * Examples:
	 * ```
	 * Accept-Ranges: bytes
	 * Accept-Ranges: none
	 * ```
	 *
	 * @see Range for the corresponding client request header.
	 * @see ContentRange for the partial-response description header.
	 */
	public const AcceptRanges = 'Accept-Ranges';

	/**
	 * `Range` — Requests that the server return only the specified byte
	 * range(s) of a resource. The server responds with `206 Partial Content`
	 * when the range is satisfiable; `416 Range Not Satisfiable` otherwise.
	 *
	 * Examples:
	 * ```
	 * Range: bytes=0-1023
	 * Range: bytes=512-
	 * Range: bytes=-500
	 * ```
	 *
	 * @see AcceptRanges for the server capability advertisement.
	 * @see ContentRange for the partial-response descriptor.
	 * @see IfRange for making a range request conditional.
	 */
	public const Range = 'Range';

	/**
	 * `Content-Range` — Sent with a `206 Partial Content` response to
	 * indicate which part of the full resource body is included. Also present
	 * in a `416 Range Not Satisfiable` response (without a body) to convey
	 * the actual resource size.
	 *
	 * Examples:
	 * ```
	 * Content-Range: bytes 0-1023/2048
	 * Content-Range: bytes *\/2048
	 * ```
	 *
	 * @see Range for the request header that triggers a partial response.
	 */
	public const ContentRange = 'Content-Range';

	/**
	 * `If-Range` — Makes a range request conditional. If the condition is met
	 * (ETag matches or the resource has not changed since the given date), the
	 * server returns the requested byte range; otherwise it returns the full
	 * resource body.
	 *
	 * Examples:
	 * ```
	 * If-Range: "33a64df5"
	 * If-Range: Wed, 21 Oct 2015 07:28:00 GMT
	 * ```
	 *
	 * @see Range for the range request header.
	 * @see IfMatch for an unconditional ETag check.
	 */
	public const IfRange = 'If-Range';

	// =========================================================================
	// Caching headers
	// =========================================================================

	/**
	 * `Cache-Control` — Directives for caching mechanisms in both requests and
	 * responses. Controls freshness, storage, and revalidation behavior.
	 *
	 * Examples:
	 * ```
	 * Cache-Control: no-store
	 * Cache-Control: no-cache
	 * Cache-Control: public, max-age=31536000, immutable
	 * Cache-Control: private, max-age=0, must-revalidate
	 * ```
	 */
	public const CacheControl = 'Cache-Control';

	/**
	 * `ETag` — Opaque identifier for a specific version of a resource.
	 * Used with `If-None-Match` to enable conditional requests and cache validation.
	 *
	 * Examples:
	 * ```
	 * ETag: "33a64df551425fcc55e4d42a148795d9f25f89d4"
	 * ETag: W/"0815"
	 * ```
	 */
	public const ETag = 'ETag';

	/**
	 * `Last-Modified` — The date and time at which the resource was last
	 * modified. Used with `If-Modified-Since` for conditional requests.
	 *
	 * Example:
	 * ```
	 * Last-Modified: Wed, 21 Oct 2015 07:28:00 GMT
	 * ```
	 */
	public const LastModified = 'Last-Modified';

	/**
	 * `Expires` — The date/time after which the response is considered stale.
	 * Overridden by `Cache-Control: max-age` when both are present.
	 *
	 * Example:
	 * ```
	 * Expires: Wed, 21 Oct 2025 07:28:00 GMT
	 * ```
	 */
	public const Expires = 'Expires';

	/**
	 * `Vary` — Indicates which request headers the server used to select the
	 * response, allowing caches to key responses correctly.
	 *
	 * Examples:
	 * ```
	 * Vary: Accept-Encoding
	 * Vary: Accept-Encoding, Accept-Language
	 * ```
	 */
	public const Vary = 'Vary';

	/**
	 * `Age` — The number of seconds the response has been in a proxy cache.
	 *
	 * Example:
	 * ```
	 * Age: 24
	 * ```
	 */
	public const Age = 'Age';

	// =========================================================================
	// Security headers
	// =========================================================================

	/**
	 * `Content-Security-Policy` — Declares an enforcing CSP policy. Violations
	 * block resource loading and, when a reporting directive is present, generate
	 * violation reports. Multiple instances of this header may be sent; the
	 * browser applies the **intersection** (most restrictive) of all policies.
	 *
	 * Example:
	 * ```
	 * Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-abc123'; object-src 'none'
	 * ```
	 *
	 * @see THttpHeaderCsp for the Prado class that builds this header.
	 * @see ContentSecurityPolicyReportOnly for the report-only variant.
	 */
	public const ContentSecurityPolicy = 'Content-Security-Policy';

	/**
	 * `Content-Security-Policy-Report-Only` — Declares a CSP policy in
	 * report-only mode: violations are reported but resources are **not**
	 * blocked. Useful for testing a new policy before enforcing it.
	 *
	 * Typically used together with {@see ContentSecurityPolicy}:
	 * ```
	 * Reporting-Endpoints: csp-endpoint="https://example.com/csp-reports"
	 * Content-Security-Policy-Report-Only: default-src https:; report-to csp-endpoint
	 * ```
	 *
	 * @see ContentSecurityPolicy for the enforcing variant.
	 * @see ReportingEndpoints for declaring named report endpoints.
	 */
	public const ContentSecurityPolicyReportOnly = 'Content-Security-Policy-Report-Only';

	/**
	 * `Strict-Transport-Security` — (HSTS) Tells browsers to access the site
	 * only over HTTPS for the specified duration. `includeSubDomains` extends
	 * the policy to all subdomains; `preload` opts into browser preload lists.
	 *
	 * Examples:
	 * ```
	 * Strict-Transport-Security: max-age=31536000
	 * Strict-Transport-Security: max-age=31536000; includeSubDomains
	 * Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
	 * ```
	 *
	 * @see TCspDirective::UpgradeInsecureRequests for the CSP complement that
	 *      upgrades sub-resource URLs within a single page load.
	 */
	public const StrictTransportSecurity = 'Strict-Transport-Security';

	/**
	 * `X-Frame-Options` — Controls whether the browser allows the page to be
	 * embedded in a frame or iframe. Values: `DENY`, `SAMEORIGIN`.
	 * Superseded by {@see TCspDirective::FrameAncestors} in browsers that
	 * support CSP; include both for full coverage.
	 *
	 * Examples:
	 * ```
	 * X-Frame-Options: DENY
	 * X-Frame-Options: SAMEORIGIN
	 * ```
	 *
	 * @see TCspDirective::FrameAncestors for the modern CSP replacement.
	 */
	public const XFrameOptions = 'X-Frame-Options';

	/**
	 * `X-Content-Type-Options` — Prevents browsers from MIME-sniffing a
	 * response away from the declared `Content-Type`. The only defined value
	 * is `nosniff`.
	 *
	 * Example:
	 * ```
	 * X-Content-Type-Options: nosniff
	 * ```
	 */
	public const XContentTypeOptions = 'X-Content-Type-Options';

	/**
	 * `Referrer-Policy` — Controls how much referrer information is included
	 * with requests. Common values: `no-referrer`, `no-referrer-when-downgrade`,
	 * `same-origin`, `strict-origin`, `strict-origin-when-cross-origin`.
	 *
	 * Examples:
	 * ```
	 * Referrer-Policy: no-referrer
	 * Referrer-Policy: strict-origin-when-cross-origin
	 * ```
	 */
	public const ReferrerPolicy = 'Referrer-Policy';

	/**
	 * `Permissions-Policy` — Allows or denies the use of browser feature APIs
	 * (camera, geolocation, microphone, payment, USB, etc.) in the current
	 * document and in embedded iframes. Replaces the deprecated `Feature-Policy`
	 * header.
	 *
	 * Complements {@see ContentSecurityPolicy}: CSP controls where resources
	 * may be loaded from; Permissions-Policy controls which browser capabilities
	 * may be used at all, regardless of origin.
	 *
	 * Examples:
	 * ```
	 * Permissions-Policy: camera=(), microphone=(), geolocation=()
	 * Permissions-Policy: geolocation=(self "https://maps.example.com")
	 * ```
	 */
	public const PermissionsPolicy = 'Permissions-Policy';

	/**
	 * `Cross-Origin-Embedder-Policy` — (COEP) Prevents a document from loading
	 * cross-origin resources that do not explicitly grant the document permission
	 * via CORS or CORP. Required alongside COOP to enable `SharedArrayBuffer` and
	 * high-resolution timers.
	 *
	 * Interacts with CSP fetch directives: COEP enforces that cross-origin
	 * resources opt in to being loaded (via CORS headers or
	 * {@see CrossOriginResourcePolicy}), while directives such as
	 * {@see TCspDirective::FrameSrc}, {@see TCspDirective::WorkerSrc}, and
	 * {@see TCspDirective::ConnectSrc} control which origins are permitted at all.
	 * Both layers must be satisfied for a resource to load.
	 *
	 * Examples:
	 * ```
	 * Cross-Origin-Embedder-Policy: require-corp
	 * Cross-Origin-Embedder-Policy: credentialless
	 * ```
	 *
	 * @see CrossOriginOpenerPolicy which must be set together to achieve cross-origin isolation.
	 * @see CrossOriginResourcePolicy for the per-resource opt-in that COEP requires.
	 */
	public const CrossOriginEmbedderPolicy = 'Cross-Origin-Embedder-Policy';

	/**
	 * `Cross-Origin-Opener-Policy` — (COOP) Ensures a top-level document does
	 * not share a browsing context group with cross-origin documents, preventing
	 * cross-origin window references and Spectre-style side-channel attacks.
	 * Required alongside COEP to enable `SharedArrayBuffer`.
	 *
	 * Works at the browsing-context level rather than the resource level; CSP
	 * {@see TCspDirective::FrameAncestors} and {@see TCspDirective::FrameSrc}
	 * control embedding relationships, while COOP controls window isolation.
	 *
	 * Examples:
	 * ```
	 * Cross-Origin-Opener-Policy: same-origin
	 * Cross-Origin-Opener-Policy: same-origin-allow-popups
	 * ```
	 *
	 * @see CrossOriginEmbedderPolicy which must be set together for cross-origin isolation.
	 */
	public const CrossOriginOpenerPolicy = 'Cross-Origin-Opener-Policy';

	/**
	 * `Cross-Origin-Resource-Policy` — (CORP) Instructs the browser to block
	 * cross-origin no-cors requests to this resource, preventing it from being
	 * read by another origin.
	 *
	 * Server-side complement to CSP fetch directives: where CSP tells the
	 * *loading* document which origins it may fetch from, CORP is set on the
	 * *resource itself* to declare who may read it. When COEP (`require-corp`) is
	 * active, every cross-origin resource must carry a CORP header or the load
	 * is blocked.
	 *
	 * Examples:
	 * ```
	 * Cross-Origin-Resource-Policy: same-origin
	 * Cross-Origin-Resource-Policy: same-site
	 * Cross-Origin-Resource-Policy: cross-origin
	 * ```
	 *
	 * @see CrossOriginEmbedderPolicy which requires resources to opt in via CORP.
	 */
	public const CrossOriginResourcePolicy = 'Cross-Origin-Resource-Policy';

	// =========================================================================
	// CORS headers
	// =========================================================================

	/**
	 * `Access-Control-Allow-Origin` — Indicates whether the response can be
	 * shared with requesting code from the given origin. Use `*` to allow all
	 * origins (credentials not supported), or specify an explicit origin.
	 *
	 * Examples:
	 * ```
	 * Access-Control-Allow-Origin: *
	 * Access-Control-Allow-Origin: https://example.com
	 * ```
	 */
	public const AccessControlAllowOrigin = 'Access-Control-Allow-Origin';

	/**
	 * `Access-Control-Allow-Methods` — Specifies the HTTP methods allowed
	 * when accessing the resource in response to a preflight request.
	 *
	 * Example:
	 * ```
	 * Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
	 * ```
	 */
	public const AccessControlAllowMethods = 'Access-Control-Allow-Methods';

	/**
	 * `Access-Control-Allow-Headers` — Indicates which HTTP headers can be
	 * used during the actual request, in response to a preflight.
	 *
	 * Example:
	 * ```
	 * Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
	 * ```
	 */
	public const AccessControlAllowHeaders = 'Access-Control-Allow-Headers';

	/**
	 * `Access-Control-Allow-Credentials` — Tells browsers whether to expose
	 * the response to JavaScript when credentials (cookies, HTTP auth) are
	 * included. Must be `true`; `Access-Control-Allow-Origin` must not be `*`.
	 *
	 * Example:
	 * ```
	 * Access-Control-Allow-Credentials: true
	 * ```
	 */
	public const AccessControlAllowCredentials = 'Access-Control-Allow-Credentials';

	/**
	 * `Access-Control-Expose-Headers` — Lists response headers that browsers
	 * are permitted to expose to JavaScript in a CORS response.
	 *
	 * Example:
	 * ```
	 * Access-Control-Expose-Headers: Content-Length, X-Request-Id
	 * ```
	 */
	public const AccessControlExposeHeaders = 'Access-Control-Expose-Headers';

	/**
	 * `Access-Control-Max-Age` — Indicates how long (in seconds) the results
	 * of a preflight request can be cached.
	 *
	 * Example:
	 * ```
	 * Access-Control-Max-Age: 86400
	 * ```
	 */
	public const AccessControlMaxAge = 'Access-Control-Max-Age';

	// =========================================================================
	// WebDAV headers (RFC 4918)
	// =========================================================================

	/**
	 * `DAV` — Advertises the WebDAV compliance class(es) supported by the
	 * server. Class `1` is the core WebDAV feature set; class `2` adds locking
	 * support; `3` adds advanced locking. Present in OPTIONS responses and in
	 * responses to any request when the server wishes to signal DAV capability.
	 *
	 * Example:
	 * ```
	 * DAV: 1, 2
	 * ```
	 */
	public const DAV = 'DAV';

	/**
	 * `Depth` — Specifies how deeply a WebDAV method (PROPFIND, COPY, DELETE,
	 * etc.) should be applied to a collection resource. Values: `0` (resource
	 * only), `1` (resource and immediate members), or `infinity` (entire tree).
	 *
	 * Examples:
	 * ```
	 * Depth: 0
	 * Depth: 1
	 * Depth: infinity
	 * ```
	 */
	public const Depth = 'Depth';

	/**
	 * `Destination` — Specifies the absolute destination URI for WebDAV COPY
	 * and MOVE operations.
	 *
	 * Example:
	 * ```
	 * Destination: https://example.com/collection/target.txt
	 * ```
	 */
	public const Destination = 'Destination';

	/**
	 * `If` — WebDAV conditional request header (RFC 4918 §10.4). Carries a
	 * list of state-token / ETag conditions that must all be satisfied for the
	 * server to process the request. Distinct from the standard HTTP conditional
	 * headers ({@see IfMatch}, {@see IfNoneMatch}) — used specifically for lock
	 * token submission and multi-resource conditions in WebDAV.
	 *
	 * Example:
	 * ```
	 * If: (<urn:uuid:181d4fae-7d8c-11d0-a765-00a0c91e6bf2> ["I am an ETag"])
	 * ```
	 */
	public const DavIf = 'If';

	/**
	 * `Lock-Token` — Identifies the lock associated with a WebDAV request.
	 * Returned by the server in a successful LOCK response, and must be
	 * submitted by the client in subsequent UNLOCK requests via the
	 * {@see DavIf} header.
	 *
	 * Example:
	 * ```
	 * Lock-Token: <urn:uuid:181d4fae-7d8c-11d0-a765-00a0c91e6bf2>
	 * ```
	 */
	public const LockToken = 'Lock-Token';

	/**
	 * `Overwrite` — Controls whether a WebDAV COPY or MOVE operation may
	 * overwrite an existing resource at the destination URI. `T` allows
	 * overwriting (default); `F` rejects the request with
	 * `412 Precondition Failed` when the destination already exists.
	 *
	 * Example:
	 * ```
	 * Overwrite: T
	 * Overwrite: F
	 * ```
	 */
	public const Overwrite = 'Overwrite';

	/**
	 * `Timeout` — Sent by the WebDAV client to express a desired lock duration,
	 * or by the server to indicate the actual lock timeout granted. Supports
	 * `Infinite` and `Second-<n>` values. Note: servers are not required to
	 * honour the client's requested timeout.
	 *
	 * Examples:
	 * ```
	 * Timeout: Infinite
	 * Timeout: Second-4100000000
	 * Timeout: Second-3600, Second-1800
	 * ```
	 */
	public const Timeout = 'Timeout';

	// =========================================================================
	// Connection upgrade headers (WebSocket — RFC 6455 / RFC 8441)
	// =========================================================================

	/**
	 * `Connection` — Lists hop-by-hop connection options. Carries `Upgrade`
	 * to request a protocol switch (the WebSocket handshake).
	 *
	 * Example:
	 * ```
	 * Connection: Upgrade
	 * ```
	 */
	public const Connection = 'Connection';

	/**
	 * `Upgrade` — Names the protocol the client wishes to switch to, paired
	 * with `Connection: Upgrade`.
	 *
	 * Example:
	 * ```
	 * Upgrade: websocket
	 * ```
	 */
	public const Upgrade = 'Upgrade';

	/**
	 * `Sec-WebSocket-Key` — The client's base64 nonce; the server hashes it
	 * into `Sec-WebSocket-Accept` to confirm the handshake.
	 *
	 * Example:
	 * ```
	 * Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==
	 * ```
	 */
	public const SecWebSocketKey = 'Sec-WebSocket-Key';

	/**
	 * `Sec-WebSocket-Accept` — The server's handshake confirmation, the
	 * base64 SHA-1 of the client `Sec-WebSocket-Key` plus the RFC 6455 GUID.
	 *
	 * Example:
	 * ```
	 * Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=
	 * ```
	 */
	public const SecWebSocketAccept = 'Sec-WebSocket-Accept';

	/**
	 * `Sec-WebSocket-Version` — The WebSocket protocol version offered by the
	 * client; 13 for RFC 6455.
	 *
	 * Example:
	 * ```
	 * Sec-WebSocket-Version: 13
	 * ```
	 */
	public const SecWebSocketVersion = 'Sec-WebSocket-Version';

	/**
	 * `Sec-WebSocket-Protocol` — The subprotocols the client offers, and the
	 * single one the server selects.
	 *
	 * Example:
	 * ```
	 * Sec-WebSocket-Protocol: chat, superchat
	 * ```
	 */
	public const SecWebSocketProtocol = 'Sec-WebSocket-Protocol';

	/**
	 * `Sec-WebSocket-Extensions` — The protocol extensions the client offers
	 * and the server accepts (e.g. per-message compression).
	 *
	 * Example:
	 * ```
	 * Sec-WebSocket-Extensions: permessage-deflate
	 * ```
	 */
	public const SecWebSocketExtensions = 'Sec-WebSocket-Extensions';

	// =========================================================================
	// Reporting headers
	// =========================================================================

	/**
	 * `Reporting-Endpoints` — Defines named HTTPS endpoints to which the
	 * browser should send reports (CSP violations, deprecation notices, network
	 * errors, etc.). Referenced by name from the `report-to` CSP directive and
	 * the `Report-To` header.
	 *
	 * Example:
	 * ```
	 * Reporting-Endpoints: csp-endpoint="https://example.com/csp-reports", default="https://example.com/reports"
	 * ```
	 *
	 * @see TCspDirective::ReportTo for referencing these endpoints in a CSP policy.
	 */
	public const ReportingEndpoints = 'Reporting-Endpoints';

	/**
	 * `Report-To` — Legacy JSON-structured header for defining reporting
	 * endpoint groups. Superseded by {@see ReportingEndpoints} but still
	 * needed for compatibility with older browsers.
	 *
	 * Example:
	 * ```
	 * Report-To: {"group":"csp-endpoint","max_age":10886400,"endpoints":[{"url":"https://example.com/csp-reports"}]}
	 * ```
	 *
	 * @see ReportingEndpoints for the modern replacement.
	 * @see TCspDirective::ReportTo for the CSP directive that references this group name.
	 */
	public const ReportTo = 'Report-To';

	/**
	 * `NEL` — Network Error Logging (RFC 8942). Instructs the browser to
	 * collect and report network-layer errors (DNS failures, TCP resets, TLS
	 * handshake errors) to an endpoint declared in {@see ReportingEndpoints}.
	 *
	 * Example:
	 * ```
	 * NEL: {"report_to":"default","max_age":86400,"include_subdomains":true}
	 * ```
	 *
	 * @see ReportingEndpoints for the endpoint declaration.
	 */
	public const NEL = 'NEL';

	// =========================================================================
	// Deprecated headers
	// =========================================================================

	/**
	 * `Accept-Charset` — *Deprecated.* Advertised the character encodings the
	 * client could handle for the response body. Modern browsers no longer send
	 * this header; UTF-8 is universally supported. Servers should always serve
	 * UTF-8 and declare it in {@see ContentType}.
	 *
	 * @deprecated No longer sent by modern browsers; serve UTF-8 universally.
	 */
	public const AcceptCharset = 'Accept-Charset';

	/**
	 * `Feature-Policy` — *Deprecated.* The draft predecessor to
	 * {@see PermissionsPolicy}. Shared the same intent but differed in syntax
	 * and was never finalised. Some older intermediaries may still log it;
	 * send {@see PermissionsPolicy} instead.
	 *
	 * @deprecated Use Permissions-Policy instead.
	 * @see PermissionsPolicy for the standardised replacement.
	 */
	public const FeaturePolicy = 'Feature-Policy';

	/**
	 * `X-XSS-Protection` — *Deprecated.* Enabled the reflective XSS filter in
	 * older browsers (IE, legacy Chrome/Safari). Modern browsers have removed
	 * this feature; use a strong {@see ContentSecurityPolicy} instead.
	 *
	 * Example:
	 * ```
	 * X-XSS-Protection: 0
	 * ```
	 *
	 * @deprecated Use Content-Security-Policy instead.
	 */
	public const XXSSProtection = 'X-XSS-Protection';

	/**
	 * `Pragma` — *Deprecated.* HTTP/1.0 cache-control directive. `Pragma: no-cache`
	 * is equivalent to `Cache-Control: no-cache`. Use {@see CacheControl} instead.
	 *
	 * Example:
	 * ```
	 * Pragma: no-cache
	 * ```
	 *
	 * @deprecated Use Cache-Control instead.
	 */
	public const Pragma = 'Pragma';
}
