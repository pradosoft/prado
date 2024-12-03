<?php

/**
 * IRepeatInfoUser, TRepeatInfo class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRepeatDirection class.
 * TRepeatDirection defines the enumerable type for the possible directions
 * that repeated contents can repeat along
 *
 * The following enumerable values are defined:
 * - Vertical
 * - Horizontal
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TRepeatDirection extends \Prado\TEnumerable
{
	public const Vertical = 'Vertical';
	public const Horizontal = 'Horizontal';
}
