<?php

/**
 * TJavaScript class file
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

use Prado\Exceptions\TConfigurationException;
use Prado\Web\THttpUtility;
use Prado\Prado;

/**
 * TJavaScript class
 *
 * Static utility class for rendering `<script>` tags and encoding PHP values
 * as JavaScript. Manages two pieces of per-request state shared across all
 * render helpers:
 *
 * - **CSP nonce** ({@see getScriptNonce()}/{@see setScriptNonce()}) — a raw
 *   nonce string that, when set, is automatically emitted as a `nonce="…"`
 *   attribute on every `<script>` tag rendered by this class. Set by
 *   {@see \Prado\Web\HttpHeaders\THttpHeaderCsp::init()} when a nonce-bearing
 *   CSP is active.
 *
 * - **SRI integrity registry** ({@see setScriptIntegrity()}/{@see getScriptIntegrity()}) —
 *   a URL-keyed map of Subresource Integrity strings. URLs are normalized before
 *   storage so protocol-relative variants, redundant default ports, mixed-case
 *   schemes/hosts, and fragment suffixes all resolve to the same key.
 *   {@see renderScriptFile()} automatically emits `integrity` and
 *   `crossorigin="anonymous"` attributes for registered remote URLs.
 *
 * **Rendering helpers:**
 * - {@see renderScriptHeader()} / {@see renderScriptFooter()} — open/close
 *   an inline `<script>` block with CDATA wrapping.
 * - {@see renderScriptBlock()} — convenience wrapper for a single inline block.
 * - {@see renderScriptBlocks()} — wraps multiple inline blocks in one tag.
 * - {@see renderScriptBlocksCallback()} — emits raw script content without a tag.
 * - {@see renderScriptFile()} / {@see renderScriptFiles()} — emit `<script src>` tags.
 *
 * **Encoding helpers:**
 * - {@see encode()} — encodes a PHP value as a JavaScript literal.
 * - {@see quoteString()} — JSON-encodes a string with HTML-safe hex escaping.
 * - {@see quoteJsLiteral()} / {@see isJsLiteral()} — wrap or detect raw JavaScript.
 * - {@see jsonEncode()} / {@see jsonDecode()} — thin wrappers around `json_encode` / `json_decode`.
 * - {@see JSMin()} — minifies a JavaScript source string.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @since 3.0
 */
class TJavaScript
{
	/**
	 * @var ?string Per-request CSP nonce to be injected into inline script tags.
	 * Set by {@see \Prado\Web\HttpHeaders\THttpHeaderCsp::init()} when a CSP policy referencing a nonce is active.
	 * Null means no CSP nonce is in use and no nonce attribute is emitted.
	 * @since 4.4.0
	 */
	private static $_scriptNonce;

	/**
	 * @var array<string,string> Registry of URL → full SRI integrity string (e.g. `sha384-…`).
	 * Populated via {@see setScriptIntegrity()} and consumed by {@see renderScriptFile()}
	 * for plain-URL assets that are not {@see TJavaScriptAsset} objects.
	 * @since 4.4.0
	 */
	private static $_scriptIntegrity = [];

	/**
	 * @var bool When `true`, rendering a remote `<script src>` that has no registered
	 * SRI value throws a {@see TConfigurationException} instead of emitting a tag with
	 * no `integrity` attribute. Defaults to `false`. Toggled via
	 * {@see setRequireScriptIntegrity()}, typically from
	 * {@see \Prado\Web\TIntegrityManager}.
	 * @since 4.4.0
	 */
	private static $_requireScriptIntegrity = false;

	/**
	 * Returns whether remote scripts are required to carry a registered SRI value.
	 * @return bool `true` when {@see renderScriptFile()} and {@see TJavaScriptAsset::__toString()}
	 *   throw for a remote URL without a registered integrity, `false` when they
	 *   render the tag without the attribute
	 * @since 4.4.0
	 */
	public static function getRequireScriptIntegrity(): bool
	{
		return self::$_requireScriptIntegrity;
	}

