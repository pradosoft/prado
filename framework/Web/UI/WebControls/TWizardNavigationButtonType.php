<?php
/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TWizardNavigationButtonType class.
 * TWizardNavigationButtonType defines the enumerable type for the possible types of buttons
 * that can be used in the navigation part of a {@link TWizard}.
 *
 * The following enumerable values are defined:
 * - Button: a regular click button
 * - Image: an image button
 * - Link: a hyperlink button
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TWizardNavigationButtonType extends TEnumerable
{
	const Button='Button';
	const Image='Image';
	const Link='Link';
}