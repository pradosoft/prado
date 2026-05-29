<?php

/**
 * TCspViolationParameter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\TEventParameter;

/**
 * TCspViolationParameter class
 *
 * TCspViolationParameter is the event parameter passed to
 * {@see TCspReportingService::onViolation()} for every CSP violation report
 * received from a browser.
 *
 * The raw violation object is stored as the event {@see getParameter() Parameter},
 * making the full report uniformly accessible via array access:
 *
 * ```php
 * $blocked = $param[TCspViolationParameter::BLOCKED_URI_LEGACY]
 *         ?? $param[TCspViolationParameter::BLOCKED_URL];
 * ```
 *
 * Convenience getters normalize the two field-name conventions automatically:
 *
 * ```php
 * $blocked    = $param->getBlockedUrl();          // checks legacy then modern
 * $directive  = $param->getEffectiveDirective();
 * $line       = $param->getLineNumber();
 * ```
 *
 * Use {@see isModernFormat()} / {@see isLegacyFormat()} to identify which format
 * the browser sent. {@see getViolatedDirective()} returns `null` for modern reports
 * because the field does not exist in `application/reports+json`.
 *
 * **Field reference.** Each getter tries the modern key first, then falls back to
 * the legacy equivalent. Shared keys (`referrer`, `disposition`) are identical in both formats.
 *
 * | Getter | Shared constant (`key`) | Legacy constant (`key`) | Modern constant (`key`) |
 * |---|---|---|---|
 * | {@see getBlockedUrl()} | | `BLOCKED_URI_LEGACY` (`blocked-uri`) | `BLOCKED_URL` (`blockedURL`) |
 * | {@see getColumnNumber()} | | `COLUMN_NUMBER_LEGACY` (`column-number`) | `COLUMN_NUMBER` (`columnNumber`) |
 * | {@see getDisposition()} | `DISPOSITION` (`disposition`) | *(shared key)* | *(shared key)* |
 * | {@see getDocumentUrl()} | | `DOCUMENT_URI_LEGACY` (`document-uri`) | `DOCUMENT_URL` (`documentURL`) |
 * | {@see getEffectiveDirective()} | | `EFFECTIVE_DIRECTIVE_LEGACY` (`effective-directive`) | `EFFECTIVE_DIRECTIVE` (`effectiveDirective`) |
 * | {@see getLineNumber()} | | `LINE_NUMBER_LEGACY` (`line-number`) | `LINE_NUMBER` (`lineNumber`) |
 * | {@see getOriginalPolicy()} | | `ORIGINAL_POLICY_LEGACY` (`original-policy`) | `ORIGINAL_POLICY` (`originalPolicy`) |
 * | {@see getReferrer()} | `REFERRER` (`referrer`) | *(shared key)* | *(shared key)* |
 * | {@see getSample()} | | `SCRIPT_SAMPLE_LEGACY` (`script-sample`) | `SAMPLE` (`sample`) |
 * | {@see getSourceFile()} | | `SOURCE_FILE_LEGACY` (`source-file`) | `SOURCE_FILE` (`sourceFile`) |
 * | {@see getStatusCode()} | | `STATUS_CODE_LEGACY` (`status-code`) | `STATUS_CODE` (`statusCode`) |
 * | {@see getViolatedDirective()} `?string` | | `VIOLATED_DIRECTIVE_LEGACY` (`violated-directive`) | `null` (field absent in modern) |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see TCspReportingService::onViolation()
 */
class TCspViolationParameter extends TEventParameter
{
	// -----------------------------------------------------------------------
	// Shared constants  (identical key in both formats)
	// -----------------------------------------------------------------------

	/** Shared: `'enforce'` or `'report'` (same key in both formats). */
	public const DISPOSITION = 'disposition';

	/** Shared: referrer of the violating document (same key in both formats). */
	public const REFERRER = 'referrer';

	// -----------------------------------------------------------------------
	// Modern Reporting API constants  (application/reports+json → body: {…})
	// -----------------------------------------------------------------------

	/** Modern: URL of the resource that was blocked. */
	public const BLOCKED_URL = 'blockedURL';

	/** Modern: column number in the source where the violation occurred. */
	public const COLUMN_NUMBER = 'columnNumber';

	/** Modern: URL of the document in which the violation occurred. */
	public const DOCUMENT_URL = 'documentURL';

	/** Modern: the directive that was effectively enforced. */
	public const EFFECTIVE_DIRECTIVE = 'effectiveDirective';

	/** Modern: line number in the source where the violation occurred. */
	public const LINE_NUMBER = 'lineNumber';

