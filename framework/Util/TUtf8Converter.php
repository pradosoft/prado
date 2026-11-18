<?php

/**
 * TUtf8Converter class file
 *
 * @author Fabio Bas <fbio.bas@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * TUtf8Converter class.
 *
 * TUtf8Converter converts strings from and to UTF-8. It calls `iconv()` when the
 * iconv extension is loaded, and the intl extension's `\UConverter::transcode()`
 * otherwise. `composer.json` suggests `iconv` and requires `intl`.
 *
 * | Case | With iconv | Without iconv |
 * |---|---|---|
 * | conversion | `iconv()` | `\UConverter::transcode()` |
 * | `//TRANSLIT` or `//IGNORE` on the encoding | applied | removed; ICU substitutes what the target encoding cannot hold |
 * | unknown encoding ({@see isEncoding()} is false) | the string unchanged | the string unchanged |
 * | input invalid in its encoding | the string unchanged | the converted string with substitutes |
 *
 * @author Fabio Bas <fbio.bas@gmail.com>
 * @since 4.0.2
 */
class TUtf8Converter
{
	/** @var ?bool whether the iconv extension is loaded, resolved on first use */
	private static ?bool $_iconv = null;

	/** @var array<string, bool> whether each encoding is known, by converter and upper-case name */
	private static array $_encodings = [];

	/**
	 * Tells whether `iconv()` converts the strings.
	 * @return bool whether the iconv extension is loaded.
	 * @since 4.4.0
	 */
	public static function hasIconv(): bool
	{
		return self::$_iconv ??= extension_loaded('iconv');
	}

	/**
	 * Tells whether the converter of {@see hasIconv()} knows an encoding. The answer
	 * is cached per process.
	 * @param string $encoding the encoding name, such as `ISO-8859-1`.
	 * @return bool whether the encoding converts to UTF-8.
	 * @since 4.4.0
	 */
	public static function isEncoding(string $encoding): bool
	{
		$iconv = self::hasIconv();
		$key = ($iconv ? 'iconv:' : 'icu:') . strtoupper($encoding);
		if (!isset(self::$_encodings[$key])) {
			// Both converters warn for an unknown encoding and return false.
			self::$_encodings[$key] = ($iconv ? @iconv($encoding, 'UTF-8', '') : @\UConverter::transcode('', 'UTF-8', explode('//', $encoding, 2)[0])) !== false;
		}
		return self::$_encodings[$key];
	}

	/**
	 * Converts a string to UTF-8. NB, the result may not be UTF-8 if the conversion
	 * failed.
	 * @param string $string string to convert to UTF-8
	 * @param string $from source encoding
	 * @param ?string $lang Language of the encoding as accepted by PHP setLocale
	 * @return string UTF-8 encoded string, original string if the conversion failed.
	 * @see https://www.php.net/manual/en/function.setlocale.php
	 *   The $lang locale information is maintained per process, not per thread.
	 */
	public static function toUTF8($string, $from, $lang = null)
	{
		if ($from != 'UTF-8') {
			if ($lang === null) {
				self::parseEncodingLanguage($from, $lang);
			}
			return self::convert((string) $string, $from, 'UTF-8', $lang);
		}
		return $string;
	}

	/**
	 * Converts a UTF-8 string to a different encoding. NB. The result may not have
	 * been encoded if the conversion failed.
	 * @param string $string the UTF-8 string for conversion
	 * @param string $to destination encoding
	 * @param ?string $lang Language of the encoding as accepted by PHP setLocale
	 * @return string encoded string, original string if the conversion failed.
	 * @see https://www.php.net/manual/en/function.setlocale.php
	 *   The $lang locale information is maintained per process, not per thread.
	 */
	public static function fromUTF8($string, $to, $lang = null)
	{
		if ($to != 'UTF-8') {
			if ($lang === null) {
				self::parseEncodingLanguage($to, $lang);
			}
			return self::convert((string) $string, 'UTF-8', $to, $lang);
		}
		return $string;
	}

	/**
	 * This parses a Character Set Encoding for an appended/embedded language.
	 * eg "ASCII" can also be "ASCII.de" to designate German ASCII layout.
	 * In this example, at input $encoding is "ASCII.de" and on output $encoding
	 * is 'ASCII' with $lang is "de".
	 * @param string $encoding The character set encoding with optional period and
	 *   language appended.
	 * @param ?string &$lang The output language of the encoding.
	 */
	public static function parseEncodingLanguage(string &$encoding, &$lang)
	{
		if (strpos($encoding, '.') !== false) {
			$parts = explode('.', $encoding, 2);
			$encoding = $parts[0];
			$lang = $parts[1];
		}
	}

	/**
	 * Converts a string between encodings under the LC_CTYPE locale $lang, which is
	 * restored afterward.
	 * @param string $string the string to convert.
	 * @param string $from the source encoding.
	 * @param string $to the destination encoding.
	 * @param ?string $lang the LC_CTYPE locale of the conversion, null for the current one.
	 * @return string the converted string, or $string when the conversion failed.
	 */
	private static function convert(string $string, string $from, string $to, ?string $lang): string
	{
		$locale = null;
		if ($lang !== null) {
			$locale = setlocale(LC_CTYPE, '0');
			setlocale(LC_CTYPE, $lang);
		}
		try {
			if (self::hasIconv()) {
				// iconv warns for an unknown encoding or invalid input and returns false.
				$result = @iconv($from, $to, $string);
			} else {
				// ICU warns for an unknown encoding and returns false.
				$result = @\UConverter::transcode($string, explode('//', $to, 2)[0], explode('//', $from, 2)[0]);
			}
		} finally {
			if ($lang !== null) {
				setlocale(LC_CTYPE, $locale);
			}
		}
		return $result !== false ? $result : $string;
	}
}