	/**
	 * Sets whether remote scripts must carry a registered SRI value to be rendered.
	 *
	 * When enabled, {@see renderScriptFile()} and {@see TJavaScriptAsset::__toString()}
	 * throw a {@see TConfigurationException} for any remote URL (per
	 * {@see THttpUtility::isLocalUrl()}) that has no integrity available, instead of
	 * emitting a `<script>` tag that a strict `Content-Security-Policy` would block.
	 * Local URLs and assets that explicitly suppress integrity are unaffected.
	 *
	 * @param bool $value `true` to enforce, `false` (default) to render leniently
	 * @since 4.4.0
	 */
	public static function setRequireScriptIntegrity(bool $value): void
	{
		self::$_requireScriptIntegrity = $value;
	}

	/**
	 * Returns the per-request CSP nonce currently registered with this class,
	 * or `null` when no nonce is active.
	 *
	 * A non-null value is automatically included as a `nonce="…"` attribute on
	 * every `<script>` tag rendered by {@see renderScriptHeader()} and
	 * {@see renderScriptFile()}. `null` means CSP nonce enforcement is not in
	 * use for this request and the attribute is omitted.
	 *
	 * @return ?string raw nonce value (no `nonce-` prefix), or `null`
	 * @since 4.4.0
	 */
	public static function getScriptNonce(): ?string
	{
		return self::$_scriptNonce;
	}

	/**
	 * Registers a per-request CSP nonce to be emitted on every `<script>` tag
	 * rendered by this class for the duration of the current request.
	 *
	 * Once set, {@see renderScriptHeader()} and {@see renderScriptFile()} will
	 * automatically include `nonce="$nonce"` so that inline scripts and external
	 * script files are permitted by a `Content-Security-Policy` header that
	 * carries a matching `'nonce-…'` source expression.
	 *
	 * This is called automatically by
	 * {@see \Prado\Web\HttpHeaders\THttpHeaderCsp::init()} when a nonce-bearing
	 * CSP is configured. Pass `null` to clear a previously registered nonce and
	 * suppress the attribute on subsequent renders.
	 *
	 * @param ?string $nonce raw nonce value (without the `nonce-` prefix), or `null` to clear
	 * @since 4.4.0
	 */
	public static function setScriptNonce(?string $nonce): void
	{
		self::$_scriptNonce = $nonce;
	}

	/**
	 * Returns `true` when an SRI integrity value has been registered for the
	 * given script URL via {@see setScriptIntegrity()}, `false` otherwise.
	 *
	 * The URL is normalized via {@see THttpUtility::normalizeIntegrityUrl()} before
	 * the lookup, so protocol-relative URLs, redundant default ports, mixed-case
	 * schemes/hosts, and fragment suffixes all resolve to the same registry entry
	 * as their canonical equivalents.
	 *
	 * @param string $url script URL to check; normalized before the lookup
	 * @return bool `true` if an SRI string is registered for the normalized URL
	 * @since 4.4.0
	 */
	public static function hasScriptIntegrity(string $url): bool
	{
		return isset(self::$_scriptIntegrity[THttpUtility::normalizeIntegrityUrl($url)]);
	}

	/**
	 * Returns the registered SRI integrity string for the given script URL, or
	 * `null` when no value has been registered via {@see setScriptIntegrity()}.
	 *
	 * The URL is normalized via {@see THttpUtility::normalizeIntegrityUrl()} before
	 * the lookup, so protocol-relative URLs, redundant default ports, mixed-case
	 * schemes/hosts, and fragment suffixes all resolve to the same registry entry
	 * as their canonical equivalents.
	 *
	 * @param string $url script URL to look up; normalized before the lookup
	 * @return ?string fully-formed `algo-digest` SRI string (e.g. `sha384-…`), or `null`
	 * @since 4.4.0
	 */
	public static function getScriptIntegrity(string $url): ?string
	{
		return self::$_scriptIntegrity[THttpUtility::normalizeIntegrityUrl($url)] ?? null;
	}

