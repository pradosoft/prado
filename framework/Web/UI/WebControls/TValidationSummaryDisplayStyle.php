<?php
/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TValidationSummaryDisplayStylw class.
 * TValidationSummaryDisplayStyle defines the enumerable type for the possible styles
 * that a {@link TValidationSummary} can display the collected error messages.
 *
 * The following enumerable values are defined:
 * - None: the error messages are not displayed
 * - Dynamic: the error messages are dynamically added to display as the corresponding validators fail
 * - Fixed: Similar to Dynamic except that the error messages physically occupy the page layout (even though they may not be visible)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TValidationSummaryDisplayStyle extends TEnumerable
{
	const None='None';
	const Dynamic='Dynamic';
	const Fixed='Fixed';
}