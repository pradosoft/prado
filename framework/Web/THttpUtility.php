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

	public static function htmlEncode($s)
	{
		return htmlspecialchars($s);
	}

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