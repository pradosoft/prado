<?php

/**
 * TDataCharset class file.
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
 * TDataCharset defines IANA-registered charset identifiers used with
 * {@see \Prado\Data\TDbConnection::setCharset()}. Each constant holds the
 * canonical IANA name; driver-specific names are resolved by the active
 * database connection layer.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDataCharset extends TEnumerable
{
	/** UTF-8 — universally supported by all drivers. */
	public const UTF8 = 'UTF-8';

	/** UTF-16 — generic UTF-16; driver resolves byte order. */
	public const UTF16 = 'UTF-16';

	/** UTF-16 little-endian. */
	public const UTF16LE = 'UTF-16LE';

	/** UTF-16 big-endian. */
	public const UTF16BE = 'UTF-16BE';

	/** ISO-8859-1 (Latin-1) — Western European. */
	public const Latin1 = 'ISO-8859-1';

	/** ISO-8859-2 (Latin-2) — Central European. */
	public const Latin2 = 'ISO-8859-2';

	/** US-ASCII — 7-bit ASCII. */
	public const ASCII = 'US-ASCII';

	/** Windows-1250 — Central European Windows encoding. */
	public const Win1250 = 'windows-1250';

	/** Windows-1251 — Cyrillic Windows encoding. */
	public const Win1251 = 'windows-1251';

	/** Windows-1252 — Western European Windows encoding. */
	public const Win1252 = 'windows-1252';

	/** KOI8-R — Russian Cyrillic. */
	public const KOI8R = 'KOI8-R';

	/** KOI8-U — Ukrainian Cyrillic. */
	public const KOI8U = 'KOI8-U';
}
