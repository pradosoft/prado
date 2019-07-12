<?php
/**
 * TTextBox class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TTextBoxMode class.
 * TTextBoxMode defines the enumerable type for the possible mode
 * that a {@link TTextBox} control could be at.
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
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TTextBoxMode extends \Prado\TEnumerable
{
	const SingleLine = 'SingleLine';
	const MultiLine = 'MultiLine';
	const Password = 'Password';
	const Color = 'Color';
	const Date = 'Date';
	const Datetime = 'Datetime';
	const DatetimeLocal = 'DatetimeLocal';
	const Email = 'Email';
	const Month = 'Month';
	const Number = 'Number';
	const Range = 'Range';
	const Search = 'Search';
	const Tel = 'Tel';
	const Time = 'Time';
	const Url = 'Url';
	const Week = 'Week';
}