	/** Modern: the full CSP policy string in effect at violation time. */
	public const ORIGINAL_POLICY = 'originalPolicy';

	/** Modern: first 40 characters of the inline script/style that was blocked. */
	public const SAMPLE = 'sample';

	/** Modern: URL of the script or stylesheet that triggered the violation. */
	public const SOURCE_FILE = 'sourceFile';

	/** Modern: HTTP status code of the resource that was blocked. */
	public const STATUS_CODE = 'statusCode';

	// -----------------------------------------------------------------------
	// Legacy format constants  (application/csp-report → {"csp-report": {…}})
	// -----------------------------------------------------------------------

	/** Legacy: URI of the resource that was blocked. */
	public const BLOCKED_URI_LEGACY = 'blocked-uri';

	/** Legacy: column number in the source where the violation occurred. */
	public const COLUMN_NUMBER_LEGACY = 'column-number';

	/** Legacy: URI of the document in which the violation occurred. */
	public const DOCUMENT_URI_LEGACY = 'document-uri';

	/** Legacy: the directive that was effectively enforced (may differ from violated). */
	public const EFFECTIVE_DIRECTIVE_LEGACY = 'effective-directive';

	/** Legacy: line number in the source where the violation occurred. */
	public const LINE_NUMBER_LEGACY = 'line-number';

	/** Legacy: the full CSP policy string in effect at violation time. */
	public const ORIGINAL_POLICY_LEGACY = 'original-policy';

	/** Legacy: first 40 characters of the inline script/style that was blocked. */
	public const SCRIPT_SAMPLE_LEGACY = 'script-sample';

	/** Legacy: URL of the script or stylesheet that triggered the violation. */
	public const SOURCE_FILE_LEGACY = 'source-file';

	/** Legacy: HTTP status code of the resource that was blocked. */
	public const STATUS_CODE_LEGACY = 'status-code';

