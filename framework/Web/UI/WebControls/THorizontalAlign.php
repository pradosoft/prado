<?php
/**
 * TStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * THorizontalAlign enum.
 * THorizontalAlign defines the enumerable type for the possible horizontal alignments in a CSS style.
 *
 * The following enumerable values are defined:
 * - NotSet: the alignment is not specified.
 * - Left: left aligned
 * - Right: right aligned
 * - Center: center aligned
 * - Justify: the begin and end are justified
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 * @deprecated use the CSS text-align property instead
 */
enum THorizontalAlign: string
{
	case NotSet = 'NotSet';
	case Left = 'Left';
	case Right = 'Right';
	case Center = 'Center';
	case Justify = 'Justify';
}
