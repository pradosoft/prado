<?php

/**
 * TJavaScriptAsset classes
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Javascripts;

use Prado\Web\THttpUtility;

/**
 * TJavaScriptAsset class.
 *
 * TJavaScriptAsset is a value object that carries the properties of a single
 * `<script src="...">` tag and renders it via {@see __toString()}.
 *
 * Properties:
 * - {@see getUrl() Url} — the URL of the JavaScript file.
 * - {@see getAsync() Async} — whether to add the `async` boolean attribute.
 * - {@see getIntegrity() Integrity} — Subresource Integrity disposition:
 *   `string` pins an explicit fully-formed `algo-digest` value (e.g. `sha384-AAAA…`);
 *   `null` (default) falls back to the per-URL registry on {@see TJavaScript}
 *   (see {@see TJavaScript::setScriptIntegrity()}); `false` explicitly suppresses
 *   the `integrity` attribute even when the registry holds a matching entry.
 *   The attribute is only emitted for remote URLs (see {@see THttpUtility::isLocalUrl()}).
 *
 * A per-request CSP nonce registered via {@see TJavaScript::setScriptNonce()} is
 * automatically included as a `nonce` attribute on every rendered tag.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TJavaScriptAsset
{
	public const DEFAULT_INTEGRITY_VALUE = null;

	/** @var string URL of the JavaScript file. */
	private string $_url;

	/** @var bool Whether to add the `async` boolean attribute. */
	private bool $_async;

	//  @todo add text with CDATA interior when present.

	/**
	 * @var null|false|string Subresource Integrity disposition:
	 *   `null` — not set; {@see getIntegrity()} falls back to the {@see TJavaScript} registry.
	 *   `false` — explicitly suppressed; no `integrity` attribute regardless of the registry.
	 *   `string` — fully-formed `algo-digest` SRI string (e.g. `sha384-…`).
	 * @since 4.4.0
	 */
	private null|false|string $_integrity = null;

	/**
	 * @param string $url URL of the JavaScript file
	 * @param bool $async whether to add the `async` boolean attribute; defaults to `false`
	 */
	public function __construct(string $url, bool $async = false)
	{
		$this->setIntegrity(static::DEFAULT_INTEGRITY_VALUE);
		$this->setUrl($url);
		$this->setAsync($async);
	}

	/**
	 * Renders the `<script src="...">` tag.
	 * The `nonce` attribute is included when a CSP nonce has been registered via
	 * {@see TJavaScript::setScriptNonce()}. The `integrity` and `crossorigin`
	 * attributes are included for remote URLs when {@see getIntegrity()} returns
	 * a non-null value (either from the asset itself or the {@see TJavaScript}
	 * registry). No trailing newline is appended; {@see TJavaScript::renderScriptFile()}
	 * adds one when delegating here.
	 * @return string the rendered `<script>` tag without a trailing newline
	 */
	public function __toString(): string
	{
		$url = $this->getUrl();
		$integrity = null;
		if (!THttpUtility::isLocalUrl($url)) {
			$integrity = $this->getIntegrity();
		}
		$attrs = THttpUtility::buildHtmlAttributes([
			'src' => $url,
			'async' => $this->getAsync() ?: null,
			'nonce' => TJavaScript::getScriptNonce(),
			'integrity' => $integrity,
			'crossorigin' => $integrity !== null ? 'anonymous' : null,
		]);
		return '<script' . ($attrs !== '' ? ' ' . $attrs : '') . '></script>';
	}

	/**
	 * @return string URL of the JavaScript file
	 */
	public function getUrl(): string
	{
		return $this->_url;
	}

	/**
	 * @param string $url URL of the JavaScript file
	 */
	public function setUrl(string $url): void
	{
		$this->_url = $url;
	}

	/**
	 * @return bool whether the `async` attribute is set
	 */
	public function getAsync(): bool
	{
		return $this->_async;
	}

	/**
	 * @param bool $async whether to add the `async` boolean attribute
	 */
	public function setAsync(bool $async): void
	{
		$this->_async = $async;
	}

	/**
	 * Returns the raw Subresource Integrity disposition stored on this asset,
	 * without consulting the {@see TJavaScript} registry or resolving `false`.
	 * @return null|false|string `null` when not set, `false` when explicitly
	 *   suppressed, or the fully-formed `algo-digest` SRI string
	 * @since 4.4.0
	 */
	protected function getIntegrityDirect(): null|false|string
	{
		return $this->_integrity;
	}

	/**
	 * Stores the Subresource Integrity disposition directly in the backing field,
	 * bypassing any logic a subclass may have added to {@see setIntegrity()}.
	 * @param null|false|string $integrity see {@see setIntegrity()} for semantics
	 * @since 4.4.0
	 */
	protected function setIntegrityDirect(null|false|string $integrity): void
	{
		$this->_integrity = $integrity;
	}

	/**
	 * Returns the resolved Subresource Integrity string for this asset, or `null`
	 * when none is available or integrity has been explicitly suppressed.
	 *
	 * Resolution order:
	 * 1. When `false` has been set via {@see setIntegrity()}, returns `null` and
	 *    skips the registry lookup — integrity is explicitly suppressed.
	 * 2. A fully-formed `algo-digest` string set directly on this asset via
	 *    {@see setIntegrity()} takes precedence over the registry.
	 * 3. The per-URL registry on {@see TJavaScript} (see
	 *    {@see TJavaScript::setScriptIntegrity()}), consulted when no asset-level
	 *    value has been set.
	 *
	 * @return ?string the fully-formed `algo-digest` SRI string, or `null`
	 * @since 4.4.0
	 */
	public function getIntegrity(): ?string
	{
		$direct = $this->getIntegrityDirect();
		if ($direct === false) {
			return null;
		}
		return $direct ?? TJavaScript::getScriptIntegrity($this->getUrl());
	}

	/**
	 * Sets the Subresource Integrity disposition for this asset.
	 *
	 * - Pass a fully-formed `algo-digest` string (e.g. `sha384-AAAA…`) to pin a
	 *   specific SRI value; it takes precedence over the {@see TJavaScript} registry.
	 * - Pass `null` to clear any asset-level value and re-enable the
	 *   {@see TJavaScript} registry fallback.
	 * - Pass `false` to explicitly suppress integrity; {@see getIntegrity()} will
	 *   return `null` and {@see __toString()} will omit the `integrity` attribute
	 *   even when the registry holds a matching entry for this URL.
	 *
	 * @param null|false|string $integrity SRI string, `null` to defer to the
	 *   registry, or `false` to suppress integrity entirely
	 * @since 4.4.0
	 */
	public function setIntegrity(null|false|string $integrity): void
	{
		$this->setIntegrityDirect($integrity);
	}
}
