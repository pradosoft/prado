<?php

/**
 * TTime class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\I18N\core\CultureInfoUnits;
use Prado\I18N\core\TIntlDateFormatterTrait;
use Prado\TPropertyValue;
use Prado\Web\UI\THtmlWriter;

/**
 * TTime class
 *
 * TTime renders the HTML5 `<time>` element. The `datetime` attribute always holds a
 * valid HTML5 datetime value derived from `DateTime` and formatted by `DateTimeFormat`.
 *
 * Properties:
 * - <b>DateTime</b>, `DateTimeInterface|DateInterval|string|int|float` — the value to
 *   encode. Accepted inputs: `\DateTimeInterface`, `\DateInterval`, an ISO 8601
 *   duration string (e.g. `P1Y2M`), a strtotime-parseable date string, a Unix
 *   timestamp integer, or a numeric string (fractional seconds are truncated to int).
 * - <b>DateTimeFormat</b>, {@see TTimeFormat} — format of the `datetime=""` attribute.
 *   Default: `HtmlDateTime`.
 * - <b>TextFormat</b>, `TTimeFormat|ICU pattern|null` — format of the visible text
 *   content. Accepts a {@see TTimeFormat} constant name or an ICU datetime pattern
 *   (e.g. `"MMMM d, yyyy"`, `"HH:mm"`). When set, child controls are ignored.
 *   Default: `null`.
 *
 * **Visible text content** is determined in priority order:
 * 1. **`TextFormat` is set** — formats `DateTime` using the given {@see TTimeFormat}
 *    constant name or ICU datetime pattern; child controls are ignored.
 * 2. **Child controls are present** — their rendered output is captured and resolved:
 *    - A {@see TTimeFormat} constant name (case-insensitive) → `DateTime` formatted in
 *      that style via {@see \IntlDateFormatter}.
 *    - A plain-text string containing at least one ICU format letter (see
 *      {@see isIcuPattern}) when `DateTime` is a `DateTimeInterface` → formatted as an
 *      ICU datetime pattern via {@see \IntlDateFormatter}; written as-is on failure.
 *    - Any other content (HTML markup, plain text, no `DateTime` set) → written as-is.
 * 3. **No children, no `TextFormat`** — `DateTime` is formatted using `DateTimeFormat`
 *    as the text style, so the visible text matches the `datetime` attribute category.
 *
 * **Negative intervals:** `DateInterval::$invert === 1` (produced by
 * `$later->diff($earlier)`) is supported in visible text — the output is prefixed with
 * `"-"`. The `datetime` attribute always renders positive; the HTML5 duration syntax
 * has no negative form.
 *
 * Culture and charset are inherited from {@see TI18NWebControl}.
 *
 * **Template examples:**
 * ```xml
 * <!-- Date only: machine-readable attribute and localized text, both as date -->
 * <com:TTime DateTime="2024-06-15" DateTimeFormat="Date" TextFormat="Date" />
 * <!-- renders: <time datetime="2024-06-15">June 15, 2024</time> -->
 *
 * <!-- Global datetime with timezone in both attribute and visible text -->
 * <com:TTime DateTime="2024-06-15T10:30:00+00:00"
 *     DateTimeFormat="HtmlDateTimeTimezone"
 *     TextFormat="HtmlDateTimeTimezone" />
 * <!-- renders: <time datetime="2024-06-15T10:30:00+00:00">June 15, 2024 at 10:30:00 AM Coordinated Universal Time</time> -->
 *
 * <!-- ISO 8601 duration; DateTimeFormat has no effect on DateInterval values -->
 * <com:TTime DateTime="P1Y6M" />
 * <!-- renders: <time datetime="P1Y6M">1 year 6 months</time> -->
 *
 * <!-- TextFormat as a TTimeFormat name -->
 * <com:TTime DateTime="2024-06-15T10:30:00Z" TextFormat="Date" />
 * <!-- renders: <time datetime="2024-06-15T10:30:00Z">June 15, 2024</time> -->
 *
 * <!-- TextFormat as an ICU datetime pattern -->
 * <com:TTime DateTime="2024-06-15T10:30:00Z" TextFormat="MMMM d, yyyy" />
 * <!-- renders: <time datetime="2024-06-15T10:30:00Z">June 15, 2024</time> -->
 *
 * <!-- Child text as a TTimeFormat name: resolved to localized datetime -->
 * <com:TTime DateTime="2024-06-15T10:30:00Z">Date</com:TTime>
 * <!-- renders: <time datetime="2024-06-15T10:30:00Z">June 15, 2024</time> -->
 *
 * <!-- Child text as an ICU pattern: formatted by IntlDateFormatter -->
 * <com:TTime DateTime="2024-06-15T10:30:00Z">MMMM d, yyyy</com:TTime>
 * <!-- renders: <time datetime="2024-06-15T10:30:00Z">June 15, 2024</time> -->
 *
 * <!-- Child text as arbitrary markup: passed through unchanged -->
 * <com:TTime DateTime="2024-06-15T10:30:00Z">
 *     Posted on <b>June 15</b>
 * </com:TTime>
 * <!-- renders: <time datetime="2024-06-15T10:30:00Z">Posted on <b>June 15</b></time> -->
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TTimeFormat
 * @see \DateInterval
 * @see \DateTimeImmutable
 * @see \DateTimeInterface
 * @see \IntlDateFormatter
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-time-element
 * @since 4.4.0
 */
