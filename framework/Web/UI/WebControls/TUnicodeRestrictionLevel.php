<?php

/**
 * TUnicodeRestrictionLevel class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TUnicodeRestrictionLevel class.
 *
 * TUnicodeRestrictionLevel defines the enumerable type for the Unicode Technical
 * Standard #39 restriction levels that {@see TUnicodeValidator} checks, from the
 * most to the least restrictive:
 * - Ascii: ASCII characters only.
 * - SingleScript: characters of one script, plus Common and Inherited characters.
 * - HighlyRestrictive: SingleScript, or characters covered by Latin + Han +
 *   Hiragana + Katakana, Latin + Han + Bopomofo, or Latin + Han + Hangul.
 * - ModeratelyRestrictive: HighlyRestrictive, or characters covered by Latin
 *   and one other recommended script except Cyrillic and Greek.
 * - MinimallyRestrictive: any mix of scripts.
 * - Unrestricted: no restriction level check.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUnicodeRestrictionLevel extends \Prado\TEnumerable
{
	public const Ascii = 'Ascii';
	public const SingleScript = 'SingleScript';
	public const HighlyRestrictive = 'HighlyRestrictive';
	public const ModeratelyRestrictive = 'ModeratelyRestrictive';
	public const MinimallyRestrictive = 'MinimallyRestrictive';
	public const Unrestricted = 'Unrestricted';
}
