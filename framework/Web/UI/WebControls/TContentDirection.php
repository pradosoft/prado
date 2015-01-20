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
 * TContentDirection class.
 * TContentDirection defines the enumerable type for the possible directions that a panel can be at.
 *
 * The following enumerable values are defined:
 * - NotSet: the direction is not specified
 * - LeftToRight: content in a panel is left to right
 * - RightToLeft: content in a panel is right to left
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TContentDirection extends TEnumerable
{
	const NotSet='NotSet';
	const LeftToRight='LeftToRight';
	const RightToLeft='RightToLeft';
}