class TTime extends TI18NWebControl
{
	use TIntlDateFormatterTrait;

	/**
	 * Returns whether `$pattern` contains at least one recognized ICU date/time format
	 * letter outside single-quoted literal sections.
	 *
	 * Single-quoted sections (`'...'`, with `''` as an escaped quote) are stripped before
	 * the check so that quoted literals do not trigger a false positive. The recognized
	 * letters are the full set defined by the ICU datetime pattern syntax:
	 * `G y Y u U r Q q M L w W d D F g E e c a b B h H k K m s S A z Z O v V X x`.
	 *
	 * A string that passes this check is a candidate for `IntlDateFormatter`; one that
	 * does not contain any of these letters is definitively not a pattern and is written
	 * as-is without an ICU formatting attempt.
	 *
	 * @param string $pattern candidate ICU datetime pattern string
	 * @return bool true when `$pattern` contains at least one ICU format letter
	 */
	public static function isIcuPattern(string $pattern): bool
	{
		$stripped = preg_replace("/'(?:[^']|'')*'/", '', $pattern) ?? $pattern;
		return (bool) preg_match('/[GyYuUrQqMLwWdDFgEecabBhHkKmsSAzZOvVXx]/', $stripped);
	}

	/**
	 * Returns the HTML tag name for this control.
	 * @return string `'time'`
	 */
	protected function getTagName()
	{
		return 'time';
	}

	/**
	 * Returns the stored datetime value.
	 *
	 * The return type mirrors what was passed to {@see setDateTime}: a
	 * `DateTimeInterface`, a `DateInterval`, a raw fallback string, or `''` when
	 * nothing has been set.
	 *
	 * @return DateInterval|DateTimeInterface|string the stored value, or `''` when unset
	 */
	public function getDateTime()
	{
		return $this->getViewState('DateTime', '');
	}

