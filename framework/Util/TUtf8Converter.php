<?php
/**
 * TUtf8Converter class file
 *
 * @author Fabio Bas <fbio.bas@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TUtf8Converter class.
 *
 * TUtf8Converter is a simple wrapper around iconv functions to convert
 * strings from and to UTF-8.
 *
 * @author Fabio Bas <fbio.bas@gmail.com>
 * @package Prado\Util
 * @since 4.0.2
 */
class TUtf8Converter
{

	/**
	 * Convert strings to UTF-8 via iconv. NB, the result may not by UTF-8
	 * if the conversion failed.
	 * @param string $string string to convert to UTF-8
	 * @param string $from source encoding
	 * @return string UTF-8 encoded string, original string if iconv failed.
	 */
	public static function toUTF8($string, $from)
	{
		if ($from != 'UTF-8') {
			$s = iconv($from, 'UTF-8', $string); //to UTF-8
			return $s !== false ? $s : $string; //it could return false
		}
		return $string;
	}

	/**
	 * Convert UTF-8 strings to a different encoding. NB. The result
	 * may not have been encoded if iconv fails.
	 * @param string $string the UTF-8 string for conversion
	 * @param string $to destination encoding
	 * @return string encoded string.
	 */
	public static function fromUTF8($string, $to)
	{
		if ($to != 'UTF-8') {
			$s = iconv('UTF-8', $to, $string);
			return $s !== false ? $s : $string;
		}
		return $string;
	}
}
