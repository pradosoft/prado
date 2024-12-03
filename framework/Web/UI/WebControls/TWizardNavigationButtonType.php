<?php

/**
 * TWizard and the relevant class definitions.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TWizardNavigationButtonType class.
 * TWizardNavigationButtonType defines the enumerable type for the possible types of buttons
 * that can be used in the navigation part of a {@see \Prado\Web\UI\WebControls\TWizard}.
 *
 * The following enumerable values are defined:
 * - Button: a regular click button
 * - Image: an image button
 * - Link: a hyperlink button
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TWizardNavigationButtonType extends \Prado\TEnumerable
{
	public const Button = 'Button';
	public const Image = 'Image';
	public const Link = 'Link';
}
