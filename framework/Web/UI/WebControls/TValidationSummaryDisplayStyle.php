<?php

/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TValidationSummaryDisplayStylw class.
 * TValidationSummaryDisplayStyle defines the enumerable type for the possible styles
 * that a {@see \Prado\Web\UI\WebControls\TValidationSummary} can display the collected error messages.
 *
 * The following enumerable values are defined:
 * - None: the error messages are not displayed
 * - Dynamic: the error messages are dynamically added to display as the corresponding validators fail
 * - Fixed: Similar to Dynamic except that the error messages physically occupy the page layout (even though they may not be visible)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TValidationSummaryDisplayStyle extends \Prado\TEnumerable
{
	public const None = 'None';
	public const Dynamic = 'Dynamic';
	public const Fixed = 'Fixed';
}
