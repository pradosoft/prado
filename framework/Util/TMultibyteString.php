<?php

/**
 * TMultibyteString class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * TMultibyteString class.
 *
 * TMultibyteString measures, cuts, and case-maps UTF-8 strings by character.
 * Each method calls the mbstring extension when it is loaded. Without it, the
 * pcre extension splits the string into UTF-8 code points and the intl
 * extension maps case through ICU, with the same results. `composer.json`
 * suggests `mbstring` and requires `pcre` and `intl`.
 *
 * | Method | With mbstring | Without mbstring |
 * |---|---|---|
 * | {@see length()} | `mb_strlen()` | the code points `preg_match_all()` matches |
 * | {@see substr()} | `mb_substr()` | `preg_split()` into code points, then `array_slice()` |
 * | {@see cut()} | `mb_strcut()` | byte `substr()`, with the start and the end moved back over UTF-8 continuation bytes |
 * | {@see upper()} | `mb_strtoupper()` | the ICU `Any-Upper` transliterator |
 * | {@see lower()} | `mb_strtolower()` | the ICU `Any-Lower` transliterator |
 *
 * {@see substr()} counts characters. {@see cut()} counts bytes and keeps whole
 * characters, for a field whose size is bounded in bytes:
 *
 * ```php
 * TMultibyteString::cut(str_repeat('日', 100), 0, 256);   // 85 characters, 255 bytes
 * TMultibyteString::cut('aé', 0, 2);                      // 'a'
 * ```
 *
 * The case mapping is the full Unicode mapping on both paths, so `upper('ß')`
 * is `SS`. A string that is not valid UTF-8 is measured and cut in bytes and
 * cased with `strtoupper()` and `strtolower()`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
final class TMultibyteString
{
	/** @var ?bool whether the mbstring extension is loaded, resolved on first use */
	private static ?bool $_mbstring = null;

	/** @var array<string, \Transliterator> the ICU transliterators by rule, created on first use */
	private static array $_transliterators = [];

	/**
	 * Tells whether the mbstring functions are used.
	 * @return bool whether the mbstring extension is loaded.
	 */
	public static function hasMbstring(): bool
	{
		return self::$_mbstring ??= extension_loaded('mbstring');
	}

	/**
	 * Returns the length of a string in characters.
	 * @param string $string the string.
	 * @return int the number of characters, or of bytes when the string is not valid UTF-8.
	 */
	public static function length(string $string): int
	{
		if (self::hasMbstring()) {
			return mb_strlen($string, 'UTF-8');
		}
		$count = preg_match_all('/./su', $string);
		return $count === false ? strlen($string) : $count;
	}

	/**
	 * Returns part of a string by character position, as `mb_substr()` does.
	 * @param string $string the string.
	 * @param int $start the first character; a negative value counts from the end.
	 * @param ?int $length the number of characters, null to the end of the string;
	 *   a negative value leaves that many characters off the end.
	 * @return string the part.
	 */
	public static function substr(string $string, int $start, ?int $length = null): string
	{
		if (self::hasMbstring()) {
			return mb_substr($string, $start, $length, 'UTF-8');
		}
		$characters = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
		if ($characters === false) {
			return substr($string, $start, $length);
		}
		return implode('', array_slice($characters, $start, $length));
	}

	/**
	 * Returns part of a string by byte position, moved to character boundaries, as
	 * `mb_strcut()` does. A start inside a character moves back to its first byte. The
	 * end is $length bytes after that start, and an end inside a character moves back so
	 * that no character is split. A string that is not valid UTF-8 is cut as `substr()`
	 * cuts it.
	 * @param string $string the string.
	 * @param int $start the first byte; a negative value counts from the end.
	 * @param ?int $length the maximum number of bytes, null to the end of the string;
	 *   a negative value leaves that many bytes off the end.
	 * @return string the part.
	 */
	public static function cut(string $string, int $start, ?int $length = null): string
	{
		if (!self::isUtf8($string)) {
			return substr($string, $start, $length);
		}
		if (self::hasMbstring()) {
			return mb_strcut($string, $start, $length, 'UTF-8');
		}
		$size = strlen($string);
		if ($start < 0) {
			$start = max(0, $size + $start);
		}
		if ($start >= $size) {
			return '';
		}
		$length ??= $size;
		if ($length < 0) {
			$length = max(0, $size - $start + $length);
		}
		$start = self::characterStart($string, $start);
		$end = self::characterStart($string, $start + min($length, $size - $start));
		return substr($string, $start, $end - $start);
	}

	/**
	 * Tells whether a string is valid UTF-8.
	 * @param string $string the string.
	 * @return bool whether every byte of the string belongs to a well-formed UTF-8 character.
	 */
	private static function isUtf8(string $string): bool
	{
		return preg_match('//u', $string) === 1;
	}

	/**
	 * Returns the byte offset of the character that holds a byte of a valid UTF-8 string.
	 * @param string $string the valid UTF-8 string.
	 * @param int $offset a byte offset from 0 through the length of the string.
	 * @return int the offset moved back over continuation bytes (`10xxxxxx`) to the first
	 *   byte of its character; the length of the string stays as it is.
	 */
	private static function characterStart(string $string, int $offset): int
	{
		$size = strlen($string);
		while ($offset > 0 && $offset < $size && (ord($string[$offset]) & 0xC0) === 0x80) {
			$offset--;
		}
		return $offset;
	}

	/**
	 * Returns a string in uppercase.
	 * @param string $string the string.
	 * @return string the uppercase string.
	 */
	public static function upper(string $string): string
	{
		if (self::hasMbstring()) {
			return mb_strtoupper($string, 'UTF-8');
		}
		return self::transliterate('Any-Upper', $string) ?? strtoupper($string);
	}

	/**
	 * Returns a string in lowercase.
	 * @param string $string the string.
	 * @return string the lowercase string.
	 */
	public static function lower(string $string): string
	{
		if (self::hasMbstring()) {
			return mb_strtolower($string, 'UTF-8');
		}
		return self::transliterate('Any-Lower', $string) ?? strtolower($string);
	}

	/**
	 * Transliterates a string with an ICU rule.
	 * @param string $rule the ICU transliterator rule, such as `Any-Lower`.
	 * @param string $string the string.
	 * @return ?string the result, or null when the rule has no transliterator or
	 *   the string is not valid UTF-8.
	 */
	private static function transliterate(string $rule, string $string): ?string
	{
		if (!isset(self::$_transliterators[$rule])) {
			$transliterator = \Transliterator::create($rule);
			if ($transliterator === null) {
				return null;
			}
			self::$_transliterators[$rule] = $transliterator;
		}
		$result = self::$_transliterators[$rule]->transliterate($string);
		return $result === false ? null : $result;
	}
}
