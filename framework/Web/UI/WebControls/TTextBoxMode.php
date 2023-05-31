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
 * TTextBoxMode class.
 * TTextBoxMode defines the enumerable type for the possible mode
 * that a {@see TTextBox} control could be at.
 *
 * The following enumerable values are defined:
 * - SingleLine: the textbox will be a regular single line input
 * - MultiLine: the textbox will be a textarea allowing multiple line input
 * - Password: the textbox will hide user input like a password input box
 *
 * Html5 declares new types that will degrade as plain textboxes on unsupported browsers:
 * - Color: a color picker can show up in the input field.
 * - Date: a date picker can show up in the input field.
 * - Datetime: a date / time / timezone picker can show up in the input field.
 * - DatetimeLocal: a date / time picker can show up in the input field.
 * - Email: the e-mail address can be automatically validated when submitted.
 * - Month: a month / year picker can show up in the input field.
 * - Number: a spinner can show up in the input field.
 * - Range: a slider can show up in the input field.
 * - Search: the textbox will be optimized for searches
 * - Tel: the text will be formatted as a telephone number.
 * - Time: a time picker can show up in the input field.
 * - Url: the url can be automatically validated when submitted.
 * - Week: a week / year picker can show up in the input field.
 *
 * In order to use the new types introduced with html5, you need to declare a proper
 * html doctype and use a browser supporting the specific inut type.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TTextBoxMode extends \Prado\TEnumerable
{
	public const SingleLine = 'SingleLine';
	public const MultiLine = 'MultiLine';
	public const Password = 'Password';
	public const Color = 'Color';
	public const Date = 'Date';
	public const Datetime = 'Datetime';
	public const DatetimeLocal = 'DatetimeLocal';
	public const Email = 'Email';
	public const Month = 'Month';
	public const Number = 'Number';
	public const Range = 'Range';
	public const Search = 'Search';
	public const Tel = 'Tel';
	public const Time = 'Time';
	public const Url = 'Url';
	public const Week = 'Week';
}
