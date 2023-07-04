<?php
/**
 * TVarDumper class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TVarDumper class.
 *
 * TVarDumper is intended to replace the buggy PHP function var_dump and print_r.
 * It can correctly identify the recursively referenced objects in a complex
 * object structure. It also has a recursive depth control to avoid indefinite
 * recursive display of some peculiar variables.
 *
 * TVarDumper can be used as follows,
 * ```php
 *   echo TVarDumper::dump($var);
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TVarDumper
{
	private static $_objects;
	private static $_output;
	private static $_depth;

	/**
	 * Converts a variable into a string representation.
	 * This method achieves the similar functionality as var_dump and print_r
	 * but is more robust when handling complex objects such as PRADO controls.
	 * @param mixed $var variable to be dumped
	 * @param int $depth maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param bool $highlight wether to highlight th resulting string
	 * @return string the string representation of the variable
	 */
	public static function dump($var, $depth = 10, $highlight = false)
	{
		self::$_output = '';
		self::$_objects = [];
		self::$_depth = $depth;
		self::dumpInternal($var, 0);
		if ($highlight) {
			$result = highlight_string("<?php\n" . self::$_output, true);
			return preg_replace('/&lt;\\?php<br \\/>/', '', $result, 1);
		} else {
			return self::$_output;
		}
	}

	private static function dumpInternal($var, $level)
	{
		switch (gettype($var)) {
			case 'boolean':
				self::$_output .= $var ? 'true' : 'false';
				break;
			case 'integer':
				self::$_output .= "$var";
				break;
			case 'double':
				self::$_output .= "$var";
				break;
			case 'string':
				self::$_output .= "'" . addslashes($var) . "'";
				break;
			case 'resource':
				self::$_output .= '{resource}';
				break;
			case 'NULL':
				self::$_output .= "null";
				break;
			case 'unknown type':
				self::$_output .= '{unknown}';
				break;
			case 'array':
				if (self::$_depth <= $level) {
					self::$_output .= 'array(...)';
				} elseif (empty($var)) {
					self::$_output .= 'array()';
				} else {
					$keys = array_keys($var);
					$spaces = str_repeat(' ', $level * 4);
					self::$_output .= "array\n" . $spaces . '(';
					foreach ($keys as $key) {
						self::$_output .= "\n" . $spaces . '    ';
						self::dumpInternal($key, 0);
						self::$_output .= ' => ';
						self::dumpInternal($var[$key], $level + 1);
					}
					self::$_output .= "\n" . $spaces . ')';
				}
				break;
			case 'object':
				if (($id = array_search($var, self::$_objects, true)) !== false) {
					self::$_output .= $var::class . '#' . ($id + 1) . '(...)';
				} elseif (self::$_depth <= $level) {
					self::$_output .= $var::class . '(...)';
				} else {
					$id = array_push(self::$_objects, $var);
					$className = $var::class;
					$spaces = str_repeat(' ', $level * 4);
					self::$_output .= "$className#$id\n" . $spaces . '(';
					if ('__PHP_Incomplete_Class' !== get_class($var) && method_exists($var, '__debugInfo')) {
						$members = $var->__debugInfo();
						if (!is_array($members)) {
							throw new TInvalidDataValueException('vardumper_not_array');
						}
					} else {
						$members = (array) $var;
					}
					foreach ($members as $key => $value) {
						$keyDisplay = strtr(trim($key), ["\0" => ':']);
						self::$_output .= "\n" . $spaces . "    [$keyDisplay] => ";
						self::$_output .= self::dumpInternal($value, $level + 1);
					}
					self::$_output .= "\n" . $spaces . ')';
				}
				break;
		}
	}
}
