<?php

/**
 * THttpHeaderContentType class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\THttpHeaderName;
use Prado\Web\THttpResponse;
use Prado\Web\TMediaType;

/**
 * THttpHeaderContentType class
 *
 * Emits a `Content-Type` header backed by a persistent {@see TMediaType} value
 * object. The shortcut properties {@see setContentType() ContentType} and
 * {@see setCharset() Charset} delegate into that object; the full parameter map
 * (e.g. `boundary`) is accessible through {@see getMediaType() MediaType}.
 *
 * **Charset resolution** (applied by {@see finalizeHeader()} if no charset has
 * been set on the internal {@see getMediaType() MediaType} object):
 * 1. {@see \Prado\I18N\TGlobalization::getCharset()} — when a globalization module
 *    is active and returns a non-empty value.
 * 2. {@see THttpResponse::DEFAULT_CHARSET} (`'UTF-8'`) — hard fallback.
 *
 * Pass `null`, `false`, or `''` to {@see setCharset()} to remove the charset
 * parameter; {@see finalizeHeader()} will then auto-resolve it.
 *
 * Plain `HeaderName="Content-Type"` config nodes are auto-promoted to this class
 * by the manager's name→class registry; `HeaderValue` is parsed into the internal
 * {@see TMediaType} object automatically, preserving all parameters.
 * Since `Content-Type` is a singleton, {@see getReplace()} returns `true` so the
 * manager's header replaces any earlier one sent by
 * {@see THttpResponse::sendContentTypeHeader()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see THttpHeaderName::ContentType
 * @see THttpResponse::sendContentTypeHeader()
 * @see THttpHeadersManager
 */
class THttpHeaderContentType extends TBaseHttpHeader
{
	/**
	 * @var ?TMediaType the media type object; lazy-initialised on first access.
	 */
	private ?TMediaType $_mediaType = null;

	// =========================================================================
	// Lifecycle
	// =========================================================================

	/**
	 * Resolves the charset from globalization or falls back to
	 * {@see THttpResponse::DEFAULT_CHARSET} when no charset has been set.
	 * No-op when a charset is already present.
	 */
	public function finalizeHeader(): void
	{
		if ($this->getMediaType()->getCharset() !== null) {
			return;
		}

		// Resolve from the globalization module if one is active.
		$app = Prado::getApplication();
		if ($app !== null && ($glob = $app->getGlobalization(false)) !== null) {
			$resolved = $glob->getCharset();
			if ($resolved !== '') {
				$this->getMediaType()->setCharset($resolved);
				return;
			}
		}

		// Hard default.
		$this->getMediaType()->setCharset(THttpResponse::DEFAULT_CHARSET);
	}

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return string `'Content-Type'`.
	 */
	public function getHeaderName(): string
	{
		return THttpHeaderName::ContentType;
	}

	/**
	 * Returns the internal {@see TMediaType} object, exposing the MIME type and any
	 * additional parameters (e.g. `boundary`, `charset`). Lazy-initialised on first call.
	 * @return TMediaType
	 */
	public function getMediaType(): TMediaType
	{
		$mt = $this->getMediaTypeDirect();
		if ($mt === null) {
			$mt = $this->newMediaType();
			$this->setMediaTypeDirect($mt);
			$mt = $this->getMediaTypeDirect();
		}
		return $mt;
	}

	/**
	 * @return string MIME type portion, e.g. `'text/html'` or `'application/json'`.
	 */
	public function getContentType(): string
	{
		return $this->getMediaType()->getMimeType();
	}

	/**
	 * @param string $value MIME type, e.g. `'text/html'`, `'application/json'`, `'image/png'`.
	 */
	public function setContentType(string $value): void
	{
		$this->getMediaType()->setMimeType($value);
	}

	/**
	 * @return ?string `charset` parameter, or `null` when not yet set (auto-resolved at finalize).
	 */
	public function getCharset(): ?string
	{
		return $this->getMediaType()->getCharset();
	}

	/**
	 * Sets the `charset` parameter. `null`, `''`, or `'false'` (case-insensitive) removes
	 * it, causing {@see finalizeHeader()} to auto-resolve it.
	 * @param ?string $value e.g. `'UTF-8'`, or `null`/`''` to remove.
	 */
	public function setCharset(?string $value): void
	{
		if ($value === null || $value === '' || strtolower($value) === 'false') {
			$this->getMediaType()->setCharset(null);
		} else {
			$this->getMediaType()->setCharset($value);
		}
	}

	/**
	 * @return string e.g. `'text/html; charset=UTF-8'` or `'image/png'`
	 */
	public function getHeaderValue(): string
	{
		return (string) $this->getMediaType();
	}

	/**
	 * Parses a raw `Content-Type` value into the internal {@see TMediaType} object,
	 * preserving all parameters. Called automatically by the manager when promoting
	 * a plain config node.
	 * @param mixed $value e.g. `'text/html; charset=UTF-8'`
	 */
	public function setHeaderValue($value): void
	{
		$this->setMediaTypeDirect(new TMediaType(TPropertyValue::ensureString($value)));
	}

	// =========================================================================
	// Protected helpers
	// =========================================================================

	/**
	 * Creates the default {@see TMediaType} object when none has been set.
	 * Subclasses may override to change the initial MIME type.
	 * @return TMediaType defaults to `text/html`.
	 */
	protected function newMediaType(): TMediaType
	{
		return new TMediaType(TMediaType::HTML);
	}

	/**
	 * Raw backing-field read; `null` until first access via {@see getMediaType()}.
	 * @return ?TMediaType
	 */
	protected function getMediaTypeDirect(): ?TMediaType
	{
		return $this->_mediaType;
	}

	/**
	 * Replaces the backing field. `null` clears it; next {@see getMediaType()}
	 * call re-initialises via {@see newMediaType()}.
	 * @param ?TMediaType $mediaType
	 */
	protected function setMediaTypeDirect(?TMediaType $mediaType): void
	{
		$this->_mediaType = $mediaType;
	}
}
