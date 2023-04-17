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
 * TVerticalAlign enum.
 * TVerticalAlign defines the enumerable type for the possible vertical alignments in a CSS style.
 *
 * The following enumerable values are defined:
 * - NotSet: the alignment is not specified.
 * - Top: top aligned
 * - Bottom: bottom aligned
 * - Middle: middle aligned
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 * @deprecated use the CSS vertical-align property instead
 */
enum TVerticalAlign: string
{
	case NotSet = 'NotSet';
	case Top = 'Top';
	case Bottom = 'Bottom';
	case Middle = 'Middle';
}
