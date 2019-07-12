<?php
/**
 * TButton class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TButtonType class.
 * TButtonType defines the enumerable type for the possible types that a {@link TButton} can take.
 *
 * The following enumerable values are defined:
 * - Submit: a normal submit button
 * - Reset: a reset button
 * - Button: a client button (normally does not perform form submission)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TButtonType extends \Prado\TEnumerable
{
	const Submit = 'Submit';
	const Reset = 'Reset';
	const Button = 'Button';
}
