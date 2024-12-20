<?php

/**
 * TPanelStyle class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

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
 * @since 3.0.4
 */
class TContentDirection extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const LeftToRight = 'LeftToRight';
	public const RightToLeft = 'RightToLeft';
}