	/**
	 * Registers or clears a Subresource Integrity (SRI) value for a script URL.
	 *
	 * The URL is normalized via {@see THttpUtility::normalizeIntegrityUrl()} before
	 * storage, so syntactically different but equivalent URLs (protocol-relative,
	 * redundant default port, mixed-case scheme/host, fragment suffix) all share
	 * the same registry entry.
	 *
	 * Pass `null` as `$hash` to remove any previously registered value; subsequent
	 * calls to {@see renderScriptFile()} will then emit no `integrity` attribute
	 * for that URL.
	 *
	 * `$hash` may be a bare base64 digest (e.g. `"AAAA…"`) or a fully-formed SRI
	 * string (e.g. `"sha384-AAAA…"`). In the bare-digest case `$hashMethod` supplies
	 * the algorithm prefix; when a fully-formed string is passed `$hashMethod` is
	 * ignored. Once registered, {@see renderScriptFile()} automatically emits the
	 * `integrity` and `crossorigin="anonymous"` attributes for matching remote URLs.
	 *
	 * @param string  $url        script URL; normalized before storage
	 * @param ?string $hash       fully-formed `algo-digest` SRI string, bare base64
	 *   digest, or `null` to clear
	 * @param string  $hashMethod algorithm prefix prepended to a bare digest
	 *   (default `'sha384'`); ignored when `$hash` is already fully-formed or `null`
	 * @since 4.4.0
	 */
	public static function setScriptIntegrity(string $url, ?string $hash, string $hashMethod = 'sha384'): void
	{
		$key = THttpUtility::normalizeIntegrityUrl($url);
		if ($hash === null) {
			unset(self::$_scriptIntegrity[$key]);
			return;
		}
		if (str_contains($hash, '-')) {
			self::$_scriptIntegrity[$key] = $hash;
		} else {
			self::$_scriptIntegrity[$key] = $hashMethod . '-' . $hash;
		}
	}

	/**
	 * Renders the opening `<script>` tag and CDATA prologue for an inline
	 * JavaScript block.
	 *
	 * The returned string has the form:
	 * ```html
	 * <script[ attr="value"…]>
	 * /*<![CDATA[*\/ 	// < - this is the C comment start and end
	 *
	 * ```
	 * The `/*<![CDATA[*\/` comment lets XHTML parsers treat the body as opaque
	 * character data while remaining valid JavaScript in HTML mode.
	 *
	 * When a CSP nonce has been registered via {@see setScriptNonce()}, a
	 * `nonce="…"` attribute is included automatically so the browser permits the
	 * inline block under a strict `Content-Security-Policy`. Any additional
	 * attributes passed in `$attributes` are merged in; a caller-supplied `nonce`
	 * key takes precedence over the registered nonce.
	 *
	 * Must be paired with {@see renderScriptFooter()} to close the block.
	 *
	 * @param array $attributes additional HTML attributes for the `<script>` tag;
	 *   keys are attribute names, values follow the {@see THttpUtility::buildHtmlAttributes()}
	 *   convention (`null`/`false` omits, `true` emits a boolean attribute)
	 * @return string opening `<script>` tag followed by the CDATA prologue
	 * @since 4.4.0
	 */
	public static function renderScriptHeader(array $attributes = []): string
	{
		$attributes['nonce'] ??= self::getScriptNonce();
		$attrs = THttpUtility::buildHtmlAttributes($attributes);
		return '<script' . ($attrs !== '' ? ' ' . $attrs : '') . ">\n/*<![CDATA[*/\n";
	}

	/**
	 * Renders the closing CDATA epilogue and `</script>` tag for an inline
	 * JavaScript block opened by {@see renderScriptHeader()}.
	 *
	 * The returned string has the form:
	 * ```
	 *
	 * /*]]>*\/		// < - this is the C comment start and end
	 * </script>
	 *
	 * ```
	 * The `/*]]>*\/` comment closes the CDATA section opened by the prologue in
	 * {@see renderScriptHeader()}, keeping the combined block valid in both HTML
	 * and XHTML contexts.
	 *
	 * @return string CDATA epilogue followed by the closing `</script>` tag
	 * @since 4.4.0
	 */
	public static function renderScriptFooter(): string
	{
		return "\n/*]]>*/\n</script>\n";
	}

