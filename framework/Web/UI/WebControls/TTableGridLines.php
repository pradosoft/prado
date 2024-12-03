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
 * TTableGridLines class.
 * TTableGridLines defines the enumerable type for the possible grid line types of an HTML table.
 *
 * The following enumerable values are defined:
 * - None: no grid lines
 * - Horizontal: horizontal grid lines only
 * - Vertical: vertical grid lines only
 * - Both: both horizontal and vertical grid lines are shown
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 * @deprecated use CSS to style the borders of individual elements
 */
class TTableGridLines extends \Prado\TEnumerable
{
	public const None = 'None';
	public const Horizontal = 'Horizontal';
	public const Vertical = 'Vertical';
	public const Both = 'Both';
}
