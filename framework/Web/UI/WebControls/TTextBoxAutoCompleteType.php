<?php

/**
 * TTextBox class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTextBoxAutoCompleteType class.
 * TTextBoxAutoCompleteType defines the possible AutoComplete type that is supported
 * by a {@see \Prado\Web\UI\WebControls\TTextBox} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TTextBoxAutoCompleteType extends \Prado\TEnumerable
{
	public const Disabled = 'Disabled';
	public const Enabled = 'Enabled';
	public const None = 'None';
}