	/**
	 * Renders a `<script src>` tag for each URL or {@see TJavaScriptAsset} in
	 * `$files` and returns the concatenated result.
	 * @param array<string|TJavaScriptAsset> $files URLs or asset objects to render
	 * @return string concatenated rendering of all script tags
	 */
	public static function renderScriptFiles($files): string
	{
		$str = '';
		foreach ($files as $file) {
			$str .= self::renderScriptFile($file);
		}
		return $str;
	}

	/**
	 * Renders a javascript file.
	 * When a CSP nonce has been registered via {@see setScriptNonce()}, the
	 * emitted `<script>` tag will include a `nonce` attribute so the browser
	 * permits the script under a strict Content-Security-Policy.
	 *
	 * For plain-URL assets (not {@see TJavaScriptAsset} objects), if an SRI hash has
	 * been registered for the URL via {@see setScriptIntegrity()} and the URL is not
	 * local (per {@see THttpUtility::isLocalUrl()}), the `integrity` and
	 * `crossorigin="anonymous"` attributes are also emitted.
	 *
	 * @param \Prado\Web\Javascripts\TJavaScriptAsset|string $asset URL to the javascript file or TJavaScriptAsset
	 * @return string rendering result
	 */
	public static function renderScriptFile($asset): string
	{
		if ($asset instanceof TJavaScriptAsset) {
			return $asset->__toString() . "\n";
		}
		$url = (string) $asset;
		$integrity = null;
		if (!THttpUtility::isLocalUrl($url)) {
			$integrity = self::getScriptIntegrity($url);
			if ($integrity === null && self::$_requireScriptIntegrity) {
				throw new TConfigurationException('javascript_script_integrity_required', $url);
			}
		}
		$attrs = THttpUtility::buildHtmlAttributes([
			'src' => $url,
			'nonce' => self::getScriptNonce(),
			'integrity' => $integrity,
			'crossorigin' => $integrity !== null ? 'anonymous' : null,
		]);
		return '<script' . ($attrs !== '' ? ' ' . $attrs : '') . "></script>\n";
	}

	/**
	 * Wraps a list of JavaScript snippets in a single `<script>` tag with CDATA
	 * delimiters. Returns an empty string when `$scripts` is empty.
	 * @param array $scripts raw JavaScript snippets to concatenate
	 * @return string the wrapped script block, or `''` when empty
	 */
	public static function renderScriptBlocks($scripts): string
	{
		if (count($scripts)) {
			return self::renderScriptHeader() . implode("\n", $scripts) . self::renderScriptFooter();
		} else {
			return '';
		}
	}

	/**
	 * Concatenates a list of JavaScript snippets without wrapping them in a
	 * `<script>` tag. Intended for callback responses where the browser already
	 * handles the script context. Returns an empty string when `$scripts` is empty.
	 * @param array $scripts raw JavaScript snippets to concatenate
	 * @return string newline-joined snippets with a trailing newline, or `''` when empty
	 */
	public static function renderScriptBlocksCallback($scripts): string
	{
		if (count($scripts)) {
			return implode("\n", $scripts) . "\n";
		} else {
			return '';
		}
	}

	/**
	 * Wraps a single JavaScript snippet in a `<script>` tag with CDATA delimiters.
	 * @param string $script raw JavaScript snippet
	 * @return string the wrapped script block
	 */
	public static function renderScriptBlock($script): string
	{
		return self::renderScriptHeader() . $script . self::renderScriptFooter();
	}

	/**
	 * JSON-encodes a string with HTML-safe hex escaping for `"`, `'`, and `<`/`>`.
	 * The result is wrapped in double quotes and is safe to embed directly in
	 * HTML attributes or JavaScript string literals.
	 * @param string $js string to encode
	 * @return string JSON-encoded string with hex-escaped HTML-special characters
	 */
	public static function quoteString($js): string
	{
		return self::jsonEncode($js, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG);
	}

