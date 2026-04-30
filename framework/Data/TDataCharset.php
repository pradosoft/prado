<?php

/**
 * TDataCharset class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\TEnumerable;

/**
 * TDataCharset class
 *
 * TDataCharset enumerates the generic PRADO charset identifiers using
 * standard PHP/system charset names that can be resolved to driver-specific
 * charset names and unresolved back from database-reported charsets.
 *
 * All constants in this class use the standard PHP/system charset notation
 * (e.g., "UTF-8", "ISO-8859-1", "Windows-1252") as their value. These are
 * the charset names users would typically use when setting
 * {@see \Prado\Data\TDbConnection::setCharset}.
 *
 * The mapping between these generic charsets and driver-specific charsets
 * is handled by {@see TDbDriverCapabilities::resolveCharset} and
 * {@see TDbDriverCapabilities::unresolveCharset}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDataCharset extends TEnumerable
{
	/**
	 * UTF-8 charset (PHP standard: "UTF-8")
	 */
	public const UTF8 = 'UTF-8';

	/**
	 * UTF-16 charset (PHP standard: "UTF-16")
	 */
	public const UTF16 = 'UTF-16';

	/**
	 * Latin-1 / ISO-8859-1 charset (PHP standard: "ISO-8859-1")
	 */
	public const Latin1 = 'ISO-8859-1';

	/**
	 * Latin-2 / ISO-8859-2 charset (PHP standard: "ISO-8859-2")
	 */
	public const Latin2 = 'ISO-8859-2';

	/**
	 * ASCII charset (PHP standard: "ASCII")
	 */
	public const ASCII = 'ASCII';

	/**
	 * Windows-1250 charset (PHP standard: "Windows-1250")
	 */
	public const Win1250 = 'Windows-1250';

	/**
	 * Windows-1251 charset (PHP standard: "Windows-1251")
	 */
	public const Win1251 = 'Windows-1251';

	/**
	 * Windows-1252 charset (PHP standard: "Windows-1252")
	 */
	public const Win1252 = 'Windows-1252';

	/**
	 * KOI8-R charset (PHP standard: "KOI8-R")
	 */
	public const KOI8R = 'KOI8-R';

	/**
	 * KOI8-U charset (PHP standard: "KOI8-U")
	 */
	public const KOI8U = 'KOI8-U';
}
