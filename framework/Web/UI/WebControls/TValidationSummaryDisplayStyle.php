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
 * TValidationSummaryDisplayStyle enum.
 * TValidationSummaryDisplayStyle defines the enumerable type for the possible styles
 * that a {@link TValidationSummary} can display the collected error messages.
 *
 * The following enumerable values are defined:
 * - None: the error messages are not displayed
 * - Dynamic: the error messages are dynamically added to display as the corresponding validators fail
 * - Fixed: Similar to Dynamic except that the error messages physically occupy the page layout (even though they may not be visible)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TValidationSummaryDisplayStyle: string
{
	case None = 'None';
	case Dynamic = 'Dynamic';
	case Fixed = 'Fixed';
}