	/**
	 * Wraps `$js` in a {@see TJavaScriptLiteral} so that {@see encode()} treats
	 * it as a raw JavaScript expression rather than a value to be encoded.
	 * When `$js` has already been wrapped, it is returned unchanged.
	 * @param mixed $js value or string to mark as a raw JavaScript literal
	 * @return TJavaScriptLiteral the literal wrapper
	 */
	public static function quoteJsLiteral($js): TJavaScriptLiteral
	{
		if ($js instanceof TJavaScriptLiteral) {
			return $js;
		} else {
			return new TJavaScriptLiteral($js);
		}
	}

	/**
	 * Returns `true` when `$js` is a {@see TJavaScriptLiteral} instance and will
	 * therefore be passed through by {@see encode()} without re-encoding.
	 * @param mixed $js value to test
	 * @return bool `true` when `$js` is a {@see TJavaScriptLiteral}
	 */
	public static function isJsLiteral($js): bool
	{
		return ($js instanceof TJavaScriptLiteral);
	}

	/**
	 * Encodes a PHP value as its JavaScript literal equivalent.
	 *
	 * Type mapping:
	 * - `string` → JSON-encoded with HTML-safe hex escaping (via {@see quoteString()})
	 * - `bool` → `'true'` or `'false'`
	 * - `int` → decimal string
	 * - `float` → decimal string; `INF` / `-INF` map to
	 *   `Number.POSITIVE_INFINITY` / `Number.NEGATIVE_INFINITY`; the decimal
	 *   separator is normalized to `.` when the current locale uses another character
	 * - `null` → `'null'`
	 * - Sequential array (`0…n-1` keys) → `[…]`
	 * - Associative array → `{'key':value,…}`
	 * - {@see TJavaScriptLiteral} → raw literal string (not re-encoded)
	 * - Other object → encoded as its public properties via `get_object_vars()`
	 * - Empty-string elements in arrays are silently skipped unless
	 *   `$encodeEmptyStrings` is `true`
	 *
	 * For complex data structures use {@see jsonEncode()} / {@see jsonDecode()} instead.
	 *
	 * ```php
	 * TJavaScript::encode(['onLoading' => 'doit', 'onComplete' => 'more']);
	 * // {'onLoading':'doit','onComplete':'more'}
	 * ```
	 *
	 * @param mixed $value PHP value to encode
	 * @param bool $toMap unused; retained for backward compatibility
	 * @param bool $encodeEmptyStrings when `true`, empty-string elements are included
	 *   in encoded arrays; defaults to `false` for backward compatibility
	 * @return string JavaScript literal representation
	 * @since 3.1.5
	 */
	public static function encode($value, $toMap = true, $encodeEmptyStrings = false): string
	{
		if (is_string($value)) {
			return self::quoteString($value);
		} elseif (is_bool($value)) {
			return $value ? 'true' : 'false';
		} elseif (is_array($value)) {
			$results = '';
			if (($n = count($value)) > 0 && array_keys($value) !== range(0, $n - 1)) {
				foreach ($value as $k => $v) {
					if ($v !== '' || $encodeEmptyStrings) {
						if ($results !== '') {
							$results .= ',';
						}
						$results .= "'$k':" . self::encode($v, $toMap, $encodeEmptyStrings);
					}
				}
				return '{' . $results . '}';
			} else {
				foreach ($value as $v) {
					if ($v !== '' || $encodeEmptyStrings) {
						if ($results !== '') {
							$results .= ',';
						}
						$results .= self::encode($v, $toMap, $encodeEmptyStrings);
					}
				}
				return '[' . $results . ']';
			}
		} elseif (is_int($value)) {
			return "$value";
		} elseif (is_float($value)) {
			switch ($value) {
				case -INF:
					return 'Number.NEGATIVE_INFINITY';
				case INF:
					return 'Number.POSITIVE_INFINITY';
				default:
					$locale = localeConv();
					if ($locale['decimal_point'] == '.') {
						return "$value";
					} else {
						return str_replace($locale['decimal_point'], '.', "$value");
					}
			}
		} elseif (is_object($value)) {
			if ($value instanceof TJavaScriptLiteral) {
				return $value->toJavaScriptLiteral();
			} else {
				return self::encode(get_object_vars($value), $toMap);
			}
		} elseif ($value === null) {
			return 'null';
		} else {
			return '';
		}
	}

