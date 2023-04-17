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
 * TWizardNavigationButtonType enum.
 * TWizardNavigationButtonType defines the enumerable type for the possible types of buttons
 * that can be used in the navigation part of a {@link TWizard}.
 *
 * The following enumerable values are defined:
 * - Button: a regular click button
 * - Image: an image button
 * - Link: a hyperlink button
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
enum TWizardNavigationButtonType: string
{
	case Button = 'Button';
	case Image = 'Image';
	case Link = 'Link';
}
