<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TValidatorDisplayStyle enum.
 * TValidatorDisplayStyle defines the enumerable type for the possible styles
 * that a validator control can display the error message.
 *
 * The following enumerable values are defined:
 * - None: the error message is not displayed
 * - Dynamic: the error message dynamically appears when the validator fails validation
 * - Fixed: Similar to Dynamic except that the error message physically occupies the page layout (even though it may not be visible)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TValidatorDisplayStyle: string
{
	case None = 'None';
	case Dynamic = 'Dynamic';
	case Fixed = 'Fixed';
}
