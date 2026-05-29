<?php

/**
 * THttpUtility class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Prado;

/**
 * THttpUtility class
 *
 * A collection of static HTTP helper methods used across the framework:
 *
 * - {@see htmlEncode()} / {@see htmlDecode()} / {@see htmlStrip()} — lightweight
 *   translation of `<`, `>`, and `"` to and from HTML entities (intentionally
 *   leaves `&` untouched, unlike {@see htmlspecialchars()}).
 * - {@see buildHtmlAttributes()} — renders an associative array to a
 *   space-separated HTML attribute string; handles boolean attributes, null/false
 *   omission, and optional raw (pre-encoded) values.
 * - {@see isLocalUrl()} — determines whether a URL resolves to the current server,
 *   with optional subdomain matching.
 * - {@see normalizeIntegrityUrl()} — canonicalizes a URL for use as a Subresource
 *   Integrity (SRI) registry key (scheme + host lowercase, default ports stripped,
 *   fragments removed).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THttpUtility
{
	private static $_encodeTable = ['<' => '&lt;', '>' => '&gt;', '"' => '&quot;'];
	private static $_decodeTable = ['&lt;' => '<', '&gt;' => '>', '&quot;' => '"'];
	private static $_stripTable = ['&lt;' => '', '&gt;' => '', '&quot;' => ''];

	/**
	 * Translates `<`, `>`, and `"` to their HTML entities (`&lt;`, `&gt;`, `&quot;`).
	 * Unlike {@see htmlspecialchars()}, `&` is left untouched.
	 * @param string $s string to encode.
	 * @return string the encoded string.
	 */
	public static function htmlEncode($s)
	{
		return strtr($s, self::$_encodeTable);
	}

	/**
	 * Reverses {@see htmlEncode()}: translates `&lt;`, `&gt;`, and `&quot;` back
	 * to `<`, `>`, and `"`. `&amp;` and other entities are left untouched.
	 * @param string $s string to decode.
	 * @return string the decoded string.
	 */
	public static function htmlDecode($s)
	{
		return strtr($s, self::$_decodeTable);
	}

	/**
	 * Removes the HTML entities `&lt;`, `&gt;`, and `&quot;` from a string,
	 * replacing each with an empty string. Literal `<`, `>`, and `"` characters
	 * and all other entities (e.g. `&amp;`) are left untouched.
	 * @param string $s string to strip.
	 * @return string the stripped string.
	 */
	public static function htmlStrip($s)
	{
		return strtr($s, self::$_stripTable);
	}

	/**
	 * Renders an HTML attribute string from an associative array.
	 *
	 * Values are handled as follows: `null`/`false` omits the attribute; `true`
	 * renders a boolean attribute (name only, e.g. `disabled`); any other scalar
	 * is cast to string and HTML-encoded via {@see htmlspecialchars()}. Prefix a
	 * name with `!` to write its value without encoding (the `!` is stripped),
	 * useful for already-trusted values.
	 *
	 * The returned string has **no leading space**. Callers are responsible for
	 * inserting a separator between the tag name and the attributes:
	 * ```php
	 * $attrs = THttpUtility::buildHtmlAttributes(['type' => 'text', 'disabled' => true]);
	 * '<input' . ($attrs !== '' ? ' ' . $attrs : '') . '>'
	 * // → '<input type="text" disabled>'
	 * ```
	 *
	 * @param array $attributes Attribute names to values; prefix a name with `!`
	 *   to skip HTML encoding of its value.
	 * @return string Space-separated attribute string without a leading space,
	 *   or an empty string when every value is `null`/`false`.
	 * @since 4.4.0
	 */
	public static function buildHtmlAttributes(array $attributes): string
	{
		$parts = [];
		foreach ($attributes as $name => $value) {
			if ($value === null || $value === false) {
				continue;
			}
			$raw = $name[0] === '!';
			if ($raw) {
				$name = substr($name, 1);
			}
			if ($value === true) {
				$parts[] = $name;
			} elseif ($raw) {
				$parts[] = $name . '="' . $value . '"';
			} else {
				$parts[] = $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
			}
		}
		return implode(' ', $parts);
	}

	/**
	 * Returns `true` when `$url` resolves to the current server (local); `false` otherwise.
	 *
	 * A URL is local when it is relative (no `://`), its host exactly matches the
	 * server name, or — when `$matchSubdomains` is `true` — its host is a subdomain
	 * of the server name (e.g. `assets.mysite.com` when serving `mysite.com`).
	 * The server name defaults to {@see \Prado\Web\THttpRequest::getServerName()};
	 * pass `$serverName` to override it in tests or CLI contexts.
	 * Returns `false` when the URL is absolute and no server name is available.
	 *
	 * **Note:** protocol-relative URLs (`//host/path`) contain no `://` and are
	 * therefore treated as relative — i.e. local. If protocol-relative URLs must be
	 * tested against the server name, expand them to `https://host/path` first.
	 *
	 * @param string      $url            URL to test.
	 * @param bool        $matchSubdomains Also treat subdomains as local. Default: `false`.
	 * @param ?string     $serverName     Hostname to compare against; defaults to the
	 *   current request's server name.
	 * @return bool `true` if local, `false` if remote or indeterminate.
	 * @since 4.4.0
	 */
	public static function isLocalUrl(string $url, bool $matchSubdomains = false, ?string $serverName = null): bool
	{
		// Relative URLs have no host component — always local.
		if (!str_contains($url, '://')) {
			return true;
		}

		$urlHost = parse_url($url, PHP_URL_HOST);
		if ($urlHost === null || $urlHost === false) {
			return false;
		}

		if ($serverName === null) {
			$app = Prado::getApplication();
			$serverName = $app !== null ? $app->getRequest()->getServerName() : null;
		}

		if ($serverName === null) {
			return false;
		}

		$urlHostLower = strtolower($urlHost);
		$serverNameLower = strtolower($serverName);

		if ($urlHostLower === $serverNameLower) {
			return true;
		}

		// When matching subdomains, accept any subdomain of the server name.
		// The host must end with ".<serverName>" to prevent "evil-mysite.com"
		// from matching "mysite.com".
		if ($matchSubdomains && str_ends_with($urlHostLower, '.' . $serverNameLower)) {
			return true;
		}

		return false;
	}

	/**
	 * Normalizes a URL for use as a Subresource Integrity (SRI) registry key.
	 *
	 * Many syntactically distinct URLs resolve to the same bytes and therefore
	 * share the same SRI hash. This method collapses the common variants so that
	 * SRI registries (e.g. in {@see \Prado\Web\Javascripts\TJavaScript}) produce
	 * consistent lookups regardless of how the caller spelled the URL.
	 *
	 * Normalization rules applied (per RFC 3986):
	 * - Protocol-relative (`//host/…`) is expanded to `https://host/…`.
	 * - Scheme and host are lowercased (both are case-insensitive in HTTP).
	 * - Redundant default ports are removed (`:80` for `http`, `:443` for `https`).
	 * - Fragment (`#…`) is stripped — fragments are client-side only and do not
	 *   affect resource content or its hash.
	 * - Path and query string are preserved unchanged — they can differentiate
	 *   resource versions (e.g. `?v=3.7.1`).
	 *
	 * **Query parameter order is intentionally not sorted.** The SRI hash is bound
	 * to the exact bytes served at a URL; HTTP servers are free to treat parameter
	 * order as significant, so sorting would risk collapsing two distinct resources
	 * with distinct hashes onto the same registry key. If a CDN is known to serve
	 * identical bytes regardless of parameter order, canonicalize the URL (eg.
	 * sorting the query parameters) before passing it to the registry — that is
	 * application-level knowledge that belongs at the call site, not inside this
	 * function.
	 *
	 * Relative URLs (no scheme or host) are returned unchanged because they are
	 * always local and SRI is not applied to local resources.
	 *
	 * @param string $url URL to normalize.
	 * @return string Normalized URL suitable for use as an SRI registry key.
	 * @since 4.4.0
	 */
	public static function normalizeIntegrityUrl(string $url): string
	{
		// Protocol-relative → https.
		if (str_starts_with($url, '//')) {
			$url = 'https:' . $url;
		}

		$parts = parse_url($url);

		// Relative or unparseable — return as-is; SRI does not apply locally.
		if ($parts === false || !isset($parts['host'])) {
			return $url;
		}

		$scheme = strtolower($parts['scheme'] ?? 'https');
		$host = strtolower($parts['host']);
		$port = $parts['port'] ?? null;

		// Strip redundant default ports.
		if (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80)) {
			$port = null;
		}

		$path = $parts['path'] ?? '/';
		$query = isset($parts['query']) ? '?' . $parts['query'] : '';
		// Fragment intentionally omitted.

		return $scheme . '://' . $host . ($port !== null ? ':' . $port : '') . $path . $query;
	}
}
