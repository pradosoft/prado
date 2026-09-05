<?php

/**
 * TRelativeTimeMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRelativeTimeMode enumeration.
 *
 * TRelativeTimeMode selects the width of the localized duration units {@see TRelativeTime}
 * displays. Each constant maps to a CLDR unit width: the wider the mode, the more verbose
 * the unit text.
 *
 * | Constant | CLDR width | Example (English, minutes) |
 * |---|---|---|
 * | `Long` | `units` | `5 minutes` |
 * | `Short` | `unitsShort` | `5 min` |
 * | `Narrow` | `unitsNarrow` | `5m` |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TRelativeTime
 * @see \Prado\I18N\core\CultureInfo::getUnitPatterns()
 * @since 4.4.0
 */
class TRelativeTimeMode extends \Prado\TEnumerable
{
	/** Full-length unit text, e.g. `5 minutes` */
	public const Long = 'Long';

	/** Abbreviated unit text, e.g. `5 min` */
	public const Short = 'Short';

	/** Narrow unit text, e.g. `5m` */
	public const Narrow = 'Narrow';
}
