<?php
/**
 * TPanelStyle class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TScrollBars class.
 * TScrollBars defines the enumerable type for the possible scroll bar mode
 * that a {@link TPanel} control could use.
 *
 * The following enumerable values are defined:
 * - None: no scroll bars.
 * - Auto: scroll bars automatically appeared when needed.
 * - Both: show both horizontal and vertical scroll bars all the time.
 * - Horizontal: horizontal scroll bar only
 * - Vertical: vertical scroll bar only
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TScrollBars extends TEnumerable
{
	const None='None';
	const Auto='Auto';
	const Both='Both';
	const Horizontal='Horizontal';
	const Vertical='Vertical';
}