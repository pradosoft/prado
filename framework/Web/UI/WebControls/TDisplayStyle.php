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
 * TDisplayStyle defines the enumerable type for the possible styles
 * that a web control can display.
 *
 * The following enumerable values are defined:
 * - None: the control is not displayed and not included in the layout.
 * - Dynamic: the control is displayed and included in the layout, the layout flow is dependent on the control (equivalent to display:'' in css).
 * - Fixed: Similar to Dynamic with CSS "visibility" set "shown".
 * - Hidden: the control is not displayed and is included in the layout.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1
 */
class TDisplayStyle extends \Prado\TEnumerable
{
	const None = 'None';
	const Dynamic = 'Dynamic';
	const Fixed = 'Fixed';
	const Hidden = 'Hidden';
}
