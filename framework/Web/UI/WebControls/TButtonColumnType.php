<?php
/**
 * TDataGridColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TButtonColumnType class.
 * TButtonColumnType defines the enumerable type for the possible types of buttons
 * that can be used in a {@link TButtonColumn}.
 *
 * The following enumerable values are defined:
 * - LinkButton: link buttons
 * - PushButton: form buttons
 * - ImageButton: image buttons
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TButtonColumnType extends TEnumerable
{
	const LinkButton='LinkButton';
	const PushButton='PushButton';
	const ImageButton='ImageButton';
}