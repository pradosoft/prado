<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBulletStyle enum.
 * TBulletStyle defines the enumerable type for the possible bullet styles that may be used
 * for a {@link TBulletedList} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TBulletStyle: string
{
	case NotSet = 'NotSet';
	case None = 'None';
	case Numbered = 'Numbered';
	case LowerAlpha = 'LowerAlpha';
	case UpperAlpha = 'UpperAlpha';
	case LowerRoman = 'LowerRoman';
	case UpperRoman = 'UpperRoman';
	case Disc = 'Disc';
	case Circle = 'Circle';
	case Square = 'Square';
	case CustomImage = 'CustomImage';
}