	/** Legacy: the CSP directive that triggered the violation. */
	public const VIOLATED_DIRECTIVE_LEGACY = 'violated-directive';

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * The violation data array is stored as the event {@see getParameter() Parameter}
	 * so callers can access individual fields via the {@see \ArrayAccess} interface
	 * using the class constants as keys.
	 *
	 * @param array<string,mixed> $report the parsed violation object extracted
	 *   from the browser's POST body (either the `csp-report` object from the
	 *   legacy format or the `body` object from a modern Reporting API entry).
	 */
	public function __construct(array $report)
	{
		parent::__construct($report);
	}

	// -----------------------------------------------------------------------
	// Raw data access
	// -----------------------------------------------------------------------

	/**
	 * Returns the complete raw violation report as parsed from the browser's
	 * JSON body. Use this to access fields not covered by a named getter, or
	 * when you need the full report for serialisation / storage.
	 *
	 * Equivalent to {@see getParameter()}.
	 *
	 * @return array<string,mixed>
	 */
	public function getReport(): array
	{
		return (array) $this->getParameter();
	}

	// -----------------------------------------------------------------------
	// Format detection
	// -----------------------------------------------------------------------

	/**
	 * Returns `true` when the report has been identified as a modern Reporting
	 * API body (`application/reports+json`), detected by the presence of at
	 * least one camelCase field name ({@see DOCUMENT_URL} or
	 * {@see EFFECTIVE_DIRECTIVE}).
	 */
	public function isModernFormat(): bool
	{
		return parent::offsetGet(self::DOCUMENT_URL) !== null
			|| parent::offsetGet(self::EFFECTIVE_DIRECTIVE) !== null;
	}

	/**
	 * Returns `true` when the report has been identified as a legacy CSP report
	 * (`application/csp-report`), detected by the presence of at least one
	 * hyphenated field name ({@see DOCUMENT_URI_LEGACY} or
	 * {@see VIOLATED_DIRECTIVE_LEGACY}).
	 */
	public function isLegacyFormat(): bool
	{
		return parent::offsetGet(self::DOCUMENT_URI_LEGACY) !== null
			|| parent::offsetGet(self::VIOLATED_DIRECTIVE_LEGACY) !== null;
	}

	// -----------------------------------------------------------------------
	// Normalized getters — check modern key first, then legacy equivalent
	// -----------------------------------------------------------------------

	/**
	 * Returns the URL of the document in which the violation occurred,
	 * trying {@see DOCUMENT_URL} then {@see DOCUMENT_URI_LEGACY}.
	 */
	public function getDocumentUrl(): string
	{
		return (string) (parent::offsetGet(self::DOCUMENT_URL) ?? parent::offsetGet(self::DOCUMENT_URI_LEGACY) ?? '');
	}

	/**
	 * Returns the referrer of the violating document ({@see REFERRER}).
	 * The key is identical in both report formats.
	 */
	public function getReferrer(): string
	{
		return (string) (parent::offsetGet(self::REFERRER) ?? '');
	}

	/**
	 * Returns the URL of the resource that was blocked, trying
	 * {@see BLOCKED_URL} then {@see BLOCKED_URI_LEGACY}.
	 */
	public function getBlockedUrl(): string
	{
		return (string) (parent::offsetGet(self::BLOCKED_URL) ?? parent::offsetGet(self::BLOCKED_URI_LEGACY) ?? '');
	}

	/**
	 * Returns the directive that was violated ({@see VIOLATED_DIRECTIVE_LEGACY}),
	 * or `null` when the report is in modern format (the field does not exist in
	 * `application/reports+json`). Returns `''` when the field is simply absent
	 * from an otherwise-legacy report.
	 *
	 * @return ?string the violated directive, `''` if absent, or `null` for modern reports
	 */
	public function getViolatedDirective(): ?string
	{
		if ($this->isModernFormat()) {
			return null;
		}
		return (string) (parent::offsetGet(self::VIOLATED_DIRECTIVE_LEGACY) ?? '');
	}

	/**
	 * Returns the directive that was effectively enforced, trying
	 * {@see EFFECTIVE_DIRECTIVE} then {@see EFFECTIVE_DIRECTIVE_LEGACY}.
	 */
	public function getEffectiveDirective(): string
	{
		return (string) (parent::offsetGet(self::EFFECTIVE_DIRECTIVE) ?? parent::offsetGet(self::EFFECTIVE_DIRECTIVE_LEGACY) ?? '');
	}

	/**
	 * Returns the full CSP policy string in effect at violation time, trying
	 * {@see ORIGINAL_POLICY} then {@see ORIGINAL_POLICY_LEGACY}.
	 */
	public function getOriginalPolicy(): string
	{
		return (string) (parent::offsetGet(self::ORIGINAL_POLICY) ?? parent::offsetGet(self::ORIGINAL_POLICY_LEGACY) ?? '');
	}

	/**
	 * Returns `'enforce'` or `'report'` ({@see DISPOSITION}).
	 * The key is identical in both report formats.
	 */
	public function getDisposition(): string
	{
		return (string) (parent::offsetGet(self::DISPOSITION) ?? '');
	}

	/**
	 * Returns the HTTP status code of the resource that was blocked as an
	 * integer, trying {@see STATUS_CODE} then {@see STATUS_CODE_LEGACY}.
	 * Returns `0` when the field is absent.
	 */
	public function getStatusCode(): int
	{
		return (int) (parent::offsetGet(self::STATUS_CODE) ?? parent::offsetGet(self::STATUS_CODE_LEGACY) ?? 0);
	}

	/**
	 * Returns the line number where the violation occurred as an integer,
	 * trying {@see LINE_NUMBER} then {@see LINE_NUMBER_LEGACY}.
	 * Returns `0` when the field is absent.
	 */
	public function getLineNumber(): int
	{
		return (int) (parent::offsetGet(self::LINE_NUMBER) ?? parent::offsetGet(self::LINE_NUMBER_LEGACY) ?? 0);
	}

	/**
	 * Returns the column number where the violation occurred as an integer,
	 * trying {@see COLUMN_NUMBER} then {@see COLUMN_NUMBER_LEGACY}.
	 * Returns `0` when the field is absent.
	 */
	public function getColumnNumber(): int
	{
		return (int) (parent::offsetGet(self::COLUMN_NUMBER) ?? parent::offsetGet(self::COLUMN_NUMBER_LEGACY) ?? 0);
	}

	/**
	 * Returns the URL of the script or stylesheet that triggered the violation,
	 * trying {@see SOURCE_FILE} then {@see SOURCE_FILE_LEGACY}.
	 */
	public function getSourceFile(): string
	{
		return (string) (parent::offsetGet(self::SOURCE_FILE) ?? parent::offsetGet(self::SOURCE_FILE_LEGACY) ?? '');
	}

	/**
	 * Returns the sample of the blocked inline script or style, trying
	 * {@see SAMPLE} then {@see SCRIPT_SAMPLE_LEGACY}.
	 */
	public function getSample(): string
	{
		return (string) (parent::offsetGet(self::SAMPLE) ?? parent::offsetGet(self::SCRIPT_SAMPLE_LEGACY) ?? '');
	}
}
