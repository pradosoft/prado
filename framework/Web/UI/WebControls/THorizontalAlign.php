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
 * THorizontalAlign class.
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
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 * @deprecated use the CSS text-align property instead
 */
class THorizontalAlign extends \Prado\TEnumerable
{
	const NotSet = 'NotSet';
	const Left = 'Left';
	const Right = 'Right';
	const Center = 'Center';
	const Justify = 'Justify';
}
