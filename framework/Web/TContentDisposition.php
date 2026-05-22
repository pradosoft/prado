<?php

/**
 * TContentDisposition class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Web\HttpHeaders\THeaderParametersTrait;

/**
 * TContentDisposition class
 *
 * TContentDisposition is a mutable value object that parses, represents, and
 * renders an HTTP `Content-Disposition` header value as defined by RFC 6266:
 *
 * ```
 * content-disposition = disposition-type *( OWS ";" OWS disposition-parm )
 * disposition-type    = "inline" / "attachment" / token
 * disposition-parm    = filename-parm / disp-ext-parm
 * filename-parm       = "filename" "=" value
 *                     / "filename*" "=" ext-value   (RFC 5987)
 * ```
 *
 * Properties:
 * - {@see getType() Type} — the disposition type, e.g. `attachment`.
 * - {@see getParameters() Parameters} — associative array of parameter names
 *   (lowercase) to values, e.g. `['filename' => 'report.pdf']`.
 * - {@see getFilename() Filename} — convenience accessor that returns the
 *   decoded filename. Prefers `filename*` (RFC 5987) when present.
 *
 * ```php
 * $cd = new TContentDisposition('attachment; filename="report.pdf"');
 * $cd->getType();       // 'attachment'
 * $cd->getFilename();   // 'report.pdf'
 * (string) $cd;         // 'attachment; filename="report.pdf"'
 *
 * // Smart filename setter — emits filename* for non-ASCII names:
 * $cd->setFilename('Quarterly Résumé.pdf');
 * (string) $cd;
 * // 'attachment; filename="Quarterly R_sum_.pdf"; filename*=UTF-8\'\'Quarterly%20R%C3%A9sum%C3%A9.pdf'
 *
 * // Use named constants instead of bare strings:
 * $cd = new TContentDisposition(TContentDisposition::ATTACHMENT);
 * ```
 *
 * **ArrayAccess.** Parameters are accessible via array syntax via
 * {@see THeaderParametersTrait}, making `TContentDisposition` a transparent
 * pipe to its parameter map:
 *
 * ```php
 * $cd = new TContentDisposition('attachment; filename="report.pdf"');
 * $cd['filename'];            // 'report.pdf'
 * isset($cd['filename']);     // true
 * $cd['filename'] = 'new.pdf';
 * unset($cd['filename']);
 * ```
 *
 * **Value quoting.** {@see __toString()} automatically wraps parameter values
 * in double quotes when they contain characters outside the RFC 9110 token
 * character set (e.g. spaces, non-ASCII). Values that are already valid tokens
 * are emitted without quotes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://www.rfc-editor.org/rfc/rfc6266 RFC 6266 (Content-Disposition in HTTP)
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-8.11 RFC 9110 §8.11 (Content-Disposition)
 * @see https://www.rfc-editor.org/rfc/rfc5987 RFC 5987 (filename* encoding)
 */
class TContentDisposition implements \ArrayAccess
{
	use THeaderParametersTrait;

	// =========================================================================
	// Named constants
	// =========================================================================

	/** `inline` — display the resource inline in the browser (default). */
	public const INLINE = 'inline';

	/** `attachment` — prompt the user to save the resource as a file download. */
	public const ATTACHMENT = 'attachment';

	/** `form-data` — used in multipart/form-data bodies (RFC 7578). */
	public const FORM_DATA = 'form-data';

	// =========================================================================
	// Backing field
	// =========================================================================

	/**
	 * @var string The disposition type, always lowercase (e.g. `attachment`).
	 */
	private string $_type;

	// =========================================================================
	// Constructor
	// =========================================================================

