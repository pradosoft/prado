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
 * TUtf8Converter is a simple wrapper around iconv functions to convert
 * strings from and to UTF-8.
 *
 * @author Fabio Bas <fbio.bas@gmail.com>
 * @since 4.0.2
 */
class TUtf8Converter
{
	/**
	 * Convert strings to UTF-8 via iconv. NB, the result may not by UTF-8
	 * if the conversion failed.
	 * @param string $string string to convert to UTF-8
	 * @param string $from source encoding
	 * @param ?string $lang Language of the encoding as accepted by PHP setLocale
	 * @return string UTF-8 encoded string, original string if iconv failed.
	 * @see https://www.php.net/manual/en/function.setlocale.php
	 *   The $lang locale information is maintained per process, not per thread.
	 */
	public static function toUTF8($string, $from, $lang = null)
	{
		if ($from != 'UTF-8') {
			$locale = null;
			if ($lang === null) {
				self::parseEncodingLanguage($from, $lang);
			}
			if ($lang !== null) {
				$locale = setLocale(LC_CTYPE, '0');
				setLocale(LC_CTYPE, $lang);
			}
			$s = iconv($from, 'UTF-8', $string); //to UTF-8
			if ($lang !== null) {
				setLocale(LC_CTYPE, $locale);
			}
			return $s !== false ? $s : $string; //it could return false
		}
		return $string;
	}

	/**
	 * Convert UTF-8 strings to a different encoding. NB. The result
	 * may not have been encoded if iconv fails.
	 * @param string $string the UTF-8 string for conversion
	 * @param string $to destination encoding
	 * @param ?string $lang Language of the encoding as accepted by PHP setLocale
	 * @return string encoded string.
	 * @see https://www.php.net/manual/en/function.setlocale.php
	 *   The $lang locale information is maintained per process, not per thread.
	 */
	public static function fromUTF8($string, $to, $lang = null)
	{
		if ($to != 'UTF-8') {
			$locale = null;
			if ($lang === null) {
				self::parseEncodingLanguage($to, $lang);
			}
			if ($lang !== null) {
				$locale = setLocale(LC_CTYPE, '0');
				setLocale(LC_CTYPE, $lang);
			}
			$s = iconv('UTF-8', $to, $string);
			if ($lang !== null) {
				setLocale(LC_CTYPE, $locale);
			}
			return $s !== false ? $s : $string;
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
			$parts = explode($encoding, '.', 1);
			$encoding = $parts[0];
			$lang = $parts[1];
		}
	}
}
