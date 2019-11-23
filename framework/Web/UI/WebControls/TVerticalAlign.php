<?php
/**
 * TStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
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
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 * @deprecated use the CSS vertical-align property instead
 */
class TVerticalAlign extends \Prado\TEnumerable
{
	const NotSet = 'NotSet';
	const Top = 'Top';
	const Bottom = 'Bottom';
	const Middle = 'Middle';
}
