<?php

/**
 * TStreamFilterName class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Filter;

use Prado\TEnumerable;

/**
 * TStreamFilterName class
 *
 * Enumerate the stream filters built into PHP, each constant mapping to its filter-name string.
 * Use the constants for typed, typo-proof filter names instead of magic strings, for example
 * `$stream->appendFilter(TStreamFilterName::ROT13)`; because each constant is the name string, it
 * passes anywhere a filter name is accepted ({@see \Prado\IO\TStream::appendFilter()},
 * {@see \Prado\IO\TStream::prependFilter()}, {@see \Prado\IO\TStream::filterExists()}).
 *
 * The `string.*` and `dechunk` filters are always present.  The compression filters depend on
 * their extension — `zlib.*` on ext-zlib, `bzip2.*` on ext-bz2 — so confirm one with
 * {@see \Prado\IO\TStream::filterExists()} before relying on it.  Parameterized filters such as
 * `convert.iconv.*` are omitted because the name is not atomic.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://www.php.net/manual/en/filters.php
 */
class TStreamFilterName extends TEnumerable
{
	// string
	public const ROT13 = 'string.rot13';
	public const TOUPPER = 'string.toupper';
	public const TOLOWER = 'string.tolower';

	// base64 and quoted-printable conversion
	public const BASE64_ENCODE = 'convert.base64-encode';
	public const BASE64_DECODE = 'convert.base64-decode';
	public const QUOTED_PRINTABLE_ENCODE = 'convert.quoted-printable-encode';
	public const QUOTED_PRINTABLE_DECODE = 'convert.quoted-printable-decode';

	// zlib compression (needs ext-zlib)
	public const DEFLATE = 'zlib.deflate';
	public const INFLATE = 'zlib.inflate';

	// bzip2 compression (needs ext-bz2)
	public const BZIP2_COMPRESS = 'bzip2.compress';
	public const BZIP2_DECOMPRESS = 'bzip2.decompress';

	// HTTP chunked transfer decoding
	public const DECHUNK = 'dechunk';
}
