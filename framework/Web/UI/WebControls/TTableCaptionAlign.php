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
 * TTableCaptionAlign enum.
 * TTableCaptionAlign defines the enumerable type for the possible alignments
 * that a table caption can take.
 *
 * The following enumerable values are defined:
 * - NotSet: alignment not specified
 * - Top: top aligned
 * - Bottom: bottom aligned
 * - Left: left aligned
 * - Right: right aligned
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TTableCaptionAlign: string
{
	case NotSet = 'NotSet';
	case Top = 'Top';
	case Bottom = 'Bottom';
	case Left = 'Left';
	case Right = 'Right';
}
