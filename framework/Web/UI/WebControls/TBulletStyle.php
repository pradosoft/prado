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
 * TBulletStyle class.
 * TBulletStyle defines the enumerable type for the possible bullet styles that may be used
 * for a {@see \Prado\Web\UI\WebControls\TBulletedList} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TBulletStyle extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const None = 'None';
	public const Numbered = 'Numbered';
	public const LowerAlpha = 'LowerAlpha';
	public const UpperAlpha = 'UpperAlpha';
	public const LowerRoman = 'LowerRoman';
	public const UpperRoman = 'UpperRoman';
	public const Disc = 'Disc';
	public const Circle = 'Circle';
	public const Square = 'Square';
	public const CustomImage = 'CustomImage';
}
