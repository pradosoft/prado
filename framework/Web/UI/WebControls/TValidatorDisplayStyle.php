<?php
/**
 * TBaseValidator class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TValidatorDisplayStyle class.
 * TValidatorDisplayStyle defines the enumerable type for the possible styles
 * that a validator control can display the error message.
 *
 * The following enumerable values are defined:
 * - None: the error message is not displayed
 * - Dynamic: the error message dynamically appears when the validator fails validation
 * - Fixed: Similar to Dynamic except that the error message physically occupies the page layout (even though it may not be visible)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TValidatorDisplayStyle extends TEnumerable
{
	const None='None';
	const Dynamic='Dynamic';
	const Fixed='Fixed';
}