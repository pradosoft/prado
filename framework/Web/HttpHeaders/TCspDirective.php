<?php

/**
 * TCspDirective class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\TEnumerable;

/**
 * TCspDirective class
 *
 * TCspDirective defines the enumerable type for all
 * Content-Security-Policy directive names. Each constant holds the exact
 * kebab-case string that appears in the HTTP header value, so constants can
 * be used directly as keys when building a {@see THttpHeaderCsp} policy map:
 *
 * ```php
 * 'policies' => [
 *     ['name' => TCspDirective::DefaultSrc,  'value' => "'self'"],
 *     ['name' => TCspDirective::ScriptSrc,   'value' => "'self' NONCE"],
 *     ['name' => TCspDirective::ReportTo,    'value' => 'csp-endpoint'],
 * ]
 * ```
 *
 * **Report-Only mode.** All directives listed here are also valid inside a
 * `Content-Security-Policy-Report-Only` header, which reports violations without
 * blocking resources. Exception: {@see Sandbox} is silently ignored in report-only
 * mode because sandboxing cannot be enforced without blocking.
 *
 * **Complementary headers.** Several HTTP headers work alongside CSP to form a
 * complete defense-in-depth posture:
 * - {@see THttpHeaderName::ContentSecurityPolicyReportOnly} — report-only variant
 *   of CSP; uses the same directive syntax.
 * - {@see THttpHeaderName::PermissionsPolicy} — restricts browser feature APIs
 *   (camera, geolocation, etc.) that CSP does not cover.
 * - {@see THttpHeaderName::CrossOriginEmbedderPolicy} — (COEP) required with COOP
 *   for cross-origin isolation; interacts with fetch directives.
 * - {@see THttpHeaderName::CrossOriginOpenerPolicy} — (COOP) required with COEP
 *   for cross-origin isolation.
 * - {@see THttpHeaderName::CrossOriginResourcePolicy} — (CORP) controls cross-origin
 *   reads of your resources; server-side complement to CSP fetch directives.
 * - {@see THttpHeaderName::ReportingEndpoints} — declares named endpoints referenced
 *   by {@see ReportTo}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see THttpHeaderCsp
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy
 * @since 4.4.0
 */
class TCspDirective extends TEnumerable
{
	// Fetch directives — control where resource types may be loaded from.
	// Fallback chain: DefaultSrc <- all; ScriptSrc <- ScriptSrcElem, ScriptSrcAttr;
	//                 StyleSrc <- StyleSrcElem, StyleSrcAttr; ChildSrc <- FrameSrc, WorkerSrc.

	/** Fallback for all other fetch directives. Example: {@code default-src 'self' cdn.example.com} */
	public const DefaultSrc = 'default-src';

	/** Web workers and nested browsing contexts (frame/iframe). Fallback for FrameSrc and WorkerSrc. Example: {@code child-src 'self' https://sandbox.example.com} */
	public const ChildSrc = 'child-src';

	/** URLs loaded via script interfaces (fetch, XHR, WebSocket, EventSource, sendBeacon, WebRTC). Example: {@code connect-src 'self' api.example.com wss://realtime.example.com} */
	public const ConnectSrc = 'connect-src';

	/** fencedframe elements (Privacy Sandbox APIs). Falls back to FrameSrc then DefaultSrc. Example: {@code fenced-frame-src https://privacy-sandbox.example.com} */
	public const FencedFrameSrc = 'fenced-frame-src';

	/** Fonts loaded via @font-face. Example: {@code font-src 'self' fonts.gstatic.com} */
	public const FontSrc = 'font-src';

	/** Nested browsing contexts in frame and iframe. Falls back to ChildSrc then DefaultSrc. Example: {@code frame-src 'self' https://widget.partner.com} */
	public const FrameSrc = 'frame-src';

	/** Images and favicons including CSS background-image. Examples: {@code img-src 'self' data: cdn.example.com} or {@code img-src *} */
	public const ImgSrc = 'img-src';

	/** Application manifest files referenced by link rel=manifest. Example: {@code manifest-src 'self'} */
	public const ManifestSrc = 'manifest-src';

	/** Media loaded via audio, video, and track elements. Example: {@code media-src 'self' media.example.com} */
	public const MediaSrc = 'media-src';

