<?php

/**
 * TTimeFormat class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTimeFormat enumeration.
 *
 * TTimeFormat selects which HTML5 `<time>` valid datetime value format {@see TTime}
 * encodes into the `datetime` attribute and, when no child controls are present, into
 * the element's visible text content. Every constant produces a string that satisfies
 * one of the valid datetime value categories defined by the WHATWG HTML specification.
 *
 * | Constant | HTML5 category | Value |
 * |---|---|---|
 * | `TimeShort` | valid time string, no seconds | `HH:MM` |
 * | `Time` | valid time string, seconds | `HH:MM:SS` |
 * | `TimePrecise` | valid time string, milliseconds | `HH:MM:SS.fff` |
 * | `Date` | valid date string | `YYYY-MM-DD` |
 * | `Month` | valid month string | `YYYY-MM` |
 * | `Week` | valid week string | `YYYY-Www` |
 * | `YearlessDate` | valid yearless date string | `MM-DD` |
 * | `Year` | valid year string | `YYYY` |
 * | `DateTimeShort` | valid local date and time string, no seconds | `YYYY-MM-DD HH:MM` |
 * | `DateTime` | valid local date and time string, seconds  | `YYYY-MM-DD HH:MM:SS` |
 * | `DateTimePrecise` | valid local date and time string, milliseconds | `YYYY-MM-DD HH:MM:SS.fff` |
 * | `HtmlDateTimeShort` | valid local date and time string, no seconds | `YYYY-MM-DDTHH:MM` |
 * | `HtmlDateTime` | valid local date and time string, seconds | `YYYY-MM-DDTHH:MM:SS` |
 * | `HtmlDateTimePrecise` | valid local date and time string, milliseconds | `YYYY-MM-DDTHH:MM:SS.fff` |
 * | `DateTimeShortTimezone` | valid global date and time string, no seconds | `YYYY-MM-DD HH:MM±HH:MM` |
 * | `DateTimeTimezone` | valid global date and time string, seconds | `YYYY-MM-DD HH:MM:SS±HH:MM` |
 * | `DateTimePreciseTimezone` | valid global date and time string, milliseconds | `YYYY-MM-DD HH:MM:SS.fff±HH:MM` |
 * | `HtmlDateTimeShortTimezone` | valid global date and time string, no seconds | `YYYY-MM-DDTHH:MM±HH:MM` |
 * | `HtmlDateTimeTimezone` | valid global date and time string, seconds | `YYYY-MM-DDTHH:MM:SS±HH:MM` |
 * | `HtmlDateTimePreciseTimezone` | valid global date and time string, milliseconds | `YYYY-MM-DDTHH:MM:SS.fff±HH:MM` |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see https://html.spec.whatwg.org/multipage/text-level-semantics.html#the-time-element
 * @see https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#dates-and-times
 * @since 4.4.0
 */
class TTimeFormat extends \Prado\TEnumerable
{
	//	--- Valid time strings ---

	/** Valid time string without seconds: `HH:MM` */
	public const TimeShort = 'TimeShort';

	/** Valid time string with seconds: `HH:MM:SS` */
	public const Time = 'Time';

	/** Valid time string with fractional seconds: `HH:MM:SS.fff` */
	public const TimePrecise = 'TimePrecise';


	//	--- Valid date strings ---

	/** Valid date string: `YYYY-MM-DD` */
	public const Date = 'Date';

	/** Valid month string: `YYYY-MM` */
	public const Month = 'Month';

	/** Valid week string: `YYYY-Www` */
	public const Week = 'Week';

	/** Valid yearless date string: `MM-DD` */
	public const YearlessDate = 'YearlessDate';

	/** Valid year string: `YYYY` */
	public const Year = 'Year';


	//	--- Valid local date and time strings (space-separated) ---

	/** Valid local date and time string, space-separated, without seconds: `YYYY-MM-DD HH:MM` */
	public const DateTimeShort = 'DateTimeShort';

	/** Valid local date and time string, space-separated, with seconds: `YYYY-MM-DD HH:MM:SS` */
	public const DateTime = 'DateTime';

	/** Valid local date and time string, space-separated, with fractional seconds: `YYYY-MM-DD HH:MM:SS.fff` */
	public const DateTimePrecise = 'DateTimePrecise';


	//	--- Valid local date and time strings (T-separated) ---

	/** Valid local date and time string, T-separated, without seconds: `YYYY-MM-DDTHH:MM` */
	public const HtmlDateTimeShort = 'HtmlDateTimeShort';

	/** Valid local date and time string, T-separated, with seconds: `YYYY-MM-DDTHH:MM:SS` */
	public const HtmlDateTime = 'HtmlDateTime';

	/** Valid local date and time string, T-separated, with fractional seconds: `YYYY-MM-DDTHH:MM:SS.fff` */
	public const HtmlDateTimePrecise = 'HtmlDateTimePrecise';


	//	--- Valid global date and time strings (space-separated) ---

	/** Valid global date and time string, space-separated, without seconds: `YYYY-MM-DD HH:MM±HH:MM` */
	public const DateTimeShortTimezone = 'DateTimeShortTimezone';

	/** Valid global date and time string, space-separated, with seconds: `YYYY-MM-DD HH:MM:SS±HH:MM` */
	public const DateTimeTimezone = 'DateTimeTimezone';

	/** Valid global date and time string, space-separated, with fractional seconds: `YYYY-MM-DD HH:MM:SS.fff±HH:MM` */
	public const DateTimePreciseTimezone = 'DateTimePreciseTimezone';


	//	--- Valid global date and time strings (T-separated) ---

	/** Valid global date and time string, T-separated, without seconds: `YYYY-MM-DDTHH:MM±HH:MM` */
	public const HtmlDateTimeShortTimezone = 'HtmlDateTimeShortTimezone';

	/** Valid global date and time string, T-separated, with seconds: `YYYY-MM-DDTHH:MM:SS±HH:MM` */
	public const HtmlDateTimeTimezone = 'HtmlDateTimeTimezone';

	/** Valid global date and time string, T-separated, with fractional seconds: `YYYY-MM-DDTHH:MM:SS.fff±HH:MM` */
	public const HtmlDateTimePreciseTimezone = 'HtmlDateTimePreciseTimezone';
}
