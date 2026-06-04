<?php

/**
 * THttpHeaderContentDisposition class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\TPropertyValue;
use Prado\Web\TContentDisposition;
use Prado\Web\THttpHeaderName;

/**
 * THttpHeaderContentDisposition class
 *
 * Emits a `Content-Disposition` header backed by a persistent
 * {@see TContentDisposition} value object. The shortcut properties
 * {@see setDispositionType() DispositionType} and {@see setFilename() Filename}
 * delegate into that object; the full RFC 6266 parameter map is accessible through
 * {@see getContentDisposition() ContentDisposition}.
 *
 * ```php
 * // Via THttpHeadersManager XML config:
 * // <header class="THttpHeaderContentDisposition" DispositionType="attachment" Filename="report.pdf" />
 *
 * // Via PHP:
 * $header = new THttpHeaderContentDisposition();
 * $header->setDispositionType(TContentDisposition::ATTACHMENT);
 * $header->setFilename('Quarterly Résumé.pdf');
 * // Emits: Content-Disposition: attachment; filename="Quarterly R_sum_.pdf";
 * //        filename*=UTF-8''Quarterly%20R%C3%A9sum%C3%A9.pdf
 *
 * // Extended parameter access via the value object:
 * $header->getContentDisposition()->setParameter('creation-date', '"Mon, 01 Jan 2024 00:00:00 GMT"');
 * ```
 *
 * **Filename encoding.** For pure-ASCII names only `filename` is set; for names
 * containing non-ASCII characters both `filename` (ASCII fallback) and `filename*`
 * (RFC 5987 `UTF-8''…` form) are set automatically. Pass an empty string to
 * remove both.
 *
 * **Manager auto-promotion.** Plain `HeaderName="Content-Disposition"` config
 * nodes are auto-promoted to this class by the manager's name→class registry;
 * `HeaderValue` is parsed into the internal {@see TContentDisposition} object,
 * preserving all parameters including extended ones.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see TContentDisposition
 * @see THttpHeaderName::ContentDisposition
 * @see THttpHeadersManager
 */
class THttpHeaderContentDisposition extends TBaseHttpHeader
{
	/**
	 * @var ?TContentDisposition the wrapped value object; lazy-initialised on first access.
	 */
	private ?TContentDisposition $_contentDisposition = null;

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return string `'Content-Disposition'`.
	 */
	public function getHeaderName(): string
	{
		return THttpHeaderName::ContentDisposition;
	}

	/**
	 * Returns the internal {@see TContentDisposition} value object, exposing the
	 * full RFC 6266 parameter map. Lazy-initialised on first call.
	 * @return TContentDisposition
	 */
	public function getContentDisposition(): TContentDisposition
	{
		$cd = $this->getContentDispositionDirect();
		if ($cd === null) {
			$cd = $this->newContentDisposition();
			$this->setContentDispositionDirect($cd);
			$cd = $this->getContentDispositionDirect();
		}
		return $cd;
	}

	/**
	 * @return string disposition type, e.g. `'attachment'` or `'inline'`.
	 */
	public function getDispositionType(): string
	{
		return $this->getContentDisposition()->getType();
	}

	/**
	 * Prefer the {@see TContentDisposition} constants.
	 * @param string $value disposition type, e.g. `'attachment'` or `'inline'`.
	 */
	public function setDispositionType(string $value): void
	{
		$this->getContentDisposition()->setType($value);
	}

	/**
	 * @return string filename, or `''` when neither `filename` nor `filename*` is set.
	 */
	public function getFilename(): string
	{
		return $this->getContentDisposition()->getFilename() ?? '';
	}

	/**
	 * Sets the filename parameter(s). For non-ASCII names both `filename` (ASCII
	 * fallback) and `filename*` (RFC 5987) are set automatically. `''` removes both.
	 * @param string $value filename, e.g. `'report.pdf'` or `'Résumé.pdf'`; `''` removes it.
	 */
	public function setFilename(string $value): void
	{
		$this->getContentDisposition()->setFilename($value !== '' ? $value : null);
	}

	/**
	 * @return string e.g. `'attachment'` or `'attachment; filename="report.pdf"'`
	 */
	public function getHeaderValue(): string
	{
		return (string) $this->getContentDisposition();
	}

	/**
	 * Parses a raw `Content-Disposition` value into the internal
	 * {@see TContentDisposition} object, preserving all parameters. Called
	 * automatically by the manager when promoting a plain config node.
	 * @param mixed $value e.g. `'attachment; filename="report.pdf"'` or `'inline'`
	 */
	public function setHeaderValue($value): void
	{
		$this->setContentDispositionDirect(new TContentDisposition(TPropertyValue::ensureString($value)));
	}

	// =========================================================================
	// Protected helpers
	// =========================================================================

	/**
	 * Creates the default {@see TContentDisposition} object when none has been set.
	 * Subclasses may override to change the initial disposition type.
	 * @return TContentDisposition defaults to `attachment`.
	 */
	protected function newContentDisposition(): TContentDisposition
	{
		return new TContentDisposition(TContentDisposition::ATTACHMENT);
	}

	/**
	 * Raw backing-field read; `null` until first access via {@see getContentDisposition()}.
	 * @return ?TContentDisposition
	 */
	protected function getContentDispositionDirect(): ?TContentDisposition
	{
		return $this->_contentDisposition;
	}

	/**
	 * Replaces the backing field. `null` clears it; next {@see getContentDisposition()}
	 * call re-initialises via {@see newContentDisposition()}.
	 * @param ?TContentDisposition $value
	 */
	protected function setContentDispositionDirect(?TContentDisposition $value): void
	{
		$this->_contentDisposition = $value;
	}
}