	/** object and embed elements. Strongly recommend 'none'. Example: {@code object-src 'none'} */
	public const ObjectSrc = 'object-src';

	/** JavaScript and WebAssembly. Fallback for ScriptSrcElem and ScriptSrcAttr. Examples: {@code script-src 'self' 'nonce-416d1177-4d12-4e3b-b7c9-f6c409789fb8'} or {@code script-src 'self' 'sha256-cd9827ad...'} or {@code script-src 'self' 'strict-dynamic' 'nonce-abc123'} */
	public const ScriptSrc = 'script-src';

	/** script elements only (inline and src=). Falls back to ScriptSrc then DefaultSrc. Example: {@code script-src-elem 'self' 'nonce-416d1177-4d12-4e3b-b7c9-f6c409789fb8'} */
	public const ScriptSrcElem = 'script-src-elem';

	/** Inline event handler attributes (onclick, onload, etc.). Falls back to ScriptSrc then DefaultSrc. Examples: {@code script-src-attr 'none'} or {@code script-src-attr 'unsafe-hashes' 'sha256-cd9827ad...'} */
	public const ScriptSrcAttr = 'script-src-attr';

	/** Stylesheets. Fallback for StyleSrcElem and StyleSrcAttr. Example: {@code style-src 'self' 'nonce-416d1177-4d12-4e3b-b7c9-f6c409789fb8' fonts.googleapis.com} */
	public const StyleSrc = 'style-src';

	/** style elements and link rel=stylesheet. Falls back to StyleSrc then DefaultSrc. Example: {@code style-src-elem 'self' fonts.googleapis.com} */
	public const StyleSrcElem = 'style-src-elem';

	/** Inline style= attributes on DOM elements. Falls back to StyleSrc then DefaultSrc. Examples: {@code style-src-attr 'none'} or {@code style-src-attr 'unsafe-hashes' 'sha256-cd9827ad...'} */
	public const StyleSrcAttr = 'style-src-attr';

	/** Worker, SharedWorker, ServiceWorker scripts. Falls back to ChildSrc then ScriptSrc then DefaultSrc. Example: {@code worker-src 'self' blob:} */
	public const WorkerSrc = 'worker-src';

	// Document directives — govern properties of the document or worker environment.

	/** Restricts URLs in a document's base element, preventing base-tag injection. Does not fall back to DefaultSrc. Example: {@code base-uri 'self'} */
	public const BaseUri = 'base-uri';

	/**
	 * Sandboxes the page similarly to an `<iframe sandbox>` attribute. Unlike other
	 * fetch directives, the value is a space-separated list of **permission tokens**,
	 * not a source list. Omitting all tokens applies the most restrictive sandbox.
	 * Re-enable individual capabilities with `allow-*` tokens:
	 * `allow-forms`, `allow-modals`, `allow-orientation-lock`, `allow-pointer-lock`,
	 * `allow-popups`, `allow-popups-to-escape-sandbox`, `allow-presentation`,
	 * `allow-same-origin`, `allow-scripts`, `allow-top-navigation`, etc.
	 *
	 * **Does not fall back to DefaultSrc.**
	 *
	 * **Note:** This directive is silently ignored when delivered via
	 * `Content-Security-Policy-Report-Only` because sandboxing cannot be
	 * reported without being enforced.
	 *
	 * Examples:
	 * ```
	 * sandbox
	 * sandbox allow-scripts allow-same-origin
	 * sandbox allow-forms allow-popups
	 * ```
	 */
	public const Sandbox = 'sandbox';

	// Navigation directives — govern where a user can navigate or submit a form.

	/** Restricts form submission target URLs. Does not fall back to DefaultSrc. Example: {@code form-action 'self' https://payments.example.com} */
	public const FormAction = 'form-action';

	/**
	 * Specifies valid parents that may embed this page using frame, iframe, object, or embed.
	 * Supersedes the X-Frame-Options response header in browsers that support CSP.
	 * Cannot be delivered via a meta http-equiv tag.
	 * Does not fall back to DefaultSrc.
	 *
	 * Examples:
	 * ```
	 * frame-ancestors 'none'
	 * frame-ancestors 'self'
	 * frame-ancestors https://parent.example.com
	 * ```
	 *
	 * @see THttpHeaderName::XFrameOptions for the legacy header this supersedes.
	 */
	public const FrameAncestors = 'frame-ancestors';

