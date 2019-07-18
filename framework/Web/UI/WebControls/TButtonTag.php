<?php
/**
 * TButtonTag class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TButtonTag class.
 * TButtonTag defines the enumerable type for the possible tag names that a {@link TButton} can use for rendering.
 *
 * The following enumerable values are defined:
 * - Input: an input tag is rendered
 * - Button: a button tag is rendered
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\WebControls
 */

class TButtonTag extends \Prado\TEnumerable
{
	const Input = 'Input';
	const Button = 'Button';
}
