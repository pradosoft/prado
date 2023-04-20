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
	public const ESC_CHARSET_MAP = [
			"\x1B\x25\x47" => 'UTF-8', // ESC-'%G'

			"\x1B\x28\x40" => 'ASCII',	"\x1B\x29\x40" => 'ASCII',	"\x1B\x2A\x40" => 'ASCII',	"\x1B\x2B\x40" => 'ASCII',
			"\x1B\x28\x41" => 'ASCII.en_GB', "\x1B\x29\x41" => 'ASCII.en_GB', "\x1B\x2A\x41" => 'ASCII.en_GB', "\x1B\x2B\x41" => 'ASCII.en_GB',
			"\x1B\x28\x42" => 'ASCII.en_US', "\x1B\x29\x42" => 'ASCII.en_US', "\x1B\x2A\x42" => 'ASCII.en_US', "\x1B\x2B\x42" => 'ASCII.en_US',
			"\x1B\x28\x43" => 'ASCII.fi',	"\x1B\x29\x43" => 'ASCII.fi',	"\x1B\x2A\x43" => 'ASCII.fi',	"\x1B\x2B\x43" => 'ASCII.fi',
			"\x1B\x28\x44" => 'ASCII.sv',	"\x1B\x29\x44" => 'ASCII.sv',	"\x1B\x2A\x44" => 'ASCII.sv',	"\x1B\x2B\x44" => 'ASCII.sv',
			"\x1B\x28\x45" => 'ASCII.no',	"\x1B\x29\x45" => 'ASCII.no',	"\x1B\x2A\x45" => 'ASCII.no',	"\x1B\x2B\x45" => 'ASCII.no',
			"\x1B\x28\x46" => 'ASCII.no',	"\x1B\x29\x46" => 'ASCII.no',	"\x1B\x2A\x46" => 'ASCII.no',	"\x1B\x2B\x46" => 'ASCII.no',
			"\x1B\x28\x47" => 'ASCII.se',	"\x1B\x29\x47" => 'ASCII.se',	"\x1B\x2A\x47" => 'ASCII.se',	"\x1B\x2B\x47" => 'ASCII.se',
			"\x1B\x28\x49" => 'JIS_X0201',	"\x1B\x29\x49" => 'JIS_X0201',	"\x1B\x2A\x49" => 'JIS_X0201',	"\x1B\x2B\x49" => 'JIS_X0201',
			"\x1B\x28\x4A" => 'JIS_X0201',	"\x1B\x29\x4A" => 'JIS_X0201',	"\x1B\x2A\x4A" => 'JIS_X0201',	"\x1B\x2B\x4A" => 'JIS_X0201',
			"\x1B\x28\x4B" => 'ASCII.de',	"\x1B\x29\x4B" => 'ASCII.de',	"\x1B\x2A\x4B" => 'ASCII.de',	"\x1B\x2B\x4B" => 'ASCII.de',
			"\x1B\x28\x4C" => 'ASCII.pt',	"\x1B\x29\x4C" => 'ASCII.pt',	"\x1B\x2A\x4C" => 'ASCII.pt',	"\x1B\x2B\x4C" => 'ASCII.pt',
			"\x1B\x28\x4E" => 'ISO-5427',	"\x1B\x29\x4E" => 'ISO-5427',	"\x1B\x2A\x4E" => 'ISO-5427',	"\x1B\x2B\x4E" => 'ISO-5427',
			"\x1B\x28\x54" => 'ISO646-CN',	"\x1B\x29\x54" => 'ISO646-CN',	"\x1B\x2A\x54" => 'ISO646-CN',	"\x1B\x2B\x54" => 'ISO646-CN',
			"\x1B\x28\x59" => 'ASCII.it',	"\x1B\x29\x59" => 'ASCII.it',	"\x1B\x2A\x59" => 'ASCII.it',	"\x1B\x2B\x59" => 'ASCII.it',
			"\x1B\x28\x5A" => 'ASCII.es',	"\x1B\x29\x5A" => 'ASCII.es',	"\x1B\x2A\x5A" => 'ASCII.es',	"\x1B\x2B\x5A" => 'ASCII.es',
			"\x1B\x28\x5B" => 'ASCII.el',	"\x1B\x29\x5B" => 'ASCII.el',	"\x1B\x2A\x5B" => 'ASCII.el',	"\x1B\x2B\x5B" => 'ASCII.el',
			"\x1B\x28\x60" => 'ASCII.no',	"\x1B\x29\x60" => 'ASCII.no',	"\x1B\x2A\x60" => 'ASCII.no',	"\x1B\x2B\x60" => 'ASCII.no',
			"\x1B\x28\x66" => 'ASCII.fr',	"\x1B\x29\x66" => 'ASCII.fr',	"\x1B\x2A\x66" => 'ASCII.fr',	"\x1B\x2B\x66" => 'ASCII.fr',
			"\x1B\x28\x67" => 'ASCII.pt',	"\x1B\x29\x67" => 'ASCII.pt',	"\x1B\x2A\x67" => 'ASCII.pt',	"\x1B\x2B\x67" => 'ASCII.pt',
			"\x1B\x28\x68" => 'ASCII.es',	"\x1B\x29\x68" => 'ASCII.es',	"\x1B\x2A\x68" => 'ASCII.es',	"\x1B\x2B\x68" => 'ASCII.es',
			"\x1B\x28\x69" => 'ASCII.hu',	"\x1B\x29\x69" => 'ASCII.hu',	"\x1B\x2A\x69" => 'ASCII.hu',	"\x1B\x2B\x69" => 'ASCII.hu',
			"\x1B\x28\x77" => 'ASCII.fr_CA', "\x1B\x29\x77" => 'ASCII.fr_CA', "\x1B\x2A\x77" => 'ASCII.fr_CA', "\x1B\x2B\x77" => 'ASCII.fr_CA',
			"\x1B\x28\x78" => 'ASCII.fr_CA', "\x1B\x29\x78" => 'ASCII.fr_CA', "\x1B\x2A\x78" => 'ASCII.fr_CA', "\x1B\x2B\x78" => 'ASCII.fr_CA',
			"\x1B\x28\x7A" => 'ASCII.yu',	"\x1B\x29\x7A" => 'ASCII.yu',	"\x1B\x2A\x7A" => 'ASCII.yu',	"\x1B\x2B\x7A" => 'ASCII.yu',

			"\x1B\x2C\x41" => 'ISO-8859-1',	"\x1B\x2D\x41" => 'ISO-8859-1',	"\x1B\x2E\x41" => 'ISO-8859-1',	"\x1B\x2F\x41" => 'ISO-8859-1',
			"\x1B\x2C\x42" => 'ISO-8859-2',	"\x1B\x2D\x42" => 'ISO-8859-2',	"\x1B\x2E\x42" => 'ISO-8859-2',	"\x1B\x2F\x42" => 'ISO-8859-2',
			"\x1B\x2C\x43" => 'ISO-8859-3',	"\x1B\x2D\x43" => 'ISO-8859-3',	"\x1B\x2E\x43" => 'ISO-8859-3',	"\x1B\x2F\x43" => 'ISO-8859-3',
			"\x1B\x2C\x44" => 'ISO-8859-4',	"\x1B\x2D\x44" => 'ISO-8859-4',	"\x1B\x2E\x44" => 'ISO-8859-4',	"\x1B\x2F\x44" => 'ISO-8859-4',
			"\x1B\x2C\x45" => 'ISO-8859-5',	"\x1B\x2D\x45" => 'ISO-8859-5',	"\x1B\x2E\x45" => 'ISO-8859-5',	"\x1B\x2F\x45" => 'ISO-8859-5',
			"\x1B\x2C\x46" => 'ISO-8859-7',	"\x1B\x2D\x46" => 'ISO-8859-7',	"\x1B\x2E\x46" => 'ISO-8859-7',	"\x1B\x2F\x46" => 'ISO-8859-7',
			"\x1B\x2C\x47" => 'ISO-8859-6',	"\x1B\x2D\x47" => 'ISO-8859-6',	"\x1B\x2E\x47" => 'ISO-8859-6',	"\x1B\x2F\x47" => 'ISO-8859-6',
			"\x1B\x2C\x48" => 'ISO-8859-8',	"\x1B\x2D\x48" => 'ISO-8859-8',	"\x1B\x2E\x48" => 'ISO-8859-8',	"\x1B\x2F\x48" => 'ISO-8859-8',
			"\x1B\x2C\x49" => 'CSN 369103',	"\x1B\x2D\x49" => 'CSN 369103',	"\x1B\x2E\x49" => 'CSN 369103',	"\x1B\x2F\x49" => 'CSN 369103',

			"\x1B\x24\x28\x40" => 'JIS0208',	"\x1B\x24\x29\x40" => 'JIS0208',	"\x1B\x24\x2A\x40" => 'JIS0208',	"\x1B\x24\x2B\x40" => 'JIS0208',
			"\x1B\x24\x28\x42" => 'JIS0208',	"\x1B\x24\x29\x42" => 'JIS0208',	"\x1B\x24\x2A\x42" => 'JIS0208',	"\x1B\x24\x2B\x42" => 'JIS0208',
			"\x1B\x24\x28\x44" => 'JIS_X0212',	"\x1B\x24\x29\x44" => 'JIS_X0212',	"\x1B\x24\x2A\x44" => 'JIS_X0212',	"\x1B\x24\x2B\x44" => 'JIS_X0212',
			"\x1B\x24\x28\x4F" => 'ISO-2022-JP-3',	"\x1B\x24\x29\x4F" => 'ISO-2022-JP-3',	"\x1B\x24\x2A\x4F" => 'ISO-2022-JP-3',	"\x1B\x24\x2B\x4F" => 'ISO-2022-JP-3',
			"\x1B\x24\x28\x50" => 'ISO-2022-JP-3',	"\x1B\x24\x29\x50" => 'ISO-2022-JP-3',	"\x1B\x24\x2A\x50" => 'ISO-2022-JP-3',	"\x1B\x24\x2B\x50" => 'ISO-2022-JP-3',
		];
	/**
	 * Convert strings to UTF-8 via iconv. NB, the result may not by UTF-8
	 * if the conversion failed.
	 * @param string $string string to convert to UTF-8
	 * @param string $from source encoding
	 * @param ?string $lang Language of the encoding as accepted by PHP setLocale
	 * @return string UTF-8 encoded string, original string if iconv failed.
	 */
	public static function toUTF8($string, $from, $lang = null)
	{
		if ($from != 'UTF-8') {
			$locale = null;
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
	 * @param ?string $lang Language of the encoding.
	 * @return string encoded string.
	 */
	public static function fromUTF8($string, $to, $lang = null)
	{
		if ($to != 'UTF-8') {
			$locale = null;
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
	 * Convert an Escape Character Code Encoding to the iconv character
	 * encoding.
	 * @param string $charset The ESC character code for conversion.
	 * @return string The decoded Character Encoding.
	 */
	public static function decodeEscapeCharset(string $charset): string
	{
		$esc = "\x1B";
		$codes = explode($esc, trim($charset, $esc));
		foreach ($codes as $code) {
			$code = $esc . $code;
			if (array_key_exists($code, self::ESC_CHARSET_MAP)) {
				return self::ESC_CHARSET_MAP[$code];
			}
		}
		return null;
	}

	/**
	 * Convert an Escape Character Code Encoding to the iconv character
	 * encoding.
	 * @param string $charset The iconv charset encoding to be encoded
	 * @return string The ESC character code representing the encoding.
	 */
	public static function encodeEscapeCharset(string $charset): string
	{
		return array_search($charset, self::ESC_CHARSET_MAP);
	}
}