	// Reporting directives — control violation report destinations.

	/**
	 * Provides a token identifying the reporting endpoint group to send CSP violation
	 * information to. The endpoint is declared in the Reporting-Endpoints response header
	 * (modern) or the legacy Report-To header. Intended to replace report-uri; specify
	 * both for compatibility with older browsers:
	 *
	 * ```
	 * Reporting-Endpoints: csp-endpoint="https://example.com/csp-reports"
	 * Content-Security-Policy: ...; report-uri https://example.com/csp-reports; report-to csp-endpoint
	 * ```
	 *
	 * Browsers that support report-to ignore report-uri when both are present.
	 *
	 * @see THttpHeaderName::ReportingEndpoints for declaring named endpoints.
	 * @see THttpHeaderName::ReportTo for the legacy Report-To header.
	 * @see ReportUri for the deprecated predecessor directive.
	 */
	public const ReportTo = 'report-to';

	// Other directives — Trusted Types, upgrade, mixed-content.

	/**
	 * Enforces Trusted Types at DOM XSS injection sinks (innerHTML, document.write,
	 * eval, etc.). The only currently defined token is `'script'`.
	 *
	 * **Must be used together with {@see TrustedTypes}.** This directive activates
	 * enforcement at the sink; `trusted-types` defines which policy names may be
	 * created via `trustedTypes.createPolicy()`. Neither directive has any effect
	 * without the other.
	 *
	 * Example:
	 * ```
	 * require-trusted-types-for 'script'
	 * trusted-types myPolicy sanitizer
	 * ```
	 *
	 * @see TrustedTypes for defining the allowed Trusted Types policy names.
	 */
	public const RequireTrustedTypesFor = 'require-trusted-types-for';

	/**
	 * Specifies an allowlist of Trusted Types policy names that may be created via
	 * `trustedTypes.createPolicy()`. Use `'none'` to disallow all policy creation,
	 * or `*` to allow any name.
	 *
	 * **Must be used together with {@see RequireTrustedTypesFor}.** Without that
	 * directive, `trusted-types` alone does not enforce anything.
	 *
	 * Examples:
	 * ```
	 * trusted-types myPolicy sanitizer
	 * trusted-types 'none'
	 * trusted-types *
	 * ```
	 *
	 * @see RequireTrustedTypesFor which activates enforcement at DOM XSS sinks.
	 */
	public const TrustedTypes = 'trusted-types';

	/**
	 * Instructs user agents to treat all of a site's insecure (HTTP) URLs as HTTPS.
	 * Intended for sites with large numbers of legacy HTTP links. No value required.
	 * Interacts with Strict-Transport-Security: HSTS enforces HTTPS at the transport
	 * layer for all requests; this directive upgrades sub-resource URLs within the page.
	 * Example: {@code upgrade-insecure-requests}
	 *
	 * @see THttpHeaderName::StrictTransportSecurity for the complementary HSTS header.
	 */
	public const UpgradeInsecureRequests = 'upgrade-insecure-requests';

	// Deprecated directives

	/**
	 * Deprecated. Prevented loading HTTP assets when page is served over HTTPS.
	 * Modern browsers block mixed content by default.
	 * Example: {@code block-all-mixed-content}
	 *
	 * @deprecated Browsers block mixed content by default. Use UpgradeInsecureRequests instead.
	 */
	public const BlockAllMixedContent = 'block-all-mixed-content';

	/**
	 * Deprecated. Specified valid sources to be prefetched or prerendered.
	 * No longer part of the active CSP specification.
	 * Falls back to DefaultSrc.
	 * Example: {@code prefetch-src 'self'}
	 *
	 * @deprecated Removed from the CSP specification.
	 */
	public const PrefetchSrc = 'prefetch-src';

	/**
	 * Deprecated. Provides a URL where the browser sends CSP violation reports
	 * as application/csp-report JSON. Superseded by ReportTo. Still specified
	 * alongside report-to for older browser compatibility:
	 * {@code Content-Security-Policy: ...; report-uri https://endpoint.example.com; report-to endpoint_name}
	 * Ignored by browsers that support report-to when both are present.
	 *
	 * @deprecated Use ReportTo with the Reporting-Endpoints header instead.
	 */
	public const ReportUri = 'report-uri';
}
