<?php

/**
 * TTable and TTableRowCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTableCaptionAlign class.
 * TTableCaptionAlign defines the enumerable type for the possible alignments
 * that a table caption can take.
 *
 * The following enumerable values are defined:
 * - NotSet: alignment not specified
 * - Top: the caption is above the table (`caption-side:top`)
 * - Bottom: the caption is below the table (`caption-side:bottom`)
 * - Left: the caption is above the table with its text aligned left (`text-align:left`)
 * - Right: the caption is above the table with its text aligned right (`text-align:right`)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TTableCaptionAlign extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const Top = 'Top';
	public const Bottom = 'Bottom';
	public const Left = 'Left';
	public const Right = 'Right';
}
