<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TBulletStyle class.
 * TBulletStyle defines the enumerable type for the possible bullet styles that may be used
 * for a {@link TBulletedList} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TBulletStyle extends TEnumerable
{
	const NotSet='NotSet';
	const None='None';
	const Numbered='Numbered';
	const LowerAlpha='LowerAlpha';
	const UpperAlpha='UpperAlpha';
	const LowerRoman='LowerRoman';
	const UpperRoman='UpperRoman';
	const Disc='Disc';
	const Circle='Circle';
	const Square='Square';
	const CustomImage='CustomImage';
}