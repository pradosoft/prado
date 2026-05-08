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
 * IANA-registered charset names that can be resolved to driver-specific
 * charset names and unresolved back from database-reported charsets.
 *
 * All constants in this class use the IANA-registered charset name as their
 * value (e.g., "UTF-8", "ISO-8859-1", "windows-1252", "US-ASCII"). These are
 * the preferred MIME charset names from the IANA Character Sets registry and
 * are suitable for use when setting {@see \Prado\Data\TDbConnection::setCharset}.
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
	 * UTF-8 charset (IANA: "UTF-8")
	 */
	public const UTF8 = 'UTF-8';

	/**
	 * UTF-16 charset (IANA: "UTF-16"), native byte order.
	 * Use {@see UTF16LE} or {@see UTF16BE} when the endianness must be explicit.
	 */
	public const UTF16 = 'UTF-16';

	/**
	 * UTF-16 little-endian charset (IANA: "UTF-16LE").
	 * Supported by MySQL (utf16le) and SQLite (UTF-16le PRAGMA encoding).
	 */
	public const UTF16LE = 'UTF-16LE';

	/**
	 * UTF-16 big-endian charset (IANA: "UTF-16BE").
	 * Supported by MySQL (utf16), SQLite (UTF-16be PRAGMA encoding),
	 * Firebird (UTF16BE), and Oracle (AL16UTF16).
	 */
	public const UTF16BE = 'UTF-16BE';

	/**
	 * Latin-1 / ISO-8859-1 charset (IANA: "ISO-8859-1")
	 */
	public const Latin1 = 'ISO-8859-1';

	/**
	 * Latin-2 / ISO-8859-2 charset (IANA: "ISO-8859-2")
	 */
	public const Latin2 = 'ISO-8859-2';

	/**
	 * ASCII / US-ASCII charset (IANA preferred MIME name: "US-ASCII")
	 */
	public const ASCII = 'US-ASCII';

	/**
	 * Windows-1250 (Central European) charset (IANA: "windows-1250")
	 */
	public const Win1250 = 'windows-1250';

	/**
	 * Windows-1251 (Cyrillic) charset (IANA: "windows-1251")
	 */
	public const Win1251 = 'windows-1251';

	/**
	 * Windows-1252 (Western European) charset (IANA: "windows-1252")
	 */
	public const Win1252 = 'windows-1252';

	/**
	 * KOI8-R charset (IANA: "KOI8-R")
	 */
	public const KOI8R = 'KOI8-R';

	/**
	 * KOI8-U charset (IANA: "KOI8-U")
	 */
	public const KOI8U = 'KOI8-U';
}