	/**
	 * Constructs a TContentDisposition by parsing a `Content-Disposition` value.
	 *
	 * ```php
	 * new TContentDisposition('attachment; filename="report.pdf"')
	 * new TContentDisposition(TContentDisposition::ATTACHMENT)
	 * new TContentDisposition()  // → 'inline'
	 * ```
	 *
	 * @param string $value the disposition value to parse; defaults to {@see INLINE}.
	 */
	public function __construct(string $value = self::INLINE)
	{
		$value = trim($value);
		$semicolon = strpos($value, ';');
		if ($semicolon !== false) {
			$this->setType(substr($value, 0, $semicolon));
			$this->setParameters(substr($value, $semicolon + 1));
		} else {
			$this->setType($value);
		}
	}

	// =========================================================================
	// Type
	// =========================================================================

	/**
	 * @return string the disposition type, always lowercase (e.g. `attachment`).
	 */
	public function getType(): string
	{
		return $this->_type;
	}

	/**
	 * Sets the disposition type. The value is normalized to lowercase.
	 * @param string $type e.g. `inline`, `attachment`, `form-data`
	 */
	public function setType(string $type): void
	{
		$this->_type = strtolower(trim($type));
	}

	// =========================================================================
	// Filename convenience
	// =========================================================================

	/**
	 * Returns the filename for this disposition.
	 *
	 * When a `filename*` parameter (RFC 5987 extended value) is present, it is
	 * decoded and returned in preference to `filename`. When only `filename` is
	 * present its stored value is returned directly. Returns `null` when neither
	 * parameter exists.
	 *
	 * @return ?string the decoded filename, or `null` when absent.
	 */
	public function getFilename(): ?string
	{
		$ext = $this->getParameter('filename*');
		if ($ext !== null) {
			return self::decodeRfc5987($ext);
		}
		return $this->getParameter('filename');
	}

	/**
	 * Sets the filename parameter(s).
	 *
	 * When `$filename` contains non-ASCII characters, both `filename` (ASCII
	 * fallback with non-ASCII code points replaced by `_`) and `filename*`
	 * (RFC 5987 `UTF-8''percent-encoded` form) are set to maximize
	 * compatibility. For pure-ASCII filenames only `filename` is set.
	 *
	 * Pass `null` to remove both `filename` and `filename*`.
	 *
	 * ```php
	 * $cd->setFilename('report.pdf');
	 * // → filename="report.pdf"
	 *
	 * $cd->setFilename('Quarterly Résumé.pdf');
	 * // → filename="Quarterly R_sum_.pdf"; filename*=UTF-8''Quarterly%20R%C3%A9sum%C3%A9.pdf
	 * ```
	 *
	 * @param ?string $filename the filename to set, or `null` to remove.
	 */
	public function setFilename(?string $filename): void
	{
		if ($filename === null) {
			$this->removeParameter('filename');
			$this->removeParameter('filename*');
			return;
		}

		// Replace non-ASCII code points with '_' for the ASCII fallback.
		// The 'u' flag ensures multi-byte characters are matched as one unit,
		// so each non-ASCII character (e.g. 'é') produces a single '_'.
		$ascii = preg_replace('/[^\x20-\x7E]/u', '_', $filename);
		$this->setParameter('filename', $ascii);

		// Emit filename* only when the name actually contains non-ASCII chars.
		if ($ascii !== $filename) {
			$this->setParameter('filename*', "UTF-8''" . rawurlencode($filename));
		} else {
			$this->removeParameter('filename*');
		}
	}

	// =========================================================================
	// Rendering
	// =========================================================================

	/**
	 * Renders the full `Content-Disposition` value,
	 * e.g. `attachment; filename="report.pdf"`.
	 *
	 * Parameter values are passed through {@see encodeQuotedString()}: valid RFC 9110
	 * tokens are emitted as-is; anything else is wrapped in a quoted-string.
	 * The `filename*` parameter is always emitted without quoting — it uses
	 * RFC 5987 percent-encoding instead.
	 *
	 * @return string the rendered disposition value.
	 */
	public function __toString(): string
	{
		$params = $this->getParameters();
		if (empty($params)) {
			return $this->getType();
		}
		$parts = [$this->getType()];
		foreach ($params as $name => $value) {
			// filename* uses RFC 5987 encoding — never wrap in quotes.
			if ($name === 'filename*') {
				$parts[] = $name . '=' . $value;
			} else {
				$parts[] = $name . '=' . self::encodeQuotedString($value);
			}
		}
		return implode('; ', $parts);
	}

