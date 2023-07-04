<?php
/**
 * TGettext class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@see mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@see http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 */

namespace Prado\I18N\core\Gettext;

// +----------------------------------------------------------------------+
// | PEAR :: File :: Gettext                                              |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available at http://www.php.net/license/3_0.txt              |
// | If you did not receive a copy of the PHP license and are unable      |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 Michael Wallner <mike@iworks.at>                  |
// +----------------------------------------------------------------------+
//
// $Id: TGettext.php 3187 2012-07-12 11:21:01Z ctrlaltca $

/**
 * File::Gettext
 *
 * @author      Michael Wallner <mike@php.net>
 * @license     PHP License
 */

/**
 * Use PHPs builtin error messages
 */
use Exception;

/**
 * File_Gettext
 *
 * GNU gettext file reader and writer.
 *
 * #################################################################
 * # All protected members of this class are public in its childs. #
 * #################################################################
 *
 * @author      Michael Wallner <mike@php.net>
 * @access      public
 */
class TGettext
{
	/**
	 * strings
	 *
	 * associative array with all [msgid => msgstr] entries
	 *
	 * @access  protected
	 * @var     array
	 */
	protected $strings = [];

	/**
	 * meta
	 *
	 * associative array containing meta
	 * information like project name or content type
	 *
	 * @access  protected
	 * @var     array
	 */
	protected $meta = [];

	/**
	 * file path
	 *
	 * @access  protected
	 * @var     string
	 */
	protected $file = '';

	/**
	 * Factory
	 *
	 * @static
	 * @access  public
	 * @param   string  $format MO or PO
	 * @param   string  $file   path to GNU gettext file
	 * @return  object  Returns File_Gettext_PO or File_Gettext_MO on success
	 *                  or PEAR_Error on failure.
	 */
	public static function factory($format, $file = '')
	{
		$format = strToUpper($format);
		$class = 'TGettext_' . $format;
		$filename = __DIR__ . '/' . $class . '.php';
		if (is_file($filename) == false) {
			throw new Exception("Class file $filename not found");
		}

		include_once $filename;
		return new $class($file);
	}

	/**
	 * poFile2moFile
	 *
	 * That's a simple fake of the 'msgfmt' console command.  It reads the
	 * contents of a GNU PO file and saves them to a GNU MO file.
	 *
	 * @static
	 * @access  public
	 * @param   string  $pofile path to GNU PO file
	 * @param   string  $mofile path to GNU MO file
	 * @return  mixed   Returns true on success or PEAR_Error on failure.
	 */
	public static function poFile2moFile($pofile, $mofile)
	{
		if (!is_file($pofile)) {
			throw new Exception("File $pofile doesn't exist.");
		}

		include_once __DIR__ . '/PO.php';

		$PO = new TGettext_PO($pofile);
		if (true !== ($e = $PO->load())) {
			return $e;
		}

		$MO = $PO->toMO();
		if (true !== ($e = $MO->save($mofile))) {
			return $e;
		}
		unset($PO, $MO);

		return true;
	}

	/**
	 * prepare
	 *
	 * @static
	 * @access  protected
	 * @param   string  $string
	 * @param   bool    $reverse
	 * @return  string
	 */
	public static function prepare($string, $reverse = false)
	{
		if ($reverse) {
			$smap = ['"', "\n", "\t", "\r"];
			$rmap = ['\"', '\\n"' . "\n" . '"', '\\t', '\\r'];
			return (string) str_replace($smap, $rmap, $string);
		} else {
			$string = preg_replace('/"\s+"/', '', $string);
			$smap = ['\\n', '\\r', '\\t', '\"'];
			$rmap = ["\n", "\r", "\t", '"'];
			return (string) str_replace($smap, $rmap, $string);
		}
	}

	/**
	 * meta2array
	 *
	 * @static
	 * @access  public
	 * @param   string $meta  $meta
	 * @return  array
	 */
	public static function meta2array($meta)
	{
		$array = [];
		foreach (explode("\n", $meta) as $info) {
			if ($info = trim($info)) {
				[$key, $value] = explode(':', $info, 2);
				$array[trim($key)] = trim($value);
			}
		}
		return $array;
	}

	/**
	 * toArray
	 *
	 * Returns meta info and strings as an array of a structure like that:
	 * ```php
	 *   array(
	 *       'meta' => array(
	 *           'Content-Type'      => 'text/plain; charset=iso-8859-1',
	 *           'Last-Translator'   => 'Michael Wallner <mike@iworks.at>',
	 *           'PO-Revision-Date'  => '2004-07-21 17:03+0200',
	 *           'Language-Team'     => 'German <mail@example.com>',
	 *       ),
	 *       'strings' => array(
	 *           'All rights reserved'   => 'Alle Rechte vorbehalten',
	 *           'Welcome'               => 'Willkommen',
	 *           // ...
	 *       )
	 *   )
	 * ```
	 *
	 * @see     fromArray()
	 * @access  protected
	 * @return  array
	 */
	public function toArray()
	{
		return ['meta' => $this->meta, 'strings' => $this->strings];
	}

	/**
	 * fromArray
	 *
	 * Assigns meta info and strings from an array of a structure like that:
	 * ```php
	 *   array(
	 *       'meta' => array(
	 *           'Content-Type'      => 'text/plain; charset=iso-8859-1',
	 *           'Last-Translator'   => 'Michael Wallner <mike@iworks.at>',
	 *           'PO-Revision-Date'  => date('Y-m-d H:iO'),
	 *           'Language-Team'     => 'German <mail@example.com>',
	 *       ),
	 *       'strings' => array(
	 *           'All rights reserved'   => 'Alle Rechte vorbehalten',
	 *           'Welcome'               => 'Willkommen',
	 *           // ...
	 *       )
	 *   )
	 * ```
	 *
	 * @see     toArray()
	 * @access  protected
	 * @param   array $array  $array
	 * @return  bool
	 */
	public function fromArray($array)
	{
		if (!array_key_exists('strings', $array)) {
			if (count($array) != 2) {
				return false;
			} else {
				[$this->meta, $this->strings] = $array;
			}
		} else {
			$this->meta = @$array['meta'];
			$this->strings = @$array['strings'];
		}
		return true;
	}

	/**
	 * toMO
	 *
	 * @access  protected
	 * @return  object  File_Gettext_MO
	 */
	public function toMO()
	{
		include_once __DIR__ . '/MO.php';
		$MO = new TGettext_MO();
		$MO->fromArray($this->toArray());
		return $MO;
	}

	/**
	 * toPO
	 *
	 * @access  protected
	 * @return  object      File_Gettext_PO
	 */
	public function toPO()
	{
		include_once __DIR__ . '/PO.php';
		$PO = new TGettext_PO();
		$PO->fromArray($this->toArray());
		return $PO;
	}
}