	/**
	 * Encodes a PHP value as a JSON string via `json_encode`.
	 *
	 * When the active globalization module reports a non-UTF-8 charset, string
	 * values inside `$value` are transcoded to UTF-8 first — but only when the
	 * charset names an encoding `iconv` actually recognises. A misconfigured
	 * charset (for example a locale code such as `'fr'`) is silently skipped
	 * so the call still returns JSON instead of escalating to a runtime error.
	 * Without a running application (e.g. in library or test contexts) the
	 * value is encoded as-is.
	 *
	 * @param mixed $value value to encode
	 * @param int $options `json_encode` option flags; `JSON_THROW_ON_ERROR`
	 *   is always added
	 * @throws \JsonException on encoding failure
	 * @return string JSON-encoded string
	 */
	public static function jsonEncode(mixed $value, int $options = 0): string
	{
		$app = Prado::getApplication();
		if ($app !== null && ($g = $app->getGlobalization(false)) !== null) {
			$enc = (string) $g->getCharset();
			if ($enc !== '' && strtoupper($enc) !== 'UTF-8' && self::isKnownEncoding($enc)) {
				self::convertToUtf8($value, $enc);
			}
		}

		return json_encode($value, $options | JSON_THROW_ON_ERROR);
	}

	/**
	 * Recursively converts string values in `$value` from `$sourceEncoding` to
	 * UTF-8 in place.
	 *
	 * Strings are transcoded directly; arrays and `stdClass`-shaped objects
	 * are walked element by element. Other scalars and objects of other
	 * classes pass through unchanged. Strings that cannot be transcoded
	 * (`iconv` returns `false`) are left as-is so the original bytes still
	 * reach `json_encode`, which handles UTF-8 validation itself.
	 *
	 * @param mixed &$value value to convert; modified in place
	 * @param string $sourceEncoding source encoding name accepted by `iconv`,
	 *   e.g. `'ISO-8859-1'`
	 */
	private static function convertToUtf8(mixed &$value, string $sourceEncoding): void
	{
		if (is_string($value)) {
			$converted = @iconv($sourceEncoding, 'UTF-8', $value);
			if ($converted !== false) {
				$value = $converted;
			}
		} elseif (is_array($value)) {
			foreach ($value as &$element) {
				self::convertToUtf8($element, $sourceEncoding);
			}
			unset($element);
		} elseif ($value instanceof \stdClass) {
			foreach (get_object_vars($value) as $key => $element) {
				self::convertToUtf8($element, $sourceEncoding);
				$value->$key = $element;
			}
		}
	}

	/**
	 * Checks whether `iconv` recognises an encoding name.
	 *
	 * The probe result is cached per process so repeated `jsonEncode()` calls
	 * under the same globalization charset do not pay the probe cost more
	 * than once.
	 *
	 * @param string $encoding encoding name to test
	 * @return bool whether `iconv` accepts the name
	 */
	private static function isKnownEncoding(string $encoding): bool
	{
		static $cache = [];
		$key = strtoupper($encoding);
		if (!isset($cache[$key])) {
			$cache[$key] = @iconv($encoding, 'UTF-8', '') !== false;
		}
		return $cache[$key];
	}

	/**
	 * Decodes a JSON string into a PHP value via `json_decode`.
	 * @param string $value JSON string to decode
	 * @param bool $assoc when `true`, JSON objects are decoded as associative arrays
	 * @param int $depth maximum recursion depth
	 * @throws \JsonException on decoding failure
	 * @return mixed decoded PHP value
	 */
	public static function jsonDecode(string $value, bool $assoc = false, int $depth = 512): mixed
	{
		return json_decode($value, $assoc, $depth, JSON_THROW_ON_ERROR);
	}

	/**
	 * Minifies a JavaScript source string using Douglas Crockford's JSMin algorithm.
	 * @param string $code JavaScript source to minify
	 * @return string minified JavaScript
	 */
	public static function JSMin($code): string
	{
		return \JSMin\JSMin::minify($code);
	}
}