	/**
	 * Sets the datetime value encoded in the `datetime` attribute and text content.
	 *
	 * Resolution order for string and numeric inputs:
	 * 1. `DateInterval` or `DateTimeInterface` instances are stored directly.
	 * 2. A numeric value (int, float, or numeric string) is used as a Unix timestamp
	 *    (fractional seconds are truncated via `(int)` cast).
	 * 3. A non-numeric string is first tried as an ISO 8601 duration (`DateInterval`),
	 *    then as a `DateTimeImmutable` constructor argument, then via `strtotime()`.
	 * 4. If all parsing fails, the raw string is stored and rendered as-is.
	 *
	 * @param DateInterval|DateTimeInterface|float|int|string $value the datetime value
	 * @throws TInvalidDataTypeException when `$value` is none of the accepted types
	 */
	public function setDateTime($value)
	{
		if ($value instanceof DateInterval || $value instanceof DateTimeInterface) {
			$this->setViewState('DateTime', $value);
			return;
		}

		$isNumeric = is_numeric($value);

		if (is_string($value) || $isNumeric) {
			if (!$isNumeric) {
				try {
					$this->setViewState('DateTime', new DateInterval($value));
					return;
				} catch (\Exception $e) {
				}

				try {
					$this->setViewState('DateTime', new DateTimeImmutable($value));
					return;
				} catch (\Exception $e) {
				}

				$timestamp = @strtotime($value);
			} else {
				$timestamp = (float) $value;
			}

			if ($timestamp !== false) {
				$this->setViewState('DateTime', (new DateTimeImmutable())->setTimestamp((int) $timestamp));
				return;
			}

			$this->setViewState('DateTime', $value);
			return;
		}
		throw new TInvalidDataTypeException('time_invalid_datetime', get_debug_type($value));
	}

	/**
	 * Returns the format used for the `datetime` HTML attribute.
	 * @return string a {@see TTimeFormat} constant value; default is `HtmlDateTime`
	 */
	public function getDateTimeFormat()
	{
		return $this->getViewState('DateTimeFormat', TTimeFormat::HtmlDateTime);
	}

	/**
	 * Sets the format used for the `datetime` HTML attribute.
	 * @param string $value a {@see TTimeFormat} constant value
	 */
	public function setDateTimeFormat($value)
	{
		$this->setViewState('DateTimeFormat', TPropertyValue::ensureEnum($value, TTimeFormat::class), TTimeFormat::HtmlDateTime);
	}

	/**
	 * Returns the text content format, or `null` when not set.
	 * @return ?string a {@see TTimeFormat} constant name, an ICU datetime pattern, or `null`
	 */
	public function getTextFormat(): ?string
	{
		return $this->getViewState('TextFormat', null);
	}

