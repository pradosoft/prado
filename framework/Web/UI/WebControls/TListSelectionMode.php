<?php
/**
 * TListBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TListSelectionMode class.
 * TListSelectionMode defines the enumerable type for the possible selection modes of a {@link TListBox}.
 *
 * The following enumerable values are defined:
 * - Single: single selection
 * - Multiple: allow multiple selection
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TListSelectionMode extends TEnumerable
{
	const Single='Single';
	const Multiple='Multiple';
}