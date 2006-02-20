<?php
/**
 * THttpUtility class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web
 */

/**
 * THttpUtility class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpUtility
{
	private static $_entityTable=null;

	/**
	 * HTML-encodes a string.
	 * It is equivalent to {@link htmlspeicalchars} PHP function.
	 * @param string string to be encoded
	 * @return string encoded string
	 */
	public static function htmlEncode($s)
	{
		return htmlspecialchars($s);
	}

	/**
	 * HTML-decodes a string.
	 * It is the inverse of {@link htmlEncode}.
	 * @param string string to be decoded
	 * @return string decoded string
	 */
	public static function htmlDecode($s)
	{
		if(!self::$_entityTable)
			self::buildEntityTable();
		return strtr($s,self::$_entityTable);
	}

	private static function buildEntityTable()
	{
		self::$_entityTable=array_flip(get_html_translation_table(HTML_ENTITIES,ENT_QUOTES));
	}
}

?>