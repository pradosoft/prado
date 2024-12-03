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
 * TVerticalAlign class.
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
class TVerticalAlign extends \Prado\TEnumerable
{
	public const NotSet = 'NotSet';
	public const Top = 'Top';
	public const Bottom = 'Bottom';
	public const Middle = 'Middle';
}