	/**
	 * Sets the format for the visible text content of the `<time>` element.
	 *
	 * Accepts a {@see TTimeFormat} constant name (e.g. `"Date"`, `"HtmlDateTime"`) or any
	 * ICU datetime pattern containing at least one recognized format letter (e.g. `"MMMM d,
	 * yyyy"`, `"HH:mm"`). See {@see isIcuPattern} for the letter set. When set, `TextFormat`
	 * takes priority over child controls and `DateTimeTextFormat`.
	 *
	 * Pass `null` or `''` to clear the property and restore default resolution.
	 *
	 * @param ?string $value a {@see TTimeFormat} constant name, an ICU datetime pattern, or `null`
	 * @throws TInvalidDataValueException when `$value` is neither a `TTimeFormat` name nor an ICU pattern
	 */
	public function setTextFormat(?string $value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('TextFormat', null, null);
			return;
		}
		try {
			TPropertyValue::ensureEnum($value, TTimeFormat::class);
		} catch (\Exception $e) {
			if (!static::isIcuPattern($value)) {
				throw new TInvalidDataValueException('time_invalid_text_format', $value);
			}
		}
		$this->setViewState('TextFormat', $value, null);
	}

	/**
	 * Adds the `datetime` attribute to the renderer when a DateTime value is set.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($dateTime = $this->getDateTime()) !== '') {
			$writer->addAttribute('datetime', $this->formatValue($dateTime));
		}
	}

	/**
	 * Renders the text content of the `<time>` element.
	 *
	 * When `TextFormat` is set and a `DateTime` is available, the text content is
	 * produced by {@see formatTextValue} with `TextFormat` as the format; children and
	 * `DateTimeTextFormat` are ignored.
	 *
	 * Otherwise, when children are present and a `DateTime` is available, the children
	 * are buffered and their rendered text is resolved in order:
	 * 1. A valid {@see TTimeFormat} constant name (case-insensitive) → formatted via
	 *    {@see formatTextValue} with that format.
	 * 2. A plain-text string (no `<` character) containing at least one ICU format letter
	 *    (see {@see isIcuPattern}) with a `DateTimeInterface` value → formatted via
	 *    {@see getIntlDateFormatter}; falls back to the children's content as-is when
	 *    the pattern is invalid or `IntlDateFormatter` produces no output.
	 * 3. Any other string with a `DateInterval` or raw string value → written as-is.
	 *
	 * When no children are present, the `DateTime` value is formatted via
	 * {@see formatTextValue} using the `DateTimeFormat` value as the text format.
	 *
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$dateTime = $this->getDateTime();
		if ($dateTime !== '' && ($textFormat = $this->getTextFormat()) !== null) {
			$writer->write($this->formatTextValue($dateTime, $textFormat));
			return;
		}

		if ($this->getControls()->getCount() > 0) {
			$dateTime = $this->getDateTime();
			if ($dateTime !== '') {
				$bufWriter = new THtmlWriter();
				parent::renderContents($bufWriter);
				$childContent = trim($bufWriter->flush());
				try {
					$format = TPropertyValue::ensureEnum($childContent, TTimeFormat::class);
					$writer->write($this->formatTextValue($dateTime, $format));
				} catch (\Exception $e) {
					$formatted = null;
					if ($dateTime instanceof DateTimeInterface
						&& strpos($childContent, '<') === false
						&& static::isIcuPattern($childContent)
					) {
						$formatter = $this->getIntlDateFormatter(
							$this->getCulture(),
							IntlDateFormatter::NONE,
							IntlDateFormatter::NONE,
							$childContent
						);
						if ($formatter !== null) {
							$result = $formatter->format($dateTime);
							if ($result !== false) {
								$formatted = (string) $result;
							}
						}
					}
					$writer->write($formatted ?? $childContent);
				}
				return;
			}
			parent::renderContents($writer);
		} else {
			if (($dateTime = $this->getDateTime()) !== '') {
				$writer->write($this->formatTextValue($dateTime, $this->getDateTimeFormat()));
			}
		}
	}

	/**
	 * Dispatches to {@see formatInterval}, {@see formatDateTime}, or string cast
	 * depending on the type of `$value`.
	 * @param DateInterval|DateTimeInterface|string $value the stored datetime value
	 * @return string machine-readable representation for the `datetime` attribute
	 */
	protected function formatValue($value): string
	{
		if ($value instanceof \DateInterval) {
			return $this->formatInterval($value);
		}

		if ($value instanceof \DateTimeInterface) {
			return $this->formatDateTime($value);
		}

		return (string) $value;
	}

	/**
	 * Formats a `DateInterval` as a valid HTML5 duration string for the `datetime` attribute.
	 *
	 * Fractional seconds stored in `DateInterval::$f` (range `0.0 ≤ f < 1.0`) are
	 * rendered as a fractional-seconds suffix (e.g. `PT1.500S`); when the fractional
	 * value rounds to 0 ms the suffix is omitted. `DateInterval::$invert` is not
	 * inspected; the HTML5 duration syntax has no negative form so the output is always
	 * a positive duration string.
	 *
	 * @param DateInterval $i the interval to format
	 * @return string valid HTML5 duration string, e.g. `P1Y2M3DT4H5M6S` or `PT0S` for zero duration
	 */
	protected function formatInterval(DateInterval $i): string
	{
		$date = '';
		if ($i->y) {
			$date .= $i->y . 'Y';
		}
		if ($i->m) {
			$date .= $i->m . 'M';
		}
		if ($i->d) {
			$date .= $i->d . 'D';
		}

		$time = '';
		if ($i->h) {
			$time .= $i->h . 'H';
		}
		if ($i->i) {
			$time .= $i->i . 'M';
		}
		if ($i->s || $i->f) {
			$seconds = $i->s;
			if ($i->f) {
				// f is fractional seconds (0.0 ≤ f < 1.0); format as milliseconds suffix
				$ms = (int) round($i->f * 1000);
				if ($ms) {
					$time .= $seconds . '.' . str_pad($ms, 3, '0', STR_PAD_LEFT) . 'S';
				} else {
					$time .= $seconds . 'S';
				}
			} else {
				$time .= $seconds . 'S';
			}
		}

		if ($time !== '') {
			return 'P' . $date . 'T' . $time;
		}
		if ($date !== '') {
			return 'P' . $date;
		}
		return 'PT0S';
	}

	/**
	 * Formats a `DateTimeInterface` as a valid HTML5 datetime value for the `datetime`
	 * attribute, using the format selected by {@see getDateTimeFormat}.
	 *
	 * Each {@see TTimeFormat} constant maps to one of the valid datetime value
	 * categories defined by the WHATWG HTML specification: valid time string,
	 * valid date string, valid month string, valid week string, valid yearless date
	 * string, valid year string, valid local date and time string, or valid global
	 * date and time string. Global date and time formats use PHP's `P` specifier,
	 * which produces an ISO 8601 offset such as `+00:00` or `-05:00`. When no case
	 * matches (unreachable in normal operation since `DateTimeFormat` is always a
	 * valid {@see TTimeFormat} constant), falls back to `HtmlDateTimeTimezone` format.
	 *
	 * @param DateTimeInterface $dt the datetime to format
	 * @return string valid HTML5 datetime value string
	 */
	protected function formatDateTime(DateTimeInterface $dt): string
	{
		switch ($this->getDateTimeFormat()) {
			case TTimeFormat::TimeShort:
				return $dt->format('H:i');
			case TTimeFormat::Time:
				return $dt->format('H:i:s');
			case TTimeFormat::TimePrecise:
				return $dt->format('H:i:s.v');

			case TTimeFormat::Date:
				return $dt->format('Y-m-d');
			case TTimeFormat::Month:
				return $dt->format('Y-m');
			case TTimeFormat::Week:
				return $dt->format('Y-\WW');
			case TTimeFormat::YearlessDate:
				return $dt->format('m-d');
			case TTimeFormat::Year:
				return $dt->format('Y');

			case TTimeFormat::DateTimeShort:
				return $dt->format('Y-m-d H:i');
			case TTimeFormat::DateTime:
				return $dt->format('Y-m-d H:i:s');
			case TTimeFormat::DateTimePrecise:
				return $dt->format('Y-m-d H:i:s.v');
			case TTimeFormat::HtmlDateTimeShort:
				return $dt->format('Y-m-d\TH:i');
			case TTimeFormat::HtmlDateTime:
				return $dt->format('Y-m-d\TH:i:s');
			case TTimeFormat::HtmlDateTimePrecise:
				return $dt->format('Y-m-d\TH:i:s.v');

			case TTimeFormat::DateTimeShortTimezone:
				return $dt->format('Y-m-d H:iP');
			case TTimeFormat::DateTimeTimezone:
				return $dt->format('Y-m-d H:i:sP');
			case TTimeFormat::DateTimePreciseTimezone:
				return $dt->format('Y-m-d H:i:s.vP');
			case TTimeFormat::HtmlDateTimeShortTimezone:
				return $dt->format('Y-m-d\TH:iP');
			case TTimeFormat::HtmlDateTimeTimezone:
				return $dt->format('Y-m-d\TH:i:sP');
			case TTimeFormat::HtmlDateTimePreciseTimezone:
				return $dt->format('Y-m-d\TH:i:s.vP');

			default:
				return $dt->format('Y-m-d\TH:i:sP');
		}
	}

	/**
	 * Dispatches to {@see formatTextInterval}, {@see formatTextDateTime}, or string
	 * cast depending on the type of `$value`.
	 * @param DateInterval|DateTimeInterface|string $value the stored datetime value
	 * @param ?string $format {@see TTimeFormat} override; `null` reads from view state
	 * @return string human-readable representation for the element's text content
	 */
	protected function formatTextValue($value, ?string $format = null)
	{
		if ($value instanceof \DateInterval) {
			return $this->formatTextInterval($value, $format);
		}

		if ($value instanceof \DateTimeInterface) {
			return $this->formatTextDateTime($value, $format);
		}

		return (string) $value;
	}

	/**
	 * Formats a `DateInterval` as a localized human-readable duration string.
	 *
	 * Each non-zero component (years, months, weeks, days, hours, minutes, seconds,
	 * milliseconds) is formatted via `CultureInfo::formatUnit` and joined with spaces.
	 * Days are decomposed into whole weeks plus remaining days before formatting.
	 * When `DateInterval::$invert === 1` (a negative interval produced by
	 * `$later->diff($earlier)`), the result is prefixed with `"-"`.
	 * A zero-duration interval returns an empty string.
	 *
	 * `$format` is accepted for signature consistency with {@see formatTextDateTime}
	 * but has no effect on interval formatting.
	 *
	 * @param DateInterval $interval the interval to format
	 * @param ?string $format accepted but unused
	 * @return string space-joined localized duration components, prefixed with `"-"` for
	 *   negative intervals, or `''` for zero duration
	 */
	protected function formatTextInterval(DateInterval $interval, ?string $format = null): string
	{
		$cultureInfo = $this->getCultureInfo();

		$components = [];
		if ($interval->y) {
			$components[] = $cultureInfo->formatUnit($interval->y, CultureInfoUnits::TYPE_DURATION_YEAR);
		}
		if ($interval->m) {
			$components[] = $cultureInfo->formatUnit($interval->m, CultureInfoUnits::TYPE_DURATION_MONTH);
		}
		if ($interval->d) {
			$weeks = floor($interval->d / 7);
			$days = $interval->d % 7;
			if ($weeks) {
				$components[] = $cultureInfo->formatUnit($weeks, CultureInfoUnits::TYPE_DURATION_WEEK);
			}
			if ($days) {
				$components[] = $cultureInfo->formatUnit($days, CultureInfoUnits::TYPE_DURATION_DAY);
			}
		}
		if ($interval->h) {
			$components[] = $cultureInfo->formatUnit($interval->h, CultureInfoUnits::TYPE_DURATION_HOUR);
		}
		if ($interval->i) {
			$components[] = $cultureInfo->formatUnit($interval->i, CultureInfoUnits::TYPE_DURATION_MINUTE);
		}
		if ($interval->s) {
			$components[] = $cultureInfo->formatUnit($interval->s, CultureInfoUnits::TYPE_DURATION_SECOND);
		}
		if ($interval->f) {
			// f is fractional seconds (0.0–1.0); convert to whole milliseconds for display
			$ms = (int) round($interval->f * 1000);
			if ($ms) {
				$components[] = $cultureInfo->formatUnit($ms, CultureInfoUnits::TYPE_DURATION_MILLISECOND);
			}
		}

		$result = implode(' ', $components);
		return ($interval->invert && $result !== '') ? '-' . $result : $result;
	}

	/**
	 * Formats a `DateTimeInterface` as a localized human-readable string using
	 * {@see \IntlDateFormatter}.
	 *
	 * When `$format` is a {@see TTimeFormat} constant name (or `null`, which defaults to
	 * `HtmlDateTime`), the mapping selects ICU date/time type constants:
	 * - Valid time strings (`TimeShort`, `Time`, `TimePrecise`): `dateType=NONE`, `timeType=LONG`
	 * - Valid date strings (`Date`, `Month`, `Week`, `YearlessDate`, `Year`): `dateType=LONG`,
	 *   `timeType=NONE`. All sub-precision date formats produce a full long-form date
	 *   (e.g. "June 15, 2024") until ICU custom patterns are introduced in a future release.
	 * - Valid local date and time strings, short (`DateTimeShort`, `HtmlDateTimeShort`):
	 *   `dateType=LONG`, `timeType=SHORT`
	 * - Valid local date and time strings (`DateTime`, `DateTimePrecise`, `HtmlDateTime`,
	 *   `HtmlDateTimePrecise`): `dateType=LONG`, `timeType=LONG`
	 * - Valid global date and time strings: `dateType=LONG`, `timeType=FULL` (ensures
	 *   timezone is always shown)
	 *
	 * When `$format` is not a `TTimeFormat` constant, it is treated as an ICU datetime
	 * pattern (e.g. `"MMMM d, yyyy"`) and passed directly to `IntlDateFormatter`. Falls
	 * back to the ISO datetime string `Y-m-d\TH:i:sP` when `IntlDateFormatter` is
	 * unavailable or `format()` returns `false`.
	 *
	 * @todo When minimum PHP requirement is 8.4, replace the type-constant approach
	 *   with ICU custom patterns to support sub-precision date rendering.
	 * @param DateTimeInterface $dt the datetime to format
	 * @param ?string $format {@see TTimeFormat} constant or an ICU datetime pattern;
	 *   `null` defaults to `HtmlDateTime` (internal calls always pass a format)
	 * @return string localized human-readable datetime string
	 */
	protected function formatTextDateTime(DateTimeInterface $dt, ?string $format = null): string
	{
		$dateType = IntlDateFormatter::LONG;
		$timeType = IntlDateFormatter::LONG;

		switch ($format ?? TTimeFormat::HtmlDateTime) {
			case TTimeFormat::TimeShort:
			case TTimeFormat::Time:
			case TTimeFormat::TimePrecise:
				$dateType = IntlDateFormatter::NONE;
				break;

			case TTimeFormat::Date:
			case TTimeFormat::Month:
			case TTimeFormat::Week:
			case TTimeFormat::YearlessDate:
			case TTimeFormat::Year:
				$timeType = IntlDateFormatter::NONE;
				break;

			case TTimeFormat::DateTimeShort:
			case TTimeFormat::HtmlDateTimeShort:
				$timeType = IntlDateFormatter::SHORT;
				break;

			case TTimeFormat::DateTime:
			case TTimeFormat::DateTimePrecise:
			case TTimeFormat::HtmlDateTime:
			case TTimeFormat::HtmlDateTimePrecise:
				break;

			case TTimeFormat::DateTimeShortTimezone:
			case TTimeFormat::HtmlDateTimeShortTimezone:
			case TTimeFormat::DateTimeTimezone:
			case TTimeFormat::DateTimePreciseTimezone:
			case TTimeFormat::HtmlDateTimeTimezone:
			case TTimeFormat::HtmlDateTimePreciseTimezone:
				// FULL ensures timezone is always visible regardless of locale
				$timeType = IntlDateFormatter::FULL;
				break;
			default:
				// Treat $format as an ICU datetime pattern (e.g. "MMMM d, yyyy")
				if ($format !== null) {
					$formatter = $this->getIntlDateFormatter(
						$this->getCulture(),
						IntlDateFormatter::NONE,
						IntlDateFormatter::NONE,
						$format
					);
					if ($formatter !== null && ($result = $formatter->format($dt)) !== false) {
						return (string) $result;
					}
				}
				return $dt->format('Y-m-d\TH:i:sP');
		}

		$formatter = $this->getIntlDateFormatter($this->getCulture(), $dateType, $timeType);

		return ($formatter !== null) ? (string) $formatter->format($dt) : $dt->format('Y-m-d\TH:i:sP');
	}
}
