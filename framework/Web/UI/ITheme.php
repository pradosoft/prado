<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

/**
 * ITheme interface.
 *
 * This interface must be implemented by theme.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
interface ITheme
{
	/**
	 * Applies this theme to the specified control.
	 * @param TControl the control to be applied with this theme
	 */
	public function applySkin($control);
}