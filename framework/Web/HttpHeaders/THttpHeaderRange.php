<?php

/**
 * THttpHeaderRange class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\TPropertyValue;
use Prado\Web\THttpHeaderName;

/**
 * THttpHeaderRange class
 *
 * THttpHeaderRange parses the `Range` request header (RFC 9110 §14.2) into its
 * unit and range set, and resolves a single byte range against a representation
 * size for {@see \Prado\Web\THttpResponse::writeFile()}.
 *
 * ```php
 * $range = new THttpHeaderRange();
 * $range->setHeaderValue($request->getHeader(THttpHeaderName::Range));
 * $span = $range->resolve($fileSize);
 * ```
 *
 * Each range in {@see getRanges()} is a `[first, last]` pair:
 *
 * | Value | Pair | Meaning |
 * |---|---|---|
 * | `bytes=0-499` | `[0, 499]` | bytes 0 through 499 |
 * | `bytes=500-` | `[500, null]` | byte 500 through the end |
 * | `bytes=-500` | `[null, 500]` | the final 500 bytes |
 *
 * A value that breaks the grammar leaves no ranges.  A position too large for
 * an integer saturates at `PHP_INT_MAX`.  {@see resolve()} returns:
 *
 * | Header | Result |
 * |---|---|
 * | invalid, a unit other than `bytes`, or more than one range | `null`, the full representation is sent |
 * | a single range within the representation | `[start, end]`, inclusive and clamped to the size |
 * | a single range outside the representation | `false`, the response is `416 Range Not Satisfiable` |
 *
 * Ignoring a multiple-range request is permitted by RFC 9110 §14.2, and avoids a
 * `multipart/byteranges` body.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see THttpHeaderName::Range
 * @see \Prado\Web\THttpResponse::getAcceptRanges()
 */
class THttpHeaderRange extends TBaseHttpHeader
{
	/**
	 * The byte range unit, the only unit {@see resolve()} serves.
	 */
	public const UNIT_BYTES = 'bytes';

	/**
	 * @var string The range unit, in lowercase; '' when the value is invalid.
	 */
	private string $_unit = '';

	/**
	 * @var list<array{0: ?int, 1: ?int}> The `[first, last]` pairs of the range set.
	 */
	private array $_ranges = [];

	/**
	 * @return string `'Range'`.
	 */
	public function getHeaderName(): string
	{
		return THttpHeaderName::Range;
	}

	/**
	 * @return string The range unit in lowercase, such as `bytes`; '' when the value is invalid.
	 */
	public function getUnit(): string
	{
		return $this->_unit;
	}

	/**
	 * @return list<array{0: ?int, 1: ?int}> The `[first, last]` pairs; empty when the value is invalid.
	 */
	public function getRanges(): array
	{
		return $this->_ranges;
	}

	/**
	 * @return bool Whether the value parsed into a unit and at least one range.
	 */
	public function getIsValid(): bool
	{
		return $this->getRanges() !== [];
	}

	/**
	 * Renders the unit and range set, such as `bytes=0-499,-500`; '' when the value is invalid.
	 * @return string The header value.
	 */
	public function getHeaderValue(): string
	{
		if (!$this->getIsValid()) {
			return '';
		}
		$specs = [];
		foreach ($this->getRanges() as [$first, $last]) {
			$specs[] = $first === null ? '-' . $last : $first . '-' . ($last ?? '');
		}
		return $this->getUnit() . '=' . implode(',', $specs);
	}

	/**
	 * Parses a `Range` value.  The unit matches without regard to case, and whitespace
	 * may surround each range.  A value that breaks the grammar, including a range whose
	 * last position precedes its first, leaves no unit and no ranges.
	 * @param mixed $value The header value, such as `bytes=0-499`; null clears the header.
	 */
	public function setHeaderValue($value): void
	{
		$this->_unit = '';
		$this->_ranges = [];
		if ($value === null) {
			return;
		}
		$value = trim(TPropertyValue::ensureString($value));
		if (!preg_match('/^([!#$%&\'*+.^_`|~0-9A-Za-z-]+)=(.*)$/s', $value, $match)) {
			return;
		}
		$ranges = [];
		foreach (explode(',', $match[2]) as $spec) {
			$spec = trim($spec);
			if ($spec === '') {
				continue;
			}
			if (!preg_match('/^(\d*)-(\d*)$/', $spec, $pos) || ($pos[1] === '' && $pos[2] === '')) {
				return;
			}
			$first = $pos[1] === '' ? null : static::toPosition($pos[1]);
			$last = $pos[2] === '' ? null : static::toPosition($pos[2]);
			if ($first !== null && $last !== null && $last < $first) {
				return;
			}
			$ranges[] = [$first, $last];
		}
		if ($ranges === []) {
			return;
		}
		$this->_unit = strtolower($match[1]);
		$this->_ranges = $ranges;
	}

	/**
	 * Resolves the range against a representation size.  Only a single `bytes` range
	 * resolves; any other valid or invalid value returns null.
	 * @param int $size The representation size in bytes.
	 * @return null|array{0: int, 1: int}|false The inclusive `[start, end]` span, false when
	 *   the range is unsatisfiable, or null when the header is to be ignored.
	 */
	public function resolve(int $size): array|false|null
	{
		$ranges = $this->getRanges();
		if ($this->getUnit() !== self::UNIT_BYTES || count($ranges) !== 1) {
			return null;
		}
		[$first, $last] = $ranges[0];
		if ($first === null) {
			if ($last === 0 || $size <= 0) {
				return false;
			}
			return [max(0, $size - $last), $size - 1];
		}
		if ($first >= $size) {
			return false;
		}
		return [$first, ($last === null || $last >= $size) ? $size - 1 : $last];
	}

	/**
	 * Converts a run of digits to a byte position, saturating at `PHP_INT_MAX`.
	 * @param string $digits The decimal digits.
	 * @return int The byte position.
	 */
	protected static function toPosition(string $digits): int
	{
		$digits = ltrim($digits, '0');
		if ($digits === '') {
			return 0;
		}
		$max = (string) PHP_INT_MAX;
		if (strlen($digits) > strlen($max) || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) {
			return PHP_INT_MAX;
		}
		return (int) $digits;
	}
}
