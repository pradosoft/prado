<?php

/**
 * TStyleSheetHelper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

use Prado\Exceptions\TConfigurationException;
use Prado\Web\THttpUtility;

/**
 * TStyleSheetHelper class
 *
 * Static utility class for rendering `<style>` blocks and `<link rel="stylesheet">`
 * tags. It is the stylesheet counterpart of {@see TJavaScript}, with the same
 * per-request state:
 *
 * - **CSP nonce** ({@see getStyleNonce()}) — the per-request nonce of
 *   {@see TJavaScript::getScriptNonce()}. A Content-Security-Policy has one nonce,
 *   which {@see \Prado\Web\HttpHeaders\THttpHeaderCsp} substitutes for its `NONCE`
 *   placeholder in `script-src` and `style-src` alike. When set, every tag rendered
 *   by this class carries `nonce="…"`.
 *
 * - **SRI integrity registry** ({@see setStyleIntegrity()}/{@see getStyleIntegrity()}) —
 *   a URL-keyed map of Subresource Integrity strings, normalized by
 *   {@see THttpUtility::normalizeIntegrityUrl()}. {@see renderStyleSheetFile()} emits
 *   `integrity` and `crossorigin="anonymous"` for registered remote URLs.
 *   {@see \Prado\Web\TIntegrityManager} fills it with the same entries it gives
 *   {@see TJavaScript}.
 *
 * - **Required integrity** ({@see setRequireStyleIntegrity()}) — when on, a remote
 *   stylesheet without a registered integrity throws instead of rendering. It is
 *   separate from {@see TJavaScript::setRequireScriptIntegrity()} because some
 *   remote stylesheets, such as font service CSS generated per browser, have no
 *   fixed hash.
 *
 * **Rendering helpers:**
 * - {@see renderStyleHeader()} / {@see renderStyleFooter()} — open/close an inline
 *   `<style>` block with CDATA wrapping.
 * - {@see renderStyleBlock()} — a single inline block.
 * - {@see renderStyleBlocks()} — multiple inline blocks in one tag.
 * - {@see renderStyleSheetFile()} / {@see renderStyleSheetFiles()} — `<link rel="stylesheet">` tags.
 *
 * A nonce does not cover `style="…"` attributes, which Prado controls render. A
 * policy that requires the nonce for style elements allows attributes separately,
 * for example `style-src-elem 'self' NONCE; style-src-attr 'unsafe-inline'`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStyleSheetHelper
{
	/**
	 * @var array<string,string> Registry of URL → full SRI integrity string (e.g. `sha384-…`).
	 * Populated via {@see setStyleIntegrity()} and consumed by {@see renderStyleSheetFile()}.
	 */
	private static $_styleIntegrity = [];

	/**
	 * @var bool When `true`, rendering a remote stylesheet that has no registered SRI
	 * value throws a {@see TConfigurationException}. Defaults to `false`.
	 */
	private static $_requireStyleIntegrity = false;

	/**
	 * Returns whether remote stylesheets are required to carry a registered SRI value.
	 * @return bool `true` when {@see renderStyleSheetFile()} throws for a remote URL
	 *   without a registered integrity, `false` when it renders the tag without it
	 */
	public static function getRequireStyleIntegrity(): bool
	{
		return self::$_requireStyleIntegrity;
	}

	/**
	 * Sets whether remote stylesheets must carry a registered SRI value to be rendered.
	 *
	 * When enabled, {@see renderStyleSheetFile()} throws a {@see TConfigurationException}
	 * for any remote URL (per {@see THttpUtility::isLocalUrl()}) that has no registered
	 * integrity. Local URLs are unaffected.
	 *
	 * @param bool $value `true` to enforce, `false` (default) to render leniently
	 */
	public static function setRequireStyleIntegrity(bool $value): void
	{
		self::$_requireStyleIntegrity = $value;
	}

	/**
	 * Returns the per-request CSP nonce, or `null` when no nonce is active.
	 *
	 * This is the nonce of {@see TJavaScript::getScriptNonce()}: one nonce serves
	 * scripts and styles. A non-null value is included as a `nonce="…"` attribute on
	 * every tag rendered by this class.
	 *
	 * @return ?string raw nonce value (no `nonce-` prefix), or `null`
	 */
	public static function getStyleNonce(): ?string
	{
		return TJavaScript::getScriptNonce();
	}

	/**
	 * Returns `true` when an SRI integrity value is registered for the stylesheet URL.
	 * The URL is normalized via {@see THttpUtility::normalizeIntegrityUrl()} first.
	 * @param string $url stylesheet URL to check
	 * @return bool `true` if an SRI string is registered for the normalized URL
	 */
	public static function hasStyleIntegrity(string $url): bool
	{
		return isset(self::$_styleIntegrity[THttpUtility::normalizeIntegrityUrl($url)]);
	}

	/**
	 * Returns the registered SRI integrity string for the stylesheet URL, or `null`.
	 * The URL is normalized via {@see THttpUtility::normalizeIntegrityUrl()} first.
	 * @param string $url stylesheet URL to look up
	 * @return ?string fully-formed `algo-digest` SRI string (e.g. `sha384-…`), or `null`
	 */
	public static function getStyleIntegrity(string $url): ?string
	{
		return self::$_styleIntegrity[THttpUtility::normalizeIntegrityUrl($url)] ?? null;
	}

	/**
	 * Registers or clears a Subresource Integrity (SRI) value for a stylesheet URL.
	 *
	 * The URL is normalized via {@see THttpUtility::normalizeIntegrityUrl()} before
	 * storage. `null` as `$hash` removes the entry. `$hash` is a fully-formed SRI
	 * string (e.g. `"sha384-AAAA…"`), stored as is, or a bare base64 digest, which
	 * `$hashMethod` prefixes.
	 *
	 * @param string  $url        stylesheet URL; normalized before storage
	 * @param ?string $hash       fully-formed `algo-digest` SRI string, bare base64
	 *   digest, or `null` to clear
	 * @param string  $hashMethod algorithm prefix prepended to a bare digest
	 *   (default `'sha384'`); ignored when `$hash` is already fully-formed or `null`
	 */
	public static function setStyleIntegrity(string $url, ?string $hash, string $hashMethod = 'sha384'): void
	{
		$key = THttpUtility::normalizeIntegrityUrl($url);
		if ($hash === null) {
			unset(self::$_styleIntegrity[$key]);
			return;
		}
		if (str_contains($hash, '-')) {
			self::$_styleIntegrity[$key] = $hash;
		} else {
			self::$_styleIntegrity[$key] = $hashMethod . '-' . $hash;
		}
	}

	/**
	 * Renders the opening `<style>` tag and CDATA prologue for an inline CSS block.
	 *
	 * The returned string has the form:
	 * ```html
	 * <style type="text/css"[ attr="value"…]>
	 * /*<![CDATA[*\/
	 *
	 * ```
	 * When a CSP nonce is active, `nonce="…"` is included so the browser permits the
	 * block under a `Content-Security-Policy`. Attributes in `$attributes` are merged
	 * in; a caller-supplied `nonce` key takes precedence over the registered nonce.
	 *
	 * Must be paired with {@see renderStyleFooter()} to close the block.
	 *
	 * @param array $attributes additional HTML attributes for the `<style>` tag;
	 *   values follow the {@see THttpUtility::buildHtmlAttributes()} convention
	 * @return string opening `<style>` tag followed by the CDATA prologue
	 */
	public static function renderStyleHeader(array $attributes = []): string
	{
		$attributes = ['type' => 'text/css'] + $attributes;
		$attributes['nonce'] ??= self::getStyleNonce();
		return '<style ' . THttpUtility::buildHtmlAttributes($attributes) . ">\n/*<![CDATA[*/\n";
	}

	/**
	 * Renders the closing CDATA epilogue and `</style>` tag for an inline CSS block
	 * opened by {@see renderStyleHeader()}.
	 * @return string CDATA epilogue followed by the closing `</style>` tag
	 */
	public static function renderStyleFooter(): string
	{
		return "\n/*]]>*/\n</style>\n";
	}

	/**
	 * Wraps a single CSS snippet in a `<style>` tag with CDATA delimiters.
	 * @param string $css CSS code
	 * @return string the wrapped style block
	 */
	public static function renderStyleBlock($css): string
	{
		return self::renderStyleHeader() . $css . self::renderStyleFooter();
	}

	/**
	 * Wraps a list of CSS snippets in a single `<style>` tag with CDATA delimiters.
	 * Returns an empty string when `$styles` is empty.
	 * @param array $styles CSS snippets to concatenate
	 * @return string the wrapped style block, or `''` when empty
	 */
	public static function renderStyleBlocks($styles): string
	{
		if (count($styles)) {
			return self::renderStyleHeader() . implode("\n", $styles) . self::renderStyleFooter();
		} else {
			return '';
		}
	}

	/**
	 * Renders a `<link rel="stylesheet">` tag for each file and returns the result.
	 * @param array<array|string> $files stylesheet URLs, or `[url, media]` pairs
	 * @return string concatenated rendering of all link tags
	 */
	public static function renderStyleSheetFiles($files): string
	{
		$str = '';
		foreach ($files as $file) {
			$str .= is_array($file) ? self::renderStyleSheetFile($file[0], $file[1]) : self::renderStyleSheetFile($file);
		}
		return $str;
	}

	/**
	 * Renders a `<link rel="stylesheet">` tag.
	 *
	 * When a CSP nonce is active, the tag includes `nonce`. For a remote URL (per
	 * {@see THttpUtility::isLocalUrl()}) with an SRI hash registered via
	 * {@see setStyleIntegrity()}, it also includes `integrity` and
	 * `crossorigin="anonymous"`.
	 *
	 * @param string $url URL of the stylesheet
	 * @param string $media media query of the stylesheet, such as `print`; `''` for all media
	 * @throws TConfigurationException when {@see getRequireStyleIntegrity()} is enabled
	 *   and the remote URL has no registered integrity
	 * @return string the `<link>` tag followed by a newline
	 */
	public static function renderStyleSheetFile($url, $media = ''): string
	{
		$url = (string) $url;
		$integrity = null;
		if (!THttpUtility::isLocalUrl($url)) {
			$integrity = self::getStyleIntegrity($url);
			if ($integrity === null && self::$_requireStyleIntegrity) {
				throw new TConfigurationException('stylesheet_integrity_required', $url);
			}
		}
		$attrs = THttpUtility::buildHtmlAttributes([
			'rel' => 'stylesheet',
			'type' => 'text/css',
			'media' => $media !== '' ? $media : null,
			'href' => $url,
			'nonce' => self::getStyleNonce(),
			'integrity' => $integrity,
			'crossorigin' => $integrity !== null ? 'anonymous' : null,
		]);
		return '<link ' . $attrs . " />\n";
	}
}