	// =========================================================================
	// Static helpers
	// =========================================================================

	/**
	 * Quotes an HTTP header parameter value according to RFC 9110 §5.6.2–5.6.4
	 * (HTTP Semantics; RFC 9110 supersedes RFC 7230).
	 *
	 * When `$value` consists entirely of **tchar** characters (a valid *token*)
	 * it is returned verbatim. Otherwise it is enclosed in double quotes
	 * (a *quoted-string*) with any internal `\` and `"` characters
	 * backslash-escaped.
	 *
	 * ```
	 * token          = 1*tchar
	 * tchar          = "!" / "#" / "$" / "%" / "&" / "'" / "*" / "+" / "-" /
	 *                  "." / "^" / "_" / "`" / "|" / "~" / DIGIT / ALPHA
	 * quoted-string  = DQUOTE *( qdtext / quoted-pair ) DQUOTE
	 * quoted-pair    = "\" ( HTAB / SP / VCHAR / obs-text )
	 * ```
	 *
	 * ```php
	 * TContentDisposition::encodeQuotedString('report.pdf');    // 'report.pdf'        — token, no quotes
	 * TContentDisposition::encodeQuotedString('my file.pdf');   // '"my file.pdf"'     — space requires quotes
	 * TContentDisposition::encodeQuotedString('say "hi"');      // '"say \"hi\""'      — quote escaped
	 * TContentDisposition::encodeQuotedString('résumé.pdf');    // '"résumé.pdf"'      — non-ASCII requires quotes
	 * ```
	 *
	 * **Note:** the `filename*` parameter uses RFC 5987 percent-encoding and
	 * must never be passed to this method — {@see __toString()} always emits
	 * it unquoted.
	 *
	 * @param string $value raw parameter value
	 * @return string the value as a token or `"quoted-string"`
	 * @see https://www.rfc-editor.org/rfc/rfc9110#section-5.6.2 RFC 9110 §5.6.2 (token / tchar)
	 * @see https://www.rfc-editor.org/rfc/rfc9110#section-5.6.4 RFC 9110 §5.6.4 (quoted-string)
	 */
	public static function encodeQuotedString(string $value): string
	{
		// token = 1*tchar  (tchar excludes whitespace, CTLs, and delimiter chars)
		if (preg_match("/^[!#\$%&'*+\-.^_`|~\w]+$/", $value)) {
			return $value;
		}
		return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
	}

	/**
	 * Decodes an RFC 5987 extended parameter value (`charset'language'pct-encoded`).
	 *
	 * Only the `UTF-8` charset is decoded; other charsets are returned with the
	 * percent-encoding intact. Language tags are silently ignored.
	 *
	 * ```php
	 * TContentDisposition::decodeRfc5987("UTF-8''report%20Q4.pdf"); // 'report Q4.pdf'
	 * TContentDisposition::decodeRfc5987("UTF-8''R%C3%A9sum%C3%A9.pdf"); // 'Résumé.pdf'
	 * ```
	 *
	 * @param string $extValue e.g. `UTF-8''foo%20bar.pdf`
	 * @return string decoded string, or the original value when unparseable.
	 * @see https://www.rfc-editor.org/rfc/rfc5987 RFC 5987 (Character Encoding in HTTP Header Fields)
	 */
	public static function decodeRfc5987(string $extValue): string
	{
		// Format: charset'language'pct-encoded-value
		if (!preg_match("/^([^']+)'([^']*)'(.+)$/", $extValue, $m)) {
			return $extValue;
		}
		$charset = $m[1];
		$encoded = $m[3];
		if (strtolower($charset) === 'utf-8') {
			return rawurldecode($encoded);
		}
		return $extValue;
	}
}
