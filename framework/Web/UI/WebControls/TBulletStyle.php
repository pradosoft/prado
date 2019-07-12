<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBulletStyle class.
 * TBulletStyle defines the enumerable type for the possible bullet styles that may be used
 * for a {@link TBulletedList} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TBulletStyle extends \Prado\TEnumerable
{
	const NotSet = 'NotSet';
	const None = 'None';
	const Numbered = 'Numbered';
	const LowerAlpha = 'LowerAlpha';
	const UpperAlpha = 'UpperAlpha';
	const LowerRoman = 'LowerRoman';
	const UpperRoman = 'UpperRoman';
	const Disc = 'Disc';
	const Circle = 'Circle';
	const Square = 'Square';
	const CustomImage = 'CustomImage';
}